<?php
/**
 * AIHelper - Proste generowanie metadanych z nazwy pliku
 * (bez AI, bez OpenAI API)
 */

declare(strict_types=1);

class AIHelper
{
    /**
     * Generuje metadata na podstawie nazwy pliku
     */
    public static function analyzeVideo(array $fileInfo, ?array $metadata = null): array
    {
        return self::generateMetadataFromFilename($fileInfo, $metadata);
    }

    /**
     * Generuje metadata z nazwy pliku
     */
    private static function generateMetadataFromFilename(array $fileInfo, ?array $metadata): array
    {
        $filename = pathinfo($fileInfo['filename'], PATHINFO_FILENAME);

        // Ładnie formatuj nazwę pliku na tytuł
        $title = self::beautifyFilename($filename);

        // Wyciągnij potencjalne tagi z nazwy
        $tags = self::extractTagsFromFilename($filename);

        // Określ kategorię na podstawie tagów
        $category = self::determineCategory($tags, $filename);

        // Prosty opis
        $description = "Film: {$title}";
        if ($metadata && isset($metadata['duration_formatted'])) {
            $description .= " ({$metadata['duration_formatted']})";
        }

        return [
            'title' => $title,
            'description' => $description,
            'tags' => $tags,
            'category' => $category,
            'ai_generated' => false,
        ];
    }

    /**
     * Ładnie formatuje nazwę pliku na tytuł
     */
    private static function beautifyFilename(string $filename): string
    {
        // Usuń typowe separatory i zastąp spacjami
        $title = preg_replace('/[_\-\.]+/', ' ', $filename);

        // Usuń numery na końcu (np. " 001", " 1080p")
        $title = preg_replace('/\s+\d+p?$/i', '', $title);

        // Usuń zbędne spacje
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);

        // Kapitalizuj pierwszą literę każdego słowa
        $title = mb_convert_case($title, MB_CASE_TITLE, 'UTF-8');

        return $title ?: 'Bez tytułu';
    }

    /**
     * Wyciąga tagi z nazwy pliku
     */
    private static function extractTagsFromFilename(string $filename): array
    {
        $tags = [];
        $filename = strtolower($filename);

        // Słowa kluczowe do rozpoznania
        $keywords = [
            'tutorial' => 'Tutorial',
            'vlog' => 'Vlog',
            'gaming' => 'Gaming',
            'gameplay' => 'Gameplay',
            'review' => 'Recenzja',
            'test' => 'Test',
            'unboxing' => 'Unboxing',
            'music' => 'Muzyka',
            'muzyka' => 'Muzyka',
            'live' => 'Live',
            'concert' => 'Koncert',
            'travel' => 'Podróże',
            'food' => 'Jedzenie',
            'sport' => 'Sport',
            'nature' => 'Natura',
            'timelapse' => 'Timelapse',
            '4k' => '4K',
            'hd' => 'HD',
            'film' => 'Film',
            'movie' => 'Film',
            'comedy' => 'Komedia',
            'funny' => 'Śmieszne',
            'family' => 'Rodzinne',
            'kids' => 'Dla dzieci',
            'education' => 'Edukacja',
            'howto' => 'Poradnik',
            'diy' => 'DIY',
        ];

        foreach ($keywords as $key => $tag) {
            if (stripos($filename, $key) !== false) {
                $tags[] = $tag;
            }
        }

        // Jeśli nie znaleziono żadnych tagów, dodaj domyślny
        if (empty($tags)) {
            $tags = ['Wideo'];
        }

        return array_unique($tags);
    }

    /**
     * Określa kategorię na podstawie tagów
     */
    private static function determineCategory(array $tags, string $filename): string
    {
        $categories = [
            'Gaming' => ['gaming', 'gameplay'],
            'Muzyka' => ['muzyka', 'music', 'concert'],
            'Edukacja' => ['tutorial', 'howto', 'education', 'test'],
            'Sport' => ['sport'],
            'Vlog' => ['vlog', 'travel', 'podróże'],
            'Film' => ['film', 'movie'],
            'Komedia' => ['comedy', 'funny', 'śmieszne'],
        ];

        $filename = strtolower($filename);
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (in_array($keyword, array_map('strtolower', $tags)) || stripos($filename, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'Różne';
    }

    /**
     * Nie używamy już AI do wyboru timestampu - zwracamy środek filmu
     */
    public static function suggestThumbnailTimestamp(array $fileInfo, ?array $metadata): float
    {
        if (!$metadata || !isset($metadata['duration'])) {
            return 0;
        }

        // Zwróć timestamp z środka filmu (bezpieczna opcja)
        return $metadata['duration'] / 2;
    }
}
