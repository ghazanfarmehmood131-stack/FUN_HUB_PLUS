<?php
/**
 * Fan Hub Plus - Configuration and Database Layer
 * Connects to MySQL using PDO, provides data helpers, session handling,
 * and intelligent poster image fallback generators.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials: use a private local config file first, then environment
// variables, and finally development-friendly defaults.
$databaseConfig = [];
$localDatabaseConfig = __DIR__ . '/config.local.php';
if (is_file($localDatabaseConfig)) {
    $loadedDatabaseConfig = require $localDatabaseConfig;
    if (is_array($loadedDatabaseConfig)) {
        $databaseConfig = $loadedDatabaseConfig;
    }
}
define('DB_HOST', $databaseConfig['host'] ?? (getenv('FAN_HUB_DB_HOST') ?: (getenv('ARCHIVE_DB_HOST') ?: '127.0.0.1')));
define('DB_PORT', $databaseConfig['port'] ?? (getenv('FAN_HUB_DB_PORT') ?: (getenv('ARCHIVE_DB_PORT') ?: '3306')));
define('DB_NAME', $databaseConfig['name'] ?? (getenv('FAN_HUB_DB_NAME') ?: (getenv('ARCHIVE_DB_NAME') ?: 'fan_hub_plus')));
define('DB_USER', $databaseConfig['user'] ?? (getenv('FAN_HUB_DB_USER') ?: (getenv('ARCHIVE_DB_USER') ?: 'fan_hub_app')));
define('DB_PASS', $databaseConfig['pass'] ?? (getenv('FAN_HUB_DB_PASS') ?: (getenv('ARCHIVE_DB_PASS') ?: '')));

// Site Constants
define('SITE_NAME', 'Fan Hub Plus');
define('SITE_TAGLINE', 'Anime, Cinema & Pop Culture Ledger');
define('SITE_VERSION', '2.0.0 (PHP/MySQL)');

/**
 * Returns a PDO database instance (Singleton pattern)
 */
function getDb() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

/**
 * Check if database is currently connected
 */
function isDbConnected() {
    $db = getDb();
    return $db !== null;
}

/**
 * Safe HTML Escaping Helper
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generates an elegant, high-contrast SVG placeholder data URI
 * Matches the warm charcoal dark aesthetic with muted brick red accent.
 */
function generateSvgPoster($title, $category = 'MEDIA') {
    $displayTitle = mb_strimwidth($title, 0, 24, '...');
    $displayCat = strtoupper($category);
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 600" width="100%" height="100%">'
         . '<defs>'
         . '<linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
         . '<stop offset="0%" stop-color="#1e1e24"/>'
         . '<stop offset="100%" stop-color="#141416"/>'
         . '</linearGradient>'
         . '<pattern id="p" width="20" height="20" patternUnits="userSpaceOnUse">'
         . '<line x1="0" y1="0" x2="20" y2="0" stroke="#25252b" stroke-width="0.5"/>'
         . '<line x1="0" y1="0" x2="0" y2="20" stroke="#25252b" stroke-width="0.5"/>'
         . '</pattern>'
         . '</defs>'
         . '<rect width="400" height="600" fill="url(#g)"/>'
         . '<rect width="400" height="600" fill="url(#p)" opacity="0.6"/>'
         . '<rect x="24" y="24" width="352" height="552" fill="none" stroke="#2c2c34" stroke-width="1"/>'
         . '<rect x="36" y="36" width="100" height="26" fill="#b94337"/>'
         . '<text x="86" y="53" fill="#ffffff" font-family="sans-serif" font-size="11" font-weight="bold" text-anchor="middle" letter-spacing="1.5">' . htmlspecialchars($displayCat) . '</text>'
         . '<circle cx="200" cy="270" r="48" fill="#18181b" stroke="#383842" stroke-width="1"/>'
         . '<polygon points="190,250 220,270 190,290" fill="#b94337"/>'
         . '<text x="200" y="380" fill="#f4f4f5" font-family="sans-serif" font-size="20" font-weight="bold" text-anchor="middle" letter-spacing="0.5">' . htmlspecialchars($displayTitle) . '</text>'
         . '<text x="200" y="415" fill="#a1a1aa" font-family="sans-serif" font-size="12" text-anchor="middle" letter-spacing="1">ARCHIVE RECORD</text>'
         . '<line x1="160" y1="440" x2="240" y2="440" stroke="#383842" stroke-width="1"/>'
         . '</svg>';
    
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

/**
 * Returns the poster URL, or fallback SVG data URI if invalid or empty
 */
function safePosterUrl($url, $title = 'Ledger Record', $category = 'MEDIA') {
    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    return generateSvgPoster($title, $category);
}

/**
 * Fetch all 8 categories
 */
function supportsPlaylistCategory($slug): bool {
    $slug = strtolower((string)($slug ?? ''));
    return $slug !== '' && $slug !== 'gaming';
}

function getAllCategories() {
    $displayNames = [
        'anime' => 'Anime',
        'gaming' => 'Games',
        'movies' => 'Movies',
        'tvshows' => 'TV Series',
        'kpop' => 'Korean Movies',
        'comics' => 'Web Series',
        'manga' => 'Japanese Comics',
        'software' => 'Software',
    ];
    $fallback = [
        ['slug' => 'anime', 'name' => 'Anime', 'short_label' => 'ANIME', 'icon' => 'tv'],
        ['slug' => 'gaming', 'name' => 'Games', 'short_label' => 'GAMES', 'icon' => 'gamepad'],
        ['slug' => 'movies', 'name' => 'Movies', 'short_label' => 'FILM', 'icon' => 'film'],
        ['slug' => 'tvshows', 'name' => 'TV Series', 'short_label' => 'TV SERIES', 'icon' => 'tv'],
        ['slug' => 'kpop', 'name' => 'Korean Movies', 'short_label' => 'KOREAN MOVIES', 'icon' => 'music'],
        ['slug' => 'comics', 'name' => 'Web Series', 'short_label' => 'WEB SERIES', 'icon' => 'book-open'],
        ['slug' => 'manga', 'name' => 'Japanese Comics', 'short_label' => 'JAPANESE COMICS', 'icon' => 'book'],
        ['slug' => 'software', 'name' => 'Software', 'short_label' => 'SOFTWARE', 'icon' => 'code'],
    ];
    $db = getDb();
    if (!$db) return $fallback;
    try {
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC");
        $cats = $stmt->fetchAll();
        if (empty($cats)) return $fallback;
        foreach ($cats as &$category) {
            if (isset($displayNames[$category['slug']])) {
                $category['name'] = $displayNames[$category['slug']];
                $category['short_label'] = strtoupper($category['name']);
            }
        }
        unset($category);
        return $cats;
    } catch (PDOException $e) {
        return $fallback;
    }
}

/**
 * Fetch category by slug
 */
function getCategoryBySlug($slug) {
    $db = getDb();
    if (!$db) return null;
    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Fetch media items with flexible filtering and sorting
 */
function getMediaItems($category = null, $genre = null, $search = null, $sort = 'score_desc', $limit = 24, $offset = 0) {
    $db = getDb();
    if (!$db) return ['items' => [], 'total' => 0];

    $where = [];
    $params = [];

    if (!empty($category) && $category !== 'all') {
        $where[] = "category_slug = ?";
        $params[] = $category;
    }

    if (!empty($genre) && $genre !== 'all') {
        $where[] = "genres LIKE ?";
        $params[] = "%" . $genre . "%";
    }

    if (!empty($search)) {
        $where[] = "(title LIKE ? OR original_title LIKE ? OR synopsis LIKE ? OR director_or_creator LIKE ?)";
        $term = "%" . $search . "%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $orderSql = "ORDER BY score DESC";
    switch ($sort) {
        case 'score_asc':
            $orderSql = "ORDER BY score ASC";
            break;
        case 'year_desc':
            $orderSql = "ORDER BY year DESC, score DESC";
            break;
        case 'year_asc':
            $orderSql = "ORDER BY year ASC, score DESC";
            break;
        case 'title_asc':
            $orderSql = "ORDER BY title ASC";
            break;
        case 'title_desc':
            $orderSql = "ORDER BY title DESC";
            break;
        case 'recent_desc':
            $orderSql = "ORDER BY GREATEST(media_items.created_at, COALESCE((
                SELECT MAX(media_videos.created_at)
                FROM media_videos
                WHERE media_videos.media_item_id = media_items.id
            ), media_items.created_at)) DESC, media_items.id DESC";
            break;
        default:
            $orderSql = "ORDER BY score DESC";
    }

    // Count total matching items
    $countSql = "SELECT COUNT(*) as total FROM media_items $whereSql";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Query paginated items
    $itemsSql = "SELECT * FROM media_items $whereSql $orderSql LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    $itemsStmt = $db->prepare($itemsSql);
    $itemsStmt->execute($params);
    $items = $itemsStmt->fetchAll();

    return ['items' => $items, 'total' => $total];
}

/**
 * Fetch a single media item by ID or external ID
 */
function getMediaItemDetail($idOrExternal) {
    $db = getDb();
    if (!$db) return null;

    $stmt = $db->prepare("SELECT * FROM media_items WHERE id = ? OR external_id = ? LIMIT 1");
    $stmt->execute([$idOrExternal, $idOrExternal]);
    $item = $stmt->fetch();
    if ($item && !empty($item['extra_meta'])) {
        $item['meta_parsed'] = json_decode($item['extra_meta'], true);
    }
    return $item ?: null;
}

/**
 * Fetch featured spotlight items for hero
 */
function getFeaturedHeroItems($limit = 6) {
    $db = getDb();
    if (!$db) return [];
    $stmt = $db->prepare("SELECT * FROM media_items WHERE is_featured = 1 ORDER BY score DESC LIMIT " . (int)$limit);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Fetch items by category with limit (e.g. for homepage category shelves)
 */
function getItemsByCategory($categorySlug, $limit = 4) {
    $result = getMediaItems($categorySlug, null, null, 'recent_desc', $limit);
    return $result['items'];
}

/**
 * Authentication Helpers
 */
function getCurrentUser() {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Archivist',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }
    return null;
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

/**
 * Watchlist Database Helpers
 */
function getUserWatchlistCount($userId) {
    $db = getDb();
    if (!$db || !$userId) return 0;
    $stmt = $db->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getUserWatchlist($userId, $category = null) {
    $db = getDb();
    if (!$db || !$userId) return [];
    
    if (!empty($category) && $category !== 'all') {
        $stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = ? AND category = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $category]);
    } else {
        $stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
    }
    return $stmt->fetchAll();
}

function isItemInWatchlist($userId, $externalId, $type) {
    $db = getDb();
    if (!$db || !$userId) return false;
    $stmt = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND external_id = ? AND type = ? LIMIT 1");
    $stmt->execute([$userId, $externalId, $type]);
    return (bool)$stmt->fetch();
}

function addToWatchlistDb($userId, $itemData) {
    $db = getDb();
    if (!$db || !$userId) return false;

    $stmt = $db->prepare("
        INSERT INTO watchlist (user_id, media_item_id, external_id, type, category, title, year, score, poster_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE title = VALUES(title), score = VALUES(score), poster_url = VALUES(poster_url)
    ");

    return $stmt->execute([
        $userId,
        $itemData['media_item_id'] ?? null,
        $itemData['external_id'],
        $itemData['type'] ?? 'media',
        $itemData['category'] ?? 'anime',
        $itemData['title'],
        $itemData['year'] ?? null,
        $itemData['score'] ?? null,
        $itemData['poster_url'] ?? null
    ]);
}

function removeFromWatchlistDb($userId, $externalId, $type) {
    $db = getDb();
    if (!$db || !$userId) return false;
    $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND external_id = ? AND type = ?");
    return $stmt->execute([$userId, $externalId, $type]);
}

/**
 * Events Database & Fallback Helpers
 */
function getAllEvents($category = null, $eventType = null, $search = null, $month = null, $limit = 50) {
    $db = getDb();
    if (!$db) {
        return getFallbackEvents($category, $eventType, $search, $month);
    }

    $where = [];
    $params = [];

    if (!empty($category) && $category !== 'all') {
        $where[] = "category = ?";
        $params[] = $category;
    }

    if (!empty($eventType) && $eventType !== 'all') {
        $where[] = "event_type = ?";
        $params[] = $eventType;
    }

    if (!empty($search)) {
        $where[] = "(title LIKE ? OR venue LIKE ? OR city LIKE ? OR description LIKE ? OR organizer LIKE ?)";
        $term = "%" . $search . "%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($month)) {
        $where[] = "DATE_FORMAT(date_start, '%Y-%m') = ?";
        $params[] = $month;
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT * FROM events $whereSql ORDER BY date_start ASC LIMIT " . (int)$limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    return !empty($results) ? $results : getFallbackEvents($category, $eventType, $search, $month);
}

function getEventById($id) {
    $db = getDb();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) return $row;
    }
    $fallback = getFallbackEvents();
    foreach ($fallback as $ev) {
        if ($ev['id'] == $id) return $ev;
    }
    return null;
}

function getFeaturedEvents($limit = 4) {
    $db = getDb();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM events WHERE is_featured = 1 ORDER BY date_start ASC LIMIT " . (int)$limit);
        $stmt->execute();
        $results = $stmt->fetchAll();
        if (!empty($results)) return $results;
    }
    return array_slice(getFallbackEvents(), 0, $limit);
}

function getFallbackEvents($category = null, $eventType = null, $search = null, $month = null) {
    $items = [
        [
            'id' => 1,
            'title' => 'TwinCon Pop-Culture & Anime Summit',
            'category' => 'convention',
            'event_type' => 'In-Person',
            'date_start' => '2026-10-15',
            'date_end' => '2026-10-17',
            'time_info' => '11:00 AM - 8:00 PM',
            'venue' => 'Pak-China Friendship Centre',
            'city' => 'Islamabad',
            'country' => 'Pakistan',
            'latitude' => 33.6938,
            'longitude' => 73.0850,
            'description' => 'Pakistan premier annual pop culture convention celebrating Japanese anime, manga, indie gaming tournaments, and artisanal cosplay contests with guest panels.',
            'organizer' => 'TwinCon Society',
            'banner_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1000&q=80',
            'badge_text' => 'CONVENTION',
            'ticket_info' => 'Pass Required',
            'is_featured' => 1
        ],
        [
            'id' => 2,
            'title' => 'Karachi Comic Con & Cosplay Gala',
            'category' => 'cosplay',
            'event_type' => 'In-Person',
            'date_start' => '2026-11-06',
            'date_end' => '2026-11-08',
            'time_info' => '10:00 AM - 9:00 PM',
            'venue' => 'Expo Centre Karachi',
            'city' => 'Karachi',
            'country' => 'Pakistan',
            'latitude' => 24.9080,
            'longitude' => 67.0792,
            'description' => 'A massive mega-event for fans across Sindh and Pakistan featuring gaming LAN parties, master armor cosplay crafts, comic book stalls, and artist alley showcases.',
            'organizer' => 'Karachi Pop Guild',
            'banner_url' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=1000&q=80',
            'badge_text' => 'SUMMIT',
            'ticket_info' => 'Tickets Available',
            'is_featured' => 1
        ],
        [
            'id' => 3,
            'title' => 'Lahore Anime & Esports Exhibition',
            'category' => 'gaming',
            'event_type' => 'In-Person',
            'date_start' => '2026-10-28',
            'date_end' => '2026-10-30',
            'time_info' => '12:00 PM - 10:00 PM',
            'venue' => 'Alhamra Arts Council, Mall Road',
            'city' => 'Lahore',
            'country' => 'Pakistan',
            'latitude' => 31.5552,
            'longitude' => 74.3299,
            'description' => 'Competitive Tekken 8 and Street Fighter 6 arena championships, voice acting masterclasses, and curated anime film screenings under the stars.',
            'organizer' => 'Lahore Gamers Alliance',
            'banner_url' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1000&q=80',
            'badge_text' => 'ESPORTS',
            'ticket_info' => 'Free Entry',
            'is_featured' => 1
        ],
        [
            'id' => 4,
            'title' => 'Tokyo Game Show (TGS 2026)',
            'category' => 'gaming',
            'event_type' => 'Hybrid',
            'date_start' => '2026-09-24',
            'date_end' => '2026-09-27',
            'time_info' => '09:30 AM - 5:30 PM',
            'venue' => 'Makuhari Messe, Chiba',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'latitude' => 35.6477,
            'longitude' => 140.0347,
            'description' => 'The epicenter of Japanese and global video games. Hands-on demos for unreleased PlayStation, Nintendo, and PC titles with worldwide livestream stages.',
            'organizer' => 'CESA Japan',
            'banner_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1000&q=80',
            'badge_text' => 'EXPO',
            'ticket_info' => 'Public Days',
            'is_featured' => 1
        ],
        [
            'id' => 5,
            'title' => 'AnimeJapan 2026 Expo',
            'category' => 'anime',
            'event_type' => 'In-Person',
            'date_start' => '2026-10-21',
            'date_end' => '2026-10-23',
            'time_info' => '09:00 AM - 5:00 PM',
            'venue' => 'Tokyo Big Sight Convention Hall',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'latitude' => 35.6298,
            'longitude' => 139.7944,
            'description' => 'The world largest Japanese animation festival. Studio MAPPA, Ufotable, and Toei Animation announce seasonal premieres with exclusive voice actor panels.',
            'organizer' => 'AnimeJapan Exec Comm',
            'banner_url' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1000&q=80',
            'badge_text' => 'ANIME EXPO',
            'ticket_info' => 'Badge Required',
            'is_featured' => 1
        ],
        [
            'id' => 6,
            'title' => 'San Diego Comic-Con (SDCC)',
            'category' => 'convention',
            'event_type' => 'In-Person',
            'date_start' => '2026-10-24',
            'date_end' => '2026-10-28',
            'time_info' => '09:00 AM - 7:00 PM',
            'venue' => 'San Diego Convention Center',
            'city' => 'San Diego',
            'country' => 'USA',
            'latitude' => 32.7071,
            'longitude' => -117.1611,
            'description' => 'Hall H movie blockbuster trailers, star-studded panels, Eisner comic awards, and legendary collectible reveals that define global entertainment.',
            'organizer' => 'Comic-Con International',
            'banner_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1000&q=80',
            'badge_text' => 'GLOBAL CON',
            'ticket_info' => 'Passes Open',
            'is_featured' => 1
        ]
    ];

    return array_filter($items, function($item) use ($category, $eventType, $search, $month) {
        if ($category && $category !== 'all' && $item['category'] !== $category) return false;
        if ($eventType && $eventType !== 'all' && $item['event_type'] !== $eventType) return false;
        if ($month && substr($item['date_start'], 0, 7) !== $month) return false;
        if ($search) {
            $blob = strtolower($item['title'] . ' ' . $item['city'] . ' ' . $item['venue'] . ' ' . $item['description']);
            if (strpos($blob, strtolower($search)) === false) return false;
        }
        return true;
    });
}

/**
 * Merchandise Showcase (Virtual Exhibition Only - Strictly Not For Sale) Helpers
 */
function getAllMerchandise($category = null, $franchise = null, $rarity = null, $search = null, $sort = 'default', $limit = 50) {
    $db = getDb();
    if (!$db) {
        return getFallbackMerchandise($category, $franchise, $rarity, $search, $sort);
    }

    $where = [];
    $params = [];

    if (!empty($category) && $category !== 'all') {
        $where[] = "category = ?";
        $params[] = $category;
    }

    if (!empty($franchise) && $franchise !== 'all') {
        $where[] = "franchise LIKE ?";
        $params[] = "%" . $franchise . "%";
    }

    if (!empty($rarity) && $rarity !== 'all') {
        $where[] = "rarity_tier = ?";
        $params[] = $rarity;
    }

    if (!empty($search)) {
        $where[] = "(name LIKE ? OR franchise LIKE ? OR description LIKE ? OR item_type LIKE ? OR material LIKE ?)";
        $term = "%" . $search . "%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $orderSql = "ORDER BY is_featured DESC, id ASC";
    if ($sort === 'year_desc') $orderSql = "ORDER BY year_released DESC";
    if ($sort === 'year_asc') $orderSql = "ORDER BY year_released ASC";
    if ($sort === 'name_asc') $orderSql = "ORDER BY name ASC";

    $sql = "SELECT * FROM merchandise $whereSql $orderSql LIMIT " . (int)$limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    return !empty($results) ? $results : getFallbackMerchandise($category, $franchise, $rarity, $search, $sort);
}

function getMerchandiseById($id) {
    $db = getDb();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM merchandise WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) return $row;
    }
    $fallback = getFallbackMerchandise();
    foreach ($fallback as $m) {
        if ($m['id'] == $id) return $m;
    }
    return null;
}

function getFeaturedMerchandise($limit = 4) {
    $db = getDb();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM merchandise WHERE is_featured = 1 ORDER BY id ASC LIMIT " . (int)$limit);
        $stmt->execute();
        $results = $stmt->fetchAll();
        if (!empty($results)) return $results;
    }
    return array_slice(getFallbackMerchandise(), 0, $limit);
}

function getFallbackMerchandise($category = null, $franchise = null, $rarity = null, $search = null, $sort = 'default') {
    $items = [
        [
            'id' => 1,
            'name' => 'Tanjiro Kamado: Hinokami Kagura Deluxe Scale',
            'category' => 'toys',
            'franchise' => 'Demon Slayer: Kimetsu no Yaiba',
            'item_type' => '1/7 Scale PVC & ABS Figure',
            'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&q=80',
            'scale_or_size' => '1/7 Scale (28cm Height)',
            'material' => 'High-Grade PVC, Translucent ABS Resin',
            'manufacturer' => 'Aniplex+ / Good Smile Company',
            'rarity_tier' => 'Master Tier',
            'year_released' => 2024,
            'description' => 'Archival museum exhibit capturing Tanjiro Kamado unleashing the Hinokami Kagura Dance of the Fire God. Cast in crystal resin flames with dynamic smoke billowing pedestal.',
            'accession_no' => 'ARC-MC-FIG01',
            'is_featured' => 1
        ],
        [
            'id' => 2,
            'name' => 'RX-78-2 Gundam: Perfect Grade Unleashed 1/60',
            'category' => 'toys',
            'franchise' => 'Mobile Suit Gundam',
            'item_type' => '1/60 Master Grade Model Kit',
            'image_url' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&q=80',
            'scale_or_size' => '1/60 Scale (30cm Height)',
            'material' => 'Multi-colored Polystyrene, Etched Metal, LEDs',
            'manufacturer' => 'Bandai Spirits Hobby Division',
            'rarity_tier' => 'Museum Artifact',
            'year_released' => 2023,
            'description' => 'The zenith of Gundam model engineering. Features a 4-layer internal skeleton, 59 points of articulation, photo-etched brass accents, and full cockpit LED lighting system.',
            'accession_no' => 'ARC-MC-GUN02',
            'is_featured' => 1
        ],
        [
            'id' => 5,
            'name' => 'Survey Corps Scout Regiment Wool Cloak',
            'category' => 'clothing',
            'franchise' => 'Attack on Titan (Shingeki no Kyojin)',
            'item_type' => 'Artisanal Archival Apparel',
            'image_url' => 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80',
            'scale_or_size' => 'Unisex Oversized (Length 125cm)',
            'material' => '100% Melton Wool, Antique Brass Clasp',
            'manufacturer' => 'Atelier Eldia Heritage',
            'rarity_tier' => 'Master Tier',
            'year_released' => 2024,
            'description' => 'Screen-accurate reproduction of the Survey Corps expedition cloak. Deep forest moss hue woven with heavy virgin wool, double-stitched Wings of Freedom crest in dual-tone thread.',
            'accession_no' => 'ARC-MC-CLO01',
            'is_featured' => 1
        ],
        [
            'id' => 6,
            'name' => 'Samurai LED Reversible Bomber Jacket',
            'category' => 'clothing',
            'franchise' => 'Cyberpunk 2077',
            'item_type' => 'Technical Streetwear Apparel',
            'image_url' => 'https://images.unsplash.com/photo-1548883354-7622d03aca27?w=800&q=80',
            'scale_or_size' => 'Size L (Tailored Fit)',
            'material' => 'Waterproof Ripstop Nylon, High-Density Fleece',
            'manufacturer' => 'CD PROJEKT RED Official Atelier',
            'rarity_tier' => 'Collector Grade',
            'year_released' => 2023,
            'description' => 'Features illuminated cyan fiber-optic neck collar with USB rechargeable battery pack, embroidered flaming Samurai oni logo on the back, and reversible safety orange interior.',
            'accession_no' => 'ARC-MC-JKT02',
            'is_featured' => 1
        ],
        [
            'id' => 9,
            'name' => 'Roronoa Zoro: Wado Ichimonji Hand-Forged Katana',
            'category' => 'collectibles',
            'franchise' => 'One Piece',
            'item_type' => '1:1 Scale Prop Replica Sword',
            'image_url' => 'https://images.unsplash.com/photo-1589254065878-42c9da997008?w=800&q=80',
            'scale_or_size' => 'Overall Length 104cm (Blade 71cm)',
            'material' => '1060 Folded High-Carbon Steel, Ray Skin',
            'manufacturer' => 'Master Swordsmith Guild Kyoto',
            'rarity_tier' => 'Museum Artifact',
            'year_released' => 2023,
            'description' => 'Authentic battle-ready recreation of Kuina beloved heirloom katana. True clay-tempered hamon wave line, ray-skin tsuka wrapped in pure white ito cord, polished white lacquer saya.',
            'accession_no' => 'ARC-MC-SWD01',
            'is_featured' => 1
        ],
        [
            'id' => 10,
            'name' => 'Death Note: Shuffled Rulebook with Crow Quill',
            'category' => 'collectibles',
            'franchise' => 'Death Note',
            'item_type' => 'Prop Replica Display Artifact',
            'image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&q=80',
            'scale_or_size' => '19cm x 26cm Hardcover',
            'material' => 'Aged Calfskin Leather, Parchment, Natural Quill',
            'manufacturer' => 'Kira Archive Bureau',
            'rarity_tier' => 'Master Tier',
            'year_released' => 2024,
            'description' => 'Textured leather ledger inscribed with silver foil lettering. Includes all 66 English/Japanese usage rules, recreated Shinigami Ryuk instructions, and genuine crow feather ink pen.',
            'accession_no' => 'ARC-MC-BOK02',
            'is_featured' => 1
        ]
    ];

    return array_filter($items, function($item) use ($category, $franchise, $rarity, $search) {
        if ($category && $category !== 'all' && $item['category'] !== $category) return false;
        if ($franchise && $franchise !== 'all' && stripos($item['franchise'], $franchise) === false) return false;
        if ($rarity && $rarity !== 'all' && $item['rarity_tier'] !== $rarity) return false;
        if ($search) {
            $blob = strtolower($item['name'] . ' ' . $item['franchise'] . ' ' . $item['description'] . ' ' . $item['item_type']);
            if (strpos($blob, strtolower($search)) === false) return false;
        }
        return true;
    });
}
