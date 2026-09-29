<?php
/**
 * Merchandise Showcase API Endpoint (Virtual Museum / Collector Catalog Only)
 * STRICTLY NON-COMMERCIAL / NOT FOR SALE
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$category  = $_GET['category'] ?? 'all';
$franchise = $_GET['franchise'] ?? 'all';
$rarity    = $_GET['rarity'] ?? 'all';
$search    = $_GET['q'] ?? '';
$sort      = $_GET['sort'] ?? 'default';
$limit     = min(100, max(1, (int)($_GET['limit'] ?? 50)));

// Single item detail
if (!empty($_GET['id'])) {
    $item = getMerchandiseById((int)$_GET['id']);
    if ($item) {
        echo json_encode([
            'success' => true,
            'data' => $item,
            'is_showcase_only' => true,
            'notice' => 'Yeh cheez sirf dekhne ke liye numayish hai, yahan se khareedi nahi ja sakti.'
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Exhibit item not found'], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$items = getAllMerchandise($category, $franchise, $rarity, $search, $sort, $limit);

echo json_encode([
    'success' => true,
    'total' => count($items),
    'is_showcase_only' => true,
    'notice' => 'Strictly Archival Collector Showcase — Items Not For Sale',
    'data' => array_values($items)
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
