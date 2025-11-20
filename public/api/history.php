<?php
/**
 * API: Zarządzanie historią oglądania
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

    switch ($action) {
        case 'add':
            $videoId = $input['video_id'] ?? '';

            if (empty($videoId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak ID filmu']);
                exit;
            }

            UserDataHelper::addToHistory($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Dodano do historii'
            ]);
            break;

        case 'get':
            $limit = (int)($input['limit'] ?? 20);
            $history = UserDataHelper::getHistory($limit);

            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;

        case 'clear':
            UserDataHelper::clearHistory();
            echo json_encode([
                'success' => true,
                'message' => 'Historia wyczyszczona'
            ]);
            break;

        case 'stats':
            $stats = UserDataHelper::getStatistics();
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w history.php: " . $e->getMessage());
}
