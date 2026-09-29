<?php
/**
 * Shared Site Footer Template
 * Contains editorial catalog index for all 8 categories, legal ledger links, TMDB modal, and JS scripts.
 */
?>
  <!-- Site Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col-brand">
          <h3 class="footer-heading"><?= e(SITE_NAME) ?></h3>
          <p>
            An independent, structured pop culture and cinema ledger documenting animation, gaming, film, television, graphic novels, manga, and artisanal costume history.
          </p>
          <div class="footer-meta-block">
            <span>VERSION 2.0.0</span><br>
            <span>PHP 8 + MYSQL BACKEND</span>
          </div>
        </div>

        <div>
          <h4 class="footer-col-title">CATEGORIES</h4>
          <ul class="footer-links">
            <li><a href="browse.php?category=anime">Anime Archive</a></li>
            <li><a href="browse.php?category=gaming">Gaming Ledger</a></li>
            <li><a href="browse.php?category=movies">Cinema Registry</a></li>
            <li><a href="browse.php?category=tvshows">TV Shows</a></li>
            <li><a href="browse.php?category=kpop">K-Pop Archive</a></li>
            <li><a href="browse.php?category=comics">Comics &amp; Graphic</a></li>
            <li><a href="browse.php?category=manga">Manga Registry</a></li>
            <li><a href="browse.php?category=software">Software Library</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-col-title">REGISTRY &amp; DISCOVERY</h4>
          <ul class="footer-links">
            <li><a href="events.php">Events &amp; Interactive Map</a></li>
            <li><a href="#" onclick="if(window.ARCHIVE_BOT) window.ARCHIVE_BOT.startTour(); return false;">Website Chalana Sikhein (Tour)</a></li>
            <li><a href="browse.php?sort=score_desc">Top Rated Index</a></li>
            <li><a href="watchlist.php">Personal Watchlist</a></li>
            <li><a href="about.php">About the Ledger</a></li>
            <li><a href="terms.php">Terms of Service</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
            <li><a href="#" id="footerTmdbTrigger" onclick="var m=document.getElementById('tmdbModal'); if(m) m.classList.add('active'); return false;">API Credentials</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-col-title">SYSTEM STATUS</h4>
          <p style="font-size: 13px; color: var(--text-dim); line-height: 1.6; margin-bottom: 12px;">
            Dual backend architecture: Local MySQL database ledger connected with live Jikan REST v4 and TMDB v3 API feeds. Includes an AI Guide and Leaflet Interactive Radar.
          </p>
          <div style="font-family: var(--font-display); font-size: 12px; color: var(--accent); letter-spacing: 0.08em; font-weight: bold;">
            STRICT ZERO-TRACKING LEDGER
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div>
          Copyright 2026 <?= e(SITE_NAME) ?>. All rights reserved.
        </div>
        <div class="footer-source-note">
          DATA SOURCED FROM MYSQL LEDGER, JIKAN &amp; THE MOVIE DATABASE
        </div>
      </div>
    </div>
  </footer>

  <!-- TMDB API Key Modal Dialog -->
  <div id="tmdbModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTmdbTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="modalTmdbTitle" class="modal-title">TMDB API CREDENTIALS</h3>
        <button type="button" id="closeTmdbModal" class="modal-close-btn">ESC / CLOSE</button>
      </div>
      <p style="font-size: 14px; color: var(--text-dim); margin-bottom: 16px; line-height: 1.5;">
        Enter your personal TMDB v3 API Key to query live movie data directly from The Movie Database. If omitted, the archive automatically utilizes the local MySQL ledger and curated catalog.
      </p>
      <div class="form-group">
        <label for="tmdbKeyInput" class="form-label">TMDB API KEY (V3 AUTH)</label>
        <input 
          type="text" 
          id="tmdbKeyInput" 
          class="form-input" 
          placeholder="e.g. 3a7b9c1d8e2f..." 
          autocomplete="off"
        />
      </div>
      <div style="font-size: 12px; color: var(--text-faint); margin-bottom: 20px;">
        You can register a free API key at <a href="https://www.themoviedb.org/settings/api" target="_blank" rel="noopener" style="text-decoration: underline; color: var(--text-dim);">themoviedb.org</a>. The key is saved locally in your browser storage or can be configured in <code>js/config.js</code>.
      </div>
      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" id="clearTmdbKeyBtn" class="btn btn-secondary">CLEAR KEY</button>
        <button type="button" id="saveTmdbKeyBtn" class="btn btn-primary">SAVE &amp; RELOAD</button>
      </div>
    </div>
  </div>

  <!-- Shared Core Scripts -->
  <script src="js/config.js"></script>
  <script src="js/fallback-data.js"></script>
  <script src="js/api.js"></script>
  <script src="js/watchlist.js"></script>
  <script src="js/auth.js"></script>
  <script src="js/ui.js"></script>
  <script src="js/chatbot.js?v=<?= filemtime(__DIR__ . '/../js/chatbot.js') ?>"></script>

  <!-- Page-Specific Script -->
  <?php if (!empty($pageScript)): ?>
    <script src="<?= e($pageScript) ?>?v=<?= filemtime(__DIR__ . '/../' . $pageScript) ?>"></script>
  <?php endif; ?>

  <!-- Mobile Menu & Global Modal Handlers -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const mobToggle = document.getElementById('mobileMenuToggle');
      const primaryNav = document.getElementById('primaryNav');
      if (mobToggle && primaryNav) {
        mobToggle.addEventListener('click', function() {
          primaryNav.classList.toggle('open');
          mobToggle.setAttribute('aria-expanded', primaryNav.classList.contains('open') ? 'true' : 'false');
        });
      }

      // Slash shortcut focuses the single homepage search field; other pages open the search page.
      document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
          const searchInput = document.getElementById('heroSearchInput');
          if (searchInput) {
            e.preventDefault();
            searchInput.focus();
          } else {
            window.location.href = 'search.php';
          }
        }
      });

      if (primaryNav) {
        primaryNav.querySelectorAll('a').forEach(function(link) {
          link.addEventListener('click', function() {
            primaryNav.classList.remove('open');
            if (mobToggle) mobToggle.setAttribute('aria-expanded', 'false');
          });
        });
      }
    });
  </script>
</body>
</html>
