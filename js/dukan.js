/**
 * ARCHIVE Ledger - Dukan (Merchandise Showcase) Logic
 * Curatorial exhibit inspection, category tabs, rarity filtering, and collector shelf.
 * STRICTLY NON-COMMERCIAL: View-only catalog.
 */

(function() {
  'use strict';

  let allMerch = [];
  let currentCategory = 'all';
  let currentRarity = 'all';
  let currentSort = 'default';
  let savedFavorites = [];

  // DOM elements
  const searchInput = document.getElementById('dukanSearchInput');
  const categoryTabs = document.getElementById('dukanCategoryTabs');
  const raritySelect = document.getElementById('rarityFilter');
  const sortSelect = document.getElementById('dukanSort');
  const merchGrid = document.getElementById('merchGrid');
  const emptyState = document.getElementById('dukanEmptyState');
  const counterText = document.getElementById('merchCounterText');
  const favShelfCount = document.getElementById('favShelfCount');
  const resetBtn = document.getElementById('resetDukanBtn');

  // Modal elements
  const modal = document.getElementById('dukanModal');
  const modalContent = document.getElementById('dukanModalContent');
  const closeModalBtn = document.getElementById('closeDukanModal');

  /**
   * Load Favorites from LocalStorage
   */
  function loadSavedFavorites() {
    try {
      const saved = localStorage.getItem('archive_merch_favs');
      savedFavorites = saved ? JSON.parse(saved) : [];
    } catch (e) {
      savedFavorites = [];
    }
    updateFavoritesBadge();
  }

  function updateFavoritesBadge() {
    if (favShelfCount) {
      favShelfCount.textContent = savedFavorites.length;
    }
  }

  /**
   * Fetch Merchandise Data
   */
  async function loadMerchData() {
    try {
      const res = await fetch('api/merchandise.php');
      const data = await res.json();
      if (data && data.success && Array.isArray(data.data)) {
        allMerch = data.data;
      }
    } catch (e) {
      console.warn('API error, extracting from DOM:', e);
      allMerch = extractMerchFromDom();
    }
    applyFilters();
  }

  function extractMerchFromDom() {
    const cards = document.querySelectorAll('.merch-card');
    const list = [];
    cards.forEach(card => {
      const id = parseInt(card.dataset.id, 10);
      const category = card.dataset.category || 'toys';
      const rarity_tier = card.dataset.rarity || 'Collector Grade';
      const franchise = card.dataset.franchise || '';
      const name = card.querySelector('.merch-title')?.textContent.trim() || '';
      const desc = card.querySelector('.merch-desc')?.textContent.trim() || '';
      const img = card.querySelector('.merch-img')?.src || '';
      list.push({
        id, category, rarity_tier, franchise, name, description: desc,
        image_url: img, scale_or_size: 'Standard', material: 'Curated Alloy & Polymer',
        manufacturer: 'Artisanal Studio', year_released: 2024, accession_no: 'ARC-MC-' + id
      });
    });
    return list;
  }

  /**
   * Filter and Render Exhibits
   */
  function applyFilters() {
    const term = (searchInput?.value || '').toLowerCase().trim();

    let filtered = allMerch.filter(item => {
      // Favorites tab check
      if (currentCategory === 'favorites') {
        return savedFavorites.includes(item.id);
      }
      // Category check
      if (currentCategory !== 'all' && item.category !== currentCategory) {
        return false;
      }
      // Rarity check
      if (currentRarity !== 'all' && item.rarity_tier !== currentRarity) {
        return false;
      }
      // Search term
      if (term) {
        const text = `${item.name} ${item.franchise} ${item.item_type || ''} ${item.material || ''} ${item.manufacturer || ''} ${item.description}`.toLowerCase();
        if (!text.includes(term)) return false;
      }
      return true;
    });

    // Sorting
    if (currentSort === 'year_desc') {
      filtered.sort((a, b) => (b.year_released || 0) - (a.year_released || 0));
    } else if (currentSort === 'year_asc') {
      filtered.sort((a, b) => (a.year_released || 0) - (b.year_released || 0));
    } else if (currentSort === 'name_asc') {
      filtered.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
    }

    // Update Counter
    if (counterText) {
      counterText.textContent = `SHOWING ${filtered.length} ARTIFACTS`;
    }

    renderGrid(filtered);
  }

  /**
   * Render Cards in Grid
   */
  function renderGrid(items) {
    if (!merchGrid) return;

    if (items.length === 0) {
      merchGrid.innerHTML = '';
      if (emptyState) emptyState.style.display = 'block';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';

    merchGrid.innerHTML = items.map(item => {
      const isSaved = savedFavorites.includes(item.id);
      const raritySlug = (item.rarity_tier || 'collector-grade').toLowerCase().replace(/\s+/g, '-');

      return `
        <article class="merch-card" data-id="${item.id}" data-category="${item.category}" data-rarity="${item.rarity_tier}">
          <div class="merch-card-media">
            <img src="${item.image_url}" alt="${item.name}" loading="lazy" class="merch-img" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'400\\' height=\\'400\\' fill=\\'%232a2826\\'><rect width=\\'400\\' height=\\'400\\'/></svg>'">
            
            <div class="exhibit-pill">
              <span class="dot-red"></span>
              <span>NOT FOR SALE / SIRF DEKHNE KE LIYE</span>
            </div>

            <span class="rarity-badge ${raritySlug}">${item.rarity_tier}</span>

            <button type="button" class="btn-quick-inspect" onclick="window.ARCHIVE_DUKAN.openInspect(${item.id})" title="Inspect Curatorial Details">
              🔍 INSPECT
            </button>
          </div>

          <div class="merch-card-body">
            <div class="merch-meta-top">
              <span class="franchise-tag">${item.franchise}</span>
              <span class="accession-code">${item.accession_no || 'ARC-MC'}</span>
            </div>

            <h3 class="merch-title">${item.name}</h3>

            <div class="merch-specs-snippet">
              <div><strong>TYPE:</strong> ${item.item_type || 'Exhibition Artifact'}</div>
              <div><strong>SCALE:</strong> ${item.scale_or_size || '1:1'}</div>
              <div><strong>MATERIAL:</strong> ${item.material || 'Museum Spec'}</div>
              <div><strong>MAKER:</strong> ${item.manufacturer || 'Atelier'}</div>
            </div>

            <p class="merch-desc">${item.description}</p>

            <div class="merch-card-footer">
              <div class="footer-badges">
                <span class="badge badge-accent">YEAR ${item.year_released || 2024}</span>
                <span class="badge badge-default">MUSEUM ARCHIVE</span>
              </div>
              
              <div class="footer-buttons">
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.ARCHIVE_DUKAN.openInspect(${item.id})">
                  TAFSEELAT
                </button>
                <button type="button" class="btn btn-secondary btn-sm btn-save-merch ${isSaved ? 'active' : ''}" onclick="window.ARCHIVE_DUKAN.toggleSave(${item.id}, this)" title="Save to Personal Showcase Shelf">
                  ${isSaved ? '★ SAVED' : '+ SAVE'}
                </button>
              </div>
            </div>

          </div>
        </article>
      `;
    }).join('');
  }

  /**
   * Toggle Save Favorite
   */
  function toggleSave(id, btnElement) {
    id = parseInt(id, 10);
    const idx = savedFavorites.indexOf(id);

    if (idx > -1) {
      savedFavorites.splice(idx, 1);
      if (btnElement) {
        btnElement.classList.remove('active');
        btnElement.textContent = '+ SAVE';
      }
    } else {
      savedFavorites.push(id);
      if (btnElement) {
        btnElement.classList.add('active');
        btnElement.textContent = '★ SAVED';
      }
    }

    try {
      localStorage.setItem('archive_merch_favs', JSON.stringify(savedFavorites));
    } catch (e) {}

    updateFavoritesBadge();

    // If currently on favorites tab, re-render
    if (currentCategory === 'favorites') {
      applyFilters();
    }
  }

  /**
   * Open Exhibit Lightbox
   */
  function openInspect(id) {
    const item = allMerch.find(m => m.id === id);
    if (!item || !modal || !modalContent) return;

    modalContent.innerHTML = `
      <div class="modal-notice-banner">
        ⚠️ <strong>NON-COMMERCIAL NOTICE:</strong> Yeh cheez sirf dekhne aur technical details parhne ke liye virtual numayish hai. Yahan se cheezain khareedi nahi ja saktin (Strictly view-only / Not for sale).
      </div>

      <div class="dukan-modal-split">
        <div class="modal-exhibit-preview">
          <img src="${item.image_url}" alt="${item.name}" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'400\\' height=\\'400\\' fill=\\'%232a2826\\'><rect width=\\'400\\' height=\\'400\\'/></svg>'">
          <div style="padding: 10px; font-size: 11px; color: var(--text-dim); text-align: center; border-top: 1px solid var(--border-subtle);">
            HIGH-FIDELITY ARCHIVAL CAPTURE • ACCESSION: <strong>${item.accession_no || 'ARC-MC'}</strong>
          </div>
        </div>

        <div>
          <div style="font-family: var(--font-display); font-size: 12px; color: var(--accent); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;">
            ${item.franchise}
          </div>
          <h2 style="font-family: var(--font-display); font-size: 24px; color: var(--text-bright); margin: 6px 0 12px; line-height: 1.2;">
            ${item.name}
          </h2>

          <div style="font-size: 14px; line-height: 1.6; color: var(--text-body); margin-bottom: 16px;">
            ${item.description}
          </div>

          <table class="specs-table">
            <tbody>
              <tr>
                <td>CLASSIFICATION</td>
                <td>${item.item_type}</td>
              </tr>
              <tr>
                <td>SCALE / SIZE</td>
                <td>${item.scale_or_size}</td>
              </tr>
              <tr>
                <td>COMPOSITION</td>
                <td>${item.material}</td>
              </tr>
              <tr>
                <td>STUDIO / MAKER</td>
                <td>${item.manufacturer}</td>
              </tr>
              <tr>
                <td>RARITY TIER</td>
                <td><strong style="color:var(--accent);">${item.rarity_tier}</strong></td>
              </tr>
              <tr>
                <td>RELEASE YEAR</td>
                <td>${item.year_released}</td>
              </tr>
              <tr>
                <td>STATUS</td>
                <td>Permanent Collection (View-Only)</td>
              </tr>
            </tbody>
          </table>

          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" onclick="window.ARCHIVE_DUKAN.toggleSave(${item.id}); window.ARCHIVE_DUKAN.closeInspect();">
              ${savedFavorites.includes(item.id) ? '★ IN SAVED SHELF' : '+ ADD TO SAVED SHELF'}
            </button>
            <button type="button" class="btn btn-primary" onclick="window.ARCHIVE_DUKAN.closeInspect()">
              DONE
            </button>
          </div>
        </div>
      </div>
    `;

    modal.classList.add('active');
  }

  function closeInspect() {
    if (modal) modal.classList.remove('active');
  }

  // Export for global access
  window.ARCHIVE_DUKAN = {
    openInspect,
    closeInspect,
    toggleSave
  };

  /**
   * Event Listeners
   */
  document.addEventListener('DOMContentLoaded', () => {
    loadSavedFavorites();
    loadMerchData();

    if (searchInput) {
      searchInput.addEventListener('input', applyFilters);
    }

    if (categoryTabs) {
      categoryTabs.addEventListener('click', (e) => {
        const btn = e.target.closest('.dukan-tab');
        if (!btn) return;
        categoryTabs.querySelectorAll('.dukan-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = btn.dataset.category || 'all';
        applyFilters();
      });
    }

    if (raritySelect) {
      raritySelect.addEventListener('change', (e) => {
        currentRarity = e.target.value;
        applyFilters();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        currentSort = e.target.value;
        applyFilters();
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        currentCategory = 'all';
        currentRarity = 'all';
        if (raritySelect) raritySelect.value = 'all';
        if (categoryTabs) {
          categoryTabs.querySelectorAll('.dukan-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.category === 'all');
          });
        }
        applyFilters();
      });
    }

    if (closeModalBtn) {
      closeModalBtn.addEventListener('click', closeInspect);
    }

    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeInspect();
      });
    }
  });

})();
