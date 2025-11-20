<?php
/**
 * JsonHelper - Zarządzanie danymi w plikach JSON
 */

declare(strict_types=1);

class JsonHelper
{
    /**
     * Odczytuje wszystkie filmy z JSON
     */
    public static function getVideos(): array
    {
        $data = self::readJson();
        return $data['videos'] ?? [];
    }

    /**
     * Pobiera pojedynczy film po ID
     */
    public static function getVideoById(string $id): ?array
    {
        $videos = self::getVideos();
        foreach ($videos as $video) {
            if ($video['id'] === $id) {
                return $video;
            }
        }
        return null;
    }

    /**
     * Dodaje nowy film do JSON
     */
    public static function addVideo(array $videoData): bool
    {
        $data = self::readJson();

        // Sprawdź czy film już istnieje
        foreach ($data['videos'] as $existing) {
            if ($existing['filename'] === $videoData['filename']) {
                debug_log("Film już istnieje: " . $videoData['filename']);
                return false;
            }
        }

        $data['videos'][] = $videoData;
        return self::writeJson($data);
    }

    /**
     * Aktualizuje dane filmu
     */
    public static function updateVideo(string $id, array $updates): bool
    {
        $data = self::readJson();
        $updated = false;

        foreach ($data['videos'] as $key => $video) {
            if ($video['id'] === $id) {
                $data['videos'][$key] = array_merge($video, $updates);
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return self::writeJson($data);
        }

        return false;
    }

    /**
     * Usuwa film z JSON
     */
    public static function deleteVideo(string $id): bool
    {
        $data = self::readJson();
        $originalCount = count($data['videos']);

        $data['videos'] = array_values(array_filter($data['videos'], function($video) use ($id) {
            return $video['id'] !== $id;
        }));

        if (count($data['videos']) < $originalCount) {
            return self::writeJson($data);
        }

        return false;
    }

    /**
     * Aktualizuje czas ostatniego skanowania
     */
    public static function updateLastScan(): bool
    {
        $data = self::readJson();
        $data['last_scan'] = date('Y-m-d H:i:s');
        return self::writeJson($data);
    }

    /**
     * Pobiera wszystkie tagi
     */
    public static function getAllTags(): array
    {
        $videos = self::getVideos();
        $tags = [];

        foreach ($videos as $video) {
            if (!empty($video['tags'])) {
                $tags = array_merge($tags, $video['tags']);
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * Wyszukuje filmy
     */
    public static function searchVideos(string $query, ?array $tags = null): array
    {
        $videos = self::getVideos();
        $query = strtolower(trim($query));

        return array_filter($videos, function($video) use ($query, $tags) {
            // Filtrowanie po zapytaniu tekstowym
            if (!empty($query)) {
                $titleMatch = stripos($video['title'] ?? '', $query) !== false;
                $descMatch = stripos($video['description'] ?? '', $query) !== false;
                $tagsMatch = false;

                if (!empty($video['tags'])) {
                    foreach ($video['tags'] as $tag) {
                        if (stripos($tag, $query) !== false) {
                            $tagsMatch = true;
                            break;
                        }
                    }
                }

                if (!$titleMatch && !$descMatch && !$tagsMatch) {
                    return false;
                }
            }

            // Filtrowanie po tagach
            if (!empty($tags)) {
                if (empty($video['tags'])) {
                    return false;
                }
                foreach ($tags as $tag) {
                    if (!in_array($tag, $video['tags'])) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    /**
     * Sortuje filmy
     */
    public static function sortVideos(array $videos, string $sortBy = 'date', string $order = 'desc'): array
    {
        usort($videos, function($a, $b) use ($sortBy, $order) {
            $comparison = 0;

            switch ($sortBy) {
                case 'title':
                    $comparison = strcasecmp($a['title'] ?? '', $b['title'] ?? '');
                    break;
                case 'duration':
                    $comparison = ($a['duration_seconds'] ?? 0) <=> ($b['duration_seconds'] ?? 0);
                    break;
                case 'date':
                default:
                    $comparison = ($a['added_at'] ?? 0) <=> ($b['added_at'] ?? 0);
                    break;
            }

            return $order === 'asc' ? $comparison : -$comparison;
        });

        return $videos;
    }

    /**
     * Pobiera podobne filmy na podstawie tagów
     */
    public static function getSimilarVideos(string $videoId, int $limit = 6): array
    {
        $currentVideo = self::getVideoById($videoId);
        if (!$currentVideo || empty($currentVideo['tags'])) {
            return [];
        }

        $videos = self::getVideos();
        $scored = [];

        foreach ($videos as $video) {
            if ($video['id'] === $videoId) {
                continue;
            }

            $score = 0;
            if (!empty($video['tags'])) {
                $commonTags = array_intersect($currentVideo['tags'], $video['tags']);
                $score = count($commonTags);
            }

            if ($score > 0) {
                $scored[] = [
                    'video' => $video,
                    'score' => $score
                ];
            }
        }

        // Sortuj po podobieństwie
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'video'), 0, $limit);
    }

    /**
     * Odczytuje dane z JSON
     */
    private static function readJson(): array
    {
        if (!file_exists(VIDEOS_JSON)) {
            return ['videos' => [], 'last_scan' => null];
        }

        $content = file_get_contents(VIDEOS_JSON);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debug_log("Błąd parsowania JSON: " . json_last_error_msg());
            return ['videos' => [], 'last_scan' => null];
        }

        return $data;
    }

    /**
     * Zapisuje dane do JSON
     */
    private static function writeJson(array $data): bool
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            debug_log("Błąd podczas tworzenia JSON: " . json_last_error_msg());
            return false;
        }

        $result = file_put_contents(VIDEOS_JSON, $json);

        if ($result === false) {
            debug_log("Nie udało się zapisać pliku JSON");
            return false;
        }

        return true;
    }
}
