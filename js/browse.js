/**
 * Browse Catalog Controller
 * Handles filtering by all 8 categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software, and All.
 */

document.addEventListener('DOMContentLoaded', () => {
  let currentCategory = 'all';
  let currentGenre = 'all';
  let currentSort = 'score_desc';
  const perPage = 100;
  let currentPage = 1;

  // Elements
  const typeTabs = document.querySelectorAll('[data-filter-type]');
  const genreSelect = document.getElementById('genreFilter');
  const sortSelect = document.getElementById('sortFilter');
  const catalogGrid = document.getElementById('browseGrid');
  const paginationInfo = document.getElementById('paginationInfo');
  const paginationPages = document.getElementById('paginationPages');
  const btnPrev = document.getElementById('btnPrevPage');
  const btnNext = document.getElementById('btnNextPage');
  const resultCount = document.getElementById('browseResultCount');
  // Check URL query parameters for pre-selected category, type, or genre
  const params = new URLSearchParams(window.location.search);
  currentPage = Math.max(1, parseInt(params.get('page') || '1', 10) || 1);
  const urlCat = params.get('category') || params.get('type');
  if (urlCat) currentCategory = urlCat.toLowerCase();
  if (params.get('genre')) currentGenre = params.get('genre');
  if (genreSelect) currentGenre = genreSelect.value;
  if (sortSelect) currentSort = sortSelect.value;

  // Match category aliases
  if (currentCategory === 'movie') currentCategory = 'movies';
  if (currentCategory === 'tv') currentCategory = 'tvshows';

  // Set initial UI state from URL params
  typeTabs.forEach(tab => {
    if (tab.getAttribute('data-filter-type') === currentCategory) {
      tab.classList.add('active');
    } else {
      tab.classList.remove('active');
    }
  });

  if (genreSelect && currentGenre !== 'all') {
    genreSelect.value = currentGenre;
  }

  // Event Listeners
  typeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      typeTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      currentCategory = tab.getAttribute('data-filter-type');
      currentPage = 1;
      
      // Update URL without page reload
      const newUrl = new URL(window.location);
      newUrl.searchParams.set('category', currentCategory);
      newUrl.searchParams.delete('page');
      window.history.replaceState({}, '', newUrl);

      fetchAndRender();
    });
  });

  if (genreSelect) {
    genreSelect.addEventListener('change', () => {
      currentGenre = genreSelect.value;
      currentPage = 1;
      fetchAndRender();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      currentSort = sortSelect.value;
      currentPage = 1;
      fetchAndRender();
    });
  }

  if (btnPrev) {
    btnPrev.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        fetchAndRender();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  if (btnNext) {
    btnNext.addEventListener('click', () => {
      currentPage++;
      fetchAndRender();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  if (paginationPages) {
    paginationPages.addEventListener('click', event => {
      const pageButton = event.target.closest('[data-page]');
      if (!pageButton) return;
      const page = parseInt(pageButton.dataset.page, 10);
      if (!Number.isInteger(page) || page === currentPage) return;
      currentPage = page;
      fetchAndRender();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function renderPagination(total) {
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (paginationInfo) paginationInfo.textContent = `PAGE ${currentPage} OF ${totalPages}`;
    if (btnPrev) {
      btnPrev.disabled = currentPage <= 1;
      btnPrev.style.opacity = currentPage <= 1 ? '0.5' : '1';
    }
    if (btnNext) {
      btnNext.disabled = currentPage >= totalPages;
      btnNext.style.opacity = currentPage >= totalPages ? '0.5' : '1';
    }
    if (!paginationPages) return;

    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    const pages = [];
    for (let page = startPage; page <= endPage; page++) pages.push(page);
    paginationPages.innerHTML = pages.map(page => `
      <button type="button" class="btn btn-secondary btn-sm pagination-page${page === currentPage ? ' active' : ''}" data-page="${page}"${page === currentPage ? ' aria-current="page"' : ''}>${page}</button>
    `).join('');
  }

  async function fetchAndRender() {
    if (!catalogGrid) return;

    // Render 8 skeleton cards immediately
    catalogGrid.innerHTML = window.AppUI.renderSkeletonCards(8);
    if (resultCount) resultCount.textContent = 'Loading records...';

    try {
      const query = new URLSearchParams({
        category: currentCategory,
        genre: currentGenre,
        sort: currentSort,
        page: currentPage,
        per_page: perPage
      });
      const response = await fetch(`api/items.php?${query.toString()}`);
      if (!response.ok) throw new Error(`Catalog request failed: ${response.status}`);
      const res = await response.json();
      if (!res.success) throw new Error('Catalog data is unavailable.');
      const items = res.data || [];
      const totalPages = Math.max(1, Math.ceil((res.total || 0) / perPage));
      if (currentPage > totalPages) {
        currentPage = totalPages;
        fetchAndRender();
        return;
      }

      // Render cards or empty message
      if (items.length === 0) {
        catalogGrid.innerHTML = `
          <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; background: var(--bg-surface); border: 1px solid var(--border-subtle);">
            <h3 style="font-size: 22px; margin-bottom: 8px;">NO TITLES FOUND IN THIS CATEGORY</h3>
            <p style="color: var(--text-dim); font-size: 14px;">Try selecting another category or loosening your genre filter.</p>
          </div>
        `;
        if (resultCount) resultCount.textContent = '0 records found';
        renderPagination(res.total || 0);
        return;
      }

      catalogGrid.innerHTML = items.map(item => window.AppUI.renderCard(item)).join('');
      if (resultCount) {
        const label = currentCategory.toUpperCase();
        resultCount.textContent = `Showing ${items.length} of ${res.total} titles in ${label === 'ALL' ? 'ALL CATEGORIES' : label}`;
      }
      renderPagination(res.total || 0);

      const newUrl = new URL(window.location);
      if (currentPage > 1) newUrl.searchParams.set('page', currentPage);
      else newUrl.searchParams.delete('page');
      window.history.replaceState({}, '', newUrl);
    } catch (err) {
      console.error('Browse fetching error', err);
      catalogGrid.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 40px; text-align: center; border: 1px solid var(--border-bold);">
          <h3>ERROR ACCESSING ARCHIVE</h3>
          <p style="color: var(--text-dim); margin-top: 8px;">Could not retrieve catalog entries. Check connection or try reloading.</p>
        </div>
      `;
    }
  }

  fetchAndRender();
});
