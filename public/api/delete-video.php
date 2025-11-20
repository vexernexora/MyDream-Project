<?php
/**
 * API: Usuwanie filmu z biblioteki (tylko z JSON, nie usuwa pliku)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Tylko POST/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    $videoId = $input['id'];

    // Usuń miniaturkę jeśli istnieje
    $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
    if (file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }

    // Usuń z JSON
    if (JsonHelper::deleteVideo($videoId)) {
        echo json_encode([
            'success' => true,
            'message' => 'Film usunięty z biblioteki'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Film nie znaleziony']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas usuwania: ' . $e->getMessage()
    ]);
    debug_log("Błąd w delete-video.php: " . $e->getMessage());
}
