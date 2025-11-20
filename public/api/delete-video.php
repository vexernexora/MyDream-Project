<?php
/**
 * API: Usuwanie filmu z biblioteki (tylko z JSON, nie usuwa pliku)
 */

require_once __DIR__ . '/common.php';

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
    $deleteFile = $input['delete_file'] ?? false;

    // Pobierz dane filmu
    $video = null;
    $videos = JsonHelper::getVideos();
    foreach ($videos as $v) {
        if ($v['id'] === $videoId) {
            $video = $v;
            break;
        }
    }

    if (!$video) {
        http_response_code(404);
        echo json_encode(['error' => 'Film nie znaleziony']);
        exit;
    }

    // Usuń miniaturkę jeśli istnieje
    $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
    if (file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }

    // Jeśli usuwanie fizyczne - usuń plik wideo
    if ($deleteFile) {
        $videoPath = VIDEOS_PATH . '/' . $video['relative_path'];
        if (file_exists($videoPath)) {
            unlink($videoPath);
        }
    } else {
        // Tylko z biblioteki - dodaj do blacklisty
        $blacklistFile = DATA_PATH . '/deleted_videos.json';
        $blacklist = [];

        if (file_exists($blacklistFile)) {
            $blacklist = json_decode(file_get_contents($blacklistFile), true) ?? [];
        }

        // Dodaj filename do blacklisty
        if (!in_array($video['filename'], $blacklist)) {
            $blacklist[] = $video['filename'];
            file_put_contents($blacklistFile, json_encode($blacklist, JSON_PRETTY_PRINT));
        }
    }

    // Usuń z JSON
    if (JsonHelper::deleteVideo($videoId)) {
        echo json_encode([
            'success' => true,
            'message' => $deleteFile ? 'Film usunięty z dysku' : 'Film ukryty (nie wróci po skanowaniu)'
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
