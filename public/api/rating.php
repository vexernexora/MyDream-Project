<?php
/**
 * API: Zarządzanie ocenami filmów (1-5 gwiazdek)
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

    if (empty($videoId) && !in_array($action, ['get_top_rated', 'get_all'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    switch ($action) {
        case 'set':
            $rating = (int)($input['rating'] ?? 0);

            if ($rating < 1 || $rating > 5) {
                http_response_code(400);
                echo json_encode(['error' => 'Ocena musi być od 1 do 5']);
                exit;
            }

            UserDataHelper::setRating($videoId, $rating);
            echo json_encode([
                'success' => true,
                'message' => 'Ocena zapisana',
                'rating' => $rating
            ]);
            break;

        case 'remove':
            UserDataHelper::removeRating($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Ocena usunięta',
                'rating' => null
            ]);
            break;

        case 'get':
            $rating = UserDataHelper::getRating($videoId);
            echo json_encode([
                'success' => true,
                'rating' => $rating
            ]);
            break;

        case 'get_all':
            echo json_encode([
                'success' => true,
                'ratings' => UserDataHelper::getAllRatings()
            ]);
            break;

        case 'get_top_rated':
            $limit = (int)($input['limit'] ?? 10);
            echo json_encode([
                'success' => true,
                'top_rated' => UserDataHelper::getTopRated($limit)
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w rating.php: " . $e->getMessage());
}
