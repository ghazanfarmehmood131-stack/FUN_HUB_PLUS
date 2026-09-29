<?php
/**
 * ARCHIVE Ledger - Search Interface
 * Live search and registry query across all 8 exact categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Search Registries - ARCHIVE';
$activeNav = 'search';
$pageTag   = 'SEARCH';
$pageScript = 'js/search-page.js';

$query = trim($_GET['q'] ?? '');
$prefixOnly = ($_GET['prefix'] ?? '') === '1';
$currentCategory = strtolower($_GET['category'] ?? 'all');
$categories = getAllCategories();

$searchResults = [];
if (!empty($query)) {
  if ($prefixOnly) {
    $db = getDb();
    if ($db) {
      $sql = 'SELECT * FROM media_items WHERE title LIKE ?';
      $params = [$query . '%'];
      if ($currentCategory !== 'all') {
        $sql .= ' AND category_slug = ?';
        $params[] = $currentCategory;
      }
      $sql .= ' ORDER BY score DESC, title ASC LIMIT 24';
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $searchResults = $stmt->fetchAll();
    }
  } else {
    $searchData = getMediaItems($currentCategory, 'all', $query, 'score_desc', 24, 0);
    $searchResults = $searchData['items'];
  }
}

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Search Interface -->
  <main class="site-main">
    
    <section class="search-header-hero">
      <div class="container">
        <div class="section-index" style="margin-bottom: 8px;">ARCHIVAL QUERY</div>
        <h1 class="search-title">ADVANCED SEARCH ENGINE</h1>
        
        <form id="searchPageForm" class="search-bar-composite" autocomplete="off" action="search.php" method="GET">
          <input 
            type="text" 
            id="searchPageInput" 
            name="q"
            value="<?= e($query) ?>"
            placeholder="Search across all 8 categories (titles, creators, studios)..."
            aria-label="Search registry"
            autofocus
          />
          <?php if ($prefixOnly): ?><input type="hidden" name="prefix" value="1" /><?php endif; ?>
          <button type="submit" class="btn btn-primary btn-lg">
            SEARCH REGISTRY
          </button>
        </form>

        <!-- 8 Exact Categories Filter Tabs -->
        <div class="filter-group-row" style="margin-top: 20px; flex-wrap: wrap;">
          <span class="form-label" style="margin: 0; align-self: center;">FILTER TYPE:</span>
          <button type="button" class="filter-tab-btn <?= $currentCategory === 'all' ? 'active' : '' ?>" data-search-type="all">ALL</button>
          <?php foreach ($categories as $cat): ?>
            <button 
              type="button" 
              class="filter-tab-btn <?= $currentCategory === $cat['slug'] ? 'active' : '' ?>" 
              data-search-type="<?= e($cat['slug']) ?>">
              <?= strtoupper(e($cat['name'])) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <div class="container section" style="padding-top: 24px;">
      <div class="search-summary-bar">
        <div id="searchSummary">
          <?php if (!empty($query)): ?>
            Found <?= count($searchResults) ?> records <?= $prefixOnly ? 'starting with' : 'matching' ?> "<?= e(strtoupper($query)) ?>" in <?= $currentCategory === 'all' ? 'ALL CATEGORIES' : strtoupper(e($currentCategory)) ?>
          <?php else: ?>
            Enter query above to search archive
          <?php endif; ?>
        </div>
      </div>

      <!-- Results Grid -->
      <div id="searchResultsContainer" class="grid-catalog-4">
        <?php if (!empty($searchResults)): ?>
          <?php foreach ($searchResults as $item): ?>
            <?php 
              $catUpper = strtoupper($item['category_slug']);
              $poster = safePosterUrl($item['poster_url'], $item['title'], $catUpper);
              $fallback = generateSvgPoster($item['title'], $catUpper);
              $genres = array_slice(array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))), 0, 3);
            ?>
            <article class="media-card" data-id="<?= e($item['external_id']) ?>" data-type="<?= e($item['type']) ?>" data-category="<?= e($item['category_slug']) ?>">
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
                  <span class="badge badge-score">SCORE <?= e($item['score']) ?></span>
                </div>
              </div>
              <div class="card-content">
                <div class="card-meta-line">
                  <span><?= e($item['year']) ?></span>
                  <span>-</span>
                  <span><?= e($item['status']) ?></span>
                </div>
                <h4 class="card-title">
                  <a href="title.php?type=<?= urlencode($item['type']) ?>&id=<?= urlencode($item['external_id']) ?>">
                    <?= e($item['title']) ?>
                  </a>
                </h4>
                <div class="card-genres"><?= e(implode(', ', $genres) ?: 'General') ?></div>
              </div>
              <div class="card-actions">
                <a href="title.php?type=<?= urlencode($item['type']) ?>&id=<?= urlencode($item['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                <button 
                  type="button" 
                  class="btn btn-sm btn-watchlist" 
                  data-action="watchlist-toggle" 
                  data-id="<?= e($item['external_id']) ?>" 
                  data-type="<?= e($item['type']) ?>"
                  data-category="<?= e($item['category_slug']) ?>"
                  title="Add to watchlist">
                  <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        <?php elseif (!empty($query)): ?>
          <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; border: 1px solid var(--border-subtle); background: var(--bg-surface);">
            <h3 style="font-size: 24px; margin-bottom: 8px;"><?= $prefixOnly ? 'NOT FOUND' : 'NO TITLES FOUND' ?></h3>
            <p style="color: var(--text-dim); font-size: 15px; max-width: 500px; margin: 0 auto 20px auto;">
              <?php if ($prefixOnly): ?>No title starts with "<?= e($query) ?>" in <?= strtoupper(e($currentCategory)) ?>.<?php else: ?>No archival matches found for "<?= e($query) ?>" in <?= strtoupper(e($currentCategory)) ?>. Try another category or loosen keyword.<?php endif; ?>
            </p>
            <a href="browse.php" class="btn btn-secondary">OPEN BROWSE CATALOG</a>
          </div>
        <?php else: ?>
          <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; border: 1px solid var(--border-subtle); background: var(--bg-surface);">
            <h3 style="font-size: 22px; margin-bottom: 8px;">TYPE TO QUERY THE ARCHIVE</h3>
            <p style="color: var(--text-dim); font-size: 14px;">Enter an anime series, game title, cinema film, tv show, or creator keyword above.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
