/**
 * Watchlist Storage Manager
 * Uses localStorage to persist saved titles across sessions.
 */

window.WatchlistService = (function() {
  const STORAGE_KEY = window.APP_CONFIG.STORAGE_WATCHLIST_KEY;

  function getAll() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      return data ? JSON.parse(data) : [];
    } catch (e) {
      console.error('Error reading watchlist from localStorage', e);
      return [];
    }
  }

  function save(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
      notify();
    } catch (e) {
      console.error('Error saving watchlist to localStorage', e);
    }
  }

  function has(type, id) {
    const list = getAll();
    return list.some(item => item.type === type && String(item.id) === String(id));
  }

  function add(item) {
    if (!item || !item.id || !item.type) return false;
    const list = getAll();
    if (has(item.type, item.id)) return false;

    const entry = {
      id: item.id,
      type: item.type,
      category: item.category || item.type,
      title: item.title,
      originalTitle: item.originalTitle || item.title,
      year: item.year || 'N/A',
      score: item.score || 'N/A',
      poster: item.poster || '',
      genres: item.genres || [],
      synopsis: item.synopsis || '',
      status: item.status || '',
      addedAt: Date.now()
    };

    list.unshift(entry);
    save(list);
    return true;
  }

  function remove(type, id) {
    const list = getAll();
    const filtered = list.filter(item => !(item.type === type && String(item.id) === String(id)));
    if (filtered.length !== list.length) {
      save(filtered);
      return true;
    }
    return false;
  }

  function toggle(item) {
    if (has(item.type, item.id)) {
      remove(item.type, item.id);
      return false; // removed
    } else {
      add(item);
      return true; // added
    }
  }

  function clear() {
    save([]);
  }

  function count() {
    return getAll().length;
  }

  function notify() {
    const items = getAll();
    window.dispatchEvent(new CustomEvent('watchlist-updated', {
      detail: { count: items.length, items }
    }));
    updateBadges();
  }

  function updateBadges() {
    const currentCount = count();
    document.querySelectorAll('.watchlist-badge-count').forEach(el => {
      el.textContent = currentCount;
    });
  }

  // Initial badge update on script load
  document.addEventListener('DOMContentLoaded', () => {
    updateBadges();
  });

  return {
    getAll,
    has,
    add,
    remove,
    toggle,
    clear,
    count,
    updateBadges
  };
})();
