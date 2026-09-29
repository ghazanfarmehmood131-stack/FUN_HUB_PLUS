/**
 * Configuration & API Credentials
 * 
 * TMDB API KEY SETUP:
 * You can put your TMDB v3 API Key in the TMDB_KEY property below,
 * or enter it directly through the "API KEY" button in the website header.
 * If left empty, the site automatically serves an authentic curated cinema
 * archive dataset with real posters, metadata, and trailers.
 */

window.APP_CONFIG = {
  // Put your TMDB v3 API key here if you have one:
  TMDB_KEY: '',

  // API Endpoints
  JIKAN_BASE_URL: 'https://api.jikan.moe/v4',
  TMDB_BASE_URL: 'https://api.themoviedb.org/3',
  TMDB_IMAGE_BASE: 'https://image.tmdb.org/t/p',

  // LocalStorage Keys
  STORAGE_WATCHLIST_KEY: 'antigravity_archive_watchlist',
  STORAGE_USER_KEY: 'antigravity_archive_user',
  STORAGE_TMDB_KEY: 'antigravity_archive_tmdb_key',

  // Get active TMDB key (checks localStorage first, then file config)
  getTmdbKey: function() {
    const stored = localStorage.getItem(this.STORAGE_TMDB_KEY);
    if (stored && stored.trim().length > 0) {
      return stored.trim();
    }
    return this.TMDB_KEY.trim();
  },

  setTmdbKey: function(key) {
    if (key && key.trim().length > 0) {
      localStorage.setItem(this.STORAGE_TMDB_KEY, key.trim());
    } else {
      localStorage.removeItem(this.STORAGE_TMDB_KEY);
    }
  },

  hasTmdbKey: function() {
    return this.getTmdbKey().length > 0;
  }
};
