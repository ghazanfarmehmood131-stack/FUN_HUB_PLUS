<?php
/**
 * Events API Endpoint
 * Serves events for Interactive Map and Calendar views.
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$category  = $_GET['category'] ?? 'all';
$eventType = $_GET['type'] ?? 'all';
$search    = $_GET['q'] ?? '';
$month     = $_GET['month'] ?? '';
$limit     = min(100, max(1, (int)($_GET['limit'] ?? 50)));

// Handle RSVP or save interest
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int)($input['event_id'] ?? 0);
    $action  = $input['action'] ?? 'rsvp';
    
    if (!$eventId) {
        echo json_encode(['success' => false, 'error' => 'Invalid event ID']);
        exit;
    }
    
    // Store in session RSVP list
    if (!isset($_SESSION['rsvp_events'])) {
        $_SESSION['rsvp_events'] = [];
    }
    
    if ($action === 'rsvp') {
        if (!in_array($eventId, $_SESSION['rsvp_events'])) {
            $_SESSION['rsvp_events'][] = $eventId;
        }
        $isRsvpd = true;
    } else {
        $_SESSION['rsvp_events'] = array_values(array_diff($_SESSION['rsvp_events'], [$eventId]));
        $isRsvpd = false;
    }
    
    echo json_encode([
        'success' => true,
        'event_id' => $eventId,
        'rsvpd' => $isRsvpd,
        'rsvps' => $_SESSION['rsvp_events'],
        'message' => $isRsvpd ? 'Event marked as Interested / RSVP confirmed' : 'RSVP removed'
    ]);
    exit;
}

$events = getAllEvents($category, $eventType, $search, $month, $limit);

// Enrich with user RSVP status if any
$userRsvps = $_SESSION['rsvp_events'] ?? [];
$formatted = array_map(function($ev) use ($userRsvps) {
    $ev['is_rsvpd'] = in_array((int)$ev['id'], $userRsvps);
    return $ev;
}, $events);

echo json_encode([
    'success' => true,
    'total' => count($formatted),
    'category' => $category,
    'type' => $eventType,
    'data' => array_values($formatted)
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
