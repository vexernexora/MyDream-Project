<?php
/**
 * Test scan.php debug
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

echo "<h1>Test Scan Debug</h1>";

// Test 1: Sprawdź czy wszystkie klasy są dostępne
echo "<h2>1. Test klas:</h2>";
echo "VideoHelper: " . (class_exists('VideoHelper') ? 'OK' : 'MISSING') . "<br>";
echo "AIHelper: " . (class_exists('AIHelper') ? 'OK' : 'MISSING') . "<br>";
echo "JsonHelper: " . (class_exists('JsonHelper') ? 'OK' : 'MISSING') . "<br>";

// Test 2: Sprawdź folder videos
echo "<h2>2. Test folderu videos:</h2>";
echo "VIDEOS_PATH: " . VIDEOS_PATH . "<br>";
echo "Istnieje: " . (is_dir(VIDEOS_PATH) ? 'TAK' : 'NIE') . "<br>";

if (is_dir(VIDEOS_PATH)) {
    $files = scandir(VIDEOS_PATH);
    echo "Pliki w folderze (" . count($files) . "):<br>";
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        echo "- $file<br>";
    }
}

// Test 3: Sprawdź VideoHelper::scanVideosFolder
echo "<h2>3. Test VideoHelper::scanVideosFolder():</h2>";
try {
    $files = VideoHelper::scanVideosFolder();
    echo "Znaleziono filmów: " . count($files) . "<br>";
    if (!empty($files)) {
        echo "<pre>";
        print_r($files);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>BŁĄD: " . $e->getMessage() . "</span><br>";
    echo "Plik: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: Sprawdź JsonHelper::getVideos
echo "<h2>4. Test JsonHelper::getVideos():</h2>";
try {
    $videos = JsonHelper::getVideos();
    echo "Filmów w bazie: " . count($videos) . "<br>";
} catch (Exception $e) {
    echo "<span style='color:red'>BŁĄD: " . $e->getMessage() . "</span><br>";
}

// Test 5: Symuluj scan
echo "<h2>5. Test pełnego scan:</h2>";
try {
    $files = VideoHelper::scanVideosFolder();

    if (empty($files)) {
        echo "Brak nowych filmów<br>";
    } else {
        $existingVideos = JsonHelper::getVideos();
        $existingFilenames = array_column($existingVideos, 'filename');

        foreach ($files as $file) {
            if (in_array($file['filename'], $existingFilenames)) {
                echo "Pominięty (już istnieje): " . $file['filename'] . "<br>";
                continue;
            }

            echo "<br><b>Nowy film: " . $file['filename'] . "</b><br>";

            // Test metadata
            echo "- Pobieranie metadata...<br>";
            $metadata = VideoHelper::getVideoMetadata($file['path']);
            echo "- Metadata: ";
            print_r($metadata);
            echo "<br>";

            // Test AI
            echo "- Analiza AI...<br>";
            $aiData = AIHelper::analyzeVideo($file, $metadata);
            echo "- AI Data: ";
            print_r($aiData);
            echo "<br>";

            break; // Testuj tylko pierwszy film
        }
    }
} catch (Exception $e) {
    echo "<span style='color:red'>BŁĄD: " . $e->getMessage() . "</span><br>";
    echo "Plik: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
