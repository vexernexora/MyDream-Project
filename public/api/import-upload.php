<?php
/**
 * API: Upload plików wideo
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    // Sprawdź czy plik został przesłany
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Plik przekracza dozwolony rozmiar',
            UPLOAD_ERR_FORM_SIZE => 'Plik przekracza dozwolony rozmiar',
            UPLOAD_ERR_PARTIAL => 'Plik został przesłany tylko częściowo',
            UPLOAD_ERR_NO_FILE => 'Nie przesłano żadnego pliku',
            UPLOAD_ERR_NO_TMP_DIR => 'Brak folderu tymczasowego',
            UPLOAD_ERR_CANT_WRITE => 'Nie można zapisać pliku',
            UPLOAD_ERR_EXTENSION => 'Rozszerzenie PHP zablokowało upload',
        ];

        $error = $_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception($errorMessages[$error] ?? 'Nieznany błąd uploadu');
    }

    $file = $_FILES['video'];
    $originalName = basename($file['name']);
    $tmpPath = $file['tmp_name'];
    $fileSize = $file['size'];

    // Sprawdź czy to plik wideo
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp'];

    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Nieprawidłowe rozszerzenie pliku. Dozwolone: ' . implode(', ', $allowedExtensions));
    }

    // Upewnij się, że folder videos istnieje
    if (!is_dir(VIDEOS_PATH)) {
        mkdir(VIDEOS_PATH, 0755, true);
    }

    // Generuj unikalną nazwę jeśli plik już istnieje
    $targetPath = VIDEOS_PATH . '/' . $originalName;
    $counter = 1;
    while (file_exists($targetPath)) {
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $newName = $nameWithoutExt . '_' . $counter . '.' . $extension;
        $targetPath = VIDEOS_PATH . '/' . $newName;
        $counter++;
    }

    // Przenieś plik
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new Exception('Nie udało się przenieść pliku do folderu docelowego');
    }

    $finalFilename = basename($targetPath);

    // Dodaj film do bazy danych
    $relativePath = str_replace(VIDEOS_PATH . '/', '', $targetPath);

    // Pobierz metadata
    $metadata = VideoHelper::getVideoMetadata($targetPath);

    // Generuj metadata z nazwy pliku
    $aiData = AIHelper::analyzeVideo([
        'filename' => $finalFilename,
        'path' => $targetPath,
        'size' => $fileSize
    ], $metadata);

    // Wygeneruj ID
    $videoId = VideoHelper::generateVideoId($finalFilename);

    // Przygotuj dane filmu
    $videoData = [
        'id' => $videoId,
        'filename' => $finalFilename,
        'relative_path' => $relativePath,
        'title' => $aiData['title'] ?? $finalFilename,
        'description' => $aiData['description'] ?? '',
        'tags' => $aiData['tags'] ?? [],
        'category' => $aiData['category'] ?? 'Inne',
        'duration_seconds' => $metadata['duration'] ?? 0,
        'duration_formatted' => $metadata['duration_formatted'] ?? '0:00',
        'size' => $fileSize,
        'size_formatted' => $metadata['size_formatted'] ?? VideoHelper::formatFileSize($fileSize),
        'width' => $metadata['width'] ?? null,
        'height' => $metadata['height'] ?? null,
        'codec' => $metadata['codec'] ?? null,
        'fps' => $metadata['fps'] ?? null,
        'added_at' => time(),
        'added_at_formatted' => date('Y-m-d H:i:s'),
        'thumbnail_generated' => false,
        'ai_generated' => false,
        'source' => 'upload'
    ];

    // Generuj miniaturkę
    $thumbnailPath = VideoHelper::getThumbnailPath($videoId);
    $timestamp = AIHelper::suggestThumbnailTimestamp([
        'filename' => $finalFilename,
        'path' => $targetPath
    ], $metadata);

    if (VideoHelper::generateThumbnail($targetPath, $thumbnailPath, $timestamp)) {
        $videoData['thumbnail_generated'] = true;
        $videoData['thumbnail_timestamp'] = $timestamp;
    }

    // Dodaj do JSON
    if (!JsonHelper::addVideo($videoData)) {
        throw new Exception('Nie udało się dodać filmu do bazy danych');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Plik został przesłany pomyślnie',
        'video' => $videoData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
