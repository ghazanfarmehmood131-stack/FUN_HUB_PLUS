<?php
/**
 * Search API Endpoint
 * Live search across all 8 categories in MySQL ledger.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$query    = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? 'all');
$prefixOnly = ($_GET['prefix'] ?? '') === '1';
$limit    = min(30, max(1, (int)($_GET['limit'] ?? 10)));

if (empty($query)) {
    echo json_encode(['success' => true, 'total' => 0, 'data' => []]);
    exit;
}

if ($prefixOnly) {
    $db = getDb();
    $items = [];
    $searchAvailable = $db !== null;
    if ($db) {
        $sql = 'SELECT * FROM media_items WHERE title LIKE ?';
        $params = [$query . '%'];
        if ($category !== 'all') {
            $sql .= ' AND category_slug = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY score DESC, title ASC LIMIT ' . $limit;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
    }
    $result = ['items' => $items, 'total' => count($items)];
} else {
    $result = getMediaItems($category, 'all', $query, 'score_desc', $limit, 0);
}

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
        'genres'      => array_filter(array_map('trim', explode(',', $item['genres'] ?? ''))),
        'synopsis'    => $item['synopsis'],
        'poster'      => safePosterUrl($item['poster_url'], $item['title'], $item['category_slug']),
        'director'    => $item['director_or_creator']
    ];
}, $result['items']);

echo json_encode([
    'success' => $searchAvailable ?? true,
    'query'   => $query,
    'total'   => $result['total'],
    'data'    => $formatted
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
