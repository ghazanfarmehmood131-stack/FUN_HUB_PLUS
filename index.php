<?php
/**
 * Fan Hub Plus - Home Page
 * Server-rendered pop culture ledger with real-time MySQL dataset for all 8 categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Pop Culture, Anime & Cinema Ledger';
$activeNav = 'home';
$pageTag   = 'LEDGER 01';
$pageScript = 'js/home.js';

$categories = getAllCategories();
$categoryLabels = array_column($categories, 'name', 'slug');
$featuredItems = getFeaturedHeroItems(1);
$spotlight = !empty($featuredItems) ? $featuredItems[0] : null;

// Get shelf previews for the homepage
$animeItems = getItemsByCategory('anime', 7);
$gamingItems = getItemsByCategory('gaming', 7);
$movieItems = getItemsByCategory('movies', 7);
$tvItems = getItemsByCategory('tvshows', 7);
$kpopItems = getItemsByCategory('kpop', 7);
$comicItems = getItemsByCategory('comics', 7);
$mangaItems = getItemsByCategory('manga', 7);
$softwareItems = getItemsByCategory('software', 7);

$homeEvents = getFeaturedEvents(3);

include __DIR__ . '/includes/header.php';
?>

  <!-- Main Content -->
  <main class="site-main">
    
    <!-- Hero Section (Asymmetric Editorial Split) -->
    <section class="home-hero">
      <div class="container">
        <div class="home-hero-grid">
          
          <!-- Left Column: Editorial Headline & Live Search Demo -->
          <div class="hero-editorial">
            <div>
              <div class="hero-kicker">ARCHIVAL CINEMA, ANIME &amp; CULTURE REGISTRY</div>
              <h1 class="hero-headline">
                CULTURE &amp; CINEMA <span>LEDGER</span>
              </h1>
              <p class="hero-intro">
                A structured, high-contrast reference catalog for Japanese animation, interactive gaming, feature cinema, serialized television, K-Pop concert features, graphic novels, manga, and software.
              </p>
            </div>

            <!-- Live Search Demo Widget -->
            <div class="hero-search-box">
              <div class="hero-search-label">
                <span>LIVE LEDGER SEARCH DEMO</span>
                <span class="demo-tag">REAL-TIME LOOKUP</span>
              </div>
              <form id="heroSearchForm" class="hero-search-form" autocomplete="off" action="search.php" method="GET">
                <div class="hero-search-input-wrap">
                  <input 
                    type="text" 
                    id="heroSearchInput" 
                    name="q"
                    class="hero-search-input" 
                    placeholder="Search across all 8 categories (e.g. Elden Ring, Frieren, Dune, Shogun)..."
                    aria-label="Search archive"
                  />
                  <!-- Dropdown preview of results -->
                  <div id="heroSearchDropdown" class="hero-search-results-dropdown"></div>
                </div>
                <button type="submit" class="btn btn-primary">
                  SEARCH
                </button>
              </form>
            </div>

          </div>

          <!-- Right Column: Spotlight Title -->
          <div id="heroSpotlight" class="hero-spotlight">
            <?php if ($spotlight): ?>
              <?php 
                $spotCat = strtoupper($spotlight['category_slug']);
                $spotPoster = safePosterUrl($spotlight['backdrop_url'] ?: $spotlight['poster_url'], $spotlight['title'], $spotCat);
                $spotFallback = generateSvgPoster($spotlight['title'], $spotCat);
              ?>
              <div class="spotlight-media">
                <img 
                  src="<?= e($spotPoster) ?>" 
                  alt="<?= e($spotlight['title']) ?>" 
                  onerror="this.onerror=null; this.src='<?= $spotFallback ?>';"
                />
                <span class="badge badge-accent spotlight-badge">ARCHIVE SPOTLIGHT</span>
              </div>
              <div class="spotlight-body">
                <div class="spotlight-header">
                  <div class="spotlight-type"><?= e($spotCat) ?> / <?= e($spotlight['year']) ?></div>
                  <h3 class="spotlight-title">
                    <a href="title.php?type=<?= urlencode($spotlight['type']) ?>&id=<?= urlencode($spotlight['external_id']) ?>">
                      <?= e($spotlight['title']) ?>
                    </a>
                  </h3>
                  <div class="card-meta-line" style="margin-bottom: 12px;">
                    <span class="badge badge-score">SCORE <?= e($spotlight['score']) ?></span>
                    <span><?= e($spotlight['status']) ?></span>
                    <span><?= e($spotlight['studio_or_publisher'] ?: $spotlight['director_or_creator'] ?: 'Archived') ?></span>
                  </div>
                  <p class="spotlight-synopsis"><?= e(mb_strimwidth($spotlight['synopsis'], 0, 180, '...')) ?></p>
                </div>
                <div class="spotlight-actions">
                  <a href="title.php?type=<?= urlencode($spotlight['type']) ?>&id=<?= urlencode($spotlight['external_id']) ?>" class="btn btn-primary" style="flex: 1;">
                    EXPLORE TITLE &amp; TRAILER
                  </a>
                  <button 
                    type="button" 
                    class="btn btn-secondary btn-watchlist" 
                    data-action="watchlist-toggle" 
                    data-id="<?= e($spotlight['external_id']) ?>" 
                    data-type="<?= e($spotlight['type']) ?>"
                    data-category="<?= e($spotlight['category_slug']) ?>">
                    <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                  </button>
                </div>
              </div>
            <?php else: ?>
              <div class="spotlight-media skeleton" style="height: 240px;"></div>
              <div class="spotlight-body">
                <div class="skeleton-line w-1-4" style="margin-bottom: 12px;"></div>
                <div class="skeleton-line w-3-4 h-title" style="margin-bottom: 16px;"></div>
                <div class="skeleton-line w-full" style="margin-bottom: 8px;"></div>
                <div class="skeleton-line w-1-2"></div>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </section>

    <!-- Interactive Features Banner Strip -->
    <section class="container" style="margin-top: -16px; margin-bottom: 36px;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
        
        <!-- Feature 1: Bolne Wala Chatbot -->
        <div style="background-color: var(--bg-surface); border: 1px solid var(--border-medium); border-left: 4px solid var(--accent); padding: 16px 20px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
          <div>
            <div style="font-family: var(--font-display); font-size: 11px; font-weight: 800; color: var(--accent); letter-spacing: 0.1em;">
              🎙️ AUDIO VOICE ASSISTANT
            </div>
            <div style="font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--text-bright); margin: 2px 0;">
              HELP CENTER
            </div>
            <div style="font-size: 12px; color: var(--text-dim); line-height: 1.4;">
              Naye hain? Website chalana seekhein aur bol kar sawal poochein.
            </div>
          </div>
          <button type="button" class="btn btn-primary btn-sm" onclick="if(window.ARCHIVE_BOT) window.ARCHIVE_BOT.startTour();" style="white-space: nowrap;">
            START TOUR
          </button>
        </div>

        <!-- Feature 2: Events aur Map -->
        <div style="background-color: var(--bg-surface); border: 1px solid var(--border-medium); border-left: 4px solid #3498db; padding: 16px 20px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
          <div>
            <div style="font-family: var(--font-display); font-size: 11px; font-weight: 800; color: #3498db; letter-spacing: 0.1em;">
              🗺️ LIVE INTERACTIVE RADAR
            </div>
            <div style="font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--text-bright); margin: 2px 0;">
              EVENTS &amp; CALENDAR
            </div>
            <div style="font-size: 12px; color: var(--text-dim); line-height: 1.4;">
              Apne aas-pass fan conventions, anime expos aur meetups dhoondein.
            </div>
          </div>
          <a href="events.php" class="btn btn-secondary btn-sm" style="white-space: nowrap;">
            EXPLORE MAP
          </a>
        </div>

      </div>
    </section>

    <!-- Featured Events & Map Radar Spotlight -->
    <section class="section" style="padding-top: 10px;">
      <div class="container">
        <header class="section-head">
          <div class="section-title-wrap">
            <span class="section-index">MAP</span>
            <div>
              <h2 class="section-title">UPCOMING FAN CONVENTIONS &amp; MEETUPS</h2>
              <div class="section-sub">Live events in Pakistan (Islamabad, Karachi, Lahore) &amp; global summits on the interactive radar</div>
            </div>
          </div>
          <a href="events.php" class="section-view-all">OPEN INTERACTIVE MAP &amp; CALENDAR ➔</a>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 20px;">
          <?php foreach ($homeEvents as $ev): ?>
            <article class="media-card" style="display: flex; flex-direction: column;">
              <div class="media-poster-wrap" style="aspect-ratio: 16/9; max-height: 180px;">
                <img src="<?= e($ev['banner_url']) ?>" alt="<?= e($ev['title']) ?>" class="media-poster" loading="lazy" style="object-fit: cover;">
                <div class="card-top-badges">
                  <span class="badge badge-accent"><?= e($ev['badge_text']) ?></span>
                  <span class="badge badge-type"><?= e($ev['event_type']) ?></span>
                </div>
              </div>
              <div class="card-content" style="flex: 1;">
                <div class="card-meta-line" style="color: var(--accent); font-weight: 700;">
                  <span>📅 <?= date('M d, Y', strtotime($ev['date_start'])) ?></span>
                  <span>•</span>
                  <span>📍 <?= e($ev['city']) ?>, <?= e($ev['country']) ?></span>
                </div>
                <h4 class="card-title" style="margin: 6px 0;">
                  <a href="events.php"><?= e($ev['title']) ?></a>
                </h4>
                <p style="font-size: 13px; color: var(--text-dim); line-height: 1.45; margin: 0 0 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                  <?= e($ev['description']) ?>
                </p>
              </div>
              <div class="card-actions" style="padding: 12px; border-top: 1px solid var(--border-subtle); background-color: var(--bg-surface);">
                <a href="events.php" class="btn btn-secondary btn-sm" style="flex: 1; text-align: center;">VIEW ON MAP</a>
                <span style="font-size: 11px; color: var(--text-faint); padding: 5px 8px; border: 1px solid var(--border-subtle); border-radius: 3px;">
                  <?= e($ev['ticket_info'] ?? 'Free') ?>
                </span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Section 1: Japanese Animation Feed -->
    <section class="section">
      <div class="container">
        <header class="section-head">
          <div class="section-title-wrap">
            <span class="section-index">01</span>
            <div>
              <h2 class="section-title">JAPANESE ANIMATION</h2>
              <div class="section-sub">Anime titles in the archive catalog</div>
            </div>
          </div>
          <a href="browse.php?category=anime" class="section-view-all">VIEW ALL ANIME</a>
        </header>

        <div id="homeAnimeGrid" class="grid-catalog-7">
          <?php foreach ($animeItems as $item): ?>
            <?php 
              $poster = safePosterUrl($item['poster_url'], $item['title'], 'ANIME');
              $fallback = generateSvgPoster($item['title'], 'ANIME');
              $genres = array_slice(array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))), 0, 3);
            ?>
            <article class="media-card" data-id="<?= e($item['external_id']) ?>" data-type="anime" data-category="anime">
              <div class="media-poster-wrap">
                <img 
                  src="<?= e($poster) ?>" 
                  alt="<?= e($item['title']) ?>" 
                  class="media-poster" 
                  loading="lazy" 
                  onerror="this.onerror=null; this.src='<?= $fallback ?>';"
                />
                <div class="card-top-badges">
                  <span class="badge badge-type">ANIME</span>
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
                  <a href="title.php?type=anime&id=<?= urlencode($item['external_id']) ?>"><?= e($item['title']) ?></a>
                </h4>
                <div class="card-genres"><?= e(implode(', ', $genres) ?: 'Animation') ?></div>
              </div>
              <div class="card-actions">
                <a href="title.php?type=anime&id=<?= urlencode($item['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                <button 
                  type="button" 
                  class="btn btn-sm btn-watchlist" 
                  data-action="watchlist-toggle" 
                  data-id="<?= e($item['external_id']) ?>" 
                  data-type="anime"
                  data-category="anime"
                  title="Add to watchlist">
                  <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Editorial Breakout Statement -->
    <section style="background-color: var(--bg-surface); border-top: 1px solid var(--border-medium); border-bottom: 1px solid var(--border-medium); padding: 48px 0;">
      <div class="container" style="display: grid; grid-template-columns: 1fr 2fr; gap: 32px; align-items: baseline;">
        <div style="font-family: var(--font-display); font-size: 14px; font-weight: 700; letter-spacing: 0.12em; color: var(--accent); text-transform: uppercase;">
          PRINCIPLE OF CATALOGING
        </div>
        <p style="font-size: 19px; line-height: 1.55; color: var(--text-bright); margin: 0;">
          Strictly organized into eight distinct cultural domains: Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, and Software. Clean metadata, archival trailers, verified production credits, and reliable relational persistence.
        </p>
      </div>
    </section>

    <!-- Section 2: Interactive Gaming Feed -->
    <section class="section">
      <div class="container">
        <header class="section-head">
          <div class="section-title-wrap">
            <span class="section-index">02</span>
            <div>
              <h2 class="section-title"><?= e(strtoupper($categoryLabels['gaming'] ?? 'Games')) ?></h2>
              <div class="section-sub">Auteur game directors, action RPGs, and legendary world-building</div>
            </div>
          </div>
          <a href="browse.php?category=gaming" class="section-view-all">VIEW ALL <?= e(strtoupper($categoryLabels['gaming'] ?? 'Games')) ?></a>
        </header>

        <div class="grid-catalog-7">
          <?php foreach ($gamingItems as $item): ?>
            <?php 
              $poster = safePosterUrl($item['poster_url'], $item['title'], 'GAMING');
              $fallback = generateSvgPoster($item['title'], 'GAMING');
              $genres = array_slice(array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))), 0, 3);
            ?>
            <article class="media-card" data-id="<?= e($item['external_id']) ?>" data-type="gaming" data-category="gaming">
              <div class="media-poster-wrap">
                <img 
                  src="<?= e($poster) ?>" 
                  alt="<?= e($item['title']) ?>" 
                  class="media-poster" 
                  loading="lazy" 
                  onerror="this.onerror=null; this.src='<?= $fallback ?>';"
                />
                <div class="card-top-badges">
                  <span class="badge badge-type">GAMING</span>
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
                  <a href="title.php?type=gaming&id=<?= urlencode($item['external_id']) ?>"><?= e($item['title']) ?></a>
                </h4>
                <div class="card-genres"><?= e(implode(', ', $genres) ?: 'Interactive') ?></div>
              </div>
              <div class="card-actions">
                <a href="title.php?type=gaming&id=<?= urlencode($item['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                <button 
                  type="button" 
                  class="btn btn-sm btn-watchlist" 
                  data-action="watchlist-toggle" 
                  data-id="<?= e($item['external_id']) ?>" 
                  data-type="gaming"
                  data-category="gaming"
                  title="Add to watchlist">
                  <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Section 3: Notable Cinema Feed -->
    <section class="section">
      <div class="container">
        <header class="section-head">
          <div class="section-title-wrap">
            <span class="section-index">03</span>
            <div>
              <h2 class="section-title"><?= e(strtoupper($categoryLabels['movies'] ?? 'Movies')) ?></h2>
              <div class="section-sub">Contemporary releases and cinema history via TMDB API &amp; local database</div>
            </div>
          </div>
          <a href="browse.php?category=movies" class="section-view-all">VIEW ALL <?= e(strtoupper($categoryLabels['movies'] ?? 'Movies')) ?></a>
        </header>

        <div id="homeCinemaGrid" class="grid-catalog-7">
          <?php foreach ($movieItems as $item): ?>
            <?php 
              $poster = safePosterUrl($item['poster_url'], $item['title'], 'FILM');
              $fallback = generateSvgPoster($item['title'], 'FILM');
              $genres = array_slice(array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))), 0, 3);
            ?>
            <article class="media-card" data-id="<?= e($item['external_id']) ?>" data-type="movie" data-category="movies">
              <div class="media-poster-wrap">
                <img 
                  src="<?= e($poster) ?>" 
                  alt="<?= e($item['title']) ?>" 
                  class="media-poster" 
                  loading="lazy" 
                  onerror="this.onerror=null; this.src='<?= $fallback ?>';"
                />
                <div class="card-top-badges">
                  <span class="badge badge-type">FILM</span>
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
                  <a href="title.php?type=movie&id=<?= urlencode($item['external_id']) ?>"><?= e($item['title']) ?></a>
                </h4>
                <div class="card-genres"><?= e(implode(', ', $genres) ?: 'Cinema') ?></div>
              </div>
              <div class="card-actions">
                <a href="title.php?type=movie&id=<?= urlencode($item['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                <button 
                  type="button" 
                  class="btn btn-sm btn-watchlist" 
                  data-action="watchlist-toggle" 
                  data-id="<?= e($item['external_id']) ?>" 
                  data-type="movie"
                  data-category="movies"
                  title="Add to watchlist">
                  <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php
      $homeShelves = [
        ['slug' => 'tvshows', 'name' => $categoryLabels['tvshows'] ?? 'TV Series', 'items' => $tvItems],
        ['slug' => 'kpop', 'name' => $categoryLabels['kpop'] ?? 'Korean Music', 'items' => $kpopItems],
        ['slug' => 'comics', 'name' => $categoryLabels['comics'] ?? 'Comics', 'items' => $comicItems],
        ['slug' => 'manga', 'name' => $categoryLabels['manga'] ?? 'Japanese Comics', 'items' => $mangaItems],
        ['slug' => 'software', 'name' => $categoryLabels['software'] ?? 'Software', 'items' => $softwareItems],
      ];
      foreach ($homeShelves as $shelfIndex => $shelf):
    ?>
    <section class="section" style="background-color: var(--bg-surface); border-top: 1px solid var(--border-subtle); padding-bottom: 64px;">
      <div class="container">
        <header class="section-head">
          <div class="section-title-wrap">
            <span class="section-index"><?= sprintf('%02d', $shelfIndex + 4) ?></span>
            <div>
              <h2 class="section-title"><?= e(strtoupper($shelf['name'])) ?></h2>
              <div class="section-sub">Recently added titles in the archive</div>
            </div>
          </div>
          <a href="browse.php?category=<?= urlencode($shelf['slug']) ?>" class="section-view-all">VIEW ALL</a>
        </header>

        <div class="grid-catalog-7">
          <?php foreach ($shelf['items'] as $item): ?>
            <?php
              $catSlug = $shelf['slug'];
              $catUpper = strtoupper($catSlug);
              $poster = safePosterUrl($item['poster_url'], $item['title'], $catUpper);
              $fallback = generateSvgPoster($item['title'], $catUpper);
              $genres = array_slice(array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))), 0, 3);
            ?>
            <article class="media-card" data-id="<?= e($item['external_id']) ?>" data-type="<?= e($item['type']) ?>" data-category="<?= e($catSlug) ?>">
              <div class="media-poster-wrap">
                <img src="<?= e($poster) ?>" alt="<?= e($item['title']) ?>" class="media-poster" loading="lazy" onerror="this.onerror=null; this.src='<?= $fallback ?>';" />
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
                <h4 class="card-title"><a href="title.php?type=<?= urlencode($item['type']) ?>&id=<?= urlencode($item['external_id']) ?>"><?= e($item['title']) ?></a></h4>
                <div class="card-genres"><?= e(implode(', ', $genres) ?: 'General') ?></div>
              </div>
              <div class="card-actions">
                <a href="title.php?type=<?= urlencode($item['type']) ?>&id=<?= urlencode($item['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                <button type="button" class="btn btn-sm btn-watchlist" data-action="watchlist-toggle" data-id="<?= e($item['external_id']) ?>" data-type="<?= e($item['type']) ?>" data-category="<?= e($catSlug) ?>" title="Add to watchlist">
                  <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                </button>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endforeach; ?>

  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
