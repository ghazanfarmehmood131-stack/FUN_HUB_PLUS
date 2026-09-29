/**
 * UI Utilities & Shared Components
 * Handles toast alerts, modal triggers, card renderers, skeletons, placeholders, and icons.
 */

window.AppUI = (function() {
  // Pure custom SVG icons (Strictly no Lucide icons)
  const icons = {
    search: `<svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/></svg>`,
    bookmark: `<svg class="icon" viewBox="0 0 24 24"><polygon points="6 3 18 3 18 21 12 17 6 21 6 3"/></svg>`,
    play: `<svg class="icon" viewBox="0 0 24 24"><polygon points="7 4 19 12 7 20 7 4"/></svg>`,
    close: `<svg class="icon" viewBox="0 0 24 24"><line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/></svg>`,
    arrowRight: `<svg class="icon" viewBox="0 0 24 24"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="14 6 20 12 14 18"/></svg>`,
    filter: `<svg class="icon" viewBox="0 0 24 24"><polygon points="3 4 21 4 14 13 14 20 10 18 10 13 3 4"/></svg>`,
    film: `<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="4" x2="9" y2="20"/><line x1="15" y1="4" x2="15" y2="20"/></svg>`,
    tv: `<svg class="icon" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13"/><polyline points="8 3 12 7 16 3"/></svg>`,
    trash: `<svg class="icon" viewBox="0 0 24 24"><polyline points="4 7 20 7"/><rect x="6" y="7" width="12" height="14"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>`
  };

  /**
   * Generates a high-quality SVG placeholder data URI in the warm charcoal aesthetic
   */
  function getPlaceholderPoster(title = 'ARCHIVE LEDGER', category = 'MEDIA') {
    const safeTitle = (title || 'ARCHIVE LEDGER').slice(0, 22).replace(/[<>&"']/g, '');
    const safeCat = (category || 'MEDIA').toUpperCase().slice(0, 16).replace(/[<>&"']/g, '');

    const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 600" width="100%" height="100%">
        <defs>
          <linearGradient id="bgG" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#1e1e24"/>
            <stop offset="100%" stop-color="#141416"/>
          </linearGradient>
          <pattern id="gridP" width="20" height="20" patternUnits="userSpaceOnUse">
            <line x1="0" y1="0" x2="20" y2="0" stroke="#26262d" stroke-width="0.75"/>
            <line x1="0" y1="0" x2="0" y2="20" stroke="#26262d" stroke-width="0.75"/>
          </pattern>
        </defs>
        <rect width="400" height="600" fill="url(#bgG)"/>
        <rect width="400" height="600" fill="url(#gridP)" opacity="0.6"/>
        <rect x="20" y="20" width="360" height="560" fill="none" stroke="#2e2e38" stroke-width="1"/>
        <rect x="32" y="32" width="110" height="26" fill="#b94337"/>
        <text x="87" y="49" fill="#ffffff" font-family="sans-serif" font-size="11" font-weight="bold" text-anchor="middle" letter-spacing="1.5">${safeCat}</text>
        <circle cx="200" cy="270" r="44" fill="#18181b" stroke="#363640" stroke-width="1"/>
        <polygon points="192,252 218,270 192,288" fill="#b94337"/>
        <text x="200" y="370" fill="#f4f4f5" font-family="sans-serif" font-size="20" font-weight="bold" text-anchor="middle">${safeTitle}</text>
        <text x="200" y="405" fill="#a1a1aa" font-family="sans-serif" font-size="12" text-anchor="middle" letter-spacing="1">ARCHIVE RECORD</text>
        <line x1="160" y1="430" x2="240" y2="430" stroke="#363640" stroke-width="1"/>
      </svg>
    `.trim();

    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  // Toast System
  function showToast(message, isAccent = false) {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${isAccent ? 'accent' : ''}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 200);
    }, 3200);
  }

  // Skeletons
  function renderSkeletonCards(count = 8) {
    let html = '';
    for (let i = 0; i < count; i++) {
      html += `
        <div class="skeleton-card">
          <div class="skeleton-poster"></div>
          <div class="skeleton-body">
            <div class="skeleton-line w-1-4"></div>
            <div class="skeleton-line w-full h-title"></div>
            <div class="skeleton-line w-3-4"></div>
            <div class="skeleton-line w-1-2" style="margin-top: auto;"></div>
          </div>
        </div>
      `;
    }
    return html;
  }

  function renderSkeletonRows(count = 4) {
    let html = '';
    for (let i = 0; i < count; i++) {
      html += `
        <div class="skeleton-row">
          <div class="skeleton-row-thumb"></div>
          <div class="skeleton-row-details">
            <div class="skeleton-line w-1-4"></div>
            <div class="skeleton-line w-3-4 h-title"></div>
            <div class="skeleton-line w-full"></div>
            <div class="skeleton-line w-1-2"></div>
          </div>
        </div>
      `;
    }
    return html;
  }

  // Media Card Renderer (Strictly 4 or 2 columns, never 3)
  function renderCard(item) {
    const isSaved = window.WatchlistService.has(item.type, item.id);
    const categoryLabels = {
      anime: 'ANIME',
      gaming: 'GAMING',
      movie: 'FILM',
      movies: 'FILM',
      tvshows: 'TV SHOW',
      tv: 'TV SHOW',
      kpop: 'K-POP',
      comics: 'COMIC',
      manga: 'MANGA',
      software: 'SOFTWARE'
    };
    const catKey = (item.category || item.type || 'anime').toLowerCase();
    const typeLabel = categoryLabels[catKey] || catKey.toUpperCase();
    const fallbackPoster = getPlaceholderPoster(item.title, typeLabel);
    const posterUrl = item.poster || fallbackPoster;
    const genresText = (item.genres || []).slice(0, 3).join(', ');

    return `
      <article class="media-card" data-id="${item.id}" data-type="${item.type}" data-category="${catKey}">
        <div class="media-poster-wrap">
          <img 
            src="${posterUrl}" 
            alt="${escapeHtml(item.title)}" 
            class="media-poster" 
            loading="lazy" 
            onerror="this.onerror=null; this.src='${fallbackPoster}';"
          />
          <div class="card-top-badges">
            <span class="badge badge-type">${typeLabel}</span>
            <span class="badge badge-score">SCORE ${item.score || '—'}</span>
          </div>
        </div>
        <div class="card-content">
          <div class="card-meta-line">
            <span>${item.year || '—'}</span>
            <span>-</span>
            <span>${item.status || 'Archived'}</span>
          </div>
          <h4 class="card-title">
            <a href="title.php?type=${encodeURIComponent(item.type)}&id=${encodeURIComponent(item.id)}">${escapeHtml(item.title)}</a>
          </h4>
          <div class="card-genres">${genresText || 'General Ledger'}</div>
        </div>
        <div class="card-actions">
          <a href="title.php?type=${encodeURIComponent(item.type)}&id=${encodeURIComponent(item.id)}" class="btn btn-secondary btn-sm" style="flex: 1;">DETAILS</a>
          <button 
            type="button" 
            class="btn btn-sm btn-watchlist ${isSaved ? 'in-watchlist' : ''}" 
            data-action="watchlist-toggle" 
            data-id="${item.id}" 
            data-type="${item.type}"
            data-category="${catKey}"
            title="${isSaved ? 'Remove from watchlist' : 'Add to watchlist'}">
            ${icons.bookmark}
          </button>
        </div>
      </article>
    `;
  }

  // Row Card Renderer (used in Watchlist and search lists)
  function renderRowCard(item) {
    const isSaved = window.WatchlistService.has(item.type, item.id);
    const categoryLabels = {
      anime: 'ANIME',
      gaming: 'GAMING',
      movie: 'FILM',
      movies: 'FILM',
      tvshows: 'TV SHOW',
      tv: 'TV SHOW',
      kpop: 'K-POP',
      comics: 'COMIC',
      manga: 'MANGA',
      software: 'SOFTWARE'
    };
    const catKey = (item.category || item.type || 'anime').toLowerCase();
    const typeLabel = categoryLabels[catKey] || catKey.toUpperCase();
    const fallbackPoster = getPlaceholderPoster(item.title, typeLabel);
    const posterUrl = item.poster || fallbackPoster;

    return `
      <article class="media-row-card" data-id="${item.id}" data-type="${item.type}" data-category="${catKey}">
        <div class="media-row-poster">
          <img 
            src="${posterUrl}" 
            alt="${escapeHtml(item.title)}" 
            loading="lazy" 
            onerror="this.onerror=null; this.src='${fallbackPoster}';"
          />
        </div>
        <div class="media-row-content">
          <div>
            <div class="card-meta-line">
              <span class="badge badge-type">${typeLabel}</span>
              <span>${item.year || '—'}</span>
              <span>-</span>
              <span>SCORE ${item.score || '—'}</span>
            </div>
            <h4 class="media-row-title">
              <a href="title.php?type=${encodeURIComponent(item.type)}&id=${encodeURIComponent(item.id)}">${escapeHtml(item.title)}</a>
            </h4>
            <p class="media-row-synopsis">${escapeHtml(item.synopsis || '')}</p>
          </div>
          <div class="media-row-footer">
            <div style="font-size: 12px; color: var(--text-faint);">
              ${(item.genres || []).join(' / ')}
            </div>
            <div style="display: flex; gap: 8px;">
              <a href="title.php?type=${encodeURIComponent(item.type)}&id=${encodeURIComponent(item.id)}" class="btn btn-secondary btn-sm">VIEW TITLE</a>
              <button 
                type="button" 
                class="btn btn-sm btn-watchlist ${isSaved ? 'in-watchlist' : ''}" 
                data-action="watchlist-toggle" 
                data-id="${item.id}" 
                data-type="${item.type}"
                data-category="${catKey}">
                ${isSaved ? 'SAVED' : 'SAVE'}
              </button>
            </div>
          </div>
        </div>
      </article>
    `;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // Global Watchlist Toggle Delegator
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-action="watchlist-toggle"]');
    if (!btn) return;

    e.preventDefault();
    const id = btn.getAttribute('data-id');
    const type = btn.getAttribute('data-type');
    const category = btn.getAttribute('data-category') || 'anime';
    if (!id || !type) return;

    let item = null;
    if (window.CURRENT_DETAIL_ITEM && String(window.CURRENT_DETAIL_ITEM.id) === String(id)) {
      item = window.CURRENT_DETAIL_ITEM;
    } else {
      const card = btn.closest('.media-card, .media-row-card');
      const title = card ? card.querySelector('.card-title, .media-row-title')?.textContent.trim() : 'Title';
      const poster = card ? card.querySelector('img')?.getAttribute('src') : '';
      item = { id, type, category, title, poster, genres: [] };
    }

    const added = window.WatchlistService.toggle(item);
    if (added) {
      btn.classList.add('in-watchlist');
      showToast(`Added "${item.title}" to watchlist.`, true);
    } else {
      btn.classList.remove('in-watchlist');
      showToast(`Removed "${item.title}" from watchlist.`);
    }

    // Sync with backend API if user is logged in
    try {
      fetch('api/watchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: added ? 'add' : 'remove',
          id: item.id,
          type: item.type,
          category: item.category || category,
          title: item.title,
          year: item.year || null,
          score: item.score || null,
          poster: item.poster || null
        })
      });
    } catch (err) {
      console.warn('Backend watchlist sync error', err);
    }

    // Update all matching buttons on current page
    document.querySelectorAll(`[data-action="watchlist-toggle"][data-id="${id}"][data-type="${type}"]`).forEach(b => {
      if (added) {
        b.classList.add('in-watchlist');
      } else {
        b.classList.remove('in-watchlist');
      }
    });
  });

  // Mobile Navigation Setup
  function initMobileNav() {
    const toggle = document.querySelector('.mobile-menu-toggle');
    const nav = document.querySelector('.primary-nav');
    if (toggle && nav) {
      toggle.addEventListener('click', () => {
        nav.classList.toggle('mobile-active');
      });
    }
  }

  // TMDB Modal Manager
  function initTmdbModal() {
    const modal = document.getElementById('tmdbModal');
    const trigger = document.getElementById('tmdbKeyTrigger') || document.getElementById('footerTmdbTrigger');
    const closeBtn = document.getElementById('closeTmdbModal');
    const saveBtn = document.getElementById('saveTmdbKeyBtn');
    const clearBtn = document.getElementById('clearTmdbKeyBtn');
    const input = document.getElementById('tmdbKeyInput');
    const statusDot = document.getElementById('tmdbStatusDot');
    const statusText = document.getElementById('tmdbStatusText');

    function updateIndicator() {
      const hasKey = window.APP_CONFIG.hasTmdbKey();
      if (statusDot) {
        if (hasKey) {
          statusDot.classList.add('active');
        } else {
          statusDot.classList.remove('active');
        }
      }
      if (statusText) {
        statusText.textContent = hasKey ? 'TMDB LIVE CONNECTED' : 'TMDB ACTIVE CATALOG';
      }
    }

    updateIndicator();

    if (trigger && modal) {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        if (input) input.value = window.APP_CONFIG.getTmdbKey();
        modal.classList.add('active');
      });
    }

    if (closeBtn && modal) {
      closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
      });
    }

    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
      });
    }

    if (saveBtn && input) {
      saveBtn.addEventListener('click', () => {
        const val = input.value.trim();
        window.APP_CONFIG.setTmdbKey(val);
        updateIndicator();
        modal.classList.remove('active');
        showToast(val ? 'TMDB API key saved. Live cinema feeds active.' : 'TMDB key cleared. Using local ledger catalog.', true);
        setTimeout(() => location.reload(), 600);
      });
    }

    if (clearBtn && input) {
      clearBtn.addEventListener('click', () => {
        input.value = '';
        window.APP_CONFIG.setTmdbKey('');
        updateIndicator();
        modal.classList.remove('active');
        showToast('TMDB key cleared. Using local ledger catalog.');
        setTimeout(() => location.reload(), 600);
      });
    }
  }

  // Keyboard shortcut listener ('/' focuses search)
  function initKeyboardShortcuts() {
    window.addEventListener('keydown', (e) => {
      if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        const searchInput = document.querySelector('.hero-search-input, .search-bar-composite input');
        if (searchInput) {
          searchInput.focus();
        } else {
          window.location.href = 'search.php';
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initTmdbModal();
    initKeyboardShortcuts();
  });

  return {
    icons,
    showToast,
    getPlaceholderPoster,
    renderCard,
    renderRowCard,
    renderSkeletonCards,
    renderSkeletonRows,
    escapeHtml
  };
})();
