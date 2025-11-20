<?php
/**
 * API: Aktualizacja danych filmu
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Tylko POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    // Pobierz dane z żądania
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        exit;
    }

    $videoId = $input['id'];
    $updates = [];

    // Dozwolone pola do aktualizacji
    $allowedFields = ['title', 'description', 'tags', 'category'];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[$field] = $input[$field];
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak danych do aktualizacji']);
        exit;
    }

    // Aktualizuj film
    if (JsonHelper::updateVideo($videoId, $updates)) {
        echo json_encode([
            'success' => true,
            'message' => 'Film zaktualizowany',
            'video' => JsonHelper::getVideoById($videoId)
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Film nie znaleziony']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas aktualizacji: ' . $e->getMessage()
    ]);
    debug_log("Błąd w update-video.php: " . $e->getMessage());
}
