<?php
/**
 * API: Budowanie APK dla Android
 */

declare(strict_types=1);

// Wyłącz wyświetlanie błędów, aby nie zepsuć JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start output buffering - przechwytuj wszystkie outputy
ob_start();

require_once __DIR__ . '/../../config.php';

// Wyczyść bufor przed wysłaniem JSON (usuń ewentualne błędy/ostrzeżenia)
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action !== 'build') {
        http_response_code(400);
        echo json_encode(['error' => 'Nieprawidłowa akcja']);
        exit;
    }

    $appName = $input['app_name'] ?? '';
    $packageId = $input['package_id'] ?? '';
    $version = $input['version'] ?? '';

    // Walidacja
    if (empty($appName) || empty($packageId) || empty($version)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych parametrów']);
        exit;
    }

    // Sprawdź czy Node.js i Cordova są dostępne
    $nodeAvailable = false;
    $cordovaAvailable = false;

    // Sprawdź Node.js
    exec('node --version 2>&1', $nodeOutput, $nodeReturnCode);
    if ($nodeReturnCode === 0) {
        $nodeAvailable = true;
    }

    // Sprawdź Cordova
    if ($nodeAvailable) {
        exec('cordova --version 2>&1', $cordovaOutput, $cordovaReturnCode);
        if ($cordovaReturnCode === 0) {
            $cordovaAvailable = true;
        }
    }

    // Jeśli Cordova nie jest dostępna, zwróć informację o pobraniu konfiguracji
    if (!$cordovaAvailable) {
        echo json_encode([
            'success' => false,
            'download_config' => true,
            'message' => 'Cordova nie jest zainstalowana na serwerze. Pobierz konfigurację aby zbudować APK lokalnie.',
            'node_available' => $nodeAvailable,
            'cordova_available' => false
        ]);
        exit;
    }

    // Cordova jest dostępna - próbujemy zbudować APK
    $buildDir = ROOT_PATH . '/build';
    $projectDir = $buildDir . '/mydream-app';

    // Utwórz folder build jeśli nie istnieje
    if (!is_dir($buildDir)) {
        mkdir($buildDir, 0755, true);
    }

    // Usuń stary projekt jeśli istnieje
    if (is_dir($projectDir)) {
        exec("rm -rf " . escapeshellarg($projectDir));
    }

    // Utwórz nowy projekt Cordova
    $createCmd = sprintf(
        'cd %s && cordova create %s %s %s 2>&1',
        escapeshellarg($buildDir),
        escapeshellarg('mydream-app'),
        escapeshellarg($packageId),
        escapeshellarg($appName)
    );

    exec($createCmd, $createOutput, $createReturnCode);

    if ($createReturnCode !== 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Nie udało się utworzyć projektu Cordova',
            'output' => implode("\n", $createOutput)
        ]);
        exit;
    }

    // Dodaj platformę Android
    $platformCmd = sprintf(
        'cd %s && cordova platform add android 2>&1',
        escapeshellarg($projectDir)
    );

    exec($platformCmd, $platformOutput, $platformReturnCode);

    if ($platformReturnCode !== 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Nie udało się dodać platformy Android',
            'output' => implode("\n", $platformOutput)
        ]);
        exit;
    }

    // Skopiuj pliki aplikacji do www/
    $wwwDir = $projectDir . '/www';

    // Wyczyść domyślne pliki
    exec("rm -rf " . escapeshellarg($wwwDir) . "/*");

    // Skopiuj pliki (uproszczona wersja - tylko główne pliki)
    $filesToCopy = [
        ROOT_PATH . '/index.php' => $wwwDir . '/index.html',
        PUBLIC_PATH . '/manifest.json' => $wwwDir . '/manifest.json',
        PUBLIC_PATH . '/js' => $wwwDir . '/js',
        PUBLIC_PATH . '/img' => $wwwDir . '/img',
    ];

    foreach ($filesToCopy as $source => $dest) {
        if (file_exists($source)) {
            if (is_dir($source)) {
                exec("cp -r " . escapeshellarg($source) . " " . escapeshellarg($dest));
            } else {
                copy($source, $dest);
            }
        }
    }

    // Zbuduj APK
    $buildCmd = sprintf(
        'cd %s && cordova build android 2>&1',
        escapeshellarg($projectDir)
    );

    exec($buildCmd, $buildOutput, $buildReturnCode);

    if ($buildReturnCode !== 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Nie udało się zbudować APK',
            'output' => implode("\n", $buildOutput)
        ]);
        exit;
    }

    // Znajdź wygenerowany APK
    $apkPath = $projectDir . '/platforms/android/app/build/outputs/apk/debug/app-debug.apk';

    if (file_exists($apkPath)) {
        // Skopiuj APK do dostępnej lokalizacji
        $publicApkPath = PUBLIC_PATH . '/downloads/mydream-video.apk';
        $downloadsDir = PUBLIC_PATH . '/downloads';

        if (!is_dir($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        copy($apkPath, $publicApkPath);

        echo json_encode([
            'success' => true,
            'message' => 'APK został wygenerowany pomyślnie!',
            'apk_url' => '/public/downloads/mydream-video.apk',
            'apk_path' => $publicApkPath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'APK został zbudowany, ale nie można go znaleźć',
            'output' => implode("\n", $buildOutput)
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getTraceAsString() : null
    ]);

    if (function_exists('debug_log')) {
        debug_log("Błąd w build-apk.php: " . $e->getMessage());
    }
}
