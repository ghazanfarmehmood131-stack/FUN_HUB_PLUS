<?php
/**
 * ARCHIVE Ledger - Privacy Policy
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Privacy Policy - ARCHIVE';
$activeNav = '';
$pageTag   = 'PRIVACY';

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    
    <header class="prose-header">
      <div class="container">
        <div class="section-index" style="margin-bottom: 8px;">PRIVACY POLICY</div>
        <h1 class="hero-headline" style="margin-bottom: 0;">DATA HANDLING &amp; PRIVACY</h1>
      </div>
    </header>

    <div class="prose-container">
      
      <section class="prose-section">
        <h2>OUR CORE PRIVACY PRINCIPLE</h2>
        <p>
          Archive is built to respect your autonomy. We do not run third-party advertising scripts, behavioral analytics, cross-site trackers, or marketing pixels. When you use this website, you are interacting directly with the local database ledger and official public entertainment registries.
        </p>
      </section>

      <section class="prose-section">
        <h2>DATA STORAGE &amp; RESIDENCY</h2>
        <p>
          Personal configuration and account data is handled transparently:
        </p>
        <ul>
          <li><strong>Personal Watchlist:</strong> When logged in, your watchlist is securely stored in your personal MySQL account ledger. Guest watchlists are cached locally in your browser storage.</li>
          <li><strong>TMDB API Key:</strong> If you input a personal TMDB API Key through the settings dialog, it is stored in your browser so that your queries can authenticate directly against TMDB.</li>
          <li><strong>User Profile:</strong> Accounts registered through the access portal are securely hashed with bcrypt (PHP password_hash) and preserved in the MySQL database.</li>
        </ul>
      </section>

      <section class="prose-section">
        <h2>UPSTREAM NETWORK REQUESTS</h2>
        <p>
          When you search or view titles, your browser initiates direct network requests to the following upstream endpoints:
        </p>
        <ul>
          <li><strong>Jikan API (api.jikan.moe):</strong> Used to fetch public anime data and character images.</li>
          <li><strong>The Movie Database (api.themoviedb.org):</strong> Used to query cinema records and fetch poster images hosted on <code>image.tmdb.org</code>.</li>
          <li><strong>YouTube (youtube.com):</strong> When playing an archival trailer, the embedded player communicates with YouTube under Google privacy policies.</li>
        </ul>
      </section>

      <section class="prose-section">
        <h2>CLEARING YOUR DATA</h2>
        <p>
          You retain complete ownership over your data. To erase all stored information, you can click "Clear All Titles" on your Watchlist page, clear your API key from the header modal, or remove your account.
        </p>
      </section>

      <section class="prose-section">
        <h2>UPDATES TO THIS POLICY</h2>
        <p>
          Any revisions to this privacy statement will be published directly to this page with an updated revision date.
        </p>
        <p style="font-size: 13px; color: var(--text-faint);">
          Effective Date: September 2026.
        </p>
      </section>

    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
