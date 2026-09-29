<?php
/** Create the first Fan Hub Plus administrator from a trusted CLI terminal. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}
require_once dirname(__DIR__) . '/config.php';

$db = getDb();
if (!$db) {
    fwrite(STDERR, "Database connection failed. Check config.local.php / FAN_HUB_DB_* settings.\n");
    exit(1);
}

function promptLine(string $label): string {
    fwrite(STDOUT, $label);
    return trim((string)fgets(STDIN));
}
function promptSecret(string $label): string {
    fwrite(STDOUT, $label);
    $ttyState = function_exists('shell_exec') ? @shell_exec('stty -g 2>/dev/null') : null;
    $isTty = is_string($ttyState) && trim($ttyState) !== '';
    if ($isTty) @shell_exec('stty -echo 2>/dev/null');
    $value = trim((string)fgets(STDIN));
    if ($isTty) {
        @shell_exec('stty ' . escapeshellarg(trim($ttyState)) . ' 2>/dev/null');
        fwrite(STDOUT, "\n");
    }
    return $value;
}

$name = promptLine('Admin name: ');
$email = strtolower(promptLine('Admin email: '));
$password = promptSecret('Admin password (min 12 characters): ');
if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Invalid input. Use a 2+ character name, valid email, and password of at least 12 characters.\n");
    exit(1);
}

$check = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) {
    fwrite(STDERR, "That email already has an account. No changes were made.\n");
    exit(1);
}
$insert = $db->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')");
$insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Administrator account created. Sign in at /admin.php and store the password securely.\n");
