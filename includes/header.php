<?php
/**
 * Shared Site Header Template
 * Warm charcoal dark-theme, sharp typography, responsive navigation ledger.
 */
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config.php';
}

$currentUser = getCurrentUser();
if (!$currentUser && !in_array(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')), ['auth.php', 'terms.php', 'privacy.php'], true)) {
  header('Location: auth.php?mode=signup');
  exit;
}
$watchlistCount = $currentUser ? getUserWatchlistCount($currentUser['id']) : 0;

// Normalize legacy per-page titles and append the current public site name.
if (isset($pageTitle)) {
    $baseTitle = preg_replace('/^ARCHIVE\s*[-|:]\s*/i', '', $pageTitle);
    $baseTitle = preg_replace('/\s*[-|:]\s*ARCHIVE$/i', '', $baseTitle);
    $fullTitle = trim($baseTitle) . ' - ' . SITE_NAME;
} else {
    $fullTitle = SITE_NAME . ' - Anime & Cinema Ledger';
}
$activeNav = $activeNav ?? 'home';
$pageTag = $pageTag ?? 'INDEX 01';

$siteCategories = getAllCategories();
$showCategoryStrip = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($fullTitle) ?></title>
  <link rel="stylesheet" href="css/main.css?v=<?= filemtime(__DIR__ . '/../css/main.css') ?>">
  <link rel="stylesheet" href="css/components.css?v=<?= filemtime(__DIR__ . '/../css/components.css') ?>">
  <link rel="stylesheet" href="css/pages.css?v=<?= filemtime(__DIR__ . '/../css/pages.css') ?>">
  <link rel="stylesheet" href="css/chatbot.css?v=<?= filemtime(__DIR__ . '/../css/chatbot.css') ?>">
</head>
<body>

  <!-- Site Header -->
  <header class="site-header">
    <div class="container header-inner">
      <div class="brand-wrapper">
        <a href="index.php" class="brand-logo">
          <span class="symbol">■</span>
          <span class="logotype"><?= e(SITE_NAME) ?></span>
        </a>
        <span class="brand-tag"><?= e($pageTag) ?></span>
      </div>

      <nav class="primary-nav" id="primaryNav">
        <a href="index.php" class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">HOME</a>
        <a href="browse.php" class="nav-link <?= $activeNav === 'browse' ? 'active' : '' ?>">BROWSE</a>
        <a href="watchlist.php" class="nav-link <?= $activeNav === 'watchlist' ? 'active' : '' ?>">WATCHLIST</a>
        <a href="about.php" class="nav-link <?= $activeNav === 'about' ? 'active' : '' ?>">ABOUT</a>
        <a href="events.php" class="nav-link <?= $activeNav === 'events' ? 'active' : '' ?>">EVENTS &amp; MAP</a>
      </nav>

      <div class="header-tools">
        <a href="watchlist.php" class="btn-header-action" title="View Watchlist">
          <span>SAVED</span>
          <span class="watchlist-badge-count"><?= (int)$watchlistCount ?></span>
        </a>
        <?php if ($currentUser): ?>
          <a href="auth.php" id="navAuthButton" class="btn-header-action accent" title="Logged in as <?= e($currentUser['email']) ?>">
            <?= strtoupper(e(mb_strimwidth($currentUser['name'], 0, 14, '..'))) ?>
          </a>
        <?php else: ?>
          <a href="auth.php" id="navAuthButton" class="btn-header-action accent">
            SIGN IN
          </a>
        <?php endif; ?>
        <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu">
          <svg class="icon" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
      </div>
    </div>
  </header>

  <?php if ($showCategoryStrip): ?>
    <div class="site-category-strip">
      <div class="container">
        <div class="site-category-row">
          <span class="site-category-label">INDEXED CATEGORIES (<?= count($siteCategories) ?>)</span>
          <?php foreach ($siteCategories as $cat): ?>
            <a href="browse.php?category=<?= e($cat['slug']) ?>" class="site-category-pill">
              <?= e($cat['short_label'] ?? $cat['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
