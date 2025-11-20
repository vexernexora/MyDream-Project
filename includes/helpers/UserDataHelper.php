<?php
/**
 * UserDataHelper - Zarządzanie danymi użytkownika
 * Historia, ulubione, watch later, oceny, progress
 */

declare(strict_types=1);

class UserDataHelper
{
    private static string $userDataFile;

    /**
     * Inicjalizacja
     */
    public static function init(): void
    {
        self::$userDataFile = DATA_PATH . '/userdata.json';

        if (!file_exists(self::$userDataFile)) {
            self::createDefaultFile();
        }
    }

    /**
     * Tworzy domyślny plik z danymi użytkownika
     */
    private static function createDefaultFile(): void
    {
        $defaultData = [
            'watch_history' => [],
            'favorites' => [],
            'watch_later' => [],
            'ratings' => [],
            'progress' => [],
            'preferences' => [
                'playback_speed' => 1.0,
                'theater_mode' => false,
                'autoplay' => true,
                'default_quality' => 'auto'
            ]
        ];

        file_put_contents(self::$userDataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
        debug_log("Utworzono plik userdata.json");
    }

    /**
     * Odczytuje wszystkie dane użytkownika
     */
    private static function readData(): array
    {
        self::init();

        if (!file_exists(self::$userDataFile)) {
            return [];
        }

        $content = file_get_contents(self::$userDataFile);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debug_log("Błąd parsowania userdata.json: " . json_last_error_msg());
            self::createDefaultFile();
            return json_decode(file_get_contents(self::$userDataFile), true);
        }

        return $data;
    }

    /**
     * Zapisuje dane użytkownika
     */
    private static function writeData(array $data): bool
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents(self::$userDataFile, $json) !== false;
    }

    // ============================================
    // HISTORIA OGLĄDANIA
    // ============================================

    /**
     * Dodaje film do historii oglądania
     */
    public static function addToHistory(string $videoId): bool
    {
        $data = self::readData();

        // Usuń stary wpis jeśli istnieje
        $data['watch_history'] = array_filter($data['watch_history'], fn($item) => $item['video_id'] !== $videoId);

        // Dodaj nowy wpis na początku
        array_unshift($data['watch_history'], [
            'video_id' => $videoId,
            'watched_at' => time(),
            'watched_at_formatted' => date('Y-m-d H:i:s')
        ]);

        // Ogranicz do 100 ostatnich
        $data['watch_history'] = array_slice($data['watch_history'], 0, 100);

        return self::writeData($data);
    }

    /**
     * Pobiera historię oglądania
     */
    public static function getHistory(int $limit = 20): array
    {
        $data = self::readData();
        return array_slice($data['watch_history'] ?? [], 0, $limit);
    }

    /**
     * Czyści historię
     */
    public static function clearHistory(): bool
    {
        $data = self::readData();
        $data['watch_history'] = [];
        return self::writeData($data);
    }

    // ============================================
    // ULUBIONE
    // ============================================

    /**
     * Dodaje film do ulubionych
     */
    public static function addToFavorites(string $videoId): bool
    {
        $data = self::readData();

        if (!in_array($videoId, $data['favorites'] ?? [])) {
            $data['favorites'][] = $videoId;
            return self::writeData($data);
        }

        return true;
    }

    /**
     * Usuwa film z ulubionych
     */
    public static function removeFromFavorites(string $videoId): bool
    {
        $data = self::readData();
        $data['favorites'] = array_values(array_filter($data['favorites'] ?? [], fn($id) => $id !== $videoId));
        return self::writeData($data);
    }

    /**
     * Sprawdza czy film jest w ulubionych
     */
    public static function isFavorite(string $videoId): bool
    {
        $data = self::readData();
        return in_array($videoId, $data['favorites'] ?? []);
    }

    /**
     * Pobiera ulubione filmy
     */
    public static function getFavorites(): array
    {
        $data = self::readData();
        return $data['favorites'] ?? [];
    }

    // ============================================
    // WATCH LATER (DO OBEJRZENIA)
    // ============================================

    /**
     * Dodaje film do watch later
     */
    public static function addToWatchLater(string $videoId): bool
    {
        $data = self::readData();

        if (!in_array($videoId, $data['watch_later'] ?? [])) {
            $data['watch_later'][] = $videoId;
            return self::writeData($data);
        }

        return true;
    }

    /**
     * Usuwa film z watch later
     */
    public static function removeFromWatchLater(string $videoId): bool
    {
        $data = self::readData();
        $data['watch_later'] = array_values(array_filter($data['watch_later'] ?? [], fn($id) => $id !== $videoId));
        return self::writeData($data);
    }

    /**
     * Sprawdza czy film jest w watch later
     */
    public static function isInWatchLater(string $videoId): bool
    {
        $data = self::readData();
        return in_array($videoId, $data['watch_later'] ?? []);
    }

    /**
     * Pobiera filmy z watch later
     */
    public static function getWatchLater(): array
    {
        $data = self::readData();
        return $data['watch_later'] ?? [];
    }

    // ============================================
    // OCENY (RATINGS)
    // ============================================

    /**
     * Ustala ocenę filmu (1-5)
     */
    public static function setRating(string $videoId, int $rating): bool
    {
        if ($rating < 1 || $rating > 5) {
            return false;
        }

        $data = self::readData();

        // Znajdź istniejącą ocenę
        $found = false;
        foreach ($data['ratings'] as &$item) {
            if ($item['video_id'] === $videoId) {
                $item['rating'] = $rating;
                $item['rated_at'] = time();
                $found = true;
                break;
            }
        }

        // Jeśli nie znaleziono, dodaj nową
        if (!$found) {
            $data['ratings'][] = [
                'video_id' => $videoId,
                'rating' => $rating,
                'rated_at' => time()
            ];
        }

        return self::writeData($data);
    }

    /**
     * Usuwa ocenę filmu
     */
    public static function removeRating(string $videoId): bool
    {
        $data = self::readData();
        $data['ratings'] = array_values(array_filter($data['ratings'], fn($item) => $item['video_id'] !== $videoId));
        return self::writeData($data);
    }

    /**
     * Pobiera ocenę filmu
     */
    public static function getRating(string $videoId): ?int
    {
        $data = self::readData();

        foreach ($data['ratings'] as $item) {
            if ($item['video_id'] === $videoId) {
                return $item['rating'];
            }
        }

        return null;
    }

    /**
     * Pobiera wszystkie oceny
     */
    public static function getAllRatings(): array
    {
        $data = self::readData();
        return $data['ratings'] ?? [];
    }

    /**
     * Pobiera najwyżej ocenione filmy
     */
    public static function getTopRated(int $limit = 10): array
    {
        $ratings = self::getAllRatings();

        // Sortuj po ocenie (malejąco)
        usort($ratings, fn($a, $b) => $b['rating'] <=> $a['rating']);

        return array_slice($ratings, 0, $limit);
    }

    // ============================================
    // PROGRESS (POSTĘP OGLĄDANIA)
    // ============================================

    /**
     * Zapisuje postęp oglądania filmu
     */
    public static function saveProgress(string $videoId, float $currentTime, float $duration): bool
    {
        $data = self::readData();

        $percentage = $duration > 0 ? round(($currentTime / $duration) * 100, 2) : 0;

        $data['progress'][$videoId] = [
            'current_time' => $currentTime,
            'duration' => $duration,
            'percentage' => $percentage,
            'updated_at' => time()
        ];

        return self::writeData($data);
    }

    /**
     * Pobiera postęp oglądania filmu
     */
    public static function getProgress(string $videoId): ?array
    {
        $data = self::readData();
        return $data['progress'][$videoId] ?? null;
    }

    /**
     * Usuwa postęp oglądania (gdy film obejrzany do końca)
     */
    public static function removeProgress(string $videoId): bool
    {
        $data = self::readData();

        if (isset($data['progress'][$videoId])) {
            unset($data['progress'][$videoId]);
            return self::writeData($data);
        }

        return true;
    }

    /**
     * Pobiera filmy w trakcie oglądania (Continue Watching)
     */
    public static function getContinueWatching(int $limit = 12): array
    {
        $data = self::readData();
        $progress = $data['progress'] ?? [];

        // Filtruj tylko te które są w trakcie (5-95%)
        $inProgress = array_filter($progress, function($item) {
            return $item['percentage'] >= 5 && $item['percentage'] < 95;
        });

        // Sortuj po czasie aktualizacji (najnowsze pierwsze)
        uasort($inProgress, fn($a, $b) => $b['updated_at'] <=> $a['updated_at']);

        return array_slice($inProgress, 0, $limit, true);
    }

    // ============================================
    // PREFERENCJE
    // ============================================

    /**
     * Zapisuje preferencję użytkownika
     */
    public static function setPreference(string $key, $value): bool
    {
        $data = self::readData();
        $data['preferences'][$key] = $value;
        return self::writeData($data);
    }

    /**
     * Pobiera preferencję użytkownika
     */
    public static function getPreference(string $key, $default = null)
    {
        $data = self::readData();
        return $data['preferences'][$key] ?? $default;
    }

    /**
     * Pobiera wszystkie preferencje
     */
    public static function getAllPreferences(): array
    {
        $data = self::readData();
        return $data['preferences'] ?? [];
    }

    // ============================================
    // STATYSTYKI
    // ============================================

    /**
     * Pobiera statystyki użytkownika
     */
    public static function getStatistics(): array
    {
        $data = self::readData();

        return [
            'total_watched' => count($data['watch_history'] ?? []),
            'favorites_count' => count($data['favorites'] ?? []),
            'watch_later_count' => count($data['watch_later'] ?? []),
            'rated_videos' => count($data['ratings'] ?? []),
            'in_progress' => count(array_filter($data['progress'] ?? [], fn($p) => $p['percentage'] >= 5 && $p['percentage'] < 95)),
            'average_rating' => self::calculateAverageRating($data['ratings'] ?? [])
        ];
    }

    /**
     * Oblicza średnią ocenę
     */
    private static function calculateAverageRating(array $ratings): float
    {
        if (empty($ratings)) {
            return 0;
        }

        $sum = array_sum(array_column($ratings, 'rating'));
        return round($sum / count($ratings), 1);
    }
}
