<?php
/**
 * API: Pobieranie filmów z zewnętrznych źródeł (Mega.nz, URL)
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

    // Stwórz folder dla statusów pobierania
    $downloadsStatusPath = DATA_PATH . '/downloads';
    if (!is_dir($downloadsStatusPath)) {
        mkdir($downloadsStatusPath, 0755, true);
    }

    // Wykryj nazwę pliku z URL jeśli nie podano
    if (empty($filename)) {
        if ($type === 'mega') {
            // Dla Mega używamy ID jako nazwy (zmieni się po pobraniu)
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

    // Status pobierania
    $statusFile = $downloadsStatusPath . '/' . $downloadId . '.json';
    $status = [
        'id' => $downloadId,
        'type' => $type,
        'url' => $url,
        'filename' => $filename,
        'progress' => 0,
        'status' => 'starting',
        'started_at' => time(),
        'completed' => false,
        'error' => null
    ];

    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));

    // Rozpocznij pobieranie w tle
    if ($type === 'mega') {
        startMegaDownload($downloadId, $url, $filename, $statusFile);
    } else {
        startUrlDownload($downloadId, $url, $filename, $statusFile);
    }

    echo json_encode([
        'success' => true,
        'download_id' => $downloadId,
        'filename' => $filename,
        'message' => 'Pobieranie rozpoczęte'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Rozpocznij pobieranie z Mega.nz
 */
function startMegaDownload(string $downloadId, string $url, string $filename, string $statusFile): void
{
    // Upewnij się że folder videos istnieje
    if (!is_dir(VIDEOS_PATH)) {
        mkdir(VIDEOS_PATH, 0755, true);
    }

    $targetPath = VIDEOS_PATH . '/' . $filename;

    // Sprawdź czy megatools jest dostępny
    $hasMegatools = false;
    if (function_exists('exec')) {
        exec('which megatools 2>&1', $output, $returnCode);
        $hasMegatools = $returnCode === 0;
    }

    if ($hasMegatools) {
        // Użyj megatools do pobierania
        $command = sprintf(
            'megatools dl --path %s %s > /dev/null 2>&1 &',
            escapeshellarg(VIDEOS_PATH),
            escapeshellarg($url)
        );

        exec($command);

        // TODO: Monitoruj postęp (wymaga osobnego skryptu w tle)
        updateDownloadStatus($statusFile, [
            'status' => 'downloading',
            'progress' => 0
        ]);
    } else {
        // Fallback - próbuj pobrać przez CURL (może nie działać dla Mega)
        updateDownloadStatus($statusFile, [
            'status' => 'error',
            'error' => 'Megatools nie jest zainstalowany. Nie można pobrać z Mega.nz. Zainstaluj: sudo apt install megatools',
            'completed' => true
        ]);
    }
}

/**
 * Rozpocznij pobieranie z URL
 */
function startUrlDownload(string $downloadId, string $url, string $filename, string $statusFile): void
{
    // Upewnij się że folder videos istnieje
    if (!is_dir(VIDEOS_PATH)) {
        mkdir(VIDEOS_PATH, 0755, true);
    }

    $targetPath = VIDEOS_PATH . '/' . $filename;

    // Sprawdź czy możemy użyć wget/curl
    $hasWget = false;
    $hasCurl = false;

    if (function_exists('exec')) {
        exec('which wget 2>&1', $output, $returnCode);
        $hasWget = $returnCode === 0;

        exec('which curl 2>&1', $output, $returnCode);
        $hasCurl = $returnCode === 0;
    }

    if ($hasWget) {
        // Użyj wget
        $command = sprintf(
            'wget -O %s %s > /dev/null 2>&1 &',
            escapeshellarg($targetPath),
            escapeshellarg($url)
        );

        exec($command);

        updateDownloadStatus($statusFile, [
            'status' => 'downloading',
            'progress' => 0,
            'target_path' => $targetPath
        ]);

        // Monitor download w tle
        monitorFileDownload($downloadId, $targetPath, $statusFile);
    } elseif ($hasCurl) {
        // Użyj curl
        $command = sprintf(
            'curl -L -o %s %s > /dev/null 2>&1 &',
            escapeshellarg($targetPath),
            escapeshellarg($url)
        );

        exec($command);

        updateDownloadStatus($statusFile, [
            'status' => 'downloading',
            'progress' => 0,
            'target_path' => $targetPath
        ]);

        monitorFileDownload($downloadId, $targetPath, $statusFile);
    } else {
        // Fallback - użyj PHP file_get_contents/fopen (może nie działać dla dużych plików)
        downloadWithPhp($url, $targetPath, $statusFile, $downloadId);
    }
}

/**
 * Monitoruj postęp pobierania pliku
 */
function monitorFileDownload(string $downloadId, string $targetPath, string $statusFile): void
{
    // Ta funkcja powinna być wykonywana w tle
    // Możemy stworzyć prosty skrypt PHP który będzie sprawdzał rozmiar pliku

    $monitorScript = DATA_PATH . '/downloads/monitor_' . $downloadId . '.php';

    $scriptContent = '<?php
$targetPath = ' . var_export($targetPath, true) . ';
$statusFile = ' . var_export($statusFile, true) . ';
$downloadId = ' . var_export($downloadId, true) . ';

// Monitoruj przez max 1 godzinę
$maxTime = time() + 3600;
$lastSize = 0;
$noChangeCount = 0;

while (time() < $maxTime) {
    if (file_exists($targetPath)) {
        $currentSize = filesize($targetPath);

        if ($currentSize > $lastSize) {
            $lastSize = $currentSize;
            $noChangeCount = 0;

            // Aktualizuj status
            $status = json_decode(file_get_contents($statusFile), true);
            $status["progress"] = min(95, ($currentSize / 1000000)); // Placeholder
            $status["status"] = "downloading";
            file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));
        } else {
            $noChangeCount++;

            // Jeśli rozmiar się nie zmienia przez 10 sekund, uznaj za zakończone
            if ($noChangeCount >= 10 && $currentSize > 0) {
                $status = json_decode(file_get_contents($statusFile), true);
                $status["progress"] = 100;
                $status["status"] = "completed";
                $status["completed"] = true;
                file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));

                // Dodaj do bazy
                require_once __DIR__ . "/../../config.php";
                addVideoToDatabase($targetPath, basename($targetPath), filesize($targetPath));

                break;
            }
        }
    }

    sleep(1);
}

unlink(__FILE__);

function addVideoToDatabase($path, $filename, $size) {
    try {
        $relativePath = str_replace(VIDEOS_PATH . \'/\', \'\', $path);
        $metadata = VideoHelper::getVideoMetadata($path);

        $aiData = AIHelper::analyzeVideo([
            \'filename\' => $filename,
            \'path\' => $path,
            \'size\' => $size
        ], $metadata);

        $videoId = VideoHelper::generateVideoId($filename);

        $videoData = [
            \'id\' => $videoId,
            \'filename\' => $filename,
            \'relative_path\' => $relativePath,
            \'title\' => $aiData[\'title\'] ?? $filename,
            \'description\' => $aiData[\'description\'] ?? \'\',
            \'tags\' => $aiData[\'tags\'] ?? [],
            \'category\' => $aiData[\'category\'] ?? \'Inne\',
            \'duration_seconds\' => $metadata[\'duration\'] ?? 0,
            \'duration_formatted\' => $metadata[\'duration_formatted\'] ?? \'0:00\',
            \'size\' => $size,
            \'size_formatted\' => VideoHelper::formatFileSize($size),
            \'width\' => $metadata[\'width\'] ?? null,
            \'height\' => $metadata[\'height\'] ?? null,
            \'codec\' => $metadata[\'codec\'] ?? null,
            \'fps\' => $metadata[\'fps\'] ?? null,
            \'added_at\' => time(),
            \'added_at_formatted\' => date(\'Y-m-d H:i:s\'),
            \'thumbnail_generated\' => false,
            \'source\' => \'download\'
        ];

        $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
        $timestamp = AIHelper::suggestThumbnailTimestamp([\'filename\' => $filename, \'path\' => $path], $metadata);

        if (VideoHelper::generateThumbnail($path, $thumbnailPath, $timestamp)) {
            $videoData[\'thumbnail_generated\'] = true;
            $videoData[\'thumbnail_timestamp\'] = $timestamp;
        }

        JsonHelper::addVideo($videoData);
    } catch (Exception $e) {
        debug_log("Błąd dodawania pobranego filmu: " . $e->getMessage());
    }
}
';

    file_put_contents($monitorScript, $scriptContent);

    // Uruchom skrypt w tle
    if (function_exists('exec')) {
        exec('php ' . escapeshellarg($monitorScript) . ' > /dev/null 2>&1 &');
    }
}

/**
 * Pobierz plik używając PHP (fallback dla małych plików)
 */
function downloadWithPhp(string $url, string $targetPath, string $statusFile, string $downloadId): void
{
    updateDownloadStatus($statusFile, [
        'status' => 'downloading',
        'progress' => 10
    ]);

    try {
        // Użyj stream context dla większych plików
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 300,
                'follow_location' => true
            ]
        ]);

        $content = file_get_contents($url, false, $ctx);

        if ($content === false) {
            throw new Exception('Nie udało się pobrać pliku');
        }

        file_put_contents($targetPath, $content);

        updateDownloadStatus($statusFile, [
            'status' => 'completed',
            'progress' => 100,
            'completed' => true,
            'target_path' => $targetPath
        ]);

        // Dodaj do bazy
        addDownloadedVideoToDatabase($targetPath, basename($targetPath), filesize($targetPath));

    } catch (Exception $e) {
        updateDownloadStatus($statusFile, [
            'status' => 'error',
            'error' => $e->getMessage(),
            'completed' => true
        ]);
    }
}

/**
 * Aktualizuj status pobierania
 */
function updateDownloadStatus(string $statusFile, array $updates): void
{
    if (!file_exists($statusFile)) return;

    $status = json_decode(file_get_contents($statusFile), true);
    $status = array_merge($status, $updates);
    file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));
}

/**
 * Dodaj pobrany film do bazy danych
 */
function addDownloadedVideoToDatabase(string $path, string $filename, int $size): void
{
    try {
        $relativePath = str_replace(VIDEOS_PATH . '/', '', $path);
        $metadata = VideoHelper::getVideoMetadata($path);

        $aiData = AIHelper::analyzeVideo([
            'filename' => $filename,
            'path' => $path,
            'size' => $size
        ], $metadata);

        $videoId = VideoHelper::generateVideoId($filename);

        $videoData = [
            'id' => $videoId,
            'filename' => $filename,
            'relative_path' => $relativePath,
            'title' => $aiData['title'] ?? $filename,
            'description' => $aiData['description'] ?? '',
            'tags' => $aiData['tags'] ?? [],
            'category' => $aiData['category'] ?? 'Inne',
            'duration_seconds' => $metadata['duration'] ?? 0,
            'duration_formatted' => $metadata['duration_formatted'] ?? '0:00',
            'size' => $size,
            'size_formatted' => VideoHelper::formatFileSize($size),
            'width' => $metadata['width'] ?? null,
            'height' => $metadata['height'] ?? null,
            'codec' => $metadata['codec'] ?? null,
            'fps' => $metadata['fps'] ?? null,
            'added_at' => time(),
            'added_at_formatted' => date('Y-m-d H:i:s'),
            'thumbnail_generated' => false,
            'source' => 'download'
        ];

        $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
        $timestamp = AIHelper::suggestThumbnailTimestamp(['filename' => $filename, 'path' => $path], $metadata);

        if (VideoHelper::generateThumbnail($path, $thumbnailPath, $timestamp)) {
            $videoData['thumbnail_generated'] = true;
            $videoData['thumbnail_timestamp'] = $timestamp;
        }

        JsonHelper::addVideo($videoData);
    } catch (Exception $e) {
        debug_log("Błąd dodawania pobranego filmu: " . $e->getMessage());
    }
}
