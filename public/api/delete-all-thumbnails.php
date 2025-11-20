<?php
/**
 * API: Usuń wszystkie miniatury
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $deleted = 0;
    $files = glob(THUMBNAILS_PATH . '/*.jpg');

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }

    // Aktualizuj status w JSON
    $videos = JsonHelper::getVideos();
    foreach ($videos as $video) {
        JsonHelper::updateVideo($video['id'], ['thumbnail_generated' => false]);
    }

    echo json_encode([
        'success' => true,
        'message' => "Usunięto {$deleted} miniatur",
        'deleted' => $deleted
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
