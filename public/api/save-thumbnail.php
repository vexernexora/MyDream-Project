<?php
/**
 * API: Zapisywanie miniaturki wygenerowanej po stronie klienta
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $videoId = $input['video_id'] ?? '';
    $imageData = $input['image_data'] ?? '';

    if (empty($videoId) || empty($imageData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych parametrów']);
        exit;
    }

    // Dekoduj base64
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
    }

    $imageData = base64_decode($imageData);

    if ($imageData === false) {
        throw new Exception('Nieprawidłowe dane obrazu');
    }

    // Upewnij się że folder istnieje
    if (!is_dir(THUMBNAILS_PATH)) {
        mkdir(THUMBNAILS_PATH, 0755, true);
    }

    // Zapisz miniaturkę
    $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
    file_put_contents($thumbnailPath, $imageData);

    // Zaktualizuj status w bazie
    $videos = JsonHelper::getVideos();
    foreach ($videos as &$video) {
        if ($video['id'] === $videoId) {
            $video['thumbnail_generated'] = true;
            break;
        }
    }
    JsonHelper::saveVideos($videos);

    echo json_encode([
        'success' => true,
        'message' => 'Miniatura zapisana',
        'thumbnail_url' => VideoHelper::getThumbnailUrl($videoId)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
