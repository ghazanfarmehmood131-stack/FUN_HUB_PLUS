<?php
/**
 * ARCHIVE Ledger - Title Detail
 * Displays archival records, specifications, trailers, cast credits,
 * and persistent watchlist actions for all 8 categories.
 */
require_once __DIR__ . '/config.php';

$type = $_GET['type'] ?? 'media';
$id   = $_GET['id'] ?? '';

$dbItem = null;
if (!empty($id)) {
    $dbItem = getMediaItemDetail($id);
}
$relatedItems = [];
if ($dbItem) {
  $relatedItems = array_values(array_filter(
    getItemsByCategory($dbItem['category_slug'], 8),
    static fn($item) => $item['external_id'] !== $dbItem['external_id']
  ));
  $relatedItems = array_slice($relatedItems, 0, 4);
}
$playlistVideos = [];
if ($dbItem && supportsPlaylistCategory($dbItem['category_slug'])) {
  $db = getDb();
  if ($db) {
    try {
      $playlistStmt = $db->prepare('SELECT id, episode_label, mime_type FROM media_videos WHERE media_item_id = ? ORDER BY sort_order ASC, id ASC');
      $playlistStmt->execute([$dbItem['id']]);
      $playlistVideos = $playlistStmt->fetchAll();
    } catch (PDOException $ex) {
      error_log('title playlist query failed: ' . $ex->getMessage());
    }
  }
}

$pageTitle = $dbItem ? $dbItem['title'] . ' - ARCHIVE' : 'Title Record - ARCHIVE';
$activeNav = 'browse';
$pageTag   = 'RECORD';
$pageScript = $dbItem ? null : 'js/title.js';

include __DIR__ . '/includes/header.php';
?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const buttons = Array.from(document.querySelectorAll('.playlist-toggle'));
    const panels = Array.from(document.querySelectorAll('.playlist-content'));
    const playlistHeading = document.getElementById('playlistHeading');
    const defaultPlaylistHeading = playlistHeading ? playlistHeading.textContent : '';

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        const panel = document.getElementById(button.dataset.target);
        const shouldOpen = button.getAttribute('aria-expanded') !== 'true';

        buttons.forEach(function (item) {
          item.setAttribute('aria-expanded', 'false');
          item.classList.remove('is-active');
        });
        panels.forEach(function (item) {
          item.classList.remove('is-open');
          item.hidden = true;
          item.querySelectorAll('iframe').forEach(function (frame) {
            frame.removeAttribute('src');
          });
          item.querySelectorAll('video').forEach(function (video) {
            video.pause();
          });
        });

        if (shouldOpen && panel) {
          button.setAttribute('aria-expanded', 'true');
          button.classList.add('is-active');
          panel.hidden = false;
          panel.classList.add('is-open');
          if (playlistHeading) playlistHeading.textContent = button.textContent.trim();
          const frame = panel.querySelector('iframe[data-src]');
          if (frame) frame.src = frame.dataset.src;
        } else if (playlistHeading) {
          playlistHeading.textContent = defaultPlaylistHeading;
        }
      });
    });

    document.querySelectorAll('[data-custom-player]').forEach(function (player) {
      const video = player.querySelector('video');
      const playButtons = player.querySelectorAll('[data-player-play]');
      const seek = player.querySelector('[data-player-seek]');
      const volume = player.querySelector('[data-player-volume]');
      const muteButton = player.querySelector('[data-player-mute]');
      const speed = player.querySelector('[data-player-speed]');
      const time = player.querySelector('[data-player-time]');
      const fullscreenButton = player.querySelector('[data-player-fullscreen]');

      if (!video) return;

      function togglePlayback() {
        if (video.paused) {
          video.play().catch(function () {});
        } else {
          video.pause();
        }
      }

      function toggleFullscreen() {
        if (document.fullscreenElement === player) {
          if (document.exitFullscreen) document.exitFullscreen();
        } else if (!document.fullscreenElement && player.requestFullscreen) {
          player.requestFullscreen().catch(function () {});
        }
      }

      function formatTime(seconds) {
        if (!Number.isFinite(seconds)) return '0:00';
        const totalSeconds = Math.floor(seconds);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const remainder = String(totalSeconds % 60).padStart(2, '0');
        return hours ? hours + ':' + String(minutes).padStart(2, '0') + ':' + remainder : minutes + ':' + remainder;
      }

      function updatePlayer() {
        const isPlaying = !video.paused && !video.ended;
        player.classList.toggle('is-playing', isPlaying);
        playButtons.forEach(function (button) {
          button.setAttribute('aria-label', isPlaying ? 'Pause video' : 'Play video');
        });
        if (time) time.textContent = formatTime(video.currentTime) + ' / ' + formatTime(video.duration);
        if (seek && Number.isFinite(video.duration) && video.duration > 0) {
          seek.value = String((video.currentTime / video.duration) * 100);
          seek.style.setProperty('--seek-progress', seek.value + '%');
        }
      }

      playButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          togglePlayback();
        });
      });

      if (seek) {
        seek.addEventListener('input', function () {
          if (Number.isFinite(video.duration) && video.duration > 0) {
            video.currentTime = (Number(seek.value) / 100) * video.duration;
            seek.style.setProperty('--seek-progress', seek.value + '%');
          }
        });
      }

      if (volume) {
        volume.addEventListener('input', function () {
          video.volume = Number(volume.value);
          video.muted = video.volume === 0;
        });
      }

      if (muteButton) {
        muteButton.addEventListener('click', function () {
          video.muted = !video.muted;
        });
      }

      if (speed) {
        speed.addEventListener('change', function () {
          video.playbackRate = Number(speed.value);
        });
      }

      if (fullscreenButton) {
        fullscreenButton.addEventListener('click', function () {
          toggleFullscreen();
        });
      }

      const playerPanel = player.closest('.playlist-content');
      document.addEventListener('keydown', function (event) {
        if (playerPanel && playerPanel.hidden) return;
        if (event.target.closest('input, select, textarea, [contenteditable="true"]')) return;
        const isFullscreen = document.fullscreenElement === player;
        const isFullscreenButton = fullscreenButton && fullscreenButton.contains(event.target);
        const key = event.key.toLowerCase();
        if (key === 'f') {
          event.preventDefault();
          if (!event.repeat) toggleFullscreen();
          return;
        }
        if (!player.contains(event.target) && !isFullscreen) return;

        const isButtonOrLink = event.target.closest('button, a');
        if ((event.code === 'Space' || key === 'k')
          && (!isButtonOrLink || (isFullscreen && isFullscreenButton))) {
          event.preventDefault();
          if (!event.repeat) togglePlayback();
          return;
        }

        let handled = true;
        if ((event.key === 'ArrowLeft' || key === 'j') && Number.isFinite(video.duration)) {
          video.currentTime = Math.max(0, video.currentTime - (key === 'j' ? 10 : 5));
        } else if ((event.key === 'ArrowRight' || key === 'l') && Number.isFinite(video.duration)) {
          video.currentTime = Math.min(video.duration, video.currentTime + (key === 'l' ? 10 : 5));
        } else if (event.key === 'ArrowUp') {
          video.muted = false;
          video.volume = Math.min(1, video.volume + 0.05);
        } else if (event.key === 'ArrowDown') {
          video.volume = Math.max(0, video.volume - 0.05);
          video.muted = video.volume === 0;
        } else if (key === 'm') {
          if (!event.repeat) video.muted = !video.muted;
        } else if (event.key === 'Home' && Number.isFinite(video.duration)) {
          video.currentTime = 0;
        } else if (event.key === 'End' && Number.isFinite(video.duration)) {
          video.currentTime = video.duration;
        } else if (/^[0-9]$/.test(event.key) && Number.isFinite(video.duration)) {
          video.currentTime = video.duration * (Number(event.key) / 10);
        } else {
          handled = false;
        }

        if (handled) event.preventDefault();
      });

      video.addEventListener('timeupdate', updatePlayer);
      video.addEventListener('loadedmetadata', updatePlayer);
      video.addEventListener('durationchange', updatePlayer);
      video.addEventListener('play', updatePlayer);
      video.addEventListener('pause', updatePlayer);
      video.addEventListener('ended', updatePlayer);
      video.addEventListener('volumechange', function () {
        if (volume) volume.value = video.muted ? '0' : String(video.volume);
        if (muteButton) muteButton.setAttribute('aria-label', video.muted ? 'Unmute video' : 'Mute video');
      });
      updatePlayer();
    });
  });
</script>

  <!-- Main Content Container -->
  <main class="site-main" id="detailContainer">
    <?php if ($dbItem): ?>
      <?php
        $catKey = strtolower($dbItem['category_slug']);
        $catNames = [
          'anime'   => 'ANIME ARCHIVE',
          'gaming'  => 'GAMING LEDGER',
          'movies'  => 'CINEMA REGISTRY',
          'movie'   => 'CINEMA REGISTRY',
          'tvshows' => 'TELEVISION REGISTRY',
          'tv'      => 'TELEVISION REGISTRY',
          'kpop'    => 'K-POP ARCHIVE',
          'comics'  => 'GRAPHIC NOVELS & COMICS',
          'manga'   => 'MANGA ARCHIVE',
          'software' => 'SOFTWARE LIBRARY'
        ];
        $typeLabel = $catNames[$catKey] ?? strtoupper($catKey);
        $poster = safePosterUrl($dbItem['poster_url'], $dbItem['title'], $typeLabel);
        $fallbackPoster = generateSvgPoster($dbItem['title'], $typeLabel);
        $genres = array_filter(array_map('trim', explode(',', $dbItem['genres'] ?? '')));
        $meta = !empty($dbItem['extra_meta']) ? json_decode($dbItem['extra_meta'], true) : [];
        $isSaved = false;
        if ($currentUser) {
            $isSaved = isItemInWatchlist($currentUser['id'], $dbItem['external_id'], $dbItem['type']);
        }
      ?>
      <!-- Header Block -->
      <div class="detail-header-wrap">
        <div class="container">
          <div class="detail-breadcrumbs">
            <a href="index.php">INDEX</a>
            <span>/</span>
            <a href="browse.php?category=<?= e($catKey) ?>"><?= e($typeLabel) ?></a>
            <span>/</span>
            <span style="color: var(--text-bright);"><?= e($dbItem['title']) ?></span>
          </div>
          
          <div class="detail-headline-row">
            <div>
              <h1 class="detail-title"><?= e($dbItem['title']) ?></h1>
              <?php if (!empty($dbItem['original_title']) && $dbItem['original_title'] !== $dbItem['title']): ?>
                <div class="detail-original-title"><?= e($dbItem['original_title']) ?></div>
              <?php endif; ?>
              <div class="detail-badges-row">
                <span class="badge badge-accent"><?= strtoupper(e($catKey)) ?></span>
                <span class="badge badge-score">SCORE <?= e($dbItem['score']) ?></span>
                <span class="badge"><?= e($dbItem['year']) ?></span>
                <span class="badge"><?= e($dbItem['status']) ?></span>
                <?php foreach ($genres as $g): ?>
                  <span class="badge"><?= e($g) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Asymmetric Content Grid -->
      <div class="container">
        <div class="detail-layout-grid">
          <!-- Main Left Column -->
          <div class="detail-main-column">
            <section class="detail-playlist-block" aria-labelledby="playlistHeading">
              <h3 id="playlistHeading">TRAILER &amp; EPISODES</h3>
              <div class="playlist-panels">
                <div class="playlist-content" id="trailer-panel" hidden>
                  <?php if (!empty($dbItem['trailer_url'])): ?>
                    <div class="trailer-container">
                      <iframe
                        class="trailer-iframe"
                        data-src="<?= e($dbItem['trailer_url']) ?>"
                        title="<?= e($dbItem['title']) ?> Video Preview"
                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                      </iframe>
                    </div>
                  <?php else: ?>
                    <div class="trailer-placeholder">
                      <div class="trailer-placeholder-icon">
                        <svg class="icon" viewBox="0 0 24 24"><polygon points="7 4 19 12 7 20 7 4"/></svg>
                      </div>
                      <h4 style="font-size: 18px; margin-bottom: 6px;">STREAM NOT EMBEDDED</h4>
                      <p>No video player feed is archived for this specific record.</p>
                    </div>
                  <?php endif; ?>
                </div>
                <?php foreach ($playlistVideos as $playlistVideo): ?>
                  <div class="playlist-content" id="playlist-panel-<?= (int)$playlistVideo['id'] ?>" hidden>
                    <?php if ($currentUser): ?>
                      <div class="custom-video-player" data-custom-player>
                        <div class="custom-player-stage">
                          <video class="playlist-video-player" preload="metadata" playsinline tabindex="0" aria-label="<?= e($playlistVideo['episode_label']) ?>">
                            <source src="video.php?id=<?= (int)$playlistVideo['id'] ?>" type="<?= e($playlistVideo['mime_type']) ?>">
                            Your browser does not support this video format.
                          </video>
                          <button type="button" class="player-center-play" data-player-play aria-label="Play video">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                          </button>
                        </div>
                        <div class="custom-player-controls">
                          <input class="player-seek" type="range" min="0" max="100" step="0.1" value="0" data-player-seek aria-label="Seek video">
                          <div class="player-control-row">
                            <button type="button" class="player-control-button player-play-button" data-player-play aria-label="Play video">
                              <svg class="player-icon-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                              <svg class="player-icon-pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
                            </button>
                            <button type="button" class="player-control-button" data-player-mute aria-label="Mute video">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9v6h4l5 4V5L7 9H3zm13.5 3a3.5 3.5 0 0 0-2-3.16v6.31a3.5 3.5 0 0 0 2-3.15zM14.5 3.23v2.06a7 7 0 0 1 0 13.42v2.06a9 9 0 0 0 0-17.54z"/></svg>
                            </button>
                            <input class="player-volume" type="range" min="0" max="1" step="0.05" value="1" data-player-volume aria-label="Volume">
                            <span class="player-time" data-player-time>0:00 / 0:00</span>
                            <span class="player-controls-spacer"></span>
                            <label class="player-speed-label" for="player-speed-<?= (int)$playlistVideo['id'] ?>">SPEED</label>
                            <select class="player-speed" id="player-speed-<?= (int)$playlistVideo['id'] ?>" data-player-speed aria-label="Playback speed">
                              <option value="0.5">0.5x</option>
                              <option value="0.75">0.75x</option>
                              <option value="1" selected>1x</option>
                              <option value="1.25">1.25x</option>
                              <option value="1.5">1.5x</option>
                              <option value="2">2x</option>
                            </select>
                            <a class="player-control-button player-download" href="video.php?id=<?= (int)$playlistVideo['id'] ?>&amp;download=1" aria-label="Download video" title="Download video">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20h14v-2H5v2zM19 9h-4V3H9v6H5l7 7 7-7z"/></svg>
                            </a>
                            <button type="button" class="player-control-button" data-player-fullscreen aria-label="Enter fullscreen">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v2H6v4H4V4zm10 0h6v6h-2V6h-4V4zM4 14h2v4h4v2H4v-6zm14 4v-4h2v6h-6v-2h4z"/></svg>
                            </button>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <div class="playlist-video-locked">
                        <p>Create an account or sign in to watch this video.</p>
                        <a class="btn btn-primary" href="auth.php?next=<?= e(rawurlencode('video.php?id=' . (int)$playlistVideo['id'])) ?>">SIGN UP / SIGN IN TO WATCH</a>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="playlist-buttons">
                <button type="button" class="playlist-toggle playlist-toggle-trailer" aria-expanded="false" aria-controls="trailer-panel" data-target="trailer-panel">TRAILER</button>
                <?php foreach ($playlistVideos as $playlistVideo): ?>
                  <button type="button" class="playlist-toggle" aria-expanded="false" aria-controls="playlist-panel-<?= (int)$playlistVideo['id'] ?>" data-target="playlist-panel-<?= (int)$playlistVideo['id'] ?>">
                    <?= e($playlistVideo['episode_label']) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </section>

            <!-- Synopsis -->
            <div class="detail-synopsis-block">
              <h3>SYNOPSIS &amp; ARCHIVAL CONTEXT</h3>
              <p class="detail-synopsis-text"><?= e($dbItem['synopsis']) ?></p>
            </div>

            <!-- Cast / Personnel Section -->
            <?php if (!empty($meta['cast'])): ?>
              <div class="detail-cast-block">
                <h3>CREDITS &amp; KEY PERSONNEL</h3>
                <div class="cast-grid">
                  <?php foreach ($meta['cast'] as $cast): ?>
                    <div class="cast-item">
                      <div class="cast-avatar" style="display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--text-faint);background:var(--bg-canvas);">
                        <?= e(substr($cast['name'], 0, 2)) ?>
                      </div>
                      <div>
                        <div class="cast-name"><?= e($cast['name']) ?></div>
                        <div class="cast-role"><?= e($cast['role']) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Side Right Column -->
          <div class="detail-side-column">
            <!-- Poster Frame with Safe Fallback -->
            <div class="detail-poster-frame">
              <img 
                src="<?= e($poster) ?>" 
                alt="<?= e($dbItem['title']) ?>" 
                class="detail-poster-img" 
                onerror="this.onerror=null; this.src='<?= $fallbackPoster ?>';"
              />
            </div>

            <!-- Watchlist and Primary Action Card -->
            <div class="detail-action-card">
              <button 
                type="button" 
                class="btn btn-primary btn-block btn-watchlist <?= $isSaved ? 'in-watchlist' : '' ?>" 
                data-action="watchlist-toggle" 
                data-id="<?= e($dbItem['external_id']) ?>" 
                data-type="<?= e($dbItem['type']) ?>"
                data-category="<?= e($catKey) ?>">
                <?= $isSaved ? 'IN YOUR WATCHLIST (CLICK TO REMOVE)' : 'ADD TO WATCHLIST' ?>
              </button>
              <a href="search.php?q=<?= urlencode($dbItem['title']) ?>" class="btn btn-secondary btn-block">
                SEARCH SIMILAR TITLES
              </a>
            </div>

            <!-- Specifications Table -->
            <div class="detail-specs-card">
              <h4 style="font-size: 16px; margin-bottom: 14px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 6px;">
                CATEGORY REGISTRY METRICS
              </h4>
              <table class="data-table">
                <tbody>
                  <tr>
                    <th>CATEGORY</th>
                    <td><?= strtoupper(e($catKey)) ?></td>
                  </tr>
                  <tr>
                    <th>RELEASE</th>
                    <td><?= e($dbItem['year']) ?></td>
                  </tr>
                  <tr>
                    <th>SCORE</th>
                    <td><?= e($dbItem['score']) ?> / 10</td>
                  </tr>
                  <tr>
                    <th>STATUS</th>
                    <td><?= e($dbItem['status']) ?></td>
                  </tr>
                  <?php if (!empty($dbItem['director_or_creator'])): ?>
                    <tr>
                      <th>DIRECTOR / CREATOR</th>
                      <td><?= e($dbItem['director_or_creator']) ?></td>
                    </tr>
                  <?php endif; ?>
                  <?php if (!empty($dbItem['studio_or_publisher'])): ?>
                    <tr>
                      <th>STUDIO / PUBLISHER</th>
                      <td><?= e($dbItem['studio_or_publisher']) ?></td>
                    </tr>
                  <?php endif; ?>
                  <?php if (!empty($dbItem['duration_or_episodes'])): ?>
                    <tr>
                      <th>RUNTIME / RUN</th>
                      <td><?= e($dbItem['duration_or_episodes']) ?></td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <th>GENRES</th>
                    <td><?= e(implode(', ', $genres) ?: 'N/A') ?></td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>
      <?php if ($relatedItems): ?>
        <section class="section" aria-labelledby="relatedTitlesHeading">
          <div class="container">
            <header class="section-head">
              <div class="section-title-wrap">
                <div>
                  <h2 class="section-title" id="relatedTitlesHeading">MORE FROM THIS CATEGORY</h2>
                  <div class="section-sub">More titles from <?= e($typeLabel) ?></div>
                </div>
              </div>
              <a href="browse.php?category=<?= urlencode($catKey) ?>" class="section-view-all">VIEW CATEGORY</a>
            </header>
            <div class="grid-catalog-4">
              <?php foreach ($relatedItems as $relatedItem): ?>
                <?php
                  $relatedPoster = safePosterUrl($relatedItem['poster_url'], $relatedItem['title'], $typeLabel);
                  $relatedFallback = generateSvgPoster($relatedItem['title'], $typeLabel);
                  $relatedGenres = array_slice(array_filter(array_map('trim', explode(',', $relatedItem['genres'] ?? ''))), 0, 3);
                ?>
                <article class="media-card" data-id="<?= e($relatedItem['external_id']) ?>" data-type="<?= e($relatedItem['type']) ?>" data-category="<?= e($catKey) ?>">
                  <div class="media-poster-wrap">
                    <img src="<?= e($relatedPoster) ?>" alt="<?= e($relatedItem['title']) ?>" class="media-poster" loading="lazy" onerror="this.onerror=null; this.src='<?= $relatedFallback ?>';" />
                    <div class="card-top-badges">
                      <span class="badge badge-type"><?= e(strtoupper($catKey)) ?></span>
                      <span class="badge badge-score">SCORE <?= e($relatedItem['score']) ?></span>
                    </div>
                  </div>
                  <div class="card-content">
                    <div class="card-meta-line"><span><?= e($relatedItem['year']) ?></span><span>-</span><span><?= e($relatedItem['status']) ?></span></div>
                    <h4 class="card-title"><a href="title.php?type=<?= urlencode($relatedItem['type']) ?>&id=<?= urlencode($relatedItem['external_id']) ?>"><?= e($relatedItem['title']) ?></a></h4>
                    <div class="card-genres"><?= e(implode(', ', $relatedGenres) ?: 'General') ?></div>
                  </div>
                  <div class="card-actions">
                    <a href="title.php?type=<?= urlencode($relatedItem['type']) ?>&id=<?= urlencode($relatedItem['external_id']) ?>" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
                    <button type="button" class="btn btn-sm btn-watchlist" data-action="watchlist-toggle" data-id="<?= e($relatedItem['external_id']) ?>" data-type="<?= e($relatedItem['type']) ?>" data-category="<?= e($catKey) ?>" title="Add to watchlist">
                      <svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>
                    </button>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
      <?php endif; ?>
    <?php else: ?>
      <!-- Dynamic Skeleton / title.js Client Loader -->
      <div class="container section">
        <div class="skeleton-line w-1-4" style="height: 18px; margin-bottom: 24px;"></div>
        <div class="skeleton-line w-1-2 h-title" style="margin-bottom: 32px;"></div>
        <div class="detail-layout-grid">
          <div class="detail-main-column">
            <div class="skeleton-card" style="height: 380px; margin-bottom: 24px;"></div>
            <div class="skeleton-line w-full" style="margin-bottom: 12px;"></div>
            <div class="skeleton-line w-3-4"></div>
          </div>
          <div class="detail-side-column">
            <div class="skeleton-card" style="height: 400px; margin-bottom: 16px;"></div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>

<?php include __DIR__ . '/includes/footer.php'; ?>
