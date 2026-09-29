<?php
/**
 * ARCHIVE Ledger - About Page
 * Information on project architecture, provenance, data sources, and specifications.
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'About the Ledger - ARCHIVE';
$activeNav = 'about';
$pageTag   = 'ABOUT';

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    
    <header class="prose-header">
      <div class="container">
        <div class="section-index" style="margin-bottom: 8px;">PROJECT ARCHIVE &amp; SPECIFICATION</div>
        <h1 class="hero-headline" style="margin-bottom: 0;">ABOUT THE LEDGER</h1>
      </div>
    </header>

    <div class="prose-container">
      
      <section class="prose-section">
        <h2>PURPOSE &amp; PHILOSOPHY</h2>
        <p>
          Archive is a reference catalog created for people who appreciate Japanese animation, interactive gaming, feature cinema, serialized television, K-Pop concert films, graphic novels, manga, and software without algorithmic traps, engagement ads, or infinite feeds. We prioritize clean typography, precise production credits, verified trailers, and resilient data persistence.
        </p>
        <p>
          Every record in this catalog is curated across eight explicit cultural domains. You can search across all categories, review official trailers, inspect original credits, and maintain a persistent personal watchlist saved directly in MySQL and synchronized with your browser.
        </p>
      </section>

      <section class="prose-section">
        <h2>THE EIGHT INDEXED CATEGORIES</h2>
        <ul>
          <li><strong>Anime:</strong> Japanese animated series, broadcasts, and OVA releases with studio credits and score metrics.</li>
          <li><strong>Gaming:</strong> Major RPGs, open-world adventures, auteur directors, and multi-platform interactive releases.</li>
          <li><strong>Movies:</strong> Contemporary theatrical cinema, auteur filmography, and historical cinematic landmarks.</li>
          <li><strong>TV Shows:</strong> Prestige serialized drama, streaming television, and limited-series narratives.</li>
          <li><strong>K-Pop:</strong> Concert feature films, world tour documentaries, and stadium visual showcases.</li>
          <li><strong>Comics:</strong> Groundbreaking Western graphic novels, creator-owned series, and vigilante noir classics.</li>
          <li><strong>Manga:</strong> Masterwork Japanese serializations, seinen epics, and tankōbon literature.</li>
          <li><strong>Software:</strong> Applications, developer tools, platform support, versions, licenses, and system requirements.</li>
        </ul>
      </section>

      <section class="prose-section">
        <h2>DATA ARCHITECTURE &amp; PROVENANCE</h2>
        <p>
          The application combines a high-speed relational MySQL backend with direct integrations to public APIs:
        </p>
        
        <div class="api-provenance-box">
          <h4>1. MYSQL RELATIONAL DATABASE LEDGER</h4>
          <p style="font-size: 14px; color: var(--text-dim); margin-bottom: 8px;">
            The primary persistence layer stores all categories, seed records, authenticated archivist profiles, and personal watchlists in InnoDB tables with foreign key integrity.
          </p>
          <div style="font-size: 12px; color: var(--accent);">Port: 3306 | Engine: MySQL 8 / MariaDB (InnoDB)</div>
        </div>

        <div class="api-provenance-box">
          <h4>2. JIKAN REST API (ANIME METRICS)</h4>
          <p style="font-size: 14px; color: var(--text-dim); margin-bottom: 8px;">
            Jikan is an open-source RESTful API parsing MyAnimeList public records. It powers live animation queries, seasonal broadcasts, episode counts, character listings, and Japanese studio metadata.
          </p>
          <div style="font-size: 12px; color: var(--text-faint);">Endpoint: api.jikan.moe/v4</div>
        </div>

        <div class="api-provenance-box">
          <h4>3. THE MOVIE DATABASE (CINEMA REGISTRY)</h4>
          <p style="font-size: 14px; color: var(--text-dim); margin-bottom: 8px;">
            The Movie Database (TMDB) provides global feature film records, box office releases, director credits, and high-definition posters. Users can supply their own free v3 API key to query live cinema records, or explore our built-in curated classic cinema registry.
          </p>
          <div style="font-size: 12px; color: var(--text-faint);">Endpoint: api.themoviedb.org/3</div>
        </div>
      </section>

      <section class="prose-section">
        <h2>TECHNICAL SPECIFICATIONS</h2>
        <p>
          The architecture follows strict design guidelines focused on legibility and endurance:
        </p>
        <ul>
          <li><strong>Typography:</strong> Barlow Condensed for structural display headings, paired with Source Sans 3 for editorial body text.</li>
          <li><strong>Palette:</strong> Warm darkish charcoal surfaces (#21201e, #2a2826) with off-white text (#f2eee6) and a single muted brick red accent (#b43d31).</li>
          <li><strong>Geometry:</strong> Strict 90-degree sharp corners throughout. Zero rounded borders.</li>
          <li><strong>Backend:</strong> Clean PHP 8 procedural &amp; PDO architecture with prepared SQL statements.</li>
          <li><strong>Resilience:</strong> Automatic inline SVG placeholder generators prevent broken or glitchy poster images on any device or network state.</li>
        </ul>
      </section>

    </div>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
