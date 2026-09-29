/**
 * Title Detail Page Controller
 * Loads full metadata, embedded trailer player, cast credits,
 * and handles watchlist toggle actions for all 8 categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 */

window.CURRENT_DETAIL_ITEM = null;

document.addEventListener('DOMContentLoaded', async () => {
  const params = new URLSearchParams(window.location.search);
  const type = params.get('type') || 'anime';
  const id = params.get('id') || '5114';

  const container = document.getElementById('detailContainer');
  if (!container) return;

  renderDetailSkeleton();

  try {
    const item = await window.AppAPI.getTitleDetail(type, id);

    if (!item) {
      throw new Error('Title record could not be loaded.');
    }

    window.CURRENT_DETAIL_ITEM = item;
    renderDetail(item);
  } catch (err) {
    console.error('Error loading detail item', err);
    renderDetailError(err.message || 'Failed to locate title in registry.');
  }
});

function renderDetailSkeleton() {
  const container = document.getElementById('detailContainer');
  if (!container) return;

  container.innerHTML = `
    <div class="detail-header-wrap">
      <div class="container">
        <div class="skeleton-line w-1-4" style="margin-bottom: 16px;"></div>
        <div class="skeleton-line w-3-4 h-title" style="margin-bottom: 12px; height: 36px;"></div>
        <div class="skeleton-line w-1-2"></div>
      </div>
    </div>
    <div class="container">
      <div class="detail-layout-grid">
        <div class="detail-main-column">
          <div class="trailer-container skeleton"></div>
          <div class="detail-synopsis-block">
            <div class="skeleton-line w-1-4 h-title" style="margin-bottom: 16px;"></div>
            <div class="skeleton-line w-full" style="margin-bottom: 8px;"></div>
            <div class="skeleton-line w-full" style="margin-bottom: 8px;"></div>
            <div class="skeleton-line w-3-4"></div>
          </div>
        </div>
        <div class="detail-side-column">
          <div class="detail-poster-frame skeleton" style="height: 380px;"></div>
          <div class="detail-action-card skeleton" style="height: 120px;"></div>
        </div>
      </div>
    </div>
  `;
}

function renderDetail(item) {
  const container = document.getElementById('detailContainer');
  if (!container) return;

  const isSaved = window.WatchlistService.has(item.type, item.id);
  const catKey = (item.category || item.type || 'anime').toLowerCase();
  
  const categoryNames = {
    anime: 'ANIME ARCHIVE',
    gaming: 'GAMING LEDGER',
    movies: 'CINEMA REGISTRY',
    movie: 'CINEMA REGISTRY',
    tvshows: 'TELEVISION REGISTRY',
    tv: 'TELEVISION REGISTRY',
    kpop: 'K-POP ARCHIVE',
    comics: 'GRAPHIC NOVELS & COMICS',
    manga: 'MANGA ARCHIVE',
    software: 'SOFTWARE LIBRARY'
  };

  const typeLabel = categoryNames[catKey] || catKey.toUpperCase();
  const genresBadges = (item.genres || []).map(g => `<span class="badge">${g}</span>`).join(' ');

  // Build Trailer / Media Preview HTML
  let mediaPreviewHtml = '';
  if (item.trailerUrl) {
    mediaPreviewHtml = `
      <div class="trailer-container">
        <iframe 
          class="trailer-iframe"
          src="${item.trailerUrl}" 
          title="${window.AppUI.escapeHtml(item.title)} Video Preview"
          allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
          allowfullscreen>
        </iframe>
      </div>
    `;
  } else {
    mediaPreviewHtml = `
      <div class="trailer-placeholder">
        <div class="trailer-placeholder-icon">
          ${window.AppUI.icons.film}
        </div>
        <h4 style="font-size: 18px; margin-bottom: 6px;">STREAM NOT EMBEDDED</h4>
        <p>No video player feed is archived for this specific record.</p>
      </div>
    `;
  }

  // Build Cast/Credits HTML
  let castHtml = '';
  if (item.cast && item.cast.length > 0) {
    const castItems = item.cast.map(c => `
      <div class="cast-item">
        ${c.avatar ? 
          `<img src="${c.avatar}" alt="${window.AppUI.escapeHtml(c.name)}" class="cast-avatar" onerror="this.style.display='none'"/>` : 
          `<div class="cast-avatar" style="display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--text-faint);">N/A</div>`
        }
        <div>
          <div class="cast-name">${window.AppUI.escapeHtml(c.name)}</div>
          <div class="cast-role">${window.AppUI.escapeHtml(c.role)}</div>
        </div>
      </div>
    `).join('');

    castHtml = `
      <div class="detail-cast-block">
        <h3>CREDITS &amp; KEY PERSONNEL</h3>
        <div class="cast-grid">
          ${castItems}
        </div>
      </div>
    `;
  }

  // Dynamic Category Metrics Generator
  let categoryMetricsHtml = '';
  if (catKey === 'gaming') {
    categoryMetricsHtml = `
      <tr><th>PLATFORMS</th><td>${item.platform || 'N/A'}</td></tr>
      <tr><th>DEVELOPER</th><td>${item.developer || 'N/A'}</td></tr>
      <tr><th>PUBLISHER</th><td>${item.publisher || 'N/A'}</td></tr>
    `;
  } else if (catKey === 'tvshows' || catKey === 'tv') {
    categoryMetricsHtml = `
      <tr><th>NETWORK</th><td>${item.network || 'N/A'}</td></tr>
      <tr><th>SEASONS</th><td>${item.seasons || 'N/A'}</td></tr>
      <tr><th>EPISODES</th><td>${item.episodes || 'N/A'}</td></tr>
      ${item.creator ? `<tr><th>CREATOR</th><td>${item.creator}</td></tr>` : ''}
    `;
  } else if (catKey === 'kpop') {
    categoryMetricsHtml = `
      <tr><th>AGENCY</th><td>${item.agency || 'N/A'}</td></tr>
      <tr><th>ARTISTS</th><td>${item.artists || 'N/A'}</td></tr>
      <tr><th>DURATION</th><td>${item.duration || 'N/A'}</td></tr>
    `;
  } else if (catKey === 'comics') {
    categoryMetricsHtml = `
      <tr><th>PUBLISHER</th><td>${item.publisher || 'N/A'}</td></tr>
      <tr><th>WRITER</th><td>${item.writer || 'N/A'}</td></tr>
      <tr><th>ARTIST</th><td>${item.artist || 'N/A'}</td></tr>
      <tr><th>ISSUES</th><td>${item.issues || 'N/A'}</td></tr>
    `;
  } else if (catKey === 'manga') {
    categoryMetricsHtml = `
      <tr><th>AUTHOR</th><td>${item.author || 'N/A'}</td></tr>
      <tr><th>MAGAZINE</th><td>${item.magazine || 'N/A'}</td></tr>
      <tr><th>VOLUMES</th><td>${item.volumes || 'N/A'}</td></tr>
    `;
  } else if (catKey === 'software') {
    categoryMetricsHtml = `
      <tr><th>SOFTWARE TYPE</th><td>${window.AppUI.escapeHtml(String(item.software_type || 'N/A'))}</td></tr>
      <tr><th>PLATFORM</th><td>${window.AppUI.escapeHtml(String(item.platform || 'N/A'))}</td></tr>
      <tr><th>VERSION</th><td>${window.AppUI.escapeHtml(String(item.version || 'N/A'))}</td></tr>
      <tr><th>LICENSE</th><td>${window.AppUI.escapeHtml(String(item.license || 'N/A'))}</td></tr>
      <tr><th>LANGUAGE</th><td>${window.AppUI.escapeHtml(String(item.programming_language || 'N/A'))}</td></tr>
      <tr><th>ENGINE / FRAMEWORK</th><td>${window.AppUI.escapeHtml(String(item.engine || 'N/A'))}</td></tr>
      <tr><th>REQUIREMENTS</th><td>${window.AppUI.escapeHtml(String(item.requirements || 'N/A'))}</td></tr>
      <tr><th>WEBSITE</th><td>${window.AppUI.escapeHtml(String(item.website || 'N/A'))}</td></tr>
    `;
  } else if (catKey === 'movie' || catKey === 'movies') {
    categoryMetricsHtml = `
      <tr><th>RUNTIME</th><td>${item.runtime || 'N/A'}</td></tr>
      <tr><th>DIRECTOR</th><td>${item.director || 'N/A'}</td></tr>
    `;
  } else {
    // anime default
    categoryMetricsHtml = `
      <tr><th>EPISODES</th><td>${item.episodes || 'N/A'}</td></tr>
      <tr><th>STUDIO</th><td>${item.studio || 'N/A'}</td></tr>
      <tr><th>DURATION</th><td>${item.duration || 'N/A'}</td></tr>
    `;
  }

  container.innerHTML = `
    <!-- Header Block -->
    <div class="detail-header-wrap">
      <div class="container">
        <div class="detail-breadcrumbs">
          <a href="index.php">INDEX</a>
          <span>/</span>
          <a href="browse.php?category=${catKey}">${typeLabel}</a>
          <span>/</span>
          <span style="color: var(--text-bright);">${window.AppUI.escapeHtml(item.title)}</span>
        </div>
        
        <div class="detail-headline-row">
          <div>
            <h1 class="detail-title">${window.AppUI.escapeHtml(item.title)}</h1>
            ${item.originalTitle && item.originalTitle !== item.title ? 
              `<div class="detail-original-title">${window.AppUI.escapeHtml(item.originalTitle)}</div>` : ''
            }
            <div class="detail-badges-row">
              <span class="badge badge-accent">${catKey.toUpperCase()}</span>
              <span class="badge badge-score">SCORE ${item.score}</span>
              <span class="badge">${item.year}</span>
              <span class="badge">${item.status}</span>
              ${genresBadges}
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
          <!-- Trailer / Footage Player -->
          <div>
            <div style="font-family: var(--font-display); font-size: 13px; font-weight: 700; letter-spacing: 0.1em; color: var(--text-dim); text-transform: uppercase; margin-bottom: 10px;">
              ARCHIVAL FOOTAGE / MEDIA STREAM
            </div>
            ${mediaPreviewHtml}
          </div>

          <!-- Synopsis -->
          <div class="detail-synopsis-block">
            <h3>SYNOPSIS &amp; ARCHIVAL CONTEXT</h3>
            <p class="detail-synopsis-text">${window.AppUI.escapeHtml(item.synopsis)}</p>
          </div>

          <!-- Cast / Personnel Section -->
          ${castHtml}
        </div>

        <!-- Side Right Column -->
        <div class="detail-side-column">
          <!-- Poster -->
          <div class="detail-poster-frame">
            <img 
              src="${item.poster || window.AppUI.getPlaceholderPoster(item.title, typeLabel)}" 
              alt="${window.AppUI.escapeHtml(item.title)}" 
              class="detail-poster-img" 
              onerror="this.onerror=null; this.src=window.AppUI.getPlaceholderPoster(this.alt, '${typeLabel}');"
            />
          </div>

          <!-- Watchlist and Primary Action Card -->
          <div class="detail-action-card">
            <button 
              type="button" 
              class="btn btn-primary btn-block btn-watchlist ${isSaved ? 'in-watchlist' : ''}" 
              data-action="watchlist-toggle" 
              data-id="${item.id}" 
              data-type="${item.type}"
              data-category="${catKey}">
              ${isSaved ? 'IN YOUR WATCHLIST (CLICK TO REMOVE)' : 'ADD TO WATCHLIST'}
            </button>
            <a href="search.php?q=${encodeURIComponent(item.title)}" class="btn btn-secondary btn-block">
              SEARCH SIMILAR TITLES
            </a>
          </div>

          <!-- Archival Specifications Table -->
          <div class="detail-specs-card">
            <h4 style="font-size: 16px; margin-bottom: 14px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 6px;">
              CATEGORY REGISTRY METRICS
            </h4>
            <table class="data-table">
              <tbody>
                <tr>
                  <th>CATEGORY</th>
                  <td>${catKey.toUpperCase()}</td>
                </tr>
                <tr>
                  <th>RELEASE</th>
                  <td>${item.year}</td>
                </tr>
                <tr>
                  <th>SCORE</th>
                  <td>${item.score} / 10</td>
                </tr>
                <tr>
                  <th>STATUS</th>
                  <td>${item.status}</td>
                </tr>
                ${categoryMetricsHtml}
                <tr>
                  <th>GENRES</th>
                  <td>${(item.genres || []).join(', ') || 'N/A'}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <section class="section detail-related-section" id="relatedItemsSection" aria-labelledby="relatedItemsHeading" hidden>
      <div class="container">
        <header class="section-head">
          <div class="section-title-wrap">
            <div>
              <h2 class="section-title" id="relatedItemsHeading">MORE FROM THIS CATEGORY</h2>
              <div class="section-sub">More titles from this category</div>
            </div>
          </div>
          <a href="browse.php" class="section-view-all" id="relatedItemsBrowse">VIEW CATEGORY</a>
        </header>
        <div class="grid-catalog-4" id="relatedItemsGrid"></div>
      </div>
    </section>
  `;

  loadRelatedItems(item, catKey);
}

async function loadRelatedItems(item, category) {
  const section = document.getElementById('relatedItemsSection');
  const grid = document.getElementById('relatedItemsGrid');
  const browseLink = document.getElementById('relatedItemsBrowse');
  if (!section || !grid) return;

  const categoryKey = category === 'movie' ? 'movies' : category === 'tv' ? 'tvshows' : category;
  if (browseLink) browseLink.href = `browse.php?category=${encodeURIComponent(categoryKey)}`;

  try {
    const response = await fetch(`api/items.php?category=${encodeURIComponent(categoryKey)}&sort=recent_desc&per_page=8`);
    if (!response.ok) throw new Error(`Related titles request failed: ${response.status}`);
    const result = await response.json();
    if (!result.success) throw new Error('Related titles are unavailable.');

    const currentTitle = String(item.title || '').trim().toLowerCase();
    const relatedItems = (result.data || [])
      .filter((related) => String(related.id) !== String(item.id) && String(related.title || '').trim().toLowerCase() !== currentTitle)
      .slice(0, 4);
    if (!relatedItems.length) return;

    grid.innerHTML = relatedItems.map((related) => window.AppUI.renderCard(related)).join('');
    section.hidden = false;
  } catch (error) {
    console.error('Error loading related titles', error);
  }
}

function renderDetailError(message) {
  const container = document.getElementById('detailContainer');
  if (!container) return;

  container.innerHTML = `
    <div class="container" style="padding: 80px 24px; text-align: center;">
      <h2 style="font-size: 32px; color: var(--accent); margin-bottom: 16px;">TITLE RECORD NOT RETRIEVED</h2>
      <p style="color: var(--text-dim); max-width: 500px; margin: 0 auto 28px auto;">${window.AppUI.escapeHtml(message)}</p>
      <div style="display: flex; justify-content: center; gap: 12px;">
        <a href="browse.php" class="btn btn-primary">RETURN TO BROWSE</a>
        <a href="index.php" class="btn btn-secondary">BACK TO INDEX</a>
      </div>
    </div>
  `;
}
