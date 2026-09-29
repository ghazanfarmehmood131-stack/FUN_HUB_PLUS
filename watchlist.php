<?php
/**
 * ARCHIVE Ledger - Personal Watchlist
 * Displays persistent saved ledger records with 8 category filters.
 * Backed by MySQL user storage and local client caching.
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Personal Watchlist - ARCHIVE';
$activeNav = 'watchlist';
$pageTag   = 'SAVED';
$pageScript = 'js/watchlist-page.js';

$currentUser = getCurrentUser();
$savedItems = [];
$totalSaved = 0;
if ($currentUser) {
    $savedItems = getUserWatchlist($currentUser['id']);
    $totalSaved = count($savedItems);
}

$categories = getAllCategories();

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Watchlist View -->
  <main class="site-main">
    
    <section class="watchlist-header">
      <div class="container">
        <div class="section-head" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
          <div class="section-title-wrap">
            <span class="section-index">PERSONAL</span>
            <div>
              <h1 class="section-title">YOUR WATCHLIST</h1>
              <div class="watchlist-metric-bar">
                <span>TOTAL SAVED: <strong id="countAll"><?= $totalSaved ?></strong></span>
              </div>
            </div>
          </div>
          <button type="button" id="clearWatchlistBtn" class="btn btn-secondary btn-sm">
            CLEAR ALL TITLES
          </button>
        </div>

        <!-- 8 Exact Categories Filter Tabs -->
        <div class="filter-group-row" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-medium); flex-wrap: wrap;">
          <span class="form-label" style="margin: 0; align-self: center;">FILTER LIST:</span>
          <button type="button" class="filter-tab-btn active" data-watchlist-filter="all">ALL SAVED</button>
          <?php foreach ($categories as $cat): ?>
            <button 
              type="button" 
              class="filter-tab-btn" 
              data-watchlist-filter="<?= e($cat['slug']) ?>">
              <?= strtoupper(e($cat['name'])) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <div class="container section">
      <div id="watchlistContainer">
        <?php if (!empty($savedItems)): ?>
          <div class="grid-catalog-4">
            <?php foreach ($savedItems as $item): ?>
              <?php 
                $catUpper = strtoupper($item['category'] ?? 'ANIME');
                $poster = safePosterUrl($item['poster_url'], $item['title'], $catUpper);
                $fallback = generateSvgPoster($item['title'], $catUpper);
              ?>
              <article class="media-card" data-id="<?= e($item['external_id']) ?>" data-type="<?= e($item['type']) ?>" data-category="<?= e($item['category']) ?>">
                <div class="media-poster-wrap">
                  <img 
                    src="<?= e($poster) ?>" 
                    alt="<?= e($item['title']) ?>" 
                    class="media-poster" 
                    loading="lazy" 
                    onerror="this.onerror=null; this.src='<?= $fallback ?>';"
                  />
                  <div class="card-top-badges">
                    <span class="badge badge-type"><?= e($catUpper) ?></span>
                    <span class="badge badge-score">SCORE <?= e($item['score'] ?: '—') ?></span>
                  </div>
                </div>
                <div class="card-content">
                  <div class="card-meta-line">
                    <span><?= e($item['year'] ?: '—') ?></span>
                    <span>-</span>
                    <span>Archived</span>
                  </div>
                  <h4 class="card-title">
                    <a href="title.php?type=<?= urlencode($item['type']) ?>&id=<?= urlencode($item['external_id']) ?>">
                      <?= e($item['title']) ?>
                    </a>
                  </h4>
                </div>
                <div class="card-actions">
                  <a href="title.php?type=<?= urlencode($item['type']) ?>&id=<?= urlencode($item['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-watchlist in-watchlist" 
                    data-action="watchlist-toggle" 
                    data-id="<?= e($item['external_id']) ?>" 
                    data-type="<?= e($item['type']) ?>"
                    data-category="<?= e($item['category']) ?>"
                    title="Remove from watchlist">
                    <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                  </button>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="watchlist-empty-state">
            <h3>NO TITLES FOUND IN THIS SELECTION</h3>
            <p>
              You have not pinned any titles in this category yet. Explore the ledger and click the bookmark button on any title card to save it.
            </p>
            <a href="browse.php" class="btn btn-primary">BROWSE CATALOG</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
