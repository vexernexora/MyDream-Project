<?php
/**
 * API: Zarządzanie listą "Do obejrzenia"
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId) && $action !== 'get_all') {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    switch ($action) {
        case 'add':
            UserDataHelper::addToWatchLater($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Dodano do "Do obejrzenia"',
                'is_in_watch_later' => true
            ]);
            break;

        case 'remove':
            UserDataHelper::removeFromWatchLater($videoId);
            echo json_encode([
                'success' => true,
                'message' => 'Usunięto z "Do obejrzenia"',
                'is_in_watch_later' => false
            ]);
            break;

        case 'toggle':
            if (UserDataHelper::isInWatchLater($videoId)) {
                UserDataHelper::removeFromWatchLater($videoId);
                $isInList = false;
                $message = 'Usunięto z "Do obejrzenia"';
            } else {
                UserDataHelper::addToWatchLater($videoId);
                $isInList = true;
                $message = 'Dodano do "Do obejrzenia"';
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'is_in_watch_later' => $isInList
            ]);
            break;

        case 'check':
            echo json_encode([
                'success' => true,
                'is_in_watch_later' => UserDataHelper::isInWatchLater($videoId)
            ]);
            break;

        case 'get_all':
            echo json_encode([
                'success' => true,
                'watch_later' => UserDataHelper::getWatchLater()
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w watch-later.php: " . $e->getMessage());
}
