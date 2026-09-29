<?php
/**
 * ARCHIVE Ledger - 404 Error Page
 */
http_response_code(404);
require_once __DIR__ . '/config.php';

$pageTitle = '404 Record Not Found - ARCHIVE';
$activeNav = '';
$pageTag   = 'ERROR 404';

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    <div class="container">
      <div class="page-404-wrap">
        <div class="code-404">404</div>
        <h1 class="title-404">RECORD NOT FOUND IN LEDGER</h1>
        <p class="text-404">
          The path you requested does not correspond to an active ledger entry or resource. The title may have been re-indexed, or the URL might contain a typographical mistake.
        </p>

        <!-- Search Form on 404 -->
        <form action="search.php" method="get" class="search-bar-composite" style="max-width: 480px; margin: 0 auto 32px auto;">
          <input 
            type="text" 
            name="q" 
            placeholder="Search across all 8 categories..." 
            aria-label="Search"
            required
          />
          <button type="submit" class="btn btn-primary">
            SEARCH
          </button>
        </form>

        <div style="display: flex; gap: 12px; justify-content: center;">
          <a href="index.php" class="btn btn-primary">RETURN TO INDEX</a>
          <a href="browse.php" class="btn btn-secondary">BROWSE ALL TITLES</a>
        </div>
      </div>
    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
