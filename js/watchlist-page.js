/**
 * Watchlist Page Controller
 * Displays saved items with all 8 category filters and management actions.
 * Syncs seamlessly between local client storage and MySQL user watchlist.
 */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('watchlistContainer');
  const countAllEl = document.getElementById('countAll');
  const filterTabs = document.querySelectorAll('[data-watchlist-filter]');
  const clearBtn = document.getElementById('clearWatchlistBtn');

  let activeFilter = 'all';

  function render() {
    if (!container) return;

    const items = window.WatchlistService.getAll();
    if (countAllEl) countAllEl.textContent = items.length;

    let filtered = items;
    if (activeFilter !== 'all') {
      const f = activeFilter.toLowerCase();
      filtered = items.filter(i => {
        const c = (i.category || i.type || '').toLowerCase();
        if (f === 'movies' || f === 'movie') return c === 'movie' || c === 'movies';
        if (f === 'tvshows' || f === 'tv') return c === 'tv' || c === 'tvshows';
        return c === f;
      });
    }

    if (filtered.length === 0) {
      container.innerHTML = `
        <div class="watchlist-empty-state">
          <h3>NO TITLES FOUND IN THIS SELECTION</h3>
          <p>
            You have not pinned any titles in this category yet. Explore the ledger and click the bookmark button on any title card to save it.
          </p>
          <a href="browse.php" class="btn btn-primary">BROWSE CATALOG</a>
        </div>
      `;
      return;
    }

    container.innerHTML = `
      <div class="grid-catalog-4">
        ${filtered.map(item => window.AppUI.renderCard(item)).join('')}
      </div>
    `;
  }

  // Filter tabs
  filterTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      filterTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      activeFilter = tab.getAttribute('data-watchlist-filter');
      render();
    });
  });

  // Clear all button
  if (clearBtn) {
    clearBtn.addEventListener('click', async () => {
      if (window.WatchlistService.count() === 0) return;
      if (confirm('Are you sure you want to remove all saved titles from your ledger watchlist?')) {
        window.WatchlistService.clear();
        try {
          await fetch('api/watchlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear' })
          });
        } catch (e) {}
        render();
        window.AppUI.showToast('Watchlist cleared.');
      }
    });
  }

  // Re-render when items change
  window.addEventListener('watchlist-updated', () => {
    render();
  });

  render();
});
