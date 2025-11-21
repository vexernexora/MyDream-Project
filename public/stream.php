<?php
/**
 * Video streaming endpoint with range request support
 * Serves videos from the videos/ directory outside public/
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// Get video ID from query parameter
$videoId = $_GET['id'] ?? null;

if (!$videoId) {
    http_response_code(400);
    exit('Missing video ID');
}

// Get video from database
$video = JsonHelper::getVideoById($videoId);

if (!$video) {
    http_response_code(404);
    exit('Video not found');
}

// Construct absolute path to video file
$videoPath = VIDEOS_PATH . '/' . $video['relative_path'];

// Check if file exists
if (!file_exists($videoPath) || !is_file($videoPath)) {
    http_response_code(404);
    exit('Video file not found');
}

// Get file info
$fileSize = filesize($videoPath);
$filename = basename($videoPath);

// Determine MIME type based on extension
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimeTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogv' => 'video/ogg',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'mkv' => 'video/x-matroska',
    'flv' => 'video/x-flv',
    'm4v' => 'video/mp4',
    '3gp' => 'video/3gpp',
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Handle range requests (required for video seeking)
$start = 0;
$end = $fileSize - 1;
$length = $fileSize;

if (isset($_SERVER['HTTP_RANGE'])) {
    // Parse Range header
    if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
        $start = intval($matches[1]);
        if (!empty($matches[2])) {
            $end = intval($matches[2]);
        }
    }

    // Validate range
    if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
        http_response_code(416); // Range Not Satisfiable
        header("Content-Range: bytes */$fileSize");
        exit;
    }

    $length = $end - $start + 1;

    // Send 206 Partial Content response
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$fileSize");
} else {
    // Send 200 OK for full file
    http_response_code(200);
}

// Set headers
header("Content-Type: $mimeType");
header("Content-Length: $length");
header("Accept-Ranges: bytes");
header("Cache-Control: public, max-age=31536000"); // Cache for 1 year
header("Content-Disposition: inline; filename=\"$filename\"");

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Open file and seek to start position
$fp = fopen($videoPath, 'rb');
if (!$fp) {
    http_response_code(500);
    exit('Cannot open video file');
}

fseek($fp, $start);

// Stream the file in chunks
$chunkSize = 1024 * 1024; // 1MB chunks
$bytesRemaining = $length;

while ($bytesRemaining > 0 && !feof($fp)) {
    $bytesToRead = min($chunkSize, $bytesRemaining);
    $chunk = fread($fp, $bytesToRead);

    if ($chunk === false) {
        break;
    }

    echo $chunk;
    flush();

    $bytesRemaining -= strlen($chunk);
}

fclose($fp);
exit;
