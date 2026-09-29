<?php
/**
 * ARCHIVE Ledger - Terms of Service
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Terms of Service - ARCHIVE';
$activeNav = '';
$pageTag   = 'LEGAL';

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    
    <header class="prose-header">
      <div class="container">
        <div class="section-index" style="margin-bottom: 8px;">TERMS OF SERVICE</div>
        <h1 class="hero-headline" style="margin-bottom: 0;">TERMS OF USE</h1>
      </div>
    </header>

    <div class="prose-container">
      
      <section class="prose-section">
        <h2>1. ACCEPTANCE OF TERMS</h2>
        <p>
          By accessing or using Archive, you agree to these Terms of Service. If you do not agree with any part of these terms, please discontinue using the service. This site provides an informational catalog and ledger for anime, gaming, cinema, TV shows, K-pop, comics, manga, and software.
        </p>
      </section>

      <section class="prose-section">
        <h2>2. METADATA &amp; THIRD-PARTY INTELLECTUAL PROPERTY</h2>
        <p>
          All movie posters, trailer videos, character names, and synopses displayed in this catalog remain the intellectual property of their respective studios, authors, publishers, distributors, and copyright owners.
        </p>
        <p>
          Archive does not host media video files directly. All trailers are served through official embedded YouTube streams via public API endpoints provided by Jikan, The Movie Database, and public archival registries.
        </p>
      </section>

      <section class="prose-section">
        <h2>3. PERSONAL USE &amp; API FAIR USE</h2>
        <p>
          This service is provided free of charge for personal, non-commercial reference. When making queries through the interface, you agree not to automate abusive scraping that degrades upstream services. If you configure your own TMDB API key, you are responsible for maintaining compliance with TMDB terms of use.
        </p>
      </section>

      <section class="prose-section">
        <h2>4. DATA RESIDENCY &amp; PRIVACY</h2>
        <p>
          Your personal watchlist items are stored in your MySQL account ledger or browser localStorage. Archive administrators do not store, view, or sell your viewing preferences. You may clear your watchlist at any time via the user interface.
        </p>
      </section>

      <section class="prose-section">
        <h2>5. DISCLAIMER OF WARRANTIES</h2>
        <p>
          The service is provided on an "as is" and "as available" basis without warranties of any kind. We do not guarantee continuous, uninterrupted access to third-party API services, nor do we guarantee the absolute accuracy of crowd-sourced database entries.
        </p>
      </section>

      <section class="prose-section">
        <h2>6. CONTACT &amp; INQUIRIES</h2>
        <p>
          If you have questions regarding metadata attribution or rights inquiries, please consult the respective upstream registries or reach out through our project repository.
        </p>
      </section>

    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
