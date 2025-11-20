#!/usr/bin/env php
<?php
/**
 * Helper do dodawania filmów do bazy danych
 * Użycie: php add-video-helper.php /path/to/video.mp4
 */

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Załaduj config
require_once __DIR__ . '/../config.php';

if ($argc < 2) {
    echo "Użycie: php add-video-helper.php /path/to/video.mp4\n";
    exit(1);
}

$videoPath = $argv[1];

if (!file_exists($videoPath)) {
    echo "Błąd: Plik nie istnieje: $videoPath\n";
    exit(1);
}

try {
    $filename = basename($videoPath);
    $size = filesize($videoPath);
    $relativePath = str_replace(VIDEOS_PATH . '/', '', $videoPath);

    echo "📹 Dodawanie filmu: $filename\n";
    echo "   Rozmiar: " . VideoHelper::formatFileSize($size) . "\n";

    // Pobierz metadata
    echo "   Pobieranie metadanych...\n";
    $metadata = VideoHelper::getVideoMetadata($videoPath);

    if ($metadata) {
        echo "   ✓ Długość: " . ($metadata['duration_formatted'] ?? '?') . "\n";
        echo "   ✓ Rozdzielczość: " . ($metadata['width'] ?? '?') . 'x' . ($metadata['height'] ?? '?') . "\n";
    }

    // Generuj metadata z nazwy pliku
    echo "   Generowanie tagów i tytułu...\n";
    $aiData = AIHelper::analyzeVideo([
        'filename' => $filename,
        'path' => $videoPath,
        'size' => $size
    ], $metadata);

    echo "   ✓ Tytuł: " . ($aiData['title'] ?? $filename) . "\n";
    if (!empty($aiData['tags'])) {
        echo "   ✓ Tagi: " . implode(', ', $aiData['tags']) . "\n";
    }

    // Wygeneruj ID
    $videoId = VideoHelper::generateVideoId($filename);

    // Przygotuj dane filmu
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
        'ai_generated' => false,
        'source' => 'download'
    ];

    // Generuj miniaturkę
    echo "   Generowanie miniaturki...\n";
    $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
    $timestamp = AIHelper::suggestThumbnailTimestamp([
        'filename' => $filename,
        'path' => $videoPath
    ], $metadata);

    if (VideoHelper::generateThumbnail($videoPath, $thumbnailPath, $timestamp)) {
        $videoData['thumbnail_generated'] = true;
        $videoData['thumbnail_timestamp'] = $timestamp;
        echo "   ✓ Miniaturka wygenerowana\n";
    } else {
        echo "   ⚠ Nie udało się wygenerować miniaturki\n";
    }

    // Dodaj do JSON
    echo "   Zapisywanie do bazy...\n";
    if (JsonHelper::addVideo($videoData)) {
        echo "✅ Film dodany do bazy pomyślnie!\n";
        echo "   ID: $videoId\n";
        exit(0);
    } else {
        echo "❌ Nie udało się dodać filmu do bazy (może już istnieje)\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Błąd: " . $e->getMessage() . "\n";
    debug_log("Błąd dodawania filmu: " . $e->getMessage());
    exit(1);
}
