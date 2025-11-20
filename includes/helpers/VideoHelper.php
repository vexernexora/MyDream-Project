<?php
/**
 * VideoHelper - Zarządzanie plikami wideo, FFmpeg, miniatury
 */

declare(strict_types=1);

class VideoHelper
{
    /**
     * Sprawdza czy funkcja exec jest dostępna
     */
    private static function isExecAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            $disabled = explode(',', ini_get('disable_functions'));
            $available = !in_array('exec', $disabled) && function_exists('exec');
        }

        return $available;
    }

    /**
     * Skanuje folder videos i zwraca listę plików wideo
     */
    public static function scanVideosFolder(): array
    {
        if (!is_dir(VIDEOS_PATH)) {
            debug_log("Folder videos nie istnieje");
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(VIDEOS_PATH, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, SUPPORTED_VIDEO_FORMATS)) {
                    $files[] = [
                        'filename' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'relative_path' => str_replace(VIDEOS_PATH . '/', '', $file->getPathname()),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime(),
                        'extension' => $extension
                    ];
                }
            }
        }

        debug_log("Znaleziono filmów: " . count($files));
        return $files;
    }

    /**
     * Sprawdza czy FFmpeg jest dostępny
     */
    public static function isFFmpegAvailable(): bool
    {
        if (!self::isExecAvailable()) {
            return false;
        }

        $output = [];
        $returnVar = 0;
        @exec('ffmpeg -version 2>&1', $output, $returnVar);
        return $returnVar === 0;
    }

    /**
     * Pobiera metadata filmu za pomocą FFprobe
     */
    public static function getVideoMetadata(string $videoPath): ?array
    {
        if (!file_exists($videoPath)) {
            debug_log("Plik nie istnieje: $videoPath");
            return null;
        }

        if (!self::isExecAvailable() || !self::isFFmpegAvailable()) {
            // Fallback - podstawowe info bez FFmpeg
            $fileSize = filesize($videoPath);
            return [
                'duration' => 0,
                'duration_formatted' => '0:00',
                'size' => $fileSize,
                'size_formatted' => self::formatFileSize($fileSize),
                'width' => null,
                'height' => null,
                'codec' => null,
                'fps' => null,
            ];
        }

        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($videoPath)
        );

        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            debug_log("FFprobe błąd dla: $videoPath");
            return null;
        }

        $json = implode('', $output);
        $data = json_decode($json, true);

        if (!$data) {
            return null;
        }

        // Parsuj metadata
        $duration = (float)($data['format']['duration'] ?? 0);
        $size = (int)($data['format']['size'] ?? 0);

        // Znajdź strumień wideo
        $videoStream = null;
        foreach ($data['streams'] ?? [] as $stream) {
            if ($stream['codec_type'] === 'video') {
                $videoStream = $stream;
                break;
            }
        }

        return [
            'duration' => $duration,
            'duration_formatted' => self::formatDuration($duration),
            'size' => $size,
            'size_formatted' => self::formatFileSize($size),
            'width' => $videoStream['width'] ?? null,
            'height' => $videoStream['height'] ?? null,
            'codec' => $videoStream['codec_name'] ?? null,
            'fps' => self::parseFps($videoStream['r_frame_rate'] ?? null),
        ];
    }

    /**
     * Generuje miniaturkę z określonej sekundy filmu
     */
    public static function generateThumbnail(string $videoPath, string $outputPath, float $timestamp = 0): bool
    {
        if (!self::isExecAvailable() || !self::isFFmpegAvailable()) {
            debug_log("FFmpeg nie jest dostępny lub exec() wyłączony");
            // Skopiuj placeholder
            $placeholder = PUBLIC_PATH . '/img/no-thumbnail.jpg';
            if (file_exists($placeholder)) {
                copy($placeholder, $outputPath);
            }
            return false;
        }

        if (!file_exists($videoPath)) {
            debug_log("Plik wideo nie istnieje: $videoPath");
            return false;
        }

        // Utwórz folder na miniatury jeśli nie istnieje
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $command = sprintf(
            'ffmpeg -ss %.2f -i %s -vframes 1 -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2" -q:v %d %s 2>&1',
            $timestamp,
            escapeshellarg($videoPath),
            THUMBNAIL_WIDTH,
            THUMBNAIL_HEIGHT,
            THUMBNAIL_WIDTH,
            THUMBNAIL_HEIGHT,
            THUMBNAIL_QUALITY,
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($outputPath)) {
            debug_log("Błąd generowania miniatury", ['output' => $output]);
            return false;
        }

        debug_log("Miniatura wygenerowana: $outputPath");
        return true;
    }

    /**
     * Analizuje film i znajduje najlepszy moment na miniaturkę
     * Używa analizy scen FFmpeg
     */
    public static function findBestThumbnailTimestamp(string $videoPath): float
    {
        $metadata = self::getVideoMetadata($videoPath);
        if (!$metadata || $metadata['duration'] < 1) {
            return 0;
        }

        $duration = $metadata['duration'];

        // Strategia: weź moment z pierwszej 1/3 filmu (zazwyczaj najciekawsze)
        // ale nie z pierwszych 5 sekund (często intro/czarne ekrany)
        $minTime = min(5, $duration * 0.05);
        $maxTime = min($duration * 0.33, $duration - 1);

        if ($maxTime <= $minTime) {
            return $duration * 0.1;
        }

        // Możemy też zrobić analizę scen, ale to jest bardziej zaawansowane
        // Na razie używamy prostej heurystyki
        return $minTime + (($maxTime - $minTime) / 2);
    }

    /**
     * Tworzy unikalne ID dla filmu
     */
    public static function generateVideoId(string $filename): string
    {
        return md5($filename . time());
    }

    /**
     * Formatuje czas trwania z sekund na format HH:MM:SS lub MM:SS
     */
    public static function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Formatuje rozmiar pliku
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Parsuje FPS z formatu FFmpeg (np. "30/1")
     */
    private static function parseFps(?string $fps): ?float
    {
        if (!$fps) {
            return null;
        }

        if (strpos($fps, '/') !== false) {
            [$num, $den] = explode('/', $fps);
            return $den > 0 ? round((float)$num / (float)$den, 2) : null;
        }

        return (float)$fps;
    }

    /**
     * Pobiera ścieżkę do miniatury dla filmu
     */
    public static function getThumbnailPath(string $videoId): string
    {
        return THUMBNAILS_PATH . '/' . $videoId . '.jpg';
    }

    /**
     * Pobiera względną ścieżkę do miniatury (dla URL)
     */
    public static function getThumbnailUrl(string $videoId): string
    {
        $path = self::getThumbnailPath($videoId);
        if (file_exists($path)) {
            return '/data/thumbnails/' . $videoId . '.jpg';
        }
        // Fallback - placeholder
        return '/public/img/no-thumbnail.jpg';
    }

    /**
     * Sprawdza czy plik wideo jest poprawny
     */
    public static function isValidVideo(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, SUPPORTED_VIDEO_FORMATS);
    }
}
