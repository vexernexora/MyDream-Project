<?php
/**
 * API: Reset bazy danych
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    // Resetuj JSON
    $emptyData = [
        'videos' => [],
        'last_scan' => null
    ];

    file_put_contents(VIDEOS_JSON, json_encode($emptyData, JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'message' => 'Baza danych zresetowana'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
