<?php
/**
 * API: Skanowanie folderu videos i dodawanie nowych filmów
 */

require_once __DIR__ . '/common.php';

// Tylko POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    // Skanuj folder
    $files = VideoHelper::scanVideosFolder();

    if (empty($files)) {
        echo json_encode([
            'success' => true,
            'message' => 'Nie znaleziono nowych filmów',
            'new_videos' => 0,
            'total_videos' => count(JsonHelper::getVideos())
        ]);
        exit;
    }

    // Pobierz istniejące filmy
    $existingVideos = JsonHelper::getVideos();
    $existingFilenames = array_column($existingVideos, 'filename');

    $newVideos = [];
    $processed = 0;

    foreach ($files as $file) {
        // Sprawdź czy film już istnieje
        if (in_array($file['filename'], $existingFilenames)) {
            continue;
        }

        $processed++;

        // Pobierz metadata z FFmpeg
        $metadata = VideoHelper::getVideoMetadata($file['path']);

        // Analizuj za pomocą AI
        $aiData = AIHelper::analyzeVideo($file, $metadata);

        // Wygeneruj ID
        $videoId = VideoHelper::generateVideoId($file['filename']);

        // Przygotuj dane filmu
        $videoData = [
            'id' => $videoId,
            'filename' => $file['filename'],
            'relative_path' => $file['relative_path'],
            'title' => $aiData['title'] ?? $file['filename'],
            'description' => $aiData['description'] ?? '',
            'tags' => $aiData['tags'] ?? [],
            'category' => $aiData['category'] ?? 'Inne',
            'duration_seconds' => $metadata['duration'] ?? 0,
            'duration_formatted' => $metadata['duration_formatted'] ?? '0:00',
            'size' => $file['size'],
            'size_formatted' => $metadata['size_formatted'] ?? VideoHelper::formatFileSize($file['size']),
            'width' => $metadata['width'] ?? null,
            'height' => $metadata['height'] ?? null,
            'codec' => $metadata['codec'] ?? null,
            'fps' => $metadata['fps'] ?? null,
            'added_at' => time(),
            'added_at_formatted' => date('Y-m-d H:i:s'),
            'thumbnail_generated' => false,
            'ai_generated' => $aiData['ai_generated'] ?? false,
        ];

        // Generuj miniaturkę w tle
        $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
        $timestamp = AIHelper::suggestThumbnailTimestamp($file, $metadata);

        if (VideoHelper::generateThumbnail($file['path'], $thumbnailPath, $timestamp)) {
            $videoData['thumbnail_generated'] = true;
            $videoData['thumbnail_timestamp'] = $timestamp;
        }

        // Dodaj do JSON
        if (JsonHelper::addVideo($videoData)) {
            $newVideos[] = $videoData;
        }
    }

    // Aktualizuj czas ostatniego skanowania
    JsonHelper::updateLastScan();

    echo json_encode([
        'success' => true,
        'message' => "Dodano {$processed} nowych filmów",
        'new_videos' => $processed,
        'total_videos' => count(JsonHelper::getVideos()),
        'videos' => $newVideos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Błąd podczas skanowania: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getTraceAsString() : null
    ]);

    if (function_exists('debug_log')) {
        debug_log("Błąd w scan.php: " . $e->getMessage() . " w " . $e->getFile() . ":" . $e->getLine());
    }
}
