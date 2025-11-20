<?php
/**
 * API: Pobieranie listy filmów z filtrowaniem i sortowaniem
 */

require_once __DIR__ . '/common.php';

try {
    // Parametry z query string
    $search = $_GET['search'] ?? '';
    $tags = isset($_GET['tags']) ? explode(',', $_GET['tags']) : null;
    $sortBy = $_GET['sort'] ?? 'date';
    $order = $_GET['order'] ?? 'desc';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = VIDEOS_PER_PAGE;

    // Pobierz filmy
    $videos = JsonHelper::getVideos();

    // Filtruj
    if (!empty($search) || !empty($tags)) {
        $videos = JsonHelper::searchVideos($search, $tags);
    }

    // Sortuj
    $videos = JsonHelper::sortVideos($videos, $sortBy, $order);

    // Paginacja
    $total = count($videos);
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    $videos = array_slice($videos, $offset, $perPage);

    // Dodaj URL miniaturek
    foreach ($videos as &$video) {
        $video['thumbnail_url'] = VideoHelper::getThumbnailUrl($video['id']);
    }

    echo json_encode([
        'success' => true,
        'videos' => array_values($videos),
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages
        ],
        'filters' => [
            'search' => $search,
            'tags' => $tags,
            'sort' => $sortBy,
            'order' => $order
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas pobierania filmów: ' . $e->getMessage()
    ]);
    debug_log("Błąd w get-videos.php: " . $e->getMessage());
}
