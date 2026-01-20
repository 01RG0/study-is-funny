<?php
/**
 * Video Streaming API Endpoint
 * Streams video with range support for seeking
 */

require_once '../includes/session_check.php';
requireLogin();

$videoId = $_GET['id'] ?? '';
if (!$videoId) {
    http_response_code(400);
    die('Video ID required');
}

require_once '../config/config.php';
$db = new DatabaseMongo();
$videoManager = new Video($db);

// Stream the video (with view count increment)
$videoManager->stream($videoId, false); // Don't increment here since we do it on the page
?>
