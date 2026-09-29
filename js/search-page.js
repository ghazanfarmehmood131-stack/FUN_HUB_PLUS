/**
 * Search Page Controller
 * Handles dedicated search querying across all 8 categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 */

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('searchPageForm');
  const input = document.getElementById('searchPageInput');
  const typeTabs = document.querySelectorAll('[data-search-type]');
  const resultsContainer = document.getElementById('searchResultsContainer');
  const summaryBar = document.getElementById('searchSummary');

  let currentCategory = 'all';
  let lastQuery = '';
  let debounceTimer = null;
  let searchRequestVersion = 0;

  // Check initial query from URL
  const params = new URLSearchParams(window.location.search);
  const initialQ = params.get('q');
  const initialCat = params.get('category');
  const prefixOnly = params.get('prefix') === '1';
  if (initialCat) currentCategory = initialCat.toLowerCase();

  // Match category tabs
  typeTabs.forEach(tab => {
    if (tab.getAttribute('data-search-type') === currentCategory) {
      tab.classList.add('active');
    } else {
      tab.classList.remove('active');
    }
  });

  if (initialQ && input) {
    input.value = initialQ;
    performSearch(initialQ);
  }

  // Category tab switching
  typeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      typeTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      currentCategory = tab.getAttribute('data-search-type');
      if (input && input.value.trim().length > 0) {
        performSearch(input.value.trim());
      }
    });
  });

  // Debounced input search
  if (input) {
    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const query = input.value.trim();
      if (query.length < (prefixOnly ? 1 : 2)) {
        if (resultsContainer) {
          resultsContainer.innerHTML = `
            <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; border: 1px solid var(--border-subtle); background: var(--bg-surface);">
              <h3 style="font-size: 22px; margin-bottom: 8px;">TYPE TO QUERY THE ARCHIVE</h3>
              <p style="color: var(--text-dim); font-size: 14px;">Enter a title, developer, studio, author, artist, or keyword above.</p>
            </div>
          `;
        }
        if (summaryBar) summaryBar.textContent = 'Awaiting query...';
        return;
      }

      debounceTimer = setTimeout(() => {
        performSearch(query);
      }, 300);
    });
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      if (input) performSearch(input.value.trim());
    });
  }

  async function performSearch(query) {
    if (!query) return;
    lastQuery = query;
    const requestVersion = ++searchRequestVersion;

    // Show skeleton loaders immediately
    if (resultsContainer) {
      resultsContainer.innerHTML = window.AppUI.renderSkeletonCards(8);
    }
    if (summaryBar) {
      summaryBar.textContent = `SEARCHING FOR "${query.toUpperCase()}"...`;
    }

    try {
      let items = [];
      if (prefixOnly) {
        let prefixApiAvailable = false;
        try {
          const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}&category=${encodeURIComponent(currentCategory)}&limit=24&prefix=1`);
          if (response.ok) {
            const payload = await response.json();
            if (payload.success && Array.isArray(payload.data)) {
              items = payload.data;
              prefixApiAvailable = true;
            }
          }
        } catch (e) {}

        if (!prefixApiAvailable) {
          const fallback = await window.AppAPI.searchAll(query, currentCategory);
          const prefix = query.toLocaleLowerCase();
          items = (fallback.data || []).filter(item =>
            typeof item.title === 'string' && item.title.trim().toLocaleLowerCase().startsWith(prefix)
          );
        }
      } else {
        const res = await window.AppAPI.searchAll(query, currentCategory);
        items = res.data || [];
      }
      if (requestVersion !== searchRequestVersion) return;

      // Update URL without page reload
      const newUrl = new URL(window.location);
      newUrl.searchParams.set('q', query);
      newUrl.searchParams.set('category', currentCategory);
      if (prefixOnly) newUrl.searchParams.set('prefix', '1');
      window.history.replaceState({}, '', newUrl);

      if (items.length === 0) {
        if (resultsContainer) {
          resultsContainer.innerHTML = `
            <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; border: 1px solid var(--border-subtle); background: var(--bg-surface);">
              <h3 style="font-size: 24px; margin-bottom: 8px;">${prefixOnly ? 'NOT FOUND' : 'NO TITLES FOUND'}</h3>
              <p style="color: var(--text-dim); font-size: 15px; max-width: 500px; margin: 0 auto 20px auto;">
                ${prefixOnly ? `No title starts with "${window.AppUI.escapeHtml(query)}" in ${currentCategory.toUpperCase()}.` : `No archival matches found for "${window.AppUI.escapeHtml(query)}" in ${currentCategory.toUpperCase()}. Try switching category filters or check the Browse page.`}
              </p>
              <a href="browse.php" class="btn btn-secondary">OPEN BROWSE CATALOG</a>
            </div>
          `;
        }
        if (summaryBar) {
          summaryBar.textContent = `0 results found for "${query.toUpperCase()}"`;
        }
        return;
      }

      if (resultsContainer) {
        resultsContainer.innerHTML = items.map(item => window.AppUI.renderCard(item)).join('');
      }

      if (summaryBar) {
        const catText = currentCategory === 'all' ? 'ALL CATEGORIES' : currentCategory.toUpperCase();
        const matchText = prefixOnly ? 'starting with' : 'matching';
        summaryBar.textContent = `Found ${items.length} titles ${matchText} "${query.toUpperCase()}" in ${catText}`;
      }
    } catch (err) {
      console.error('Search error', err);
      if (resultsContainer) {
        resultsContainer.innerHTML = `
          <div style="grid-column: 1 / -1; padding: 40px; text-align: center; border: 1px solid var(--border-bold);">
            <h3>SEARCH QUERY ERROR</h3>
            <p style="color: var(--text-dim); margin-top: 8px;">An error occurred while querying the archive. Please try again.</p>
          </div>
        `;
      }
    }
  }
});
