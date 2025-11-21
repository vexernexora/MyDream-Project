<?php
/**
 * API: System polubień/niepolubień dla filmów
 * YouTube-like like/dislike system
 */

require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda niedozwolona']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'toggle_like':
            toggleLike($input);
            break;

        case 'toggle_dislike':
            toggleDislike($input);
            break;

        case 'get_stats':
            getStats($input);
            break;

        case 'get_user_reaction':
            getUserReaction($input);
            break;

        case 'get_top_liked':
            getTopLiked($input);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w likes.php: " . $e->getMessage());
}

/**
 * Toggle like for video
 */
function toggleLike($input) {
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        return;
    }

    $likesFile = DATA_PATH . '/video_likes.json';
    $likes = [];

    if (file_exists($likesFile)) {
        $likes = json_decode(file_get_contents($likesFile), true) ?? [];
    }

    if (!isset($likes[$videoId])) {
        $likes[$videoId] = [
            'likes' => 0,
            'dislikes' => 0
        ];
    }

    // Pobierz reakcje użytkownika
    $userReactions = getUserVideoReactions();
    $previousReaction = $userReactions[$videoId] ?? null;

    // Usuń poprzednią reakcję jeśli istniała
    if ($previousReaction === 'dislike') {
        $likes[$videoId]['dislikes'] = max(0, $likes[$videoId]['dislikes'] - 1);
    } elseif ($previousReaction === 'like') {
        $likes[$videoId]['likes'] = max(0, $likes[$videoId]['likes'] - 1);
    }

    // Toggle like
    if ($previousReaction === 'like') {
        // User is removing like
        unset($userReactions[$videoId]);
        $newReaction = null;
    } else {
        // User is adding like
        $likes[$videoId]['likes']++;
        $userReactions[$videoId] = 'like';
        $newReaction = 'like';
    }

    // Zapisz
    if (!file_put_contents($likesFile, json_encode($likes, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się zapisać polubień');
    }

    saveUserVideoReactions($userReactions);

    echo json_encode([
        'success' => true,
        'likes' => $likes[$videoId]['likes'],
        'dislikes' => $likes[$videoId]['dislikes'],
        'user_reaction' => $newReaction
    ]);
}

/**
 * Toggle dislike for video
 */
function toggleDislike($input) {
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        return;
    }

    $likesFile = DATA_PATH . '/video_likes.json';
    $likes = [];

    if (file_exists($likesFile)) {
        $likes = json_decode(file_get_contents($likesFile), true) ?? [];
    }

    if (!isset($likes[$videoId])) {
        $likes[$videoId] = [
            'likes' => 0,
            'dislikes' => 0
        ];
    }

    // Pobierz reakcje użytkownika
    $userReactions = getUserVideoReactions();
    $previousReaction = $userReactions[$videoId] ?? null;

    // Usuń poprzednią reakcję jeśli istniała
    if ($previousReaction === 'like') {
        $likes[$videoId]['likes'] = max(0, $likes[$videoId]['likes'] - 1);
    } elseif ($previousReaction === 'dislike') {
        $likes[$videoId]['dislikes'] = max(0, $likes[$videoId]['dislikes'] - 1);
    }

    // Toggle dislike
    if ($previousReaction === 'dislike') {
        // User is removing dislike
        unset($userReactions[$videoId]);
        $newReaction = null;
    } else {
        // User is adding dislike
        $likes[$videoId]['dislikes']++;
        $userReactions[$videoId] = 'dislike';
        $newReaction = 'dislike';
    }

    // Zapisz
    if (!file_put_contents($likesFile, json_encode($likes, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się zapisać niepolubień');
    }

    saveUserVideoReactions($userReactions);

    echo json_encode([
        'success' => true,
        'likes' => $likes[$videoId]['likes'],
        'dislikes' => $likes[$videoId]['dislikes'],
        'user_reaction' => $newReaction
    ]);
}

/**
 * Get like/dislike stats for video
 */
function getStats($input) {
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        return;
    }

    $likesFile = DATA_PATH . '/video_likes.json';
    $likes = [];

    if (file_exists($likesFile)) {
        $likes = json_decode(file_get_contents($likesFile), true) ?? [];
    }

    $stats = $likes[$videoId] ?? [
        'likes' => 0,
        'dislikes' => 0
    ];

    // Calculate like ratio
    $total = $stats['likes'] + $stats['dislikes'];
    $likeRatio = $total > 0 ? ($stats['likes'] / $total) * 100 : 0;

    echo json_encode([
        'success' => true,
        'likes' => $stats['likes'],
        'dislikes' => $stats['dislikes'],
        'total' => $total,
        'like_ratio' => round($likeRatio, 1)
    ]);
}

/**
 * Get user's reaction to video
 */
function getUserReaction($input) {
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        return;
    }

    $userReactions = getUserVideoReactions();

    echo json_encode([
        'success' => true,
        'reaction' => $userReactions[$videoId] ?? null
    ]);
}

/**
 * Get top liked videos
 */
function getTopLiked($input) {
    $limit = (int)($input['limit'] ?? 10);

    $likesFile = DATA_PATH . '/video_likes.json';

    if (!file_exists($likesFile)) {
        echo json_encode([
            'success' => true,
            'videos' => []
        ]);
        return;
    }

    $likes = json_decode(file_get_contents($likesFile), true) ?? [];

    // Sort by likes count
    uasort($likes, function($a, $b) {
        return $b['likes'] <=> $a['likes'];
    });

    // Get top N
    $topVideos = array_slice($likes, 0, $limit, true);

    $result = [];
    foreach ($topVideos as $videoId => $stats) {
        $result[] = [
            'video_id' => $videoId,
            'likes' => $stats['likes'],
            'dislikes' => $stats['dislikes']
        ];
    }

    echo json_encode([
        'success' => true,
        'videos' => $result
    ]);
}

/**
 * Get user video reactions from storage
 */
function getUserVideoReactions(): array {
    $reactionsFile = DATA_PATH . '/user_video_reactions.json';

    if (!file_exists($reactionsFile)) {
        return [];
    }

    return json_decode(file_get_contents($reactionsFile), true) ?? [];
}

/**
 * Save user video reactions to storage
 */
function saveUserVideoReactions(array $reactions): void {
    $reactionsFile = DATA_PATH . '/user_video_reactions.json';
    file_put_contents($reactionsFile, json_encode($reactions, JSON_PRETTY_PRINT));
}
