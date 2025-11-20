<?php
/**
 * API: Zarządzanie postępem oglądania filmów
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

    if (empty($videoId) && $action !== 'get_continue_watching') {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    switch ($action) {
        case 'save':
            $currentTime = (float)($input['current_time'] ?? 0);
            $duration = (float)($input['duration'] ?? 0);

            if ($duration <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Nieprawidłowy czas trwania']);
                exit;
            }

            $percentage = round(($currentTime / $duration) * 100, 2);

            // Jeśli film obejrzany w 95%+, usuń z progress (uznaj za ukończony)
            if ($percentage >= 95) {
                UserDataHelper::removeProgress($videoId);
                // Dodaj do historii jako obejrzany
                UserDataHelper::addToHistory($videoId);

                echo json_encode([
                    'success' => true,
                    'message' => 'Film obejrzany',
                    'completed' => true
                ]);
            } else {
                UserDataHelper::saveProgress($videoId, $currentTime, $duration);

                echo json_encode([
                    'success' => true,
                    'message' => 'Postęp zapisany',
                    'percentage' => $percentage
                ]);
            }
            break;

        case 'get':
            $progress = UserDataHelper::getProgress($videoId);
            echo json_encode([
                'success' => true,
                'progress' => $progress
            ]);
            break;

        case 'remove':
            UserDataHelper::removeProgress($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Postęp usunięty'
            ]);
            break;

        case 'get_continue_watching':
            $limit = (int)($input['limit'] ?? 12);
            $continueWatching = UserDataHelper::getContinueWatching($limit);

            echo json_encode([
                'success' => true,
                'continue_watching' => $continueWatching
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w progress.php: " . $e->getMessage());
}
