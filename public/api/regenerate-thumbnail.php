<?php
/**
 * API: Regenerowanie miniatury dla filmu
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Tylko POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    // Pobierz dane
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    $videoId = $input['id'];
    $video = JsonHelper::getVideoById($videoId);

    if (!$video) {
        http_response_code(404);
        echo json_encode(['error' => 'Film nie znaleziony']);
        exit;
    }

    // Ścieżka do filmu
    $videoPath = VIDEOS_PATH . '/' . $video['relative_path'];

    if (!file_exists($videoPath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Plik wideo nie istnieje']);
        exit;
    }

    // Pobierz timestamp (możesz użyć custom timestamp z requestu)
    $timestamp = $input['timestamp'] ?? null;

    if ($timestamp === null) {
        // Znajdź najlepszy moment
        $metadata = VideoHelper::getVideoMetadata($videoPath);
        $timestamp = AIHelper::suggestThumbnailTimestamp([
            'path' => $videoPath,
            'filename' => $video['filename']
        ], $metadata);
    }

    // Wygeneruj miniaturkę
    $thumbnailPath = VideoHelper::getThumbnailPath($videoId);

    // Usuń starą miniaturkę jeśli istnieje
    if (file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }

    if (VideoHelper::generateThumbnail($videoPath, $thumbnailPath, (float)$timestamp)) {
        // Aktualizuj dane w JSON
        JsonHelper::updateVideo($videoId, [
            'thumbnail_generated' => true,
            'thumbnail_timestamp' => $timestamp,
            'thumbnail_regenerated_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Miniatura wygenerowana',
            'thumbnail_url' => VideoHelper::getThumbnailUrl($videoId),
            'timestamp' => $timestamp
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Nie udało się wygenerować miniatury']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas generowania miniatury: ' . $e->getMessage()
    ]);
    debug_log("Błąd w regenerate-thumbnail.php: " . $e->getMessage());
}
