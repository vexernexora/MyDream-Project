<?php
/**
 * API: Pobieranie filmów z zewnętrznych źródeł (Mega.nz, URL)
 * NOWA WERSJA: Używa systemu kolejki i workerów
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $type = $input['type'] ?? '';
    $url = $input['url'] ?? '';
    $filename = $input['filename'] ?? '';

    if (!in_array($type, ['mega', 'url'])) {
        throw new Exception('Nieprawidłowy typ pobierania');
    }

    if (empty($url)) {
        throw new Exception('Brak URL do pobrania');
    }

    // Walidacja URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('Nieprawidłowy format URL');
    }

    // Dla Mega sprawdź czy to link Mega
    if ($type === 'mega' && !str_contains($url, 'mega.nz')) {
        throw new Exception('To nie jest link Mega.nz');
    }

    // Wygeneruj unikalny ID pobierania
    $downloadId = uniqid('dl_', true);

    // Stwórz foldery
    $downloadsStatusPath = DATA_PATH . '/downloads';
    $queuePath = DATA_PATH . '/queue';

    if (!is_dir($downloadsStatusPath)) {
        mkdir($downloadsStatusPath, 0755, true);
    }
    if (!is_dir($queuePath)) {
        mkdir($queuePath, 0755, true);
    }

    // Wykryj nazwę pliku z URL jeśli nie podano
    if (empty($filename)) {
        if ($type === 'mega') {
            // Dla Mega używamy ID jako nazwy (worker może zmienić po pobraniu)
            $filename = 'mega_' . time() . '.mp4';
        } else {
            // Dla URL wyciągamy nazwę z linku
            $urlPath = parse_url($url, PHP_URL_PATH);
            $filename = basename($urlPath);

            if (empty($filename) || !preg_match('/\.(mp4|mkv|avi|mov|wmv|flv|webm|mpeg|mpg|m4v|3gp)$/i', $filename)) {
                $filename = 'video_' . time() . '.mp4';
            }
        }
    }

    // Upewnij się że filename ma rozszerzenie
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    if (empty($extension)) {
        $filename .= '.mp4';
    }

    // Sprawdź czy plik już nie istnieje
    $targetPath = VIDEOS_PATH . '/' . $filename;
    if (file_exists($targetPath)) {
        throw new Exception('Plik o tej nazwie już istnieje: ' . $filename);
    }

    // Status pobierania
    $statusFile = $downloadsStatusPath . '/' . $downloadId . '.json';
    $status = [
        'id' => $downloadId,
        'type' => $type,
        'url' => $url,
        'filename' => $filename,
        'progress' => 0,
        'status' => 'queued',
        'started_at' => time(),
        'completed' => false,
        'error' => null
    ];

    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));

    // Dodaj zadanie do kolejki (dla workera)
    $queueFile = $queuePath . '/' . $downloadId . '.json';
    $queueData = [
        'id' => $downloadId,
        'type' => $type,
        'url' => $url,
        'filename' => $filename,
        'added_at' => time(),
        'status_file' => $statusFile
    ];

    file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));

    // Sprawdź czy worker działa
    $workerPidFile = ROOT_PATH . '/data/worker.pid';
    $workerRunning = false;

    if (file_exists($workerPidFile)) {
        $workerPid = (int)file_get_contents($workerPidFile);
        if ($workerPid > 0 && function_exists('posix_kill') && posix_kill($workerPid, 0)) {
            $workerRunning = true;
        }
    }

    $message = 'Zadanie dodane do kolejki';
    if (!$workerRunning) {
        $message .= ' ⚠️ Worker nie działa - uruchom: ./workers/worker-control.sh start';
    }

    echo json_encode([
        'success' => true,
        'download_id' => $downloadId,
        'filename' => $filename,
        'message' => $message,
        'worker_running' => $workerRunning,
        'queue_position' => count(glob($queuePath . '/*.json'))
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
