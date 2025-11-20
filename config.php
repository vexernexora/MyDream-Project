<?php
/**
 * Offline Video App - Konfiguracja główna
 * Zaawansowana lokalna aplikacja do zarządzania filmami
 */

declare(strict_types=1);

//Ścieżki projektu
define('ROOT_PATH', __DIR__);
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('DATA_PATH', ROOT_PATH . '/data');
define('VIDEOS_PATH', ROOT_PATH . '/videos');
define('THUMBNAILS_PATH', DATA_PATH . '/thumbnails');
define('CACHE_PATH', DATA_PATH . '/cache');
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// Plik z danymi filmów
define('VIDEOS_JSON', DATA_PATH . '/videos.json');

// Ustawienia miniaturek (opcjonalnie - wymaga FFmpeg)
define('THUMBNAIL_WIDTH', 1280);
define('THUMBNAIL_HEIGHT', 720);
define('THUMBNAIL_QUALITY', 85);

// Obsługiwane formaty wideo
define('SUPPORTED_VIDEO_FORMATS', [
    'mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv',
    'webm', 'mpeg', 'mpg', 'm4v', '3gp'
]);

// Ustawienia paginacji
define('VIDEOS_PER_PAGE', 12);

// Tryb debugowania
define('DEBUG_MODE', true);

// Strefa czasowa
date_default_timezone_set('Europe/Warsaw');

// Autoloader dla klas pomocniczych
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/helpers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Inicjalizacja sesji
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funkcja pomocnicza do wyświetlania błędów w trybie debug
function debug_log($message, $data = null): void {
    if (DEBUG_MODE) {
        error_log('[VIDEO_APP] ' . $message . ($data ? ': ' . print_r($data, true) : ''));
    }
}

// Utworzenie wymaganych folderów jeśli nie istnieją
$requiredDirs = [
    VIDEOS_PATH,
    THUMBNAILS_PATH,
    CACHE_PATH,
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        debug_log("Utworzono folder: $dir");
    }
}

// Inicjalizacja pliku JSON jeśli nie istnieje
if (!file_exists(VIDEOS_JSON)) {
    file_put_contents(VIDEOS_JSON, json_encode(['videos' => [], 'last_scan' => null], JSON_PRETTY_PRINT));
    debug_log("Utworzono plik videos.json");
}
