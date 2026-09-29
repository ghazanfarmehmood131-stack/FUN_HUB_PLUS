<?php
/**
 * Items API Endpoint
 * Provides JSON dataset queried from MySQL with category filtering,
 * genres, sorting, and pagination.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$category = $_GET['category'] ?? 'all';
$genre    = $_GET['genre'] ?? 'all';
$search   = $_GET['q'] ?? '';
$sort     = $_GET['sort'] ?? 'score_desc';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = min(100, max(1, (int)($_GET['per_page'] ?? 12)));
$offset   = ($page - 1) * $perPage;

if (isset($_GET['id'])) {
    $item = getMediaItemDetail((string)$_GET['id']);
    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit;
    }
    $meta = is_array($item['meta_parsed'] ?? null) ? $item['meta_parsed'] : [];
    echo json_encode(['success' => true, 'item' => array_merge($meta, [
        'id' => $item['external_id'],
        'type' => $item['type'],
        'category' => $item['category_slug'],
        'title' => $item['title'],
        'originalTitle' => $item['original_title'],
        'year' => (int)$item['year'],
        'score' => (float)$item['score'],
        'status' => $item['status'],
        'duration' => $item['duration_or_episodes'],
        'genres' => array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))),
        'synopsis' => $item['synopsis'],
        'poster' => safePosterUrl($item['poster_url'], $item['title'], $item['category_slug']),
        'backdrop' => safePosterUrl($item['backdrop_url'], $item['title'], $item['category_slug']),
        'trailerKey' => $item['trailer_key'],
        'trailerUrl' => $item['trailer_url'],
        'director' => $item['director_or_creator'],
        'studio' => $item['studio_or_publisher'],
    ])], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$result = getMediaItems($category, $genre, $search, $sort, $perPage, $offset);

// Format items for client consumption
$formatted = array_map(function($item) {
    return [
        'id'          => $item['external_id'],
        'db_id'       => $item['id'],
        'type'        => $item['type'],
        'category'    => $item['category_slug'],
        'title'       => $item['title'],
        'originalTitle' => $item['original_title'],
        'year'        => (int)$item['year'],
        'score'       => (float)$item['score'],
        'status'      => $item['status'],
        'duration'    => $item['duration_or_episodes'],
        'genres'      => array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))),
        'synopsis'    => $item['synopsis'],
        'poster'      => safePosterUrl($item['poster_url'], $item['title'], $item['category_slug']),
        'backdrop'    => safePosterUrl($item['backdrop_url'], $item['title'], $item['category_slug']),
        'trailerKey'  => $item['trailer_key'],
        'trailerUrl'  => $item['trailer_url'],
        'director'    => $item['director_or_creator'],
        'studio'      => $item['studio_or_publisher']
    ];
}, $result['items']);

echo json_encode([
    'success' => true,
    'category' => $category,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $result['total'],
    'has_next_page' => ($offset + $perPage) < $result['total'],
    'data' => $formatted
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
