/**
 * Home Page Controller
 * Handles hero spotlight, live demo search bar with dropdown preview,
 * and feeds from MySQL database, Jikan API & TMDB API with full skeleton loaders.
 */

document.addEventListener('DOMContentLoaded', async () => {
  initHeroSearchDemo();
  loadHeroSpotlight();
  loadAnimeSection();
  loadCinemaSection();
});

// Live Search Demo on Homepage
function initHeroSearchDemo() {
  const form = document.getElementById('heroSearchForm');
  const input = document.getElementById('heroSearchInput');
  const dropdown = document.getElementById('heroSearchDropdown');

  if (!input || !dropdown) return;

  let debounceTimer = null;
  let requestVersion = 0;

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const query = input.value.trim();
    const currentRequest = ++requestVersion;

    if (query.length === 0) {
      dropdown.classList.remove('active');
      dropdown.innerHTML = '';
      return;
    }

    dropdown.classList.add('active');
    dropdown.innerHTML = `
      <div style="padding: 14px; font-size: 12px; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-display); letter-spacing: 0.05em;">
        Searching titles...
      </div>
    `;

    debounceTimer = setTimeout(async () => {
      try {
        // Try local backend search API first
        let combined = [];
        let prefixApiAvailable = false;
        try {
          const apiRes = await fetch(`api/search.php?q=${encodeURIComponent(query)}&limit=6&prefix=1`);
          if (apiRes.ok) {
            const apiJson = await apiRes.json();
            if (apiJson.success && Array.isArray(apiJson.data)) {
              combined = apiJson.data;
              prefixApiAvailable = true;
            }
          }
        } catch (e) {}

        // Use fallback search only when the local prefix source is unavailable.
        if (!prefixApiAvailable) {
          const results = await window.AppAPI.searchAll(query);
          const prefix = query.toLocaleLowerCase();
          combined = (results.data || []).filter(item =>
            typeof item.title === 'string' && item.title.trim().toLocaleLowerCase().startsWith(prefix)
          );
        }

        const prefix = query.toLocaleLowerCase();
        combined = combined.filter(item =>
          typeof item.title === 'string' && item.title.trim().toLocaleLowerCase().startsWith(prefix)
        ).slice(0, 6);

        if (currentRequest !== requestVersion) return;

        if (combined.length === 0) {
          dropdown.innerHTML = `
            <div style="padding: 16px; font-size: 13px; color: var(--text-dim);" role="status">
              <strong style="display: block; color: var(--text-bright); margin-bottom: 4px;">NOT FOUND</strong>
              No title starts with "${window.AppUI.escapeHtml(query)}".
            </div>
          `;
          return;
        }

        let html = '';
        combined.slice(0, 6).forEach(item => {
          const catLabel = (item.category || item.type || 'media').toUpperCase();
          const fallback = window.AppUI.getPlaceholderPoster(item.title, catLabel);
          html += `
            <a href="title.php?type=${encodeURIComponent(item.type)}&id=${encodeURIComponent(item.id)}" class="dropdown-item">
              <img 
                src="${item.poster || fallback}" 
                alt="" 
                class="dropdown-thumb" 
                onerror="this.onerror=null; this.src='${fallback}';"
              />
              <div class="dropdown-info">
                <div class="dropdown-title">${window.AppUI.escapeHtml(item.title)}</div>
                <div class="dropdown-meta">${catLabel} (${item.year || '—'}) - SCORE ${item.score || '—'}</div>
              </div>
            </a>
          `;
        });

        html += `
          <a href="search.php?q=${encodeURIComponent(query)}&prefix=1" class="dropdown-item" style="background-color: var(--bg-surface); justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--accent);">
            VIEW ALL RESULTS IN FAN HUB PLUS ${window.AppUI.icons.arrowRight}
          </a>
        `;

        dropdown.innerHTML = html;
      } catch (err) {
        if (currentRequest !== requestVersion) return;
        console.error('Hero live search error', err);
        dropdown.innerHTML = `
          <div style="padding: 14px; font-size: 12px; color: var(--text-dim);">
            Live lookup limited. Press enter to search catalog.
          </div>
        `;
      }
    }, 250);
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (!form.contains(e.target)) {
      dropdown.classList.remove('active');
    }
  });

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = input.value.trim();
      if (q) {
        window.location.href = `search.php?q=${encodeURIComponent(q)}&prefix=1`;
      }
    });
  }
}

// Hero Spotlight Title (Pulls high-rated feature)
async function loadHeroSpotlight() {
  const container = document.getElementById('heroSpotlight');
  if (!container) return;

  try {
    const spotlightItem = window.FALLBACK_DATA.anime.find(a => a.id === 'anime-5114' || a.id === 5114) || window.FALLBACK_DATA.anime[0];
    const isSaved = window.WatchlistService.has(spotlightItem.type, spotlightItem.id);
    const catLabel = (spotlightItem.category || spotlightItem.type || 'ANIME').toUpperCase();
    const fallback = window.AppUI.getPlaceholderPoster(spotlightItem.title, catLabel);

    container.innerHTML = `
      <div class="spotlight-media">
        <img 
          src="${spotlightItem.backdrop || spotlightItem.poster || fallback}" 
          alt="${window.AppUI.escapeHtml(spotlightItem.title)}" 
          onerror="this.onerror=null; this.src='${fallback}';"
        />
        <span class="badge badge-accent spotlight-badge">ARCHIVE SPOTLIGHT</span>
      </div>
      <div class="spotlight-body">
        <div class="spotlight-header">
          <div class="spotlight-type">${catLabel} ARCHIVE / ${spotlightItem.year}</div>
          <h3 class="spotlight-title">
            <a href="title.php?type=${encodeURIComponent(spotlightItem.type)}&id=${encodeURIComponent(spotlightItem.id)}">
              ${window.AppUI.escapeHtml(spotlightItem.title)}
            </a>
          </h3>
          <div class="card-meta-line" style="margin-bottom: 12px;">
            <span class="badge badge-score">SCORE ${spotlightItem.score}</span>
            <span>${spotlightItem.status}</span>
            <span>${spotlightItem.studio || 'Studio'}</span>
          </div>
          <p class="spotlight-synopsis">${window.AppUI.escapeHtml(spotlightItem.synopsis)}</p>
        </div>
        <div class="spotlight-actions">
          <a href="title.php?type=${encodeURIComponent(spotlightItem.type)}&id=${encodeURIComponent(spotlightItem.id)}" class="btn btn-primary" style="flex: 1;">
            EXPLORE TITLE &amp; TRAILER
          </a>
          <button 
            type="button" 
            class="btn btn-secondary btn-watchlist ${isSaved ? 'in-watchlist' : ''}" 
            data-action="watchlist-toggle" 
            data-id="${spotlightItem.id}" 
            data-type="${spotlightItem.type}"
            data-category="${spotlightItem.category || 'anime'}">
            ${window.AppUI.icons.bookmark}
          </button>
        </div>
      </div>
    `;
  } catch (err) {
    console.error('Error rendering hero spotlight', err);
  }
}

// Section 1: Japanese Animation
async function loadAnimeSection() {
  const grid = document.getElementById('homeAnimeGrid');
  if (!grid) return;

  grid.innerHTML = window.AppUI.renderSkeletonCards(7);

  try {
    const response = await fetch('api/items.php?category=anime&sort=recent_desc&per_page=7');
    if (!response.ok) throw new Error(`Anime catalog request failed: ${response.status}`);
    const res = await response.json();
    if (!res.success) throw new Error('Anime catalog data is unavailable.');
    const items = res.data || [];

    if (items.length === 0) {
      grid.innerHTML = `<p style="color: var(--text-dim);">No anime records retrieved.</p>`;
      return;
    }

    grid.innerHTML = items.map(item => window.AppUI.renderCard(item)).join('');
  } catch (err) {
    console.error('Error loading home anime section', err);
    grid.innerHTML = `<p style="color: var(--text-dim);">Anime catalog is temporarily unavailable.</p>`;
  }
}

// Section 2: Cinema & Feature Films
async function loadCinemaSection() {
  const grid = document.getElementById('homeCinemaGrid');
  if (!grid) return;

  grid.innerHTML = window.AppUI.renderSkeletonCards(7);

  try {
    const response = await fetch('api/items.php?category=movies&sort=recent_desc&per_page=7');
    if (!response.ok) throw new Error(`Local cinema catalog request failed: ${response.status}`);
    const localResponse = await response.json();
    if (!localResponse.success) throw new Error('Local cinema catalog data is unavailable.');

    const items = localResponse.data || [];
    if (items.length < 7) {
      const trending = await window.AppAPI.getTrendingMovies(1);
      const localIds = new Set(items.map(item => String(item.id)));
      items.push(...trending.data.filter(item => !localIds.has(String(item.id))).slice(0, 7 - items.length));
    }

    if (items.length === 0) {
      grid.innerHTML = `<p style="color: var(--text-dim);">No cinema records retrieved.</p>`;
      return;
    }

    grid.innerHTML = items.map(item => window.AppUI.renderCard(item)).join('');
  } catch (err) {
    console.error('Error loading home cinema section', err);
    grid.innerHTML = window.FALLBACK_DATA.movies.slice(0, 7).map(item => window.AppUI.renderCard(item)).join('');
  }
}
