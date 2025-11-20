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
            debug_log("FFmpeg nie jest dostępny - generuję prostą miniaturkę");
            // Wygeneruj prostą miniaturkę z GD (gradient + nazwa)
            return self::generateSimpleThumbnail($videoPath, $outputPath);
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
     * Generuje prostą miniaturkę bez FFmpeg (gradient + tekst)
     */
    private static function generateSimpleThumbnail(string $videoPath, string $outputPath): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            debug_log("GD library nie jest dostępna");
            // Fallback - skopiuj placeholder
            $placeholder = PUBLIC_PATH . '/img/no-thumbnail.jpg';
            if (file_exists($placeholder)) {
                copy($placeholder, $outputPath);
                return true;
            }
            return false;
        }

        // Utwórz folder jeśli nie istnieje
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $width = 1280;
        $height = 720;

        // Stwórz obrazek
        $image = imagecreatetruecolor($width, $height);

        // Wygeneruj losowy kolor na podstawie nazwy pliku (zawsze ten sam dla tego pliku)
        $filename = basename($videoPath);
        $hash = md5($filename);
        $hue = hexdec(substr($hash, 0, 2)) / 255;

        // Konwertuj HSL na RGB dla ładnych kolorów
        $color1 = self::hslToRgb($hue, 0.7, 0.3);
        $color2 = self::hslToRgb($hue, 0.7, 0.2);

        // Narysuj gradient
        for ($i = 0; $i < $height; $i++) {
            $ratio = $i / $height;
            $r = $color1[0] + ($color2[0] - $color1[0]) * $ratio;
            $g = $color1[1] + ($color2[1] - $color1[1]) * $ratio;
            $b = $color1[2] + ($color2[2] - $color1[2]) * $ratio;

            $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
            imageline($image, 0, $i, $width, $i, $color);
        }

        // Dodaj tekst - nazwę pliku
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        // Nazwa bez rozszerzenia
        $title = pathinfo($filename, PATHINFO_FILENAME);
        if (strlen($title) > 40) {
            $title = substr($title, 0, 37) . '...';
        }

        // Użyj wbudowanej czcionki lub TTF jeśli dostępna
        $fontSize = 5; // Największa wbudowana czcionka (1-5)
        $textWidth = imagefontwidth($fontSize) * strlen($title);
        $textHeight = imagefontheight($fontSize);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;

        // Cień
        imagestring($image, $fontSize, $x + 2, $y + 2, $title, $black);
        // Tekst
        imagestring($image, $fontSize, $x, $y, $title, $white);

        // Ikona play (trójkąt)
        $playSize = 80;
        $playX = $width / 2;
        $playY = $height / 2 + 60;

        $triangle = [
            $playX - $playSize / 2, $playY - $playSize / 2,
            $playX - $playSize / 2, $playY + $playSize / 2,
            $playX + $playSize / 2, $playY
        ];

        // Cień play
        $playTriangleShadow = array_map(function($v) { return $v + 3; }, $triangle);
        imagefilledpolygon($image, $playTriangleShadow, 3, $black);

        // Play button
        imagefilledpolygon($image, $triangle, 3, $white);

        // Zapisz
        $result = imagejpeg($image, $outputPath, 85);
        imagedestroy($image);

        debug_log("Prosta miniatura wygenerowana: $outputPath");
        return $result;
    }

    /**
     * Konwertuje HSL na RGB
     */
    private static function hslToRgb(float $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h * 6, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 1/6) {
            $rgb = [$c, $x, 0];
        } elseif ($h < 2/6) {
            $rgb = [$x, $c, 0];
        } elseif ($h < 3/6) {
            $rgb = [0, $c, $x];
        } elseif ($h < 4/6) {
            $rgb = [0, $x, $c];
        } elseif ($h < 5/6) {
            $rgb = [$x, 0, $c];
        } else {
            $rgb = [$c, 0, $x];
        }

        return [
            ($rgb[0] + $m) * 255,
            ($rgb[1] + $m) * 255,
            ($rgb[2] + $m) * 255
        ];
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
