<?php
require_once __DIR__ . '/config.php';

$videoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$downloadRequested = isset($_GET['download']) && $_GET['download'] === '1';
if (!$videoId) {
    http_response_code(404);
    exit;
}

if (!isLoggedIn()) {
    header('Location: auth.php?next=' . rawurlencode('video.php?id=' . $videoId));
    exit;
}
session_write_close();

$db = getDb();
if (!$db) {
    http_response_code(503);
    exit('Video service unavailable.');
}

$stmt = $db->prepare("SELECT v.file_name, v.mime_type, i.title
    FROM media_videos v
    INNER JOIN media_items i ON i.id = v.media_item_id
    WHERE v.id = ? AND i.category_slug <> 'gaming'
    LIMIT 1");
$stmt->execute([$videoId]);
$video = $stmt->fetch();
if (!$video || !preg_match('/^[a-f0-9]{64}\.(?:mp4|webm|ogv)$/', $video['file_name'])) {
    http_response_code(404);
    exit;
}

$storagePath = realpath(__DIR__ . '/storage/videos');
$filePath = $storagePath ? realpath($storagePath . DIRECTORY_SEPARATOR . $video['file_name']) : false;
$mimeTypes = [
    'video/mp4' => 'video/mp4',
    'application/mp4' => 'video/mp4',
    'video/webm' => 'video/webm',
    'video/ogg' => 'video/ogg',
    'application/ogg' => 'video/ogg',
];
if (!$storagePath || !$filePath || dirname($filePath) !== $storagePath || !is_file($filePath)
    || !isset($mimeTypes[$video['mime_type']])) {
    http_response_code(404);
    exit;
}

$size = filesize($filePath);
if ($size === false || $size <= 0) {
    http_response_code(404);
    exit;
}

$start = 0;
$end = $size - 1;
$status = 200;
if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/D', (string)$_SERVER['HTTP_RANGE'], $matches)
        || ($matches[1] === '' && $matches[2] === '')) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    if ($matches[1] === '') {
        $suffixLength = (int)$matches[2];
        if ($suffixLength <= 0) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
        $start = max(0, $size - $suffixLength);
    } else {
        $start = (int)$matches[1];
        if ($matches[2] !== '') $end = min($end, (int)$matches[2]);
    }
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    $status = 206;
}

http_response_code($status);
header('Content-Type: ' . $mimeTypes[$video['mime_type']]);
$disposition = $downloadRequested ? 'attachment' : 'inline';
$extension = pathinfo($video['file_name'], PATHINFO_EXTENSION);
header('Content-Disposition: ' . $disposition . '; filename="fan-hub-video-' . $videoId . '.' . $extension . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Accept-Ranges: bytes');
header('Content-Length: ' . ($end - $start + 1));
if ($status === 206) header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') exit;
$handle = fopen($filePath, 'rb');
if (!$handle || fseek($handle, $start) !== 0) {
    if (is_resource($handle)) fclose($handle);
    exit;
}
while (!feof($handle) && ($remaining = $end - ftell($handle) + 1) > 0) {
    $chunk = fread($handle, min(1024 * 1024, $remaining));
    if ($chunk === false || $chunk === '') break;
    echo $chunk;
    flush();
    if (connection_aborted()) break;
}
fclose($handle);