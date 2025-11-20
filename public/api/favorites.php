<?php
/**
 * API: Zarządzanie ulubionymi filmami
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    switch ($action) {
        case 'add':
            UserDataHelper::addToFavorites($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Dodano do ulubionych',
                'is_favorite' => true
            ]);
            break;

        case 'remove':
            UserDataHelper::removeFromFavorites($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Usunięto z ulubionych',
                'is_favorite' => false
            ]);
            break;

        case 'toggle':
            if (UserDataHelper::isFavorite($videoId)) {
                UserDataHelper::removeFromFavorites($videoId);
                $isFavorite = false;
                $message = 'Usunięto z ulubionych';
            } else {
                UserDataHelper::addToFavorites($videoId);
                $isFavorite = true;
                $message = 'Dodano do ulubionych';
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'is_favorite' => $isFavorite
            ]);
            break;

        case 'check':
            echo json_encode([
                'success' => true,
                'is_favorite' => UserDataHelper::isFavorite($videoId)
            ]);
            break;

        case 'get_all':
            echo json_encode([
                'success' => true,
                'favorites' => UserDataHelper::getFavorites()
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w favorites.php: " . $e->getMessage());
}
