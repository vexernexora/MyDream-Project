<?php
/**
 * API: Zarządzanie preferencjami użytkownika
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
        case 'set':
            $key = $input['key'] ?? '';
            $value = $input['value'] ?? null;

            if (empty($key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak klucza']);
                exit;
            }

            UserDataHelper::setPreference($key, $value);
            echo json_encode([
                'success' => true,
                'message' => 'Preferencja zapisana'
            ]);
            break;

        case 'get':
            $key = $input['key'] ?? '';
            $default = $input['default'] ?? null;

            if (empty($key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak klucza']);
                exit;
            }

            $value = UserDataHelper::getPreference($key, $default);
            echo json_encode([
                'success' => true,
                'value' => $value
            ]);
            break;

        case 'get_all':
            $preferences = UserDataHelper::getAllPreferences();
            echo json_encode([
                'success' => true,
                'preferences' => $preferences
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w preferences.php: " . $e->getMessage());
}
