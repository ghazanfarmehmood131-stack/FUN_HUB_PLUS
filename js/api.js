/**
 * API Service for All 8 Categories:
 * Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, Software.
 * Connects to Jikan v4 (Anime & Manga) and TMDB v3 (Movies & TV Shows),
 * plus rich curated registries for Gaming, K-Pop, Comics, and Software.
 */

window.AppAPI = (function() {
  const cache = new Map();

  function cacheGet(key) {
    const item = cache.get(key);
    if (!item) return null;
    if (Date.now() - item.time > 10 * 60 * 1000) {
      cache.delete(key);
      return null;
    }
    return item.data;
  }

  function cacheSet(key, data) {
    cache.set(key, { data, time: Date.now() });
  }

  // --- Normalizers ---

  function normalizeAnime(item) {
    if (!item) return null;
    
    let trailerKey = null;
    let trailerUrl = null;
    if (item.trailer) {
      if (item.trailer.youtube_id) {
        trailerKey = item.trailer.youtube_id;
        trailerUrl = `https://www.youtube.com/embed/${item.trailer.youtube_id}`;
      } else if (item.trailer.embed_url) {
        trailerUrl = item.trailer.embed_url;
      }
    }

    const poster = item.images?.webp?.large_image_url || 
                   item.images?.jpg?.large_image_url || 
                   item.images?.jpg?.image_url || '';

    const genres = (item.genres || []).map(g => g.name);
    if (item.demographics) {
      item.demographics.forEach(d => { if (!genres.includes(d.name)) genres.push(d.name); });
    }

    const studio = (item.studios && item.studios.length > 0) ? item.studios[0].name : 'Unknown Studio';

    return {
      id: item.mal_id,
      type: 'anime',
      category: 'anime',
      title: item.title_english || item.title,
      originalTitle: item.title_japanese || item.title,
      year: item.year || (item.aired?.from ? new Date(item.aired.from).getFullYear() : 'N/A'),
      score: item.score || 'N/A',
      status: item.status || 'Finished Airing',
      episodes: item.episodes || 'TBD',
      duration: item.duration || 'N/A',
      rating: item.rating || 'N/A',
      genres: genres.length > 0 ? genres : ['Animation'],
      synopsis: item.synopsis ? item.synopsis.replace(/\[Written by MAL Rewrite\]/g, '').trim() : 'No synopsis available in archive.',
      poster: poster,
      backdrop: poster,
      trailerKey: trailerKey,
      trailerUrl: trailerUrl,
      studio: studio,
      cast: []
    };
  }

  function normalizeManga(item) {
    if (!item) return null;
    const poster = item.images?.webp?.large_image_url || 
                   item.images?.jpg?.large_image_url || 
                   item.images?.jpg?.image_url || '';

    const genres = (item.genres || []).map(g => g.name);
    const authors = (item.authors || []).map(a => a.name).join(', ') || 'Unknown Author';

    return {
      id: item.mal_id,
      type: 'manga',
      category: 'manga',
      title: item.title_english || item.title,
      originalTitle: item.title_japanese || item.title,
      year: item.published?.from ? new Date(item.published.from).getFullYear() : 'N/A',
      score: item.score || 'N/A',
      status: item.status || 'Publishing',
      volumes: item.volumes || 'TBD',
      genres: genres.length > 0 ? genres : ['Manga'],
      synopsis: item.synopsis ? item.synopsis.replace(/\[Written by MAL Rewrite\]/g, '').trim() : 'No synopsis recorded in archive.',
      poster: poster,
      backdrop: poster,
      trailerUrl: null,
      author: authors,
      cast: []
    };
  }

  function normalizeTmdbMovie(item) {
    if (!item) return null;
    const config = window.APP_CONFIG;
    const imgBase = config.TMDB_IMAGE_BASE;

    let trailerKey = null;
    let trailerUrl = null;
    if (item.videos && item.videos.results) {
      const vid = item.videos.results.find(v => v.site === 'YouTube' && (v.type === 'Trailer' || v.type === 'Teaser')) || item.videos.results[0];
      if (vid && vid.key) {
        trailerKey = vid.key;
        trailerUrl = `https://www.youtube.com/embed/${vid.key}`;
      }
    }

    let director = 'N/A';
    const cast = [];
    if (item.credits) {
      if (item.credits.crew) {
        const dir = item.credits.crew.find(c => c.job === 'Director');
        if (dir) director = dir.name;
      }
      if (item.credits.cast) {
        item.credits.cast.slice(0, 8).forEach(c => {
          cast.push({
            name: c.name,
            role: c.character || 'Cast',
            avatar: c.profile_path ? `${imgBase}/w185${c.profile_path}` : ''
          });
        });
      }
    }

    const genres = item.genres ? item.genres.map(g => g.name) : [];
    const year = item.release_date ? new Date(item.release_date).getFullYear() : 'N/A';
    const poster = item.poster_path ? `${imgBase}/w500${item.poster_path}` : '';
    const backdrop = item.backdrop_path ? `${imgBase}/original${item.backdrop_path}` : poster;

    return {
      id: item.id,
      type: 'movie',
      category: 'movies',
      title: item.title,
      originalTitle: item.original_title || item.title,
      year: year,
      score: item.vote_average ? Number(item.vote_average).toFixed(1) : 'N/A',
      status: item.status || 'Released',
      runtime: item.runtime ? `${item.runtime} min` : 'N/A',
      genres: genres.length > 0 ? genres : ['Cinema'],
      synopsis: item.overview || 'No synopsis recorded in archive.',
      poster: poster,
      backdrop: backdrop,
      trailerKey: trailerKey,
      trailerUrl: trailerUrl,
      director: director,
      cast: cast
    };
  }

  function normalizeTmdbTv(item) {
    if (!item) return null;
    const config = window.APP_CONFIG;
    const imgBase = config.TMDB_IMAGE_BASE;

    let trailerKey = null;
    let trailerUrl = null;
    if (item.videos && item.videos.results) {
      const vid = item.videos.results.find(v => v.site === 'YouTube' && (v.type === 'Trailer' || v.type === 'Teaser')) || item.videos.results[0];
      if (vid && vid.key) {
        trailerKey = vid.key;
        trailerUrl = `https://www.youtube.com/embed/${vid.key}`;
      }
    }

    const year = item.first_air_date ? new Date(item.first_air_date).getFullYear() : 'N/A';
    const poster = item.poster_path ? `${imgBase}/w500${item.poster_path}` : '';
    const backdrop = item.backdrop_path ? `${imgBase}/original${item.backdrop_path}` : poster;
    const genres = item.genres ? item.genres.map(g => g.name) : ['Drama'];

    return {
      id: item.id,
      type: 'tvshows',
      category: 'tvshows',
      title: item.name || item.original_name,
      originalTitle: item.original_name || item.name,
      year: year,
      score: item.vote_average ? Number(item.vote_average).toFixed(1) : 'N/A',
      status: item.status || 'Broadcast',
      seasons: item.number_of_seasons || 'N/A',
      episodes: item.number_of_episodes || 'N/A',
      genres: genres,
      synopsis: item.overview || 'No synopsis recorded in archive.',
      poster: poster,
      backdrop: backdrop,
      trailerKey: trailerKey,
      trailerUrl: trailerUrl,
      network: (item.networks && item.networks.length > 0) ? item.networks[0].name : 'Television',
      cast: []
    };
  }

  // --- Anime & Manga (Jikan) ---

  async function getTopAnime(page = 1) {
    const cacheKey = `jikan_top_anime_${page}`;
    const cached = cacheGet(cacheKey);
    if (cached) return cached;

    try {
      const url = `${window.APP_CONFIG.JIKAN_BASE_URL}/top/anime?page=${page}&limit=12`;
      const res = await fetch(url);
      if (!res.ok) throw new Error(`Jikan HTTP error: ${res.status}`);
      const json = await res.json();
      const normalized = (json.data || []).map(normalizeAnime);
      const result = { data: normalized, pagination: json.pagination || { current_page: page, has_next_page: false } };
      cacheSet(cacheKey, result);
      return result;
    } catch (err) {
      console.warn('Jikan anime failed, using fallback', err);
      return { data: window.FALLBACK_DATA.anime, pagination: { current_page: 1, has_next_page: false } };
    }
  }

  async function getTopManga(page = 1) {
    const cacheKey = `jikan_top_manga_${page}`;
    const cached = cacheGet(cacheKey);
    if (cached) return cached;

    try {
      const url = `${window.APP_CONFIG.JIKAN_BASE_URL}/top/manga?page=${page}&limit=12`;
      const res = await fetch(url);
      if (!res.ok) throw new Error(`Jikan HTTP error: ${res.status}`);
      const json = await res.json();
      const normalized = (json.data || []).map(normalizeManga);
      const result = { data: normalized, pagination: json.pagination || { current_page: page, has_next_page: false } };
      cacheSet(cacheKey, result);
      return result;
    } catch (err) {
      console.warn('Jikan manga failed, using fallback', err);
      return { data: window.FALLBACK_DATA.manga, pagination: { current_page: 1, has_next_page: false } };
    }
  }

  async function getAnimeDetail(id) {
    const cacheKey = `jikan_detail_${id}`;
    const cached = cacheGet(cacheKey);
    if (cached) return cached;

    const fallbackItem = window.FALLBACK_DATA.anime.find(a => String(a.id) === String(id));

    try {
      const detailUrl = `${window.APP_CONFIG.JIKAN_BASE_URL}/anime/${id}/full`;
      const res = await fetch(detailUrl);
      if (!res.ok) throw new Error(`Jikan HTTP error: ${res.status}`);
      const json = await res.json();
      const item = normalizeAnime(json.data);

      try {
        const charRes = await fetch(`${window.APP_CONFIG.JIKAN_BASE_URL}/anime/${id}/characters`);
        if (charRes.ok) {
          const charJson = await charRes.json();
          item.cast = (charJson.data || []).slice(0, 8).map(c => ({
            name: c.character.name,
            role: c.role || 'Character',
            avatar: c.character.images?.jpg?.image_url || ''
          }));
        }
      } catch (e) {}

      cacheSet(cacheKey, item);
      return item;
    } catch (err) {
      if (fallbackItem) return fallbackItem;
      throw err;
    }
  }

  // --- Movies & TV Shows (TMDB) ---

  async function getTrendingMovies(page = 1) {
    const key = window.APP_CONFIG.getTmdbKey();
    if (!key) {
      return { data: window.FALLBACK_DATA.movies, pagination: { current_page: 1, total_pages: 1 }, isFallback: true };
    }

    const cacheKey = `tmdb_trending_movies_${page}`;
    const cached = cacheGet(cacheKey);
    if (cached) return cached;

    try {
      const url = `${window.APP_CONFIG.TMDB_BASE_URL}/trending/movie/week?api_key=${key}&page=${page}`;
      const res = await fetch(url);
      if (!res.ok) throw new Error(`TMDB HTTP error: ${res.status}`);
      const json = await res.json();
      const normalized = (json.results || []).map(normalizeTmdbMovie);
      const result = { data: normalized, pagination: { current_page: json.page, total_pages: json.total_pages }, isFallback: false };
      cacheSet(cacheKey, result);
      return result;
    } catch (err) {
      return { data: window.FALLBACK_DATA.movies, pagination: { current_page: 1, total_pages: 1 }, isFallback: true };
    }
  }

  async function getTrendingTv(page = 1) {
    const key = window.APP_CONFIG.getTmdbKey();
    if (!key) {
      return { data: window.FALLBACK_DATA.tvshows, pagination: { current_page: 1, total_pages: 1 }, isFallback: true };
    }

    const cacheKey = `tmdb_trending_tv_${page}`;
    const cached = cacheGet(cacheKey);
    if (cached) return cached;

    try {
      const url = `${window.APP_CONFIG.TMDB_BASE_URL}/trending/tv/week?api_key=${key}&page=${page}`;
      const res = await fetch(url);
      if (!res.ok) throw new Error(`TMDB HTTP error: ${res.status}`);
      const json = await res.json();
      const normalized = (json.results || []).map(normalizeTmdbTv);
      const result = { data: normalized, pagination: { current_page: json.page, total_pages: json.total_pages }, isFallback: false };
      cacheSet(cacheKey, result);
      return result;
    } catch (err) {
      return { data: window.FALLBACK_DATA.tvshows, pagination: { current_page: 1, total_pages: 1 }, isFallback: true };
    }
  }

  async function getMovieDetail(id) {
    const fallbackItem = window.FALLBACK_DATA.movies.find(m => String(m.id) === String(id));
    const key = window.APP_CONFIG.getTmdbKey();

    if (!key) {
      if (fallbackItem) return fallbackItem;
      throw new Error('Movie not found in archive. Add TMDB API key to search full cinema registry.');
    }

    const cacheKey = `tmdb_detail_${id}`;
    const cached = cacheGet(cacheKey);
    if (cached) return cached;

    try {
      const url = `${window.APP_CONFIG.TMDB_BASE_URL}/movie/${id}?api_key=${key}&append_to_response=videos,credits`;
      const res = await fetch(url);
      if (!res.ok) throw new Error(`TMDB HTTP error: ${res.status}`);
      const json = await res.json();
      const item = normalizeTmdbMovie(json);
      cacheSet(cacheKey, item);
      return item;
    } catch (err) {
      if (fallbackItem) return fallbackItem;
      throw err;
    }
  }

  // --- Category Dispatcher ---

  async function getByCategory(category = 'all', page = 1) {
    const cat = category.toLowerCase();

    if (cat === 'anime') {
      return await getTopAnime(page);
    } else if (cat === 'manga') {
      return await getTopManga(page);
    } else if (cat === 'movies' || cat === 'movie') {
      return await getTrendingMovies(page);
    } else if (cat === 'tvshows' || cat === 'tv') {
      return await getTrendingTv(page);
    } else if (cat === 'gaming') {
      return { data: window.FALLBACK_DATA.gaming, pagination: { current_page: 1, has_next_page: false } };
    } else if (cat === 'kpop') {
      return { data: window.FALLBACK_DATA.kpop, pagination: { current_page: 1, has_next_page: false } };
    } else if (cat === 'comics') {
      return { data: window.FALLBACK_DATA.comics, pagination: { current_page: 1, has_next_page: false } };
    } else if (cat === 'software') {
      return { data: window.FALLBACK_DATA.software || [], pagination: { current_page: 1, has_next_page: false } };
    } else {
      // 'all': assemble representative sample from across categories
      const [animeRes, movieRes] = await Promise.allSettled([
        getTopAnime(page),
        getTrendingMovies(page)
      ]);

      const animeList = animeRes.status === 'fulfilled' ? animeRes.value.data.slice(0, 2) : [];
      const movieList = movieRes.status === 'fulfilled' ? movieRes.value.data.slice(0, 2) : [];
      const gameList = window.FALLBACK_DATA.gaming.slice(0, 2);
      const tvList = window.FALLBACK_DATA.tvshows.slice(0, 2);
      const kpopList = window.FALLBACK_DATA.kpop.slice(0, 1);
      const comicList = window.FALLBACK_DATA.comics.slice(0, 1);
      const mangaList = window.FALLBACK_DATA.manga.slice(0, 1);
      const softwareList = (window.FALLBACK_DATA.software || []).slice(0, 1);

      return {
        data: [...animeList, ...movieList, ...gameList, ...tvList, ...mangaList, ...kpopList, ...comicList, ...softwareList],
        pagination: { current_page: page, has_next_page: false }
      };
    }
  }

  // --- Title Detail Dispatcher ---

  async function getTitleDetail(type, id) {
    const t = (type || 'anime').toLowerCase();

    // Check all fallback categories first
    const allCategories = ['movies', 'anime', 'gaming', 'tvshows', 'kpop', 'comics', 'manga', 'software'];
    for (const cat of allCategories) {
      const found = (window.FALLBACK_DATA[cat] || []).find(item => String(item.id) === String(id));
      if (found) return found;
    }

    // Try live APIs
    if (t === 'anime') {
      return await getAnimeDetail(id);
    } else if (t === 'movie' || t === 'movies') {
      return await getMovieDetail(id);
    } else if (t === 'manga') {
      try {
        const url = `${window.APP_CONFIG.JIKAN_BASE_URL}/manga/${id}/full`;
        const res = await fetch(url);
        if (res.ok) {
          const json = await res.json();
          return normalizeManga(json.data);
        }
      } catch (e) {}
    } else if (t === 'software') {
      const response = await fetch(`api/items.php?id=${encodeURIComponent(id)}`);
      if (response.ok) {
        const result = await response.json();
        if (result.success) return result.item;
      }
    }

    throw new Error('Title record not found in archive registry.');
  }

  // --- Search across Categories ---

  async function searchAll(query, category = 'all') {
    if (!query || query.trim().length === 0) return { data: [], total: 0 };
    const q = query.toLowerCase().trim();
    const cat = category.toLowerCase();

    let pool = [];

    // Local categories match
    const localCategories = ['gaming', 'tvshows', 'kpop', 'comics', 'manga', 'software', 'movies', 'anime'];
    localCategories.forEach(c => {
      if (cat === 'all' || cat === c || (cat === 'movies' && c === 'movies') || (cat === 'tvshows' && c === 'tvshows')) {
        const items = window.FALLBACK_DATA[c] || [];
        const matches = items.filter(it => 
          (it.title && it.title.toLowerCase().includes(q)) ||
          (it.synopsis && it.synopsis.toLowerCase().includes(q)) ||
          (it.genres && it.genres.some(g => g.toLowerCase().includes(q)))
        );
        pool.push(...matches);
      }
    });

    // Also run live anime search if 'all' or 'anime'
    if (cat === 'all' || cat === 'anime') {
      try {
        const animeRes = await fetch(`${window.APP_CONFIG.JIKAN_BASE_URL}/anime?q=${encodeURIComponent(q)}&limit=6&sfw=true`);
        if (animeRes.ok) {
          const json = await animeRes.json();
          const liveAnime = (json.data || []).map(normalizeAnime);
          liveAnime.forEach(la => {
            if (!pool.some(p => String(p.id) === String(la.id))) {
              pool.unshift(la);
            }
          });
        }
      } catch (e) {}
    }

    return {
      data: pool,
      total: pool.length
    };
  }

  return {
    getTopAnime,
    getTopManga,
    getAnimeDetail,
    getTrendingMovies,
    getTrendingTv,
    getMovieDetail,
    getByCategory,
    getTitleDetail,
    searchAll
  };
})();
