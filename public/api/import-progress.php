<?php
/**
 * API: Sprawdzanie postępu pobierania
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $downloadId = $_GET['id'] ?? '';

    if (empty($downloadId)) {
        throw new Exception('Brak ID pobierania');
    }

    $downloadsStatusPath = DATA_PATH . '/downloads';
    $statusFile = $downloadsStatusPath . '/' . $downloadId . '.json';

    if (!file_exists($statusFile)) {
        throw new Exception('Nie znaleziono pobierania');
    }

    $status = json_decode(file_get_contents($statusFile), true);

    if (!$status) {
        throw new Exception('Błąd odczytu statusu');
    }

    echo json_encode([
        'success' => true,
        'id' => $status['id'],
        'filename' => $status['filename'],
        'progress' => $status['progress'] ?? 0,
        'status' => $status['status'] ?? 'unknown',
        'completed' => $status['completed'] ?? false,
        'error' => $status['error'] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
