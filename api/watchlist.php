<?php
/**
 * Watchlist API Endpoint
 * Handles reading and modifying watchlist stored in MySQL for the current session.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$currentUser = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!$currentUser) {
        echo json_encode([
            'success' => true,
            'authenticated' => false,
            'message' => 'Guest user. Relying on local client storage.',
            'data' => []
        ]);
        exit;
    }

    $category = $_GET['category'] ?? 'all';
    $items = getUserWatchlist($currentUser['id'], $category);
    
    $formatted = array_map(function($row) {
        return [
            'id' => $row['external_id'],
            'type' => $row['type'],
            'category' => $row['category'],
            'title' => $row['title'],
            'year' => (int)$row['year'],
            'score' => (float)$row['score'],
            'poster' => safePosterUrl($row['poster_url'], $row['title'], $row['category']),
            'savedAt' => $row['created_at']
        ];
    }, $items);

    echo json_encode([
        'success' => true,
        'authenticated' => true,
        'total' => count($formatted),
        'data' => $formatted
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'toggle';

    if (!$currentUser) {
        // Return guidance to store in localStorage
        echo json_encode([
            'success' => true,
            'authenticated' => false,
            'message' => 'Guest action stored locally.'
        ]);
        exit;
    }

    $userId = $currentUser['id'];

    if ($action === 'add') {
        $ok = addToWatchlistDb($userId, [
            'external_id' => $input['id'] ?? '',
            'type'        => $input['type'] ?? 'media',
            'category'    => $input['category'] ?? 'anime',
            'title'       => $input['title'] ?? 'Untitled Record',
            'year'        => $input['year'] ?? null,
            'score'       => $input['score'] ?? null,
            'poster_url'  => $input['poster'] ?? null
        ]);
        echo json_encode(['success' => (bool)$ok, 'action' => 'added']);
        exit;
    }

    if ($action === 'remove') {
        $externalId = $input['id'] ?? '';
        $type       = $input['type'] ?? 'media';
        $ok = removeFromWatchlistDb($userId, $externalId, $type);
        echo json_encode(['success' => (bool)$ok, 'action' => 'removed']);
        exit;
    }

    if ($action === 'clear') {
        $db = getDb();
        if ($db) {
            $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        echo json_encode(['success' => true, 'action' => 'cleared']);
        exit;
    }

    if ($action === 'toggle') {
        $externalId = $input['id'] ?? '';
        $type       = $input['type'] ?? 'media';
        $has = isItemInWatchlist($userId, $externalId, $type);
        if ($has) {
            removeFromWatchlistDb($userId, $externalId, $type);
            echo json_encode(['success' => true, 'saved' => false, 'action' => 'removed']);
        } else {
            addToWatchlistDb($userId, [
                'external_id' => $externalId,
                'type'        => $type,
                'category'    => $input['category'] ?? 'anime',
                'title'       => $input['title'] ?? 'Untitled Record',
                'year'        => $input['year'] ?? null,
                'score'       => $input['score'] ?? null,
                'poster_url'  => $input['poster'] ?? null
            ]);
            echo json_encode(['success' => true, 'saved' => true, 'action' => 'added']);
        }
        exit;
    }
}
