<?php
/**
 * ARCHIVE Ledger - Catalog Browse
 * Comprehensive filtering across all 8 exact categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Registry Browse - ARCHIVE';
$activeNav = 'browse';
$pageTag   = 'CATALOG';
$pageScript = 'js/browse.js';

// URL Parameters
$currentCategory = strtolower($_GET['category'] ?? $_GET['type'] ?? 'all');
if ($currentCategory === 'movie') $currentCategory = 'movies';
if ($currentCategory === 'tv') $currentCategory = 'tvshows';

$currentGenre = $_GET['genre'] ?? 'all';
$currentSort  = $_GET['sort'] ?? 'score_desc';
$currentPage  = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 100;
$offset       = ($currentPage - 1) * $perPage;

$categories = getAllCategories();

// Server-side query for initial render
$mediaData = getMediaItems($currentCategory, $currentGenre, null, $currentSort, $perPage, $offset);
$items = $mediaData['items'];
$total = $mediaData['total'];
$totalPages = max(1, (int)ceil($total / $perPage));
if ($currentPage > $totalPages) {
  $currentPage = $totalPages;
  $offset = ($currentPage - 1) * $perPage;
  $mediaData = getMediaItems($currentCategory, $currentGenre, null, $currentSort, $perPage, $offset);
  $items = $mediaData['items'];
}

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    <div class="container section">
      
      <!-- Page Header -->
      <div class="section-head" style="margin-bottom: 24px;">
        <div class="section-title-wrap">
          <span class="section-index">CATALOG</span>
          <div>
            <h1 class="section-title">REGISTRY BROWSE</h1>
            <div class="section-sub" id="browseResultCount">
              Showing <?= count($items) ?> of <?= $total ?> records in <?= $currentCategory === 'all' ? 'ALL REGISTRIES' : strtoupper(e($currentCategory)) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Controls Toolbar -->
      <div class="filter-bar">
        <!-- 8 Exact Category Filter Tabs -->
        <div class="filter-group-row" style="flex-wrap: wrap;">
          <span class="form-label" style="margin: 0 4px 0 0; align-self: center;">CATEGORY:</span>
          <button type="button" class="filter-tab-btn <?= $currentCategory === 'all' ? 'active' : '' ?>" data-filter-type="all">ALL</button>
          <?php foreach ($categories as $cat): ?>
            <button 
              type="button" 
              class="filter-tab-btn <?= $currentCategory === $cat['slug'] ? 'active' : '' ?>" 
              data-filter-type="<?= e($cat['slug']) ?>">
              <?= strtoupper(e($cat['name'])) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <!-- Secondary Filters (Genre & Sort) -->
        <div class="filter-group-row">
          <div style="display: flex; align-items: center; gap: 8px;">
            <label for="genreFilter" class="form-label" style="margin: 0;">GENRE:</label>
            <select id="genreFilter" class="form-select filter-select">
              <option value="all" <?= $currentGenre === 'all' ? 'selected' : '' ?>>ALL GENRES</option>
              <option value="Action" <?= $currentGenre === 'Action' ? 'selected' : '' ?>>ACTION</option>
              <option value="Adventure" <?= $currentGenre === 'Adventure' ? 'selected' : '' ?>>ADVENTURE</option>
              <option value="Drama" <?= $currentGenre === 'Drama' ? 'selected' : '' ?>>DRAMA</option>
              <option value="Sci-Fi" <?= $currentGenre === 'Sci-Fi' ? 'selected' : '' ?>>SCIENCE FICTION</option>
              <option value="Fantasy" <?= $currentGenre === 'Fantasy' ? 'selected' : '' ?>>FANTASY</option>
              <option value="Thriller" <?= $currentGenre === 'Thriller' ? 'selected' : '' ?>>THRILLER</option>
              <option value="Suspense" <?= $currentGenre === 'Suspense' ? 'selected' : '' ?>>SUSPENSE</option>
              <option value="Mystery" <?= $currentGenre === 'Mystery' ? 'selected' : '' ?>>MYSTERY</option>
              <option value="Historical" <?= $currentGenre === 'Historical' ? 'selected' : '' ?>>HISTORICAL</option>
              <option value="Cyberpunk" <?= $currentGenre === 'Cyberpunk' ? 'selected' : '' ?>>CYBERPUNK</option>
              <option value="Pop" <?= $currentGenre === 'Pop' ? 'selected' : '' ?>>POP / MUSIC</option>
              <option value="Craft" <?= $currentGenre === 'Craft' ? 'selected' : '' ?>>ATELIER &amp; CRAFT</option>
            </select>
          </div>

          <div style="display: flex; align-items: center; gap: 8px;">
            <label for="sortFilter" class="form-label" style="margin: 0;">SORT:</label>
            <select id="sortFilter" class="form-select filter-select">
              <option value="score_desc" <?= $currentSort === 'score_desc' ? 'selected' : '' ?>>SCORE (HIGH TO LOW)</option>
              <option value="year_desc" <?= $currentSort === 'year_desc' ? 'selected' : '' ?>>RELEASE YEAR (NEWEST)</option>
              <option value="year_asc" <?= $currentSort === 'year_asc' ? 'selected' : '' ?>>RELEASE YEAR (OLDEST)</option>
              <option value="title_asc" <?= $currentSort === 'title_asc' ? 'selected' : '' ?>>TITLE (A TO Z)</option>
            </select>
          </div>

        </div>
      </div>

      <!-- Responsive Catalog Grid -->
      <div id="browseGrid" class="grid-catalog-7">
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $item): ?>
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
        <?php else: ?>
          <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; background: var(--bg-surface); border: 1px solid var(--border-subtle);">
            <h3 style="font-size: 22px; margin-bottom: 8px;">NO TITLES FOUND IN THIS CATEGORY</h3>
            <p style="color: var(--text-dim); font-size: 14px;">Try selecting another category or loosening your genre filter.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Pagination Toolbar -->
      <div class="pagination-wrap">
        <div class="pagination-info" id="paginationInfo">
          PAGE <?= $currentPage ?> OF <?= $totalPages ?>
        </div>
        <div class="pagination-controls">
          <button 
            type="button" 
            id="btnPrevPage" 
            class="btn btn-secondary btn-sm" 
            <?= $currentPage <= 1 ? 'disabled style="opacity: 0.5;"' : '' ?>>
            PREVIOUS PAGE
          </button>
          <div class="pagination-pages" id="paginationPages" aria-label="Catalog pages">
            <?php for ($pageNumber = max(1, $currentPage - 2), $lastPageNumber = min($totalPages, $currentPage + 2); $pageNumber <= $lastPageNumber; $pageNumber++): ?>
              <button
                type="button"
                class="btn btn-secondary btn-sm pagination-page<?= $pageNumber === $currentPage ? ' active' : '' ?>"
                data-page="<?= $pageNumber ?>"
                <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>>
                <?= $pageNumber ?>
              </button>
            <?php endfor; ?>
          </div>
          <button 
            type="button" 
            id="btnNextPage" 
            class="btn btn-secondary btn-sm"
            <?= $currentPage >= $totalPages ? 'disabled style="opacity: 0.5;"' : '' ?>>
            NEXT PAGE
          </button>
        </div>
      </div>

    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
