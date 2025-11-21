<?php
/**
 * Thumbnail serving endpoint
 * Serves thumbnail images from data/thumbnails/ directory
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// Get video ID from query parameter
$videoId = $_GET['id'] ?? null;

if (!$videoId) {
    // Serve placeholder if no ID
    serveNoThumbnail();
    exit;
}

// Validate video ID format (basic security check)
if (!preg_match('/^[a-f0-9]{32}$/', $videoId)) {
    serveNoThumbnail();
    exit;
}

// Construct path to thumbnail
$thumbnailPath = THUMBNAILS_PATH . '/' . $videoId . '.jpg';

// Check if thumbnail exists
if (!file_exists($thumbnailPath) || !is_file($thumbnailPath)) {
    serveNoThumbnail();
    exit;
}

// Serve thumbnail
$fileSize = filesize($thumbnailPath);
$lastModified = filemtime($thumbnailPath);

// Set caching headers
header('Content-Type: image/jpeg');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Check if client has cached version
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($ifModifiedSince >= $lastModified) {
        http_response_code(304); // Not Modified
        exit;
    }
}

// Output file
readfile($thumbnailPath);
exit;

/**
 * Serve placeholder thumbnail
 */
function serveNoThumbnail(): void
{
    $placeholderPath = __DIR__ . '/img/no-thumbnail.jpg';

    if (file_exists($placeholderPath)) {
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($placeholderPath));
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day
        readfile($placeholderPath);
    } else {
        // Generate a simple 1x1 gray pixel if placeholder doesn't exist
        http_response_code(200);
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mM8c+ZMPQAGgAKJtA9XPQAAAABJRU5ErkJggg==');
    }
}
