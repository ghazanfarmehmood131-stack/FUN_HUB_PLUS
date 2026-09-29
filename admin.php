<?php
/**
 * ARCHIVE Ledger - Admin Console (admin.php)
 *
 * One self-contained file. It reuses config.php (PDO, sessions, e()) and the
 * existing application tables. Run database.sql once before first use.
 *
 * Sections:
 *   Dashboard  counts and recent admin activity
 *   Items      add, edit, delete catalog records and manage anime/movie videos
 *   Images     audit and fix poster and backdrop links
 *   Users      add, edit, reset password, delete accounts
 *
 * Security summary:
 *   - Admin role is re-checked against the database on every request
 *   - Separate admin sign-in with idle timeout (30 minutes)
 *   - Failed sign-ins are logged and throttled per IP and per email
 *   - CSRF token and same-origin check on every POST, no state change on GET
 *   - Prepared statements only, whitelisted sort columns
 *   - All output escaped, strict Content-Security-Policy with nonces
 */

// Harden the session cookie before config.php starts the session.
if (session_status() === PHP_SESSION_NONE) {
    $adminIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    if ($adminIsHttps) {
        @ini_set('session.cookie_secure', '1');
    }
}

require_once __DIR__ . '/config.php';

/* ------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------ */
const ADMIN_IDLE_LIMIT     = 1800;  // seconds of inactivity before sign-in is required again
const ADMIN_FAILS_PER_IP   = 5;     // failed sign-ins per IP in the window
const ADMIN_FAILS_PER_MAIL = 10;    // failed sign-ins per email in the window
const ADMIN_ITEMS_PER_PAGE = 20;
const ADMIN_USERS_PER_PAGE = 20;
const ADMIN_IMAGES_PER_PAGE = 15;
// Hash of a throwaway string. Verified when an email is unknown so timing does not reveal valid emails.
const ADMIN_DUMMY_HASH = '$2y$10$xXwYj.hATLvNb3DYZyZwVutvtYCN0YGmDY3KCM9YvSJS3w6WVqwT2';
// SQL fragment: poster or backdrop link is empty or not an http(s) URL.
const ADMIN_BAD_IMAGE_SQL = "(poster_url IS NULL OR poster_url = '' OR NOT (poster_url LIKE 'http://%' OR poster_url LIKE 'https://%')
    OR backdrop_url IS NULL OR backdrop_url = '' OR NOT (backdrop_url LIKE 'http://%' OR backdrop_url LIKE 'https://%'))";

/* ------------------------------------------------------------------
 * Security headers
 * ------------------------------------------------------------------ */
$nonce = base64_encode(random_bytes(16));
header_remove('X-Powered-By');
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'none'; "
    . "style-src 'self' 'nonce-$nonce' https://fonts.googleapis.com; "
    . "font-src https://fonts.gstatic.com; "
    . "img-src 'self' data: https: http:; "
    . "script-src 'nonce-$nonce'; "
    . "form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('X-Robots-Tag: noindex, nofollow');

/* ------------------------------------------------------------------
 * Small helpers
 * ------------------------------------------------------------------ */
function h($s): string { return e(is_scalar($s) ? (string)$s : ''); }

function str($v): string { return is_scalar($v) ? (string)$v : ''; }

/** Single-line text: strips control characters, trims. */
function line($v): string
{
    $s = str($v);
    if (!mb_check_encoding($s, 'UTF-8')) return '';
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s);
    return trim($s === null ? '' : $s);
}

/** Multi-line text: keeps newlines, strips other control characters. */
function block($v): string
{
    $s = str($v);
    if (!mb_check_encoding($s, 'UTF-8')) return '';
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
    $s = str_replace("\r\n", "\n", $s === null ? '' : $s);
    return trim(str_replace("\r", "\n", $s));
}

function adminUrl(array $params = []): string
{
    return 'admin.php' . ($params ? '?' . http_build_query($params) : '');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void { $_SESSION['admin_flash'][] = ['t' => $type, 'm' => $msg]; }

function takeFlash(): array
{
    $f = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);
    return is_array($f) ? $f : [];
}

function clientIp(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }

function csrfToken(): string
{
    if (empty($_SESSION['admin_csrf']) || !is_string($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function csrfField(): string { return '<input type="hidden" name="csrf" value="' . h(csrfToken()) . '">'; }

function stopWith(int $code, string $message): void
{
    http_response_code($code);
    global $nonce;
    adminHead('Error', '');
    echo '<div class="a-flash err">' . h($message) . '</div>';
    echo '<p><a class="a-btn" href="' . h(adminUrl()) . '">Back to admin</a></p>';
    adminFoot();
    exit;
}

function csrfCheck(): void
{
    $t = $_POST['csrf'] ?? '';
    $s = $_SESSION['admin_csrf'] ?? '';
    if (!is_string($t) || !is_string($s) || $s === '' || !hash_equals($s, $t)) {
        stopWith(419, 'This form expired or was not sent from this site. Reload the page and try again.');
    }
}

function sameOriginCheck(): void
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $k) {
        if (!empty($_SERVER[$k])) {
            $p = parse_url((string)$_SERVER[$k]);
            $h = strtolower(($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : ''));
            if ($h === '' || $h !== $host) {
                stopWith(403, 'Cross-site request blocked.');
            }
            return;
        }
    }
}

function logActivity(PDO $db, ?int $userId, string $action, string $target = '', string $detail = ''): void
{
    try {
        $st = $db->prepare("INSERT INTO admin_activity (user_id, action, target, detail, ip) VALUES (?, ?, ?, ?, ?)");
        $st->execute([$userId, $action, mb_substr($target, 0, 120), mb_substr($detail, 0, 255), clientIp()]);
    } catch (PDOException $e) {
        error_log('admin_activity insert failed: ' . $e->getMessage());
    }
}

set_exception_handler(function ($ex) {
    error_log('admin.php uncaught: ' . $ex->getMessage());
    if (!headers_sent()) http_response_code(500);
    echo '<!DOCTYPE html><meta charset="UTF-8"><title>Error</title><p>Something went wrong. Check the PHP error log.</p>';
});

function activityTableReady(PDO $db): bool
{
    try { $db->query("SELECT 1 FROM admin_activity LIMIT 1"); return true; }
    catch (PDOException $e) { return false; }
}

function tooManyFailures(PDO $db, string $ip, string $email): bool
{
    $st = $db->prepare("SELECT COUNT(*) FROM admin_activity WHERE action = 'login_failed' AND ip = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)");
    $st->execute([$ip]);
    if ((int)$st->fetchColumn() >= ADMIN_FAILS_PER_IP) return true;
    $st = $db->prepare("SELECT COUNT(*) FROM admin_activity WHERE action = 'login_failed' AND target = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)");
    $st->execute([mb_substr($email, 0, 120)]);
    return (int)$st->fetchColumn() >= ADMIN_FAILS_PER_MAIL;
}

function currentAdmin(PDO $db): ?array
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) return null;
    $st = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $u = $st->fetch();
    return ($u && $u['role'] === 'admin') ? $u : null;
}

function isHttpUrl(?string $u): bool
{
    return is_string($u) && $u !== '' && preg_match('#^https?://[^\s]+$#i', $u) === 1;
}

/** Validates an optional image link. Returns the clean link or null. */
function checkImageUrl(string $u, string $label, string $key, array &$err): ?string
{
    if ($u === '') return null;
    if (strlen($u) > 500) { $err[$key] = "$label link is longer than 500 characters."; return null; }
    $p = parse_url($u);
    $scheme = strtolower((string)($p['scheme'] ?? ''));
    if (!$p || empty($p['host']) || !in_array($scheme, ['http', 'https'], true)
        || isset($p['user']) || isset($p['pass']) || !filter_var($u, FILTER_VALIDATE_URL)) {
        $err[$key] = "$label link must be a full http or https address with no spaces or login details.";
        return null;
    }
    return $u;
}

function youtubeId(string $in): ?string
{
    $in = trim($in);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $in)) return $in;
    $p = parse_url($in);
    if (!$p || empty($p['host'])) return null;
    $host = strtolower(preg_replace('/^www\./i', '', (string)$p['host']));
    $path = (string)($p['path'] ?? '');
    if ($host === 'youtu.be' && preg_match('#^/([A-Za-z0-9_-]{11})#', $path, $m)) return $m[1];
    if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
        if (preg_match('#^/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})#', $path, $m)) return $m[1];
        if ($path === '/watch') {
            parse_str((string)($p['query'] ?? ''), $q);
            if (isset($q['v']) && is_string($q['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', $q['v'])) return $q['v'];
        }
    }
    return null;
}

/** Same naming the seed data uses: movies use type "movie", every other category uses its slug. */
function typeForCategory(string $slug): string { return $slug === 'movies' ? 'movie' : $slug; }

function idPrefixForCategory(string $slug): string
{
    $map = ['anime' => 'anime', 'gaming' => 'game', 'movies' => 'movie', 'tvshows' => 'tv',
            'kpop' => 'kpop', 'comics' => 'comic', 'manga' => 'manga', 'software' => 'software'];
    return $map[$slug] ?? preg_replace('/[^a-z0-9]+/', '', strtolower($slug));
}

function slugify(string $s): string
{
    $t = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) : false;
    $t = strtolower($t !== false && $t !== '' ? $t : $s);
    $t = trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    return substr($t, 0, 60);
}

function fetchCategories(PDO $db): array
{
    return $db->query("SELECT slug, name FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
}

function pager(int $total, int $page, int $per, array $params): string
{
    $pages = max(1, (int)ceil($total / $per));
    if ($pages <= 1) return '<p class="a-muted">' . (int)$total . ' total</p>';
    $out = '<nav class="a-pager" aria-label="Pagination"><span>Page ' . $page . ' of ' . $pages . ' (' . (int)$total . ' total)</span>';
    if ($page > 1) $out .= '<a class="a-btn sm" href="' . h(adminUrl($params + ['page' => $page - 1])) . '">Previous</a>';
    if ($page < $pages) $out .= '<a class="a-btn sm" href="' . h(adminUrl($params + ['page' => $page + 1])) . '">Next</a>';
    return $out . '</nav>';
}

/* ------------------------------------------------------------------
 * Layout
 * ------------------------------------------------------------------ */
function adminHead(string $title, string $active, ?array $admin = null): void
{
    global $nonce;
    $nav = ['dashboard' => 'Dashboard', 'items' => 'Items', 'images' => 'Images', 'users' => 'Users'];
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($title) ?> - <?= h(SITE_NAME) ?> Admin</title>
  <link rel="stylesheet" href="css/main.css">
  <style nonce="<?= h($nonce) ?>">
    :root { color-scheme: dark; }
    [hidden] { display: none !important; }
    body.a-body { display: block; min-height: 100vh; }
    .a-top { position: sticky; top: 0; z-index: 20; background: var(--bg-canvas); border-bottom: 1px solid var(--border-medium); }
    .a-top-in { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 24px; padding: 12px 24px; max-width: var(--container-max); margin: 0 auto; }
    .a-brand { font-family: var(--font-display); font-weight: 800; font-size: 22px; letter-spacing: 0.08em; color: var(--text-bright); text-transform: uppercase; }
    .a-brand span { color: var(--accent); }
    .a-nav { display: flex; flex-wrap: wrap; gap: 4px; flex: 1; }
    .a-nav a { font-family: var(--font-display); font-weight: 700; font-size: 14px; letter-spacing: 0.08em; text-transform: uppercase; padding: 8px 12px; color: var(--text-dim); border: 1px solid transparent; }
    .a-nav a:hover { color: var(--text-bright); background: var(--bg-panel); }
    .a-nav a.on { color: var(--text-bright); border-color: var(--accent); }
    .a-menu-toggle { display: none; align-items: center; justify-content: center; flex-direction: column; gap: 4px; width: 42px; height: 40px; padding: 8px; color: var(--text-bright); background: var(--bg-panel); border: 1px solid var(--border-medium); cursor: pointer; }
    .a-menu-toggle span { display: block; width: 18px; height: 2px; background: currentColor; }
    .a-who { display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--text-dim); }
    .a-main { max-width: var(--container-max); margin: 0 auto; padding: 32px 24px 64px; width: 100%; }
    .a-main h1 { font-size: 40px; margin-bottom: 8px; }
    .a-main h2 { font-size: 22px; margin: 32px 0 14px; letter-spacing: 0.06em; }
    .a-lead { color: var(--text-dim); margin-bottom: 28px; max-width: 70ch; }
    .a-muted { color: var(--text-faint); font-size: 13px; }
    a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 2px solid var(--accent-hover); outline-offset: 2px; }
    .a-btn { display: inline-flex; align-items: center; justify-content: center; padding: 9px 16px; font-family: var(--font-display); font-size: 14px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; background: var(--bg-panel); border: 1px solid var(--border-medium); color: var(--text-bright); cursor: pointer; white-space: nowrap; }
    .a-btn:hover { background: var(--bg-subtle); border-color: var(--border-bold); }
    .a-btn.primary { background: var(--accent); border-color: var(--accent); }
    .a-btn.primary:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
    .a-btn.danger { background: var(--accent-subtle); border-color: var(--accent); }
    .a-btn.danger:hover { background: var(--accent-deep); }
    .a-btn.sm { padding: 5px 10px; font-size: 12px; }
    .a-btn.ghost { background: transparent; }
    .a-flash { border: 1px solid var(--border-bold); background: var(--bg-panel); padding: 12px 16px; margin-bottom: 20px; color: var(--text-bright); }
    .a-flash.err { border-color: var(--accent); background: var(--accent-subtle); }
    .a-panel { background: var(--bg-surface); border: 1px solid var(--border-medium); padding: 20px; margin-bottom: 28px; }
    .a-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); border: 1px solid var(--border-medium); margin-bottom: 28px; }
    .a-stat { padding: 16px 18px; border-right: 1px solid var(--border-medium); background: var(--bg-surface); }
    .a-stat:last-child { border-right: 0; }
    .a-stat b { display: block; font-family: var(--font-display); font-size: 38px; color: var(--text-bright); line-height: 1; }
    .a-stat span { font-family: var(--font-display); font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-dim); }
    .a-tablewrap { overflow-x: auto; border: 1px solid var(--border-medium); margin-bottom: 16px; }
    table.a-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .a-table th { text-align: left; padding: 10px 12px; font-family: var(--font-display); font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-dim); background: var(--bg-panel); border-bottom: 1px solid var(--border-medium); white-space: nowrap; }
    .a-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-subtle); vertical-align: middle; color: var(--text-body); }
    .a-table tr:last-child td { border-bottom: 0; }
    .a-table .t-title { color: var(--text-bright); font-weight: 600; }
    .a-table .t-sub { display: block; font-size: 12px; color: var(--text-faint); word-break: break-all; }
    .a-table .t-actions { display: flex; gap: 6px; flex-wrap: nowrap; }
    .a-thumb { width: 42px; height: 63px; object-fit: cover; background: var(--bg-canvas); border: 1px solid var(--border-medium); }
    .a-thumb-none { display: flex; align-items: center; justify-content: center; width: 42px; height: 63px; font-size: 10px; color: var(--text-faint); border: 1px solid var(--border-medium); background: var(--bg-canvas); }
    .a-tag { display: inline-block; padding: 2px 8px; font-family: var(--font-display); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; border: 1px solid var(--border-bold); color: var(--text-dim); margin-right: 4px; }
    .a-tag.hot { border-color: var(--accent); color: var(--text-bright); }
    .a-tag.bad { border-color: var(--accent); background: var(--accent-subtle); color: var(--text-bright); }
    .a-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 16px; }
    .a-toolbar .f { display: flex; flex-direction: column; gap: 4px; }
    .a-toolbar .grow { flex: 1; min-width: 200px; }
    .a-pager { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 13px; color: var(--text-dim); }
    .a-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 24px; }
    .a-form .full { grid-column: 1 / -1; }
    .a-field { margin-bottom: 18px; }
    .a-field label, .a-label { display: block; font-family: var(--font-display); font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-dim); margin-bottom: 6px; }
    .a-field input[type=text], .a-field input[type=email], .a-field input[type=password], .a-field input[type=number], .a-field input[type=url], .a-field select, .a-field textarea, .a-in { width: 100%; padding: 10px 12px; background: var(--bg-canvas); border: 1px solid var(--border-medium); color: var(--text-bright); font-size: 14px; }
    .a-field input[type=file] { width: 100%; padding: 10px 12px; background: var(--bg-canvas); border: 1px solid var(--border-medium); color: var(--text-bright); font-size: 14px; }
    .a-password-control { display: flex; align-items: stretch; gap: 8px; }
    .a-password-control input { flex: 1; min-width: 0; width: auto; }
    .a-field textarea { min-height: 110px; resize: vertical; font-family: var(--font-body); }
    .a-field textarea.mono { font-family: Consolas, Menlo, monospace; font-size: 13px; }
    .a-field input:read-only { color: var(--text-faint); background: var(--bg-surface); }
    .a-field input:focus, .a-field select:focus, .a-field textarea:focus { border-color: var(--accent); outline: none; }
    .a-field input:focus-visible, .a-field select:focus-visible, .a-field textarea:focus-visible { outline: 2px solid var(--accent-hover); outline-offset: 2px; }
    .a-help { font-size: 12px; color: var(--text-faint); margin-top: 5px; }
    .a-field.has-err input, .a-field.has-err select, .a-field.has-err textarea { border-color: var(--accent); }
    .a-errmsg { font-size: 13px; color: var(--text-bright); background: var(--accent-subtle); border: 1px solid var(--accent); padding: 6px 10px; margin-top: 6px; }
    .a-check { display: flex; align-items: center; gap: 10px; margin: 6px 0 18px; }
    .a-check input { width: 18px; height: 18px; accent-color: var(--accent); }
    .a-check label { margin: 0; }
    .a-prevbox { display: flex; gap: 12px; align-items: flex-start; margin-top: 8px; }
    .a-prev { max-width: 100%; max-height: 180px; border: 1px solid var(--border-medium); background: var(--bg-canvas); }
    .a-prevnote { font-size: 12px; color: var(--text-faint); }
    .a-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
    .a-login { max-width: 420px; margin: 72px auto; padding: 0 24px; }
    .a-inline { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: center; }
    .a-inline input { width: 100%; padding: 7px 10px; background: var(--bg-canvas); border: 1px solid var(--border-medium); color: var(--text-bright); font-size: 13px; }
    .a-inline input:focus { border-color: var(--accent); outline: none; }
    .a-imgcell { display: flex; gap: 10px; align-items: center; min-width: 190px; }
    .a-imgcell .lbl { font-size: 11px; color: var(--text-faint); display: block; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.06em; }
        @media (max-width: 800px) {
            .a-top-in { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px 12px; padding: 10px 16px; }
            .a-brand { font-size: 19px; }
            .a-menu-toggle { display: inline-flex; grid-column: 2; grid-row: 1; }
            .a-nav { grid-column: 1 / -1; display: none; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4px; }
            .a-top.nav-open .a-nav { display: grid; }
            .a-nav a { min-width: 0; padding: 9px 6px; text-align: center; font-size: 12px; }
            .a-who { grid-column: 1 / -1; justify-content: space-between; }
            .a-main { padding: 24px 16px 48px; }
            .a-form { grid-template-columns: 1fr; }
            .a-main h1 { font-size: 32px; line-height: 1.1; }
            .a-inline { grid-template-columns: 1fr; }
            .a-pager { flex-wrap: wrap; }
            .a-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .a-stat { border-bottom: 1px solid var(--border-medium); }
            .a-stat:nth-child(2n) { border-right: 0; }
            .a-stat:nth-last-child(-n + 2) { border-bottom: 0; }
            .a-stat b { font-size: 32px; }
            .a-login { margin: 40px auto; padding: 0 16px; }
        }
        @media (max-width: 520px) {
            .a-top-in { padding: 10px 12px; }
            .a-brand { font-size: 16px; }
            .a-nav { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .a-nav a { font-size: 11px; }
            .a-who { gap: 8px; font-size: 12px; }
            .a-main { padding: 20px 12px 40px; }
            .a-main h1 { font-size: 28px; }
            .a-main h2 { font-size: 19px; }
            .a-panel { padding: 14px; }
            .a-toolbar { align-items: stretch; }
            .a-toolbar .f, .a-toolbar .grow { flex: 1 1 100%; min-width: 0; }
            .a-toolbar > .a-btn { flex: 1 1 auto; }
            .a-btn { padding: 9px 12px; font-size: 12px; }
            .a-table { min-width: 680px; }
            .a-actions .a-btn { flex: 1 1 120px; }
            .a-stat { padding: 12px; }
            .a-stat b { font-size: 28px; }
        }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
  </style>
</head>
<body class="a-body">
  <header class="a-top">
    <div class="a-top-in">
      <a class="a-brand" href="<?= h(adminUrl()) ?>"><span>&#9632;</span> <?= h(SITE_NAME) ?> Admin</a>
<?php if ($admin): ?>
    <button type="button" class="a-menu-toggle" id="adminMenuToggle" aria-controls="adminNavigation" aria-expanded="false" aria-label="Open admin navigation" title="Open admin navigation">
      <span></span><span></span><span></span>
    </button>
    <nav class="a-nav" id="adminNavigation" aria-label="Admin sections">
<?php foreach ($nav as $k => $label): ?>
        <a href="<?= h(adminUrl($k === 'dashboard' ? [] : ['view' => $k])) ?>" class="<?= $active === $k ? 'on' : '' ?>"<?= $active === $k ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
<?php endforeach; ?>
        <a href="index.php" target="_blank" rel="noopener">View site</a>
      </nav>
      <div class="a-who">
        <span><?= h($admin['name']) ?></span>
        <form method="post" action="admin.php">
          <?= csrfField() ?>
          <input type="hidden" name="do" value="logout">
          <button type="submit" class="a-btn sm">Sign out</button>
        </form>
      </div>
<?php endif; ?>
    </div>
  </header>
  <main class="a-main">
<?php
    foreach (takeFlash() as $f) {
        echo '<div class="a-flash ' . ($f['t'] === 'err' ? 'err' : 'ok') . '" role="' . ($f['t'] === 'err' ? 'alert' : 'status') . '">' . h($f['m']) . '</div>';
    }
}

function adminFoot(): void
{
    global $nonce;
    ?>
  </main>
  <script nonce="<?= h($nonce) ?>">
  (function () {
        var passwordInput = document.getElementById('lgPass');
        var passwordToggle = document.getElementById('lgPassToggle');
        if (passwordInput && passwordToggle) {
            passwordToggle.addEventListener('click', function () {
                var isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                passwordToggle.textContent = isHidden ? 'Hide' : 'Show';
                passwordToggle.setAttribute('aria-label', (isHidden ? 'Hide' : 'Show') + ' password');
                passwordToggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            });
        }
        var adminMenuToggle = document.getElementById('adminMenuToggle');
        var adminNavigation = document.getElementById('adminNavigation');
        var adminHeader = document.querySelector('.a-top');
        if (adminMenuToggle && adminNavigation && adminHeader) {
            function setAdminMenuOpen(isOpen) {
                adminHeader.classList.toggle('nav-open', isOpen);
                adminMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                adminMenuToggle.setAttribute('aria-label', isOpen ? 'Close admin navigation' : 'Open admin navigation');
                adminMenuToggle.title = isOpen ? 'Close admin navigation' : 'Open admin navigation';
            }
            adminMenuToggle.addEventListener('click', function () {
                setAdminMenuOpen(adminMenuToggle.getAttribute('aria-expanded') !== 'true');
            });
            adminNavigation.addEventListener('click', function (event) {
                if (event.target.closest('a') && window.matchMedia('(max-width: 800px)').matches) {
                    setAdminMenuOpen(false);
                }
            });
        }
        var categorySelect = document.getElementById('f_category_slug');
        var comicFields = document.getElementById('comicFields');
        var mangaFields = document.getElementById('mangaFields');
        var softwareFields = document.getElementById('softwareFields');
        if (categorySelect) {
            var softwareOnlyLabels = {
                title: 'Software name', year: 'Release year', status: 'Availability status',
                genres: 'Tags / features', director_or_creator: 'Developer',
                studio_or_publisher: 'Publisher / company', synopsis: 'Description',
                poster_url: 'Application artwork', backdrop_url: 'Screenshot / banner', trailer: 'Product demo'
            };
            var softwareIrrelevantFields = ['original_title', 'duration_or_episodes', 'cast', 'other_meta'];
            function updateCategoryFields() {
                var isSoftware = categorySelect.value === 'software';
                if (comicFields) comicFields.hidden = categorySelect.value !== 'comics';
                if (mangaFields) mangaFields.hidden = categorySelect.value !== 'manga';
                if (softwareFields) softwareFields.hidden = !isSoftware;
                softwareIrrelevantFields.forEach(function (key) {
                    var input = document.getElementById('f_' + key);
                    var field = input && input.closest('.a-field');
                    if (field) field.hidden = isSoftware;
                });
                Object.keys(softwareOnlyLabels).forEach(function (key) {
                    var label = document.querySelector('label[for="f_' + key + '"]');
                    if (label) label.textContent = isSoftware ? softwareOnlyLabels[key] : label.dataset.defaultLabel;
                });
            }
            categorySelect.addEventListener('change', updateCategoryFields);
            updateCategoryFields();
        }
    // Live preview for image link fields.
    document.querySelectorAll('input[data-preview]').forEach(function (input) {
      var img = document.getElementById(input.getAttribute('data-preview'));
      var note = document.getElementById(input.getAttribute('data-preview') + 'Note');
      if (!img) return;
      function update() {
        var v = input.value.trim();
        if (/^https?:\/\/\S+$/i.test(v)) {
          img.hidden = false; img.src = v;
          if (note) note.textContent = 'Loading preview';
        } else {
          img.hidden = true; img.removeAttribute('src');
          if (note) note.textContent = v === '' ? 'No link set' : 'Not a valid http or https link';
        }
      }
      img.addEventListener('load', function () { if (note) note.textContent = 'Link loads'; });
      img.addEventListener('error', function () {
        if (!img.getAttribute('src')) return;
        img.hidden = true;
        if (note) note.textContent = 'Link failed to load';
      });
      input.addEventListener('input', update);
      update();
    });
    // Flag thumbnails whose link fails to load.
    document.querySelectorAll('img[data-check]').forEach(function (img) {
      function mark() {
        var tag = img.parentNode.querySelector('.js-broken');
        if (tag) tag.hidden = false;
      }
      if (img.complete && img.naturalWidth === 0) mark();
      img.addEventListener('error', mark);
    });
  })();
  </script>
</body>
</html>
<?php
}

/* ------------------------------------------------------------------
 * Bootstrap: database, sign-in state, setup check
 * ------------------------------------------------------------------ */
$db = getDb();
if (!$db) {
    http_response_code(503);
    adminHead('Database offline', '');
    echo '<div class="a-flash err">The database connection failed. Check the credentials in config.php and that MySQL is running.</div>';
    adminFoot();
    exit;
}

if (!activityTableReady($db)) {
    http_response_code(503);
    adminHead('Setup required', '');
    echo '<h1>Setup required</h1><p class="a-lead">The database schema is incomplete. From the project folder, run <code>mysql -u root -p &lt; database.sql</code> to safely create any missing tables in the configured Fan Hub Plus database, then reload this page.</p>';
    adminFoot();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$admin = null;
if (!empty($_SESSION['user_id']) && !empty($_SESSION['admin_auth_time'])) {
    if (time() - (int)($_SESSION['admin_last'] ?? 0) > ADMIN_IDLE_LIMIT) {
        unset($_SESSION['admin_auth_time'], $_SESSION['admin_last']);
        flash('err', 'Your admin session timed out. Sign in again.');
    } else {
        $admin = currentAdmin($db);
        if ($admin) {
            $_SESSION['admin_last'] = time();
        } else {
            unset($_SESSION['admin_auth_time'], $_SESSION['admin_last']);
        }
    }
}

/* ------------------------------------------------------------------
 * Sign in and sign out
 * ------------------------------------------------------------------ */
function renderLogin(): void
{
    adminHead('Sign in', '');
    ?>
    <div class="a-login">
      <h1>Admin sign in</h1>
      <p class="a-lead">Administrator accounts only. Sign in again even if you are already signed in to the site.</p>
      <form method="post" action="admin.php" class="a-panel">
        <?= csrfField() ?>
        <input type="hidden" name="do" value="login">
        <div class="a-field">
          <label for="lgEmail">Email</label>
          <input type="email" id="lgEmail" name="email" autocomplete="username" required maxlength="150">
        </div>
        <div class="a-field">
          <label for="lgPass">Password</label>
                    <div class="a-password-control">
                        <input type="password" id="lgPass" name="password" autocomplete="current-password" required maxlength="200">
                        <button type="button" id="lgPassToggle" class="a-btn sm ghost" aria-controls="lgPass" aria-pressed="false" aria-label="Show password">Show</button>
                    </div>
        </div>
        <button type="submit" class="a-btn primary">Sign in</button>
      </form>
    </div>
    <?php
    adminFoot();
    exit;
}

if ($method === 'POST') {
    sameOriginCheck();
    $do = str($_POST['do'] ?? '');

    if ($do === 'login') {
        csrfCheck();
        $email = strtolower(line($_POST['email'] ?? ''));
        $password = str($_POST['password'] ?? '');
        $ip = clientIp();

        if (tooManyFailures($db, $ip, $email)) {
            logActivity($db, null, 'login_blocked', $email);
            flash('err', 'Too many failed sign-in attempts. Wait 15 minutes and try again.');
            redirect(adminUrl());
        }

        $st = $db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();
        $passOk = password_verify($password, $u ? $u['password_hash'] : ADMIN_DUMMY_HASH);

        if ($u && $passOk && $u['role'] === 'admin') {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $u['id'];
            $_SESSION['user_name']  = $u['name'];
            $_SESSION['user_email'] = $u['email'];
            $_SESSION['user_role']  = $u['role'];
            $_SESSION['admin_auth_time'] = time();
            $_SESSION['admin_last']      = time();
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                $up = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $up->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            }
            logActivity($db, (int)$u['id'], 'login', $u['email']);
            redirect(adminUrl());
        }

        logActivity($db, null, 'login_failed', $email);
        flash('err', 'Sign in failed. Check the email and password, and that the account is an administrator.');
        redirect(adminUrl());
    }

    if (!$admin) {
        flash('err', 'Sign in to continue.');
        redirect(adminUrl());
    }

    csrfCheck();

    if ($do === 'logout') {
        logActivity($db, (int)$admin['id'], 'logout', $admin['email']);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cp = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cp['path'], $cp['domain'], (bool)$cp['secure'], (bool)$cp['httponly']);
        }
        session_destroy();
        session_start();
        flash('ok', 'You are signed out.');
        redirect(adminUrl());
    }
    // Other POST actions are dispatched below once the helpers are defined.
}

if (!$admin) {
    renderLogin();
}

/* ------------------------------------------------------------------
 * Items: validation and persistence
 * ------------------------------------------------------------------ */
const COMIC_META_FIELDS = [
    'writer' => 'Writer', 'artist' => 'Artist', 'inker' => 'Inker', 'colorist' => 'Colorist',
    'letterer' => 'Letterer', 'cover_artist' => 'Cover artist', 'series' => 'Series',
    'volume' => 'Volume', 'issues' => 'Issue number or range', 'format' => 'Format',
    'imprint' => 'Imprint', 'isbn' => 'ISBN',
];
const MANGA_META_FIELDS = ['author' => 'Author', 'magazine' => 'Magazine', 'volumes' => 'Volumes'];
const SOFTWARE_META_FIELDS = [
    'software_type' => 'Software type', 'platform' => 'Platform / operating system',
    'version' => 'Version', 'license' => 'License', 'programming_language' => 'Programming language',
    'engine' => 'Engine / framework', 'requirements' => 'System requirements', 'website' => 'Official website',
];

function emptyItemForm(string $category = ''): array
{
    $form = ['category_slug' => $category, 'external_id' => '', 'title' => '', 'original_title' => '', 'year' => '',
        'score' => '', 'status' => 'Released', 'duration_or_episodes' => '', 'genres' => '',
        'director_or_creator' => '', 'studio_or_publisher' => '', 'synopsis' => '', 'poster_url' => '',
        'backdrop_url' => '', 'trailer' => '', 'cast' => '', 'other_meta' => '', 'is_featured' => '', 'is_trending' => ''];
    foreach (array_merge(array_keys(COMIC_META_FIELDS), array_keys(MANGA_META_FIELDS), array_keys(SOFTWARE_META_FIELDS)) as $key) $form[$key] = '';
    return $form;
}

function itemRowToForm(array $r): array
{
    $meta = [];
    if (!empty($r['extra_meta'])) {
        $d = json_decode($r['extra_meta'], true);
        if (is_array($d)) $meta = $d;
    }
    $castLines = [];
    if (!empty($meta['cast']) && is_array($meta['cast'])) {
        foreach ($meta['cast'] as $c) {
            if (is_array($c) && isset($c['name'])) {
                $castLines[] = str($c['name']) . ' | ' . str($c['role'] ?? '');
            }
        }
    }
    unset($meta['cast']);
    $form = [
        'category_slug' => str($r['category_slug']), 'external_id' => str($r['external_id']),
        'title' => str($r['title']), 'original_title' => str($r['original_title']),
        'year' => $r['year'] === null ? '' : str($r['year']),
        'score' => $r['score'] === null ? '' : rtrim(rtrim(number_format((float)$r['score'], 1, '.', ''), '0'), '.'),
        'status' => str($r['status']), 'duration_or_episodes' => str($r['duration_or_episodes']),
        'genres' => str($r['genres']), 'director_or_creator' => str($r['director_or_creator']),
        'studio_or_publisher' => str($r['studio_or_publisher']), 'synopsis' => str($r['synopsis']),
        'poster_url' => str($r['poster_url']), 'backdrop_url' => str($r['backdrop_url']),
        'trailer' => !empty($r['trailer_key']) ? str($r['trailer_key']) : str($r['trailer_url']),
        'cast' => implode("\n", $castLines),
        'other_meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '',
        'is_featured' => !empty($r['is_featured']) ? '1' : '', 'is_trending' => !empty($r['is_trending']) ? '1' : '',
    ];
    foreach (array_merge(COMIC_META_FIELDS, MANGA_META_FIELDS, SOFTWARE_META_FIELDS) as $key => $label) {
        $form[$key] = str($meta[$key] ?? '');
        unset($meta[$key]);
    }
    $form['other_meta'] = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '';
    return $form;
}

/** Returns [cleanData, errors, formValues]. */
function validateItem(PDO $db, array $p, array $cats, ?array $existing): array
{
    $err = [];
    $f = emptyItemForm();
    foreach (['category_slug', 'external_id', 'title', 'original_title', 'status', 'duration_or_episodes', 'genres',
              'director_or_creator', 'studio_or_publisher', 'poster_url', 'backdrop_url', 'trailer', 'year', 'score'] as $k) {
        $f[$k] = line($p[$k] ?? '');
    }
    foreach (array_merge(COMIC_META_FIELDS, MANGA_META_FIELDS, SOFTWARE_META_FIELDS) as $key => $label) {
        $f[$key] = $key === 'requirements' ? block($p[$key] ?? '') : line($p[$key] ?? '');
    }
    foreach (['synopsis', 'cast', 'other_meta'] as $k) $f[$k] = block($p[$k] ?? '');
    $f['is_featured'] = !empty($p['is_featured']) ? '1' : '';
    $f['is_trending'] = !empty($p['is_trending']) ? '1' : '';

    $slugs = array_column($cats, 'slug');
    if (!in_array($f['category_slug'], $slugs, true)) $err['category_slug'] = 'Choose a category from the list.';
    if ($existing && $f['category_slug'] !== $existing['category_slug']) {
        $videoCount = $db->prepare('SELECT COUNT(*) FROM media_videos WHERE media_item_id = ?');
        $videoCount->execute([$existing['id']]);
        if ((int)$videoCount->fetchColumn() > 0 && !supportsPlaylistCategory($f['category_slug'])) {
            $err['category_slug'] = 'Remove this title\'s playlist videos before moving it to Gaming.';
        }
    }

    if ($f['title'] === '') $err['title'] = 'Title is required.';
    elseif (mb_strlen($f['title']) > 255) $err['title'] = 'Title must be 255 characters or fewer.';
    if (mb_strlen($f['original_title']) > 255) $err['original_title'] = 'Original title must be 255 characters or fewer.';
    if (mb_strlen($f['status']) > 50) $err['status'] = 'Status must be 50 characters or fewer.';
    if (mb_strlen($f['duration_or_episodes']) > 100) $err['duration_or_episodes'] = 'Keep this under 100 characters.';
    if (mb_strlen($f['director_or_creator']) > 255) $err['director_or_creator'] = 'Keep this under 255 characters.';
    if (mb_strlen($f['studio_or_publisher']) > 255) $err['studio_or_publisher'] = 'Keep this under 255 characters.';
    if (mb_strlen($f['synopsis']) > 6000) $err['synopsis'] = 'Synopsis must be 6000 characters or fewer.';
    if (mb_strlen($f['requirements']) > 1000) $err['requirements'] = 'System requirements must be 1000 characters or fewer.';

    // Year
    $year = null;
    if ($f['year'] !== '') {
        if (!ctype_digit($f['year']) || (int)$f['year'] < 1888 || (int)$f['year'] > (int)date('Y') + 10) {
            $err['year'] = 'Enter a four digit year between 1888 and ' . ((int)date('Y') + 10) . '.';
        } else {
            $year = (int)$f['year'];
        }
    }

    // Score
    $score = 0.0;
    if ($f['score'] !== '') {
        $raw = str_replace(',', '.', $f['score']);
        if (!is_numeric($raw) || (float)$raw < 0 || (float)$raw > 10) $err['score'] = 'Score must be a number from 0 to 10.';
        else $score = round((float)$raw, 1);
    }

    // Genres: normalise to "A, B, C"
    $genreList = [];
    foreach (explode(',', $f['genres']) as $g) {
        $g = trim($g);
        if ($g !== '' && !in_array(mb_strtolower($g), array_map('mb_strtolower', $genreList), true)) $genreList[] = $g;
    }
    $genres = implode(', ', $genreList);
    if (mb_strlen($genres) > 255) $err['genres'] = 'Genres are too long (255 characters in total).';

    // Image links
    $poster   = checkImageUrl($f['poster_url'], 'Poster', 'poster_url', $err);
    $backdrop = checkImageUrl($f['backdrop_url'], 'Backdrop', 'backdrop_url', $err);

    // Trailer
    $trailerKey = null; $trailerUrl = null;
    if ($f['trailer'] !== '') {
        $yt = youtubeId($f['trailer']);
        if ($yt === null) $err['trailer'] = 'Enter a YouTube link or the 11 character video ID.';
        else { $trailerKey = $yt; $trailerUrl = 'https://www.youtube.com/embed/' . $yt; }
    }

    // Cast and extra metadata
    $cast = [];
    $castErr = false;
    if ($f['cast'] !== '') {
        foreach (preg_split('/\n/', $f['cast']) as $ln) {
            $ln = trim($ln);
            if ($ln === '') continue;
            $bits = explode('|', $ln, 2);
            $name = trim($bits[0]);
            $role = trim($bits[1] ?? '');
            if ($name === '' || mb_strlen($name) > 100 || mb_strlen($role) > 100 || count($cast) >= 30) { $castErr = true; break; }
            $cast[] = ['name' => $name, 'role' => $role];
        }
        if ($castErr) $err['cast'] = 'Use one person per line as Name | Role, up to 100 characters each and 30 lines.';
    }
    $meta = [];
    if ($f['other_meta'] !== '') {
        $d = strlen($f['other_meta']) <= 8000 ? json_decode($f['other_meta'], true, 6) : null;
        $isObject = is_array($d) && ($d === [] || array_keys($d) !== range(0, count($d) - 1));
        if (json_last_error() !== JSON_ERROR_NONE || !$isObject) {
            $err['other_meta'] = 'Other metadata must be a JSON object of 8000 characters or fewer, for example {"platform": "PC"}.';
        } else {
            unset($d['cast']);
            $meta = $d;
        }
    }
    if ($cast) $meta['cast'] = $cast;
    foreach (array_merge(COMIC_META_FIELDS, MANGA_META_FIELDS, SOFTWARE_META_FIELDS) as $key => $label) {
        if ($f[$key] !== '') $meta[$key] = $f[$key];
        else unset($meta[$key]);
    }
    $extra = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    // External ID: chosen on create, never changed afterwards (watchlists reference it)
    $externalId = $existing ? $existing['external_id'] : '';
    if (!$existing && !isset($err['category_slug'])) {
        $custom = strtolower($f['external_id']);
        if ($custom !== '') {
            if (!preg_match('/^[a-z0-9][a-z0-9_-]{2,99}$/', $custom) || !preg_match('/[a-z]/', $custom)) {
                $err['external_id'] = 'Use 3 to 100 lowercase letters, digits, hyphens or underscores, with at least one letter.';
            } else {
                $externalId = $custom;
            }
        } elseif ($f['title'] !== '') {
            $base = idPrefixForCategory($f['category_slug']) . '-' . (slugify($f['title']) ?: bin2hex(random_bytes(3)));
            $base = substr($base, 0, 90);
            $externalId = $base;
            $chk = $db->prepare("SELECT 1 FROM media_items WHERE external_id = ? LIMIT 1");
            for ($i = 2; $i < 50; $i++) {
                $chk->execute([$externalId]);
                if (!$chk->fetchColumn()) break;
                $externalId = $base . '-' . $i;
            }
        }
        if ($externalId !== '' && !isset($err['external_id'])) {
            $chk = $db->prepare("SELECT 1 FROM media_items WHERE external_id = ? LIMIT 1");
            $chk->execute([$externalId]);
            if ($chk->fetchColumn()) $err['external_id'] = 'That ID is already used by another item.';
        }
    }

    $data = [
        'external_id' => $externalId,
        'category_slug' => $f['category_slug'],
        'type' => typeForCategory($f['category_slug']),
        'title' => $f['title'],
        'original_title' => $f['original_title'] !== '' ? $f['original_title'] : null,
        'year' => $year,
        'score' => $score,
        'status' => $f['status'] !== '' ? $f['status'] : 'Released',
        'duration_or_episodes' => $f['duration_or_episodes'] !== '' ? $f['duration_or_episodes'] : null,
        'genres' => $genres !== '' ? $genres : null,
        'synopsis' => $f['synopsis'] !== '' ? $f['synopsis'] : null,
        'poster_url' => $poster,
        'backdrop_url' => $backdrop,
        'trailer_key' => $trailerKey,
        'trailer_url' => $trailerUrl,
        'director_or_creator' => $f['director_or_creator'] !== '' ? $f['director_or_creator'] : null,
        'studio_or_publisher' => $f['studio_or_publisher'] !== '' ? $f['studio_or_publisher'] : null,
        'extra_meta' => $extra,
        'is_featured' => $f['is_featured'] === '1' ? 1 : 0,
        'is_trending' => $f['is_trending'] === '1' ? 1 : 0,
    ];
    return [$data, $err, $f];
}

const ITEM_INSERT_COLS = ['external_id', 'category_slug', 'type', 'title', 'original_title', 'year', 'score', 'status',
    'duration_or_episodes', 'genres', 'synopsis', 'poster_url', 'backdrop_url', 'trailer_key', 'trailer_url',
    'director_or_creator', 'studio_or_publisher', 'extra_meta', 'is_featured', 'is_trending'];

function fetchItem(PDO $db, int $id): ?array
{
    $st = $db->prepare("SELECT * FROM media_items WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function parseIniBytes(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') return PHP_INT_MAX;
    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    $multiplier = ['g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024][$unit] ?? 1;
    return (int)min(PHP_INT_MAX, $number * $multiplier);
}

function maxAdminVideoBytes(): int
{
    $postLimit = parseIniBytes((string)ini_get('post_max_size'));
    $uploadLimit = parseIniBytes((string)ini_get('upload_max_filesize'));
    return max(0, min(1024 ** 3, $uploadLimit, $postLimit - 1024 ** 2));
}

function formatAdminBytes(int $bytes): string
{
    return $bytes >= 1024 ** 3
        ? number_format($bytes / (1024 ** 3), 1) . ' GB'
        : number_format($bytes / (1024 ** 2), 1) . ' MB';
}

function videoFilePath(string $fileName): ?string
{
    if (!preg_match('/^[a-f0-9]{64}\.(?:mp4|webm|ogv)$/', $fileName)) return null;
    return __DIR__ . '/storage/videos/' . $fileName;
}

/** Keeps saved watchlist rows in step with the item (they store a copy of title, poster, type). */
function syncWatchlist(PDO $db, array $old, array $new): void
{
    $st = $db->prepare("UPDATE watchlist SET title = ?, year = ?, score = ?, poster_url = ?, category = ?, type = ?
                        WHERE media_item_id = ? OR (external_id = ? AND type = ?)");
    $st->execute([$new['title'], $new['year'], $new['score'], $new['poster_url'], $new['category_slug'], $new['type'],
                  $old['id'], $old['external_id'], $old['type']]);
}

function renderItemForm(PDO $db, array $f, array $err, ?int $editId): void
{
    $cats = fetchCategories($db);
    $isEdit = $editId !== null;
    adminHead($isEdit ? 'Edit item' : 'Add item', 'items', $GLOBALS['admin']);
    $fld = function (string $key, string $label, string $html, string $help = '', bool $full = false) use ($err) {
        echo '<div class="a-field' . ($full ? ' full' : '') . (isset($err[$key]) ? ' has-err' : '') . '">';
        echo '<label for="f_' . h($key) . '" data-default-label="' . h($label) . '">' . h($label) . '</label>' . $html;
        if ($help !== '') echo '<div class="a-help">' . h($help) . '</div>';
        if (isset($err[$key])) echo '<div class="a-errmsg" role="alert">' . h($err[$key]) . '</div>';
        echo '</div>';
    };
    $in = function (string $key, string $type = 'text', array $extra = []) use ($f) {
        $a = '';
        foreach ($extra as $k => $v) $a .= ' ' . $k . ($v === true ? '' : '="' . h($v) . '"');
        return '<input type="' . $type . '" id="f_' . h($key) . '" name="' . h($key) . '" value="' . h($f[$key]) . '"' . $a . '>';
    };
    ?>
    <h1><?= $isEdit ? 'Edit item' : 'Add item' ?></h1>
    <p class="a-lead"><?= $isEdit ? 'Changes appear on the public pages straight away.' : 'Fill in the record. Only category and title are required.' ?></p>
    <?php if ($err): ?><div class="a-flash err" role="alert">Fix the highlighted fields and save again.</div><?php endif; ?>
    <form method="post" action="admin.php" class="a-panel" autocomplete="off">
      <?= csrfField() ?>
      <input type="hidden" name="do" value="item_save">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editId ?>"><?php endif; ?>
      <div class="a-form">
        <?php
        $opts = '<select id="f_category_slug" name="category_slug" required><option value="">Select a category</option>';
        foreach ($cats as $c) {
            $opts .= '<option value="' . h($c['slug']) . '"' . ($f['category_slug'] === $c['slug'] ? ' selected' : '') . '>' . h($c['name']) . '</option>';
        }
        $opts .= '</select>';
        $fld('category_slug', 'Category', $opts, 'Sets the type used by the site (Movies use "movie").');
        $fld('external_id', 'Item ID', $in('external_id', 'text', $isEdit ? ['readonly' => true, 'maxlength' => '100'] : ['maxlength' => '100', 'placeholder' => 'Leave blank to generate']),
            $isEdit ? 'Cannot be changed. Watchlists and page links use it.' : 'Optional. Lowercase letters, digits, hyphens. Example: anime-my-title');
        $fld('title', 'Title', $in('title', 'text', ['required' => true, 'maxlength' => '255']), '', true);
        $fld('original_title', 'Original title', $in('original_title', 'text', ['maxlength' => '255']));
        $fld('year', 'Year', $in('year', 'text', ['inputmode' => 'numeric', 'maxlength' => '4', 'placeholder' => '2024']));
        $fld('score', 'Score (0 to 10)', $in('score', 'text', ['inputmode' => 'decimal', 'maxlength' => '4', 'placeholder' => '8.5']));
        $fld('status', 'Status', $in('status', 'text', ['maxlength' => '50', 'list' => 'statusList']) .
            '<datalist id="statusList"><option value="Released"><option value="Finished Airing"><option value="Currently Airing"><option value="Available"><option value="Completed"><option value="Ended"><option value="Renewed"><option value="Publishing"><option value="On Hiatus"><option value="Archived Showcase"><option value="Beta"><option value="Early Access"><option value="Discontinued"></datalist>');
        $fld('duration_or_episodes', 'Duration or episodes', $in('duration_or_episodes', 'text', ['maxlength' => '100', 'placeholder' => '24 Episodes']));
        $fld('genres', 'Genres', $in('genres', 'text', ['maxlength' => '255', 'placeholder' => 'Action, Drama, Fantasy']), 'Separate with commas.', true);
        $fld('director_or_creator', 'Director or creator', $in('director_or_creator', 'text', ['maxlength' => '255']));
        $fld('studio_or_publisher', 'Studio or publisher', $in('studio_or_publisher', 'text', ['maxlength' => '255']));
        $fld('synopsis', 'Synopsis', '<textarea id="f_synopsis" name="synopsis" maxlength="6000">' . h($f['synopsis']) . '</textarea>', '', true);
        foreach (['poster_url' => 'Poster link', 'backdrop_url' => 'Backdrop link'] as $k => $label) {
            $pid = 'prev_' . $k;
            $fld($k, $label, $in($k, 'text', ['maxlength' => '500', 'inputmode' => 'url', 'placeholder' => 'https://', 'data-preview' => $pid]) .
                '<div class="a-prevbox"><img id="' . $pid . '" class="a-prev" alt="' . h($label) . ' preview" hidden><span id="' . $pid . 'Note" class="a-prevnote"></span></div>',
                'Full http or https address of the image.');
        }
        $fld('trailer', 'Trailer', $in('trailer', 'text', ['maxlength' => '200', 'placeholder' => 'https://www.youtube.com/watch?v=...']), 'YouTube link or video ID. The embed link is built for you.', true);
        $fld('cast', 'Cast and credits', '<textarea id="f_cast" name="cast" class="mono" placeholder="Edward Elric | Main">' . h($f['cast']) . '</textarea>', 'One person per line as Name | Role.', true);
        $fld('other_meta', 'Other metadata (JSON)', '<textarea id="f_other_meta" name="other_meta" class="mono" placeholder="{&quot;platform&quot;: &quot;PC&quot;}">' . h($f['other_meta']) . '</textarea>', 'Optional JSON object, for example the network, magazine or volume count. Cast is handled above.', true);
        ?>
                <section class="a-comic-fields full" id="comicFields"<?= $f['category_slug'] === 'comics' ? '' : ' hidden' ?>>
                    <h2>Comic details</h2>
                    <div class="a-form">
                        <?php foreach (COMIC_META_FIELDS as $key => $label):
                                $placeholder = match ($key) {
                                        'issues' => '1-12 or 12 issues',
                                        'volume' => 'Volume 1',
                                        'format' => 'Trade paperback, hardcover, single issue',
                                        'isbn' => 'ISBN-13',
                                        default => '',
                                };
                                $fld($key, $label, $in($key, 'text', ['maxlength' => $key === 'isbn' ? '32' : '255', 'placeholder' => $placeholder]));
                        endforeach; ?>
                    </div>
                </section>
                <section class="a-comic-fields full" id="mangaFields"<?= $f['category_slug'] === 'manga' ? '' : ' hidden' ?>>
                    <h2>Manga details</h2>
                    <div class="a-form">
                        <?php foreach (MANGA_META_FIELDS as $key => $label):
                                $placeholder = $key === 'volumes' ? 'e.g. 42' : '';
                                $fld($key, $label, $in($key, 'text', ['maxlength' => '255', 'placeholder' => $placeholder]));
                        endforeach; ?>
                    </div>
                </section>
                <section class="a-comic-fields full" id="softwareFields"<?= $f['category_slug'] === 'software' ? '' : ' hidden' ?>>
                    <h2>Software details</h2>
                    <div class="a-form">
                        <?php foreach (SOFTWARE_META_FIELDS as $key => $label):
                                $extra = ['maxlength' => '500'];
                                if ($key === 'requirements') $extra['placeholder'] = 'Minimum CPU, memory, storage, and operating system';
                                elseif ($key === 'website') $extra['placeholder'] = 'https://';
                                elseif ($key === 'software_type') $extra['placeholder'] = 'Desktop app, mobile app, web app, library';
                                $html = $key === 'requirements'
                                        ? '<textarea id="f_' . h($key) . '" name="' . h($key) . '" maxlength="1000">' . h($f[$key]) . '</textarea>'
                                        : $in($key, 'text', $extra);
                                $fld($key, $label, $html, '', $key === 'requirements');
                        endforeach; ?>
                    </div>
                </section>
        <div class="full">
          <div class="a-check"><input type="checkbox" id="f_is_featured" name="is_featured" value="1"<?= $f['is_featured'] === '1' ? ' checked' : '' ?>><label for="f_is_featured">Featured on the home page hero</label></div>
          <div class="a-check"><input type="checkbox" id="f_is_trending" name="is_trending" value="1"<?= $f['is_trending'] === '1' ? ' checked' : '' ?>><label for="f_is_trending">Trending</label></div>
        </div>
      </div>
      <div class="a-actions">
        <button type="submit" class="a-btn primary"><?= $isEdit ? 'Save changes' : 'Add item' ?></button>
        <a class="a-btn" href="<?= h(adminUrl(['view' => 'items'])) ?>">Cancel</a>
      </div>
    </form>
    <?php
    adminFoot();
    exit;
}

/* ------------------------------------------------------------------
 * Users: validation and persistence
 * ------------------------------------------------------------------ */
function adminCount(PDO $db, int $excludeId = 0): int
{
    $st = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id <> ?");
    $st->execute([$excludeId]);
    return (int)$st->fetchColumn();
}

function renderUserForm(array $f, array $err, ?int $editId, bool $isSelf): void
{
    $isEdit = $editId !== null;
    adminHead($isEdit ? 'Edit user' : 'Add user', 'users', $GLOBALS['admin']);
    $showErr = function (string $k) use ($err) {
        return isset($err[$k]) ? '<div class="a-errmsg" role="alert">' . h($err[$k]) . '</div>' : '';
    };
    ?>
    <h1><?= $isEdit ? 'Edit user' : 'Add user' ?></h1>
    <p class="a-lead"><?= $isEdit ? 'Leave the password blank to keep the current one.' : 'Passwords are stored as bcrypt hashes.' ?></p>
    <?php if ($err): ?><div class="a-flash err" role="alert">Fix the highlighted fields and save again.</div><?php endif; ?>
    <form method="post" action="admin.php" class="a-panel" autocomplete="off">
      <?= csrfField() ?>
      <input type="hidden" name="do" value="user_save">
      <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$editId ?>"><?php endif; ?>
      <div class="a-form">
        <div class="a-field<?= isset($err['name']) ? ' has-err' : '' ?>">
          <label for="u_name">Name</label>
          <input type="text" id="u_name" name="name" value="<?= h($f['name']) ?>" required minlength="2" maxlength="100">
          <?= $showErr('name') ?>
        </div>
        <div class="a-field<?= isset($err['email']) ? ' has-err' : '' ?>">
          <label for="u_email">Email</label>
          <input type="email" id="u_email" name="email" value="<?= h($f['email']) ?>" required maxlength="150">
          <?= $showErr('email') ?>
        </div>
        <div class="a-field<?= isset($err['role']) ? ' has-err' : '' ?>">
          <label for="u_role">Role</label>
          <select id="u_role" name="role"<?= $isSelf ? ' disabled' : '' ?>>
            <option value="user"<?= $f['role'] === 'user' ? ' selected' : '' ?>>User</option>
            <option value="admin"<?= $f['role'] === 'admin' ? ' selected' : '' ?>>Admin</option>
          </select>
          <?php if ($isSelf): ?><input type="hidden" name="role" value="admin"><div class="a-help">You cannot change your own role.</div><?php endif; ?>
          <?= $showErr('role') ?>
        </div>
        <div class="a-field<?= isset($err['password']) ? ' has-err' : '' ?>">
          <label for="u_pass"><?= $isEdit ? 'New password' : 'Password' ?></label>
          <input type="password" id="u_pass" name="password" autocomplete="new-password" maxlength="72"<?= $isEdit ? '' : ' required' ?> minlength="8">
          <div class="a-help">8 to 72 characters.</div>
          <?= $showErr('password') ?>
        </div>
      </div>
      <div class="a-actions">
        <button type="submit" class="a-btn primary"><?= $isEdit ? 'Save changes' : 'Add user' ?></button>
        <a class="a-btn" href="<?= h(adminUrl(['view' => 'users'])) ?>">Cancel</a>
      </div>
    </form>
    <?php
    adminFoot();
    exit;
}

/* ------------------------------------------------------------------
 * POST actions (admin only, CSRF already verified)
 * ------------------------------------------------------------------ */
if ($method === 'POST') {
    $adminId = (int)$admin['id'];

    if ($do === 'item_save') {
        $id = (int)($_POST['id'] ?? 0);
        $existing = $id > 0 ? fetchItem($db, $id) : null;
        if ($id > 0 && !$existing) { flash('err', 'That item no longer exists.'); redirect(adminUrl(['view' => 'items'])); }
        $cats = fetchCategories($db);
        [$data, $err, $form] = validateItem($db, $_POST, $cats, $existing);
        if ($existing) $form['external_id'] = $existing['external_id'];
        if ($err) renderItemForm($db, $form, $err, $existing ? $id : null);

        try {
            $db->beginTransaction();
            if ($existing) {
                $cols = array_slice(ITEM_INSERT_COLS, 1); // external_id is never updated
                $sql = "UPDATE media_items SET " . implode(' = ?, ', $cols) . " = ? WHERE id = ? LIMIT 1";
                $vals = [];
                foreach ($cols as $c) $vals[] = $data[$c];
                $vals[] = $id;
                $db->prepare($sql)->execute($vals);
                syncWatchlist($db, $existing, $data);
                $db->commit();
                logActivity($db, $adminId, 'item_update', $data['external_id'], $data['title']);
                flash('ok', 'Saved "' . $data['title'] . '".');
            } else {
                $sql = "INSERT INTO media_items (" . implode(', ', ITEM_INSERT_COLS) . ") VALUES (" . implode(', ', array_fill(0, count(ITEM_INSERT_COLS), '?')) . ")";
                $vals = [];
                foreach (ITEM_INSERT_COLS as $c) $vals[] = $data[$c];
                $db->prepare($sql)->execute($vals);
                $db->commit();
                logActivity($db, $adminId, 'item_create', $data['external_id'], $data['title']);
                flash('ok', 'Added "' . $data['title'] . '" to ' . $data['category_slug'] . '.');
            }
        } catch (PDOException $ex) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('admin item_save failed: ' . $ex->getMessage());
            $err['title'] = ($ex->getCode() === '23000') ? 'That item ID already exists. Pick a different one.' : 'The database rejected this save. Check the values and try again.';
            renderItemForm($db, $form, $err, $existing ? $id : null);
        }
        redirect(adminUrl(['view' => 'items']));
    }

    if ($do === 'item_delete') {
        $id = (int)($_POST['id'] ?? 0);
        $item = $id > 0 ? fetchItem($db, $id) : null;
        if (!$item) { flash('err', 'That item no longer exists.'); redirect(adminUrl(['view' => 'items'])); }
        $videoStmt = $db->prepare('SELECT file_name FROM media_videos WHERE media_item_id = ?');
        $videoStmt->execute([$id]);
        $videoFiles = $videoStmt->fetchAll(PDO::FETCH_COLUMN);
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM watchlist WHERE media_item_id = ? OR (external_id = ? AND type = ?)")
               ->execute([$id, $item['external_id'], $item['type']]);
            $db->prepare("DELETE FROM media_items WHERE id = ? LIMIT 1")->execute([$id]);
            $db->commit();
            foreach ($videoFiles as $fileName) {
                $path = videoFilePath((string)$fileName);
                if ($path && is_file($path) && !@unlink($path)) {
                    error_log('admin item_delete could not remove video file: ' . basename($path));
                }
            }
            logActivity($db, $adminId, 'item_delete', $item['external_id'], $item['title']);
            flash('ok', 'Deleted "' . $item['title'] . '" and removed it from user watchlists.');
        } catch (PDOException $ex) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('admin item_delete failed: ' . $ex->getMessage());
            flash('err', 'The item could not be deleted.');
        }
        redirect(adminUrl(['view' => 'items']));
    }

    if ($do === 'video_upload') {
        $itemId = (int)($_POST['media_item_id'] ?? 0);
        $item = $itemId > 0 ? fetchItem($db, $itemId) : null;
        $back = adminUrl(['view' => 'playlist', 'id' => $itemId]);
        if (!$item || !supportsPlaylistCategory($item['category_slug'])) {
            flash('err', 'Videos can only be added to an existing item outside Gaming.');
            redirect(adminUrl(['view' => 'items']));
        }

        $label = line($_POST['episode_label'] ?? '');
        $upload = $_FILES['video_file'] ?? null;
        if ($label === '' || mb_strlen($label) > 150) {
            flash('err', 'Enter a label of 1 to 150 characters.');
            redirect($back);
        }
        if (empty($_POST['rights_confirm'])) {
            flash('err', 'Confirm that you own or are licensed to host this video.');
            redirect($back);
        }
        if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            $message = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                ? 'The video exceeds the server upload limit of ' . formatAdminBytes(maxAdminVideoBytes()) . '.'
                : 'Choose a video file and try again.';
            flash('err', $message);
            redirect($back);
        }

        $maxBytes = maxAdminVideoBytes();
        $tempPath = (string)($upload['tmp_name'] ?? '');
        $fileSize = (int)($upload['size'] ?? 0);
        $extension = strtolower(pathinfo((string)($upload['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedTypes = [
            'mp4' => ['video/mp4', 'application/mp4'],
            'webm' => ['video/webm'],
            'ogv' => ['video/ogg', 'application/ogg'],
        ];
        if (!isset($allowedTypes[$extension]) || !is_uploaded_file($tempPath) || $fileSize <= 0 || $fileSize > $maxBytes) {
            flash('err', 'Upload a non-empty MP4, WebM, or OGV video no larger than ' . formatAdminBytes($maxBytes) . '.');
            redirect($back);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tempPath);
        if (!in_array($mimeType, $allowedTypes[$extension], true)) {
            flash('err', 'The file contents do not match a supported video format.');
            redirect($back);
        }

        $directory = __DIR__ . '/storage/videos';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            flash('err', 'The private video storage folder could not be created.');
            redirect($back);
        }
        $fileName = bin2hex(random_bytes(32)) . '.' . $extension;
        $destination = videoFilePath($fileName);
        if (!$destination || !move_uploaded_file($tempPath, $destination)) {
            flash('err', 'The video could not be moved into private storage.');
            redirect($back);
        }

        try {
            $sort = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM media_videos WHERE media_item_id = ?');
            $sort->execute([$itemId]);
            $sortOrder = (int)$sort->fetchColumn();
            $insert = $db->prepare('INSERT INTO media_videos (media_item_id, episode_label, file_name, mime_type, file_size, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
            $insert->execute([$itemId, $label, $fileName, $mimeType, $fileSize, $sortOrder]);
            logActivity($db, $adminId, 'video_upload', $item['external_id'], $label);
            flash('ok', 'Added "' . $label . '" to ' . $item['title'] . '.');
        } catch (PDOException $ex) {
            @unlink($destination);
            error_log('admin video_upload failed: ' . $ex->getMessage());
            flash('err', 'The database rejected this video entry.');
        }
        redirect($back);
    }

    if ($do === 'video_delete') {
        $videoId = (int)($_POST['video_id'] ?? 0);
        $itemId = (int)($_POST['media_item_id'] ?? 0);
        $back = adminUrl(['view' => 'playlist', 'id' => $itemId]);
        $item = $itemId > 0 ? fetchItem($db, $itemId) : null;
        $st = $db->prepare('SELECT id, episode_label, file_name FROM media_videos WHERE id = ? AND media_item_id = ? LIMIT 1');
        $st->execute([$videoId, $itemId]);
        $video = $st->fetch();
        if (!$item || !$video) {
            flash('err', 'That playlist entry no longer exists.');
            redirect(adminUrl(['view' => 'items']));
        }
        $db->prepare('DELETE FROM media_videos WHERE id = ? LIMIT 1')->execute([$videoId]);
        $path = videoFilePath((string)$video['file_name']);
        $removed = $path && (!is_file($path) || @unlink($path));
        logActivity($db, $adminId, 'video_delete', $item['external_id'], $video['episode_label']);
        flash($removed ? 'ok' : 'err', $removed ? 'Removed "' . $video['episode_label'] . '" from the playlist.' : 'Playlist entry removed, but its stored file needs manual cleanup.');
        redirect($back);
    }

    if ($do === 'image_save') {
        $id = (int)($_POST['id'] ?? 0);
        $item = $id > 0 ? fetchItem($db, $id) : null;
        $back = ['view' => 'images'];
        $pg = (int)($_POST['page'] ?? 1);
        if ($pg > 1) $back['page'] = $pg;
        if (($_POST['filter'] ?? '') === 'problems') $back['filter'] = 'problems';
        if (!$item) { flash('err', 'That item no longer exists.'); redirect(adminUrl($back)); }
        $err = [];
        $poster = checkImageUrl(line($_POST['poster_url'] ?? ''), 'Poster', 'poster_url', $err);
        $backdrop = checkImageUrl(line($_POST['backdrop_url'] ?? ''), 'Backdrop', 'backdrop_url', $err);
        if ($err) { flash('err', $item['title'] . ': ' . implode(' ', $err)); redirect(adminUrl($back)); }
        try {
            $db->beginTransaction();
            $db->prepare("UPDATE media_items SET poster_url = ?, backdrop_url = ? WHERE id = ? LIMIT 1")->execute([$poster, $backdrop, $id]);
            $db->prepare("UPDATE watchlist SET poster_url = ? WHERE media_item_id = ? OR (external_id = ? AND type = ?)")
               ->execute([$poster, $id, $item['external_id'], $item['type']]);
            $db->commit();
            logActivity($db, $adminId, 'image_update', $item['external_id'], $item['title']);
            flash('ok', 'Image links saved for "' . $item['title'] . '".');
        } catch (PDOException $ex) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('admin image_save failed: ' . $ex->getMessage());
            flash('err', 'The image links could not be saved.');
        }
        redirect(adminUrl($back));
    }

    if ($do === 'user_save') {
        $id = (int)($_POST['id'] ?? 0);
        $target = null;
        if ($id > 0) {
            $st = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
            $st->execute([$id]);
            $target = $st->fetch() ?: null;
            if (!$target) { flash('err', 'That user no longer exists.'); redirect(adminUrl(['view' => 'users'])); }
        }
        $isSelf = $target && (int)$target['id'] === $adminId;
        $f = ['name' => line($_POST['name'] ?? ''), 'email' => strtolower(line($_POST['email'] ?? '')), 'role' => line($_POST['role'] ?? 'user')];
        $password = str($_POST['password'] ?? '');
        $err = [];
        if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) $err['name'] = 'Name must be 2 to 100 characters.';
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 150) $err['email'] = 'Enter a valid email address of 150 characters or fewer.';
        if (!in_array($f['role'], ['user', 'admin'], true)) $err['role'] = 'Choose User or Admin.';
        if ($isSelf) $f['role'] = 'admin';
        if ($target && !$isSelf && $target['role'] === 'admin' && $f['role'] !== 'admin' && adminCount($db, (int)$target['id']) === 0) {
            $err['role'] = 'This is the last administrator. Promote another user first.';
        }
        if ($password !== '' || !$target) {
            if (strlen($password) < 8 || strlen($password) > 72) $err['password'] = 'Password must be 8 to 72 characters.';
        }
        if (!isset($err['email'])) {
            $st = $db->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
            $st->execute([$f['email'], $id]);
            if ($st->fetch()) $err['email'] = 'Another account already uses this email.';
        }
        if ($err) renderUserForm($f, $err, $target ? $id : null, (bool)$isSelf);

        try {
            if ($target) {
                if ($password !== '') {
                    $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password_hash = ? WHERE id = ? LIMIT 1")
                       ->execute([$f['name'], $f['email'], $f['role'], password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ? LIMIT 1")
                       ->execute([$f['name'], $f['email'], $f['role'], $id]);
                }
                if ($isSelf) { $_SESSION['user_name'] = $f['name']; $_SESSION['user_email'] = $f['email']; }
                logActivity($db, $adminId, 'user_update', $f['email'], 'role=' . $f['role'] . ($password !== '' ? ', password reset' : ''));
                flash('ok', 'Saved ' . $f['email'] . '.');
            } else {
                $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)")
                   ->execute([$f['name'], $f['email'], password_hash($password, PASSWORD_DEFAULT), $f['role']]);
                logActivity($db, $adminId, 'user_create', $f['email'], 'role=' . $f['role']);
                flash('ok', 'Added ' . $f['email'] . '.');
            }
        } catch (PDOException $ex) {
            error_log('admin user_save failed: ' . $ex->getMessage());
            $err['email'] = ($ex->getCode() === '23000') ? 'Another account already uses this email.' : 'The database rejected this save.';
            renderUserForm($f, $err, $target ? $id : null, (bool)$isSelf);
        }
        redirect(adminUrl(['view' => 'users']));
    }

    if ($do === 'user_delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $target = $st->fetch();
        if (!$target) { flash('err', 'That user no longer exists.'); redirect(adminUrl(['view' => 'users'])); }
        if ((int)$target['id'] === $adminId) { flash('err', 'You cannot delete your own account.'); redirect(adminUrl(['view' => 'users'])); }
        if ($target['role'] === 'admin' && adminCount($db, (int)$target['id']) === 0) {
            flash('err', 'You cannot delete the last administrator.'); redirect(adminUrl(['view' => 'users']));
        }
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM watchlist WHERE user_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM users WHERE id = ? LIMIT 1")->execute([$id]);
            $db->commit();
            logActivity($db, $adminId, 'user_delete', $target['email'], 'role=' . $target['role']);
            flash('ok', 'Deleted ' . $target['email'] . ' and their watchlist.');
        } catch (PDOException $ex) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('admin user_delete failed: ' . $ex->getMessage());
            flash('err', 'The user could not be deleted.');
        }
        redirect(adminUrl(['view' => 'users']));
    }

    flash('err', 'Unknown action.');
    redirect(adminUrl());
}

/* ------------------------------------------------------------------
 * GET views
 * ------------------------------------------------------------------ */
$view = str($_GET['view'] ?? 'dashboard');
$page = max(1, (int)($_GET['page'] ?? 1));

/* ---------- Dashboard ---------- */
if ($view === 'dashboard') {
    $totals = $db->query("SELECT
        (SELECT COUNT(*) FROM media_items) AS items,
        (SELECT COUNT(*) FROM users) AS users,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') AS admins,
        (SELECT COUNT(*) FROM watchlist) AS saved,
        (SELECT COUNT(*) FROM media_items WHERE " . ADMIN_BAD_IMAGE_SQL . ") AS badimg")->fetch();
    $perCat = $db->query("SELECT c.slug, c.name, COUNT(m.id) AS n FROM categories c LEFT JOIN media_items m ON m.category_slug = c.slug GROUP BY c.id, c.slug, c.name ORDER BY c.sort_order ASC, c.id ASC")->fetchAll();
    $recent = $db->query("SELECT a.action, a.target, a.detail, a.ip, a.created_at, u.name FROM admin_activity a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 10")->fetchAll();

    adminHead('Dashboard', 'dashboard', $admin);
    ?>
    <h1>Dashboard</h1>
    <p class="a-lead">Signed in as <?= h($admin['email']) ?>. Use the sections above to manage the catalog, image links and accounts.</p>
    <div class="a-stats">
      <div class="a-stat"><b><?= (int)$totals['items'] ?></b><span>Items</span></div>
      <div class="a-stat"><b><?= (int)$totals['users'] ?></b><span>Users (<?= (int)$totals['admins'] ?> admin)</span></div>
      <div class="a-stat"><b><?= (int)$totals['saved'] ?></b><span>Saved watchlist entries</span></div>
      <div class="a-stat"><b><?= (int)$totals['badimg'] ?></b><span>Items with image link problems</span></div>
    </div>
    <div class="a-actions">
      <a class="a-btn primary" href="<?= h(adminUrl(['view' => 'item_form'])) ?>">Add item</a>
      <a class="a-btn" href="<?= h(adminUrl(['view' => 'user_form'])) ?>">Add user</a>
      <?php if ((int)$totals['badimg'] > 0): ?><a class="a-btn" href="<?= h(adminUrl(['view' => 'images', 'filter' => 'problems'])) ?>">Fix image links</a><?php endif; ?>
    </div>
    <h2>Items by category</h2>
    <div class="a-tablewrap"><table class="a-table">
      <thead><tr><th>Category</th><th>Items</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($perCat as $c): ?>
        <tr><td class="t-title"><?= h($c['name']) ?></td><td><?= (int)$c['n'] ?></td>
        <td><a class="a-btn sm" href="<?= h(adminUrl(['view' => 'items', 'category' => $c['slug']])) ?>">View</a></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <h2>Recent admin activity</h2>
    <div class="a-tablewrap"><table class="a-table">
      <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Detail</th><th>IP</th></tr></thead>
      <tbody>
      <?php if (!$recent): ?><tr><td colspan="6" class="a-muted">Nothing logged yet.</td></tr><?php endif; ?>
      <?php foreach ($recent as $r): ?>
        <tr><td><?= h($r['created_at']) ?></td><td><?= h($r['name'] ?? 'Not signed in') ?></td><td><?= h($r['action']) ?></td>
        <td><?= h($r['target']) ?></td><td><?= h($r['detail']) ?></td><td><?= h($r['ip']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php
    adminFoot();
    exit;
}

/* ---------- Item list ---------- */
if ($view === 'items') {
    $cats = fetchCategories($db);
    $catSlugs = array_column($cats, 'slug');
    $fCat = in_array(str($_GET['category'] ?? ''), $catSlugs, true) ? str($_GET['category']) : '';
    $fQ = line($_GET['q'] ?? '');
    $fFlag = in_array(str($_GET['flag'] ?? ''), ['featured', 'trending'], true) ? str($_GET['flag']) : '';
    $sorts = ['new' => 'id DESC', 'title' => 'title ASC', 'score' => 'score DESC, id DESC', 'year' => 'year DESC, id DESC'];
    $fSort = isset($sorts[str($_GET['sort'] ?? '')]) ? str($_GET['sort']) : 'new';

    $where = []; $params = [];
    if ($fCat !== '') { $where[] = 'category_slug = ?'; $params[] = $fCat; }
    if ($fQ !== '') {
        $like = '%' . addcslashes($fQ, '%_\\') . '%';
        $where[] = '(title LIKE ? OR original_title LIKE ? OR external_id LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($fFlag === 'featured') $where[] = 'is_featured = 1';
    if ($fFlag === 'trending') $where[] = 'is_trending = 1';
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $st = $db->prepare("SELECT COUNT(*) FROM media_items $whereSql");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $offset = ($page - 1) * ADMIN_ITEMS_PER_PAGE;
    $st = $db->prepare("SELECT id, external_id, category_slug, title, year, score, status, poster_url, is_featured, is_trending
                        FROM media_items $whereSql ORDER BY {$sorts[$fSort]} LIMIT " . (int)ADMIN_ITEMS_PER_PAGE . " OFFSET " . (int)$offset);
    $st->execute($params);
    $rows = $st->fetchAll();
    $catNames = array_column($cats, 'name', 'slug');
    $keep = array_filter(['view' => 'items', 'category' => $fCat, 'q' => $fQ, 'flag' => $fFlag, 'sort' => $fSort === 'new' ? '' : $fSort], function ($v) { return $v !== ''; });

    adminHead('Items', 'items', $admin);
    ?>
    <h1>Items</h1>
    <p class="a-lead">Every record shown on the public site. Deleting an item also removes it from user watchlists.</p>
    <form method="get" action="admin.php" class="a-toolbar">
      <input type="hidden" name="view" value="items">
      <div class="f grow"><label class="a-label" for="q">Search</label><input class="a-in" type="text" id="q" name="q" value="<?= h($fQ) ?>" placeholder="Title or item ID" maxlength="100"></div>
      <div class="f"><label class="a-label" for="cat">Category</label>
        <select class="a-in" id="cat" name="category"><option value="">All</option>
        <?php foreach ($cats as $c): ?><option value="<?= h($c['slug']) ?>"<?= $fCat === $c['slug'] ? ' selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label class="a-label" for="flag">Flag</label>
        <select class="a-in" id="flag" name="flag"><option value="">Any</option><option value="featured"<?= $fFlag === 'featured' ? ' selected' : '' ?>>Featured</option><option value="trending"<?= $fFlag === 'trending' ? ' selected' : '' ?>>Trending</option></select></div>
      <div class="f"><label class="a-label" for="sort">Sort</label>
        <select class="a-in" id="sort" name="sort"><option value="new"<?= $fSort === 'new' ? ' selected' : '' ?>>Newest</option><option value="title"<?= $fSort === 'title' ? ' selected' : '' ?>>Title</option><option value="score"<?= $fSort === 'score' ? ' selected' : '' ?>>Score</option><option value="year"<?= $fSort === 'year' ? ' selected' : '' ?>>Year</option></select></div>
      <button type="submit" class="a-btn">Filter</button>
      <a class="a-btn primary" href="<?= h(adminUrl(['view' => 'item_form'] + ($fCat !== '' ? ['category' => $fCat] : []))) ?>">Add item</a>
    </form>
    <?= pager($total, $page, ADMIN_ITEMS_PER_PAGE, $keep) ?>
    <div class="a-tablewrap"><table class="a-table">
      <thead><tr><th>Poster</th><th>Title</th><th>Category</th><th>Year</th><th>Score</th><th>Flags</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7" class="a-muted">No items match.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php if (isHttpUrl($r['poster_url'])): ?><img class="a-thumb" src="<?= h($r['poster_url']) ?>" alt="Poster for <?= h($r['title']) ?>" width="42" height="63" loading="lazy"><?php else: ?><span class="a-thumb-none">NONE</span><?php endif; ?></td>
          <td><span class="t-title"><?= h($r['title']) ?></span><span class="t-sub"><?= h($r['external_id']) ?> / <?= h($r['status']) ?></span></td>
          <td><?= h($catNames[$r['category_slug']] ?? $r['category_slug']) ?></td>
          <td><?= h($r['year']) ?></td>
          <td><?= h($r['score']) ?></td>
          <td><?php if ($r['is_featured']): ?><span class="a-tag hot">Featured</span><?php endif; ?><?php if ($r['is_trending']): ?><span class="a-tag">Trending</span><?php endif; ?></td>
          <td><div class="t-actions">
                        <?php if (supportsPlaylistCategory($r['category_slug'])): ?><a class="a-btn sm" href="<?= h(adminUrl(['view' => 'playlist', 'id' => $r['id']])) ?>">Playlist</a><?php endif; ?>
            <a class="a-btn sm" href="<?= h(adminUrl(['view' => 'item_form', 'id' => $r['id']])) ?>">Edit</a>
            <a class="a-btn sm danger" href="<?= h(adminUrl(['view' => 'item_delete', 'id' => $r['id']])) ?>">Delete</a>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= pager($total, $page, ADMIN_ITEMS_PER_PAGE, $keep) ?>
    <?php
    adminFoot();
    exit;
}

/* ---------- Item add / edit form ---------- */
if ($view === 'item_form') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $item = fetchItem($db, $id);
        if (!$item) { flash('err', 'That item no longer exists.'); redirect(adminUrl(['view' => 'items'])); }
        renderItemForm($db, itemRowToForm($item), [], $id);
    }
    $catSlugs = array_column(fetchCategories($db), 'slug');
    $pre = in_array(str($_GET['category'] ?? ''), $catSlugs, true) ? str($_GET['category']) : '';
    renderItemForm($db, emptyItemForm($pre), [], null);
}

/* ---------- Anime/movie video playlist ---------- */
if ($view === 'playlist') {
        $item = fetchItem($db, (int)($_GET['id'] ?? 0));
        if (!$item || !supportsPlaylistCategory($item['category_slug'])) {
                flash('err', 'Choose an existing item outside Gaming to manage its playlist.');
                redirect(adminUrl(['view' => 'items']));
        }
        $st = $db->prepare('SELECT id, episode_label, mime_type, file_size, sort_order, created_at FROM media_videos WHERE media_item_id = ? ORDER BY sort_order ASC, id ASC');
        $st->execute([$item['id']]);
        $videos = $st->fetchAll();
        $maxUpload = maxAdminVideoBytes();
        adminHead('Playlist', 'items', $admin);
        ?>
        <h1>Video playlist</h1>
        <p class="a-lead"><?= h($item['title']) ?> · <?= h($item['category_slug'] === 'anime' ? 'Anime' : 'Movie') ?>. Upload only videos you own, are licensed to host, or are in the public domain.</p>
        <?php if ($maxUpload <= 0): ?><div class="a-flash err">PHP upload limits are too low. Increase upload_max_filesize and post_max_size in php.ini.</div><?php endif; ?>
        <form method="post" action="admin.php" class="a-panel" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="do" value="video_upload">
            <input type="hidden" name="media_item_id" value="<?= (int)$item['id'] ?>">
            <div class="a-form">
                <div class="a-field">
                    <label for="episode_label">Episode or video label</label>
                    <input type="text" id="episode_label" name="episode_label" maxlength="150" required placeholder="Episode 1 / Episode 1 Part 1 / Full Movie">
                    <div class="a-help">Required. The label is saved as entered; it will not be generated automatically.</div>
                </div>
                <div class="a-field">
                    <label for="video_file">Video file</label>
                    <input type="file" id="video_file" name="video_file" accept=".mp4,.webm,.ogv,video/mp4,video/webm,video/ogg" required>
                    <div class="a-help">MP4, WebM, or OGV · Maximum <?= h(formatAdminBytes($maxUpload)) ?> per file.</div>
                </div>
                <div class="full a-check">
                    <input type="checkbox" id="rights_confirm" name="rights_confirm" value="1" required>
                    <label for="rights_confirm">I own this video or have permission to host and stream it.</label>
                </div>
            </div>
            <div class="a-actions">
                <button type="submit" class="a-btn primary"<?= $maxUpload <= 0 ? ' disabled' : '' ?>>Upload video</button>
                <a class="a-btn" href="<?= h(adminUrl(['view' => 'items'])) ?>">Back to items</a>
            </div>
        </form>
        <h2>Playlist entries</h2>
        <div class="a-tablewrap"><table class="a-table">
            <thead><tr><th>Episode / video</th><th>Format</th><th>Size</th><th>Added</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$videos): ?><tr><td colspan="5" class="a-muted">No uploaded videos for this title.</td></tr><?php endif; ?>
            <?php foreach ($videos as $video): ?>
                <tr>
                    <td class="t-title"><?= h($video['episode_label']) ?></td>
                    <td><?= h(strtoupper(str_replace('video/', '', $video['mime_type']))) ?></td>
                    <td><?= h(formatAdminBytes((int)$video['file_size'])) ?></td>
                    <td><?= h($video['created_at']) ?></td>
                    <td><div class="t-actions">
                        <a class="a-btn sm" href="video.php?id=<?= (int)$video['id'] ?>" target="_blank" rel="noopener">Preview</a>
                        <form method="post" action="admin.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="do" value="video_delete">
                            <input type="hidden" name="media_item_id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="video_id" value="<?= (int)$video['id'] ?>">
                            <button type="submit" class="a-btn sm danger">Remove</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php
        adminFoot();
        exit;
}

/* ---------- Item delete confirmation ---------- */
if ($view === 'item_delete') {
    $item = fetchItem($db, (int)($_GET['id'] ?? 0));
    if (!$item) { flash('err', 'That item no longer exists.'); redirect(adminUrl(['view' => 'items'])); }
    $st = $db->prepare("SELECT COUNT(*) FROM watchlist WHERE media_item_id = ? OR (external_id = ? AND type = ?)");
    $st->execute([$item['id'], $item['external_id'], $item['type']]);
    $saved = (int)$st->fetchColumn();
    adminHead('Delete item', 'items', $admin);
    ?>
    <h1>Delete item</h1>
    <div class="a-panel">
      <p><strong><?= h($item['title']) ?></strong> (<?= h($item['external_id']) ?>) will be deleted permanently.</p>
      <p class="a-muted"><?= $saved ?> saved watchlist <?= $saved === 1 ? 'entry' : 'entries' ?> will be removed with it. This cannot be undone.</p>
      <form method="post" action="admin.php" class="a-actions">
        <?= csrfField() ?>
        <input type="hidden" name="do" value="item_delete">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
        <button type="submit" class="a-btn danger">Delete item</button>
        <a class="a-btn" href="<?= h(adminUrl(['view' => 'items'])) ?>">Cancel</a>
      </form>
    </div>
    <?php
    adminFoot();
    exit;
}

/* ---------- Images ---------- */
if ($view === 'images') {
    $onlyBad = str($_GET['filter'] ?? '') === 'problems';
    $whereSql = $onlyBad ? 'WHERE ' . ADMIN_BAD_IMAGE_SQL : '';
    $total = (int)$db->query("SELECT COUNT(*) FROM media_items $whereSql")->fetchColumn();
    $offset = ($page - 1) * ADMIN_IMAGES_PER_PAGE;
    $rows = $db->query("SELECT id, external_id, category_slug, title, poster_url, backdrop_url FROM media_items $whereSql ORDER BY id DESC LIMIT " . (int)ADMIN_IMAGES_PER_PAGE . " OFFSET " . (int)$offset)->fetchAll();
    $keep = ['view' => 'images'] + ($onlyBad ? ['filter' => 'problems'] : []);

    adminHead('Images', 'images', $admin);
    ?>
    <h1>Images</h1>
    <p class="a-lead">Poster and backdrop links for every item. Links must be full http or https addresses. Thumbnails that fail to load in your browser are flagged.</p>
    <div class="a-actions">
      <a class="a-btn<?= $onlyBad ? '' : ' primary' ?>" href="<?= h(adminUrl(['view' => 'images'])) ?>">All items</a>
      <a class="a-btn<?= $onlyBad ? ' primary' : '' ?>" href="<?= h(adminUrl(['view' => 'images', 'filter' => 'problems'])) ?>">Missing or invalid links</a>
    </div>
    <br>
    <?= pager($total, $page, ADMIN_IMAGES_PER_PAGE, $keep) ?>
    <div class="a-tablewrap"><table class="a-table">
      <thead><tr><th>Item</th><th>Poster</th><th>Backdrop</th><th>Edit links</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="4" class="a-muted"><?= $onlyBad ? 'Every item has valid image links.' : 'No items yet.' ?></td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><span class="t-title"><?= h($r['title']) ?></span><span class="t-sub"><?= h($r['external_id']) ?></span></td>
          <?php foreach (['poster_url' => 'Poster', 'backdrop_url' => 'Backdrop'] as $col => $label): ?>
          <td><div class="a-imgcell">
            <?php if (isHttpUrl($r[$col])): ?>
              <div><img class="a-thumb" src="<?= h($r[$col]) ?>" data-check="1" alt="<?= h($label) ?> for <?= h($r['title']) ?>" width="42" height="63" loading="lazy"><span class="a-tag bad js-broken" hidden>Failed to load</span></div>
            <?php else: ?>
              <div><span class="a-thumb-none">NONE</span><span class="a-tag bad">Missing or invalid</span></div>
            <?php endif; ?>
          </div></td>
          <?php endforeach; ?>
          <td>
            <form method="post" action="admin.php" class="a-inline">
              <?= csrfField() ?>
              <input type="hidden" name="do" value="image_save">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="page" value="<?= (int)$page ?>">
              <?php if ($onlyBad): ?><input type="hidden" name="filter" value="problems"><?php endif; ?>
              <input type="text" name="poster_url" value="<?= h($r['poster_url']) ?>" maxlength="500" placeholder="Poster link https://" aria-label="Poster link for <?= h($r['title']) ?>">
              <input type="text" name="backdrop_url" value="<?= h($r['backdrop_url']) ?>" maxlength="500" placeholder="Backdrop link https://" aria-label="Backdrop link for <?= h($r['title']) ?>">
              <button type="submit" class="a-btn sm primary">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= pager($total, $page, ADMIN_IMAGES_PER_PAGE, $keep) ?>
    <?php
    adminFoot();
    exit;
}

/* ---------- Users list ---------- */
if ($view === 'users') {
    $fQ = line($_GET['q'] ?? '');
    $where = ''; $params = [];
    if ($fQ !== '') {
        $like = '%' . addcslashes($fQ, '%_\\') . '%';
        $where = 'WHERE u.name LIKE ? OR u.email LIKE ?';
        $params = [$like, $like];
    }
    $st = $db->prepare("SELECT COUNT(*) FROM users u $where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $offset = ($page - 1) * ADMIN_USERS_PER_PAGE;
    $st = $db->prepare("SELECT u.id, u.name, u.email, u.role, u.created_at, COALESCE(w.c, 0) AS saved
                        FROM users u LEFT JOIN (SELECT user_id, COUNT(*) AS c FROM watchlist GROUP BY user_id) w ON w.user_id = u.id
                        $where ORDER BY u.id ASC LIMIT " . (int)ADMIN_USERS_PER_PAGE . " OFFSET " . (int)$offset);
    $st->execute($params);
    $rows = $st->fetchAll();
    $keep = array_filter(['view' => 'users', 'q' => $fQ], function ($v) { return $v !== ''; });

    adminHead('Users', 'users', $admin);
    ?>
    <h1>Users</h1>
    <p class="a-lead">Site accounts. Deleting a user also deletes their saved watchlist.</p>
    <form method="get" action="admin.php" class="a-toolbar">
      <input type="hidden" name="view" value="users">
      <div class="f grow"><label class="a-label" for="uq">Search</label><input class="a-in" type="text" id="uq" name="q" value="<?= h($fQ) ?>" placeholder="Name or email" maxlength="100"></div>
      <button type="submit" class="a-btn">Search</button>
      <a class="a-btn primary" href="<?= h(adminUrl(['view' => 'user_form'])) ?>">Add user</a>
    </form>
    <?= pager($total, $page, ADMIN_USERS_PER_PAGE, $keep) ?>
    <div class="a-tablewrap"><table class="a-table">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Saved</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="7" class="a-muted">No users match.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): $self = (int)$r['id'] === (int)$admin['id']; ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td class="t-title"><?= h($r['name']) ?><?= $self ? ' (you)' : '' ?></td>
          <td><?= h($r['email']) ?></td>
          <td><span class="a-tag<?= $r['role'] === 'admin' ? ' hot' : '' ?>"><?= h($r['role']) ?></span></td>
          <td><?= (int)$r['saved'] ?></td>
          <td><?= h($r['created_at']) ?></td>
          <td><div class="t-actions">
            <a class="a-btn sm" href="<?= h(adminUrl(['view' => 'user_form', 'id' => $r['id']])) ?>">Edit</a>
            <?php if (!$self): ?><a class="a-btn sm danger" href="<?= h(adminUrl(['view' => 'user_delete', 'id' => $r['id']])) ?>">Delete</a><?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= pager($total, $page, ADMIN_USERS_PER_PAGE, $keep) ?>
    <?php
    adminFoot();
    exit;
}

/* ---------- User add / edit form ---------- */
if ($view === 'user_form') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $st = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $u = $st->fetch();
        if (!$u) { flash('err', 'That user no longer exists.'); redirect(adminUrl(['view' => 'users'])); }
        renderUserForm(['name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']], [], $id, (int)$u['id'] === (int)$admin['id']);
    }
    renderUserForm(['name' => '', 'email' => '', 'role' => 'user'], [], null, false);
}

/* ---------- User delete confirmation ---------- */
if ($view === 'user_delete') {
    $st = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
    $st->execute([(int)($_GET['id'] ?? 0)]);
    $u = $st->fetch();
    if (!$u) { flash('err', 'That user no longer exists.'); redirect(adminUrl(['view' => 'users'])); }
    if ((int)$u['id'] === (int)$admin['id']) { flash('err', 'You cannot delete your own account.'); redirect(adminUrl(['view' => 'users'])); }
    $st = $db->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
    $st->execute([$u['id']]);
    $saved = (int)$st->fetchColumn();
    adminHead('Delete user', 'users', $admin);
    ?>
    <h1>Delete user</h1>
    <div class="a-panel">
      <p><strong><?= h($u['name']) ?></strong> (<?= h($u['email']) ?>, <?= h($u['role']) ?>) will be deleted permanently.</p>
      <p class="a-muted"><?= $saved ?> saved watchlist <?= $saved === 1 ? 'entry' : 'entries' ?> will be removed with the account. This cannot be undone.</p>
      <form method="post" action="admin.php" class="a-actions">
        <?= csrfField() ?>
        <input type="hidden" name="do" value="user_delete">
        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <button type="submit" class="a-btn danger">Delete user</button>
        <a class="a-btn" href="<?= h(adminUrl(['view' => 'users'])) ?>">Cancel</a>
      </form>
    </div>
    <?php
    adminFoot();
    exit;
}

// Unknown view
redirect(adminUrl());
