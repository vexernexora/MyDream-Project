<?php
/**
 * API: System playlist
 * YouTube-like playlists management
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
        case 'create':
            createPlaylist($input);
            break;

        case 'get_all':
            getAllPlaylists();
            break;

        case 'get':
            getPlaylist($input);
            break;

        case 'update':
            updatePlaylist($input);
            break;

        case 'delete':
            deletePlaylist($input);
            break;

        case 'add_video':
            addVideoToPlaylist($input);
            break;

        case 'remove_video':
            removeVideoFromPlaylist($input);
            break;

        case 'reorder':
            reorderPlaylist($input);
            break;

        case 'get_video_playlists':
            getVideoPlaylists($input);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w playlists.php: " . $e->getMessage());
}

/**
 * Create new playlist
 */
function createPlaylist($input) {
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $isPublic = (bool)($input['is_public'] ?? true);

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nazwa playlisty jest wymagana']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';
    $playlists = [];

    if (file_exists($playlistsFile)) {
        $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];
    }

    $playlistId = uniqid('playlist_', true);

    $newPlaylist = [
        'id' => $playlistId,
        'name' => htmlspecialchars($name),
        'description' => htmlspecialchars($description),
        'is_public' => $isPublic,
        'created_at' => time(),
        'updated_at' => time(),
        'video_ids' => [],
        'thumbnail' => null
    ];

    $playlists[] = $newPlaylist;

    if (!file_put_contents($playlistsFile, json_encode($playlists, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się utworzyć playlisty');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Playlista utworzona',
        'playlist' => $newPlaylist
    ]);
}

/**
 * Get all playlists
 */
function getAllPlaylists() {
    $playlistsFile = DATA_PATH . '/playlists.json';
    $playlists = [];

    if (file_exists($playlistsFile)) {
        $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];
    }

    // Add video count for each playlist
    foreach ($playlists as &$playlist) {
        $playlist['video_count'] = count($playlist['video_ids']);
    }

    echo json_encode([
        'success' => true,
        'playlists' => $playlists
    ]);
}

/**
 * Get single playlist with videos
 */
function getPlaylist($input) {
    $playlistId = $input['playlist_id'] ?? '';

    if (empty($playlistId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID playlisty']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';

    if (!file_exists($playlistsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie istnieje']);
        return;
    }

    $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];

    $playlist = null;
    foreach ($playlists as $p) {
        if ($p['id'] === $playlistId) {
            $playlist = $p;
            break;
        }
    }

    if (!$playlist) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie znaleziona']);
        return;
    }

    // Load video details
    require_once __DIR__ . '/../../includes/helpers/JsonHelper.php';

    $videos = [];
    foreach ($playlist['video_ids'] as $videoId) {
        $video = JsonHelper::getVideoById($videoId);
        if ($video) {
            $videos[] = $video;
        }
    }

    $playlist['videos'] = $videos;
    $playlist['video_count'] = count($videos);

    echo json_encode([
        'success' => true,
        'playlist' => $playlist
    ]);
}

/**
 * Update playlist
 */
function updatePlaylist($input) {
    $playlistId = $input['playlist_id'] ?? '';
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $isPublic = (bool)($input['is_public'] ?? true);

    if (empty($playlistId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID playlisty']);
        return;
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nazwa playlisty jest wymagana']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';

    if (!file_exists($playlistsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie istnieje']);
        return;
    }

    $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];
    $found = false;

    foreach ($playlists as &$playlist) {
        if ($playlist['id'] === $playlistId) {
            $playlist['name'] = htmlspecialchars($name);
            $playlist['description'] = htmlspecialchars($description);
            $playlist['is_public'] = $isPublic;
            $playlist['updated_at'] = time();
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie znaleziona']);
        return;
    }

    if (!file_put_contents($playlistsFile, json_encode($playlists, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się zaktualizować playlisty');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Playlista zaktualizowana'
    ]);
}

/**
 * Delete playlist
 */
function deletePlaylist($input) {
    $playlistId = $input['playlist_id'] ?? '';

    if (empty($playlistId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID playlisty']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';

    if (!file_exists($playlistsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie istnieje']);
        return;
    }

    $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];

    $playlists = array_filter($playlists, function($p) use ($playlistId) {
        return $p['id'] !== $playlistId;
    });

    if (!file_put_contents($playlistsFile, json_encode(array_values($playlists), JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się usunąć playlisty');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Playlista usunięta'
    ]);
}

/**
 * Add video to playlist
 */
function addVideoToPlaylist($input) {
    $playlistId = $input['playlist_id'] ?? '';
    $videoId = $input['video_id'] ?? '';

    if (empty($playlistId) || empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';

    if (!file_exists($playlistsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie istnieje']);
        return;
    }

    $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];
    $found = false;

    foreach ($playlists as &$playlist) {
        if ($playlist['id'] === $playlistId) {
            // Check if video already in playlist
            if (!in_array($videoId, $playlist['video_ids'])) {
                $playlist['video_ids'][] = $videoId;
                $playlist['updated_at'] = time();

                // Update thumbnail if first video
                if (count($playlist['video_ids']) === 1) {
                    $playlist['thumbnail'] = $videoId;
                }
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie znaleziona']);
        return;
    }

    if (!file_put_contents($playlistsFile, json_encode($playlists, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się dodać filmu do playlisty');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Film dodany do playlisty'
    ]);
}

/**
 * Remove video from playlist
 */
function removeVideoFromPlaylist($input) {
    $playlistId = $input['playlist_id'] ?? '';
    $videoId = $input['video_id'] ?? '';

    if (empty($playlistId) || empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';

    if (!file_exists($playlistsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie istnieje']);
        return;
    }

    $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];
    $found = false;

    foreach ($playlists as &$playlist) {
        if ($playlist['id'] === $playlistId) {
            $playlist['video_ids'] = array_values(array_filter($playlist['video_ids'], function($id) use ($videoId) {
                return $id !== $videoId;
            }));
            $playlist['updated_at'] = time();

            // Update thumbnail if removed video was thumbnail
            if ($playlist['thumbnail'] === $videoId) {
                $playlist['thumbnail'] = $playlist['video_ids'][0] ?? null;
            }

            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error'] => 'Playlista nie znaleziona']);
        return;
    }

    if (!file_put_contents($playlistsFile, json_encode($playlists, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się usunąć filmu z playlisty');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Film usunięty z playlisty'
    ]);
}

/**
 * Reorder videos in playlist
 */
function reorderPlaylist($input) {
    $playlistId = $input['playlist_id'] ?? '';
    $videoIds = $input['video_ids'] ?? [];

    if (empty($playlistId) || !is_array($videoIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';

    if (!file_exists($playlistsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie istnieje']);
        return;
    }

    $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];
    $found = false;

    foreach ($playlists as &$playlist) {
        if ($playlist['id'] === $playlistId) {
            $playlist['video_ids'] = $videoIds;
            $playlist['updated_at'] = time();
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlista nie znaleziona']);
        return;
    }

    if (!file_put_contents($playlistsFile, json_encode($playlists, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się zaktualizować kolejności');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Kolejność zaktualizowana'
    ]);
}

/**
 * Get all playlists containing a video
 */
function getVideoPlaylists($input) {
    $videoId = $input['video_id'] ?? '';

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        return;
    }

    $playlistsFile = DATA_PATH . '/playlists.json';
    $result = [];

    if (file_exists($playlistsFile)) {
        $playlists = json_decode(file_get_contents($playlistsFile), true) ?? [];

        foreach ($playlists as $playlist) {
            $result[] = [
                'id' => $playlist['id'],
                'name' => $playlist['name'],
                'contains_video' => in_array($videoId, $playlist['video_ids'])
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'playlists' => $result
    ]);
}
