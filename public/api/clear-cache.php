<?php
/**
 * API: Czyszczenie cache
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
    $deleted = 0;
    $files = glob(CACHE_PATH . '/*');

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Wyczyszczono cache ({$deleted} plików)",
        'deleted' => $deleted
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
