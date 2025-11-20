<?php
/**
 * Wspólna obsługa błędów dla wszystkich API endpoints
 */

declare(strict_types=1);

// Wyłącz wyświetlanie błędów, aby nie zepsuć JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start output buffering - przechwytuj wszystkie outputy
ob_start();

// Custom error handler - konwertuj tylko poważne błędy PHP na wyjątki
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ignoruj drobne błędy (notices, warnings, deprecated)
    if (in_array($errno, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT])) {
        return false; // Użyj domyślnego handlera
    }

    // Konwertuj tylko poważne błędy na wyjątki
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Custom exception handler - zawsze zwracaj JSON
set_exception_handler(function($e) {
    ob_clean(); // Wyczyść bufor
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getTraceAsString() : null
    ]);
    exit;
});

// Załaduj config
require_once __DIR__ . '/../../config.php';

// Wyczyść bufor przed wysłaniem JSON (usuń ewentualne błędy/ostrzeżenia)
ob_clean();

// Ustaw Content-Type na JSON
header('Content-Type: application/json');
