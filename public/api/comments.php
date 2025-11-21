<?php
/**
 * API: System komentarzy z odpowiedziami i reakcjami
 * YouTube-like comments system
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
        case 'add':
            addComment($input);
            break;

        case 'get':
            getComments($input);
            break;

        case 'delete':
            deleteComment($input);
            break;

        case 'edit':
            editComment($input);
            break;

        case 'react':
            reactToComment($input);
            break;

        case 'reply':
            replyToComment($input);
            break;

        case 'get_replies':
            getReplies($input);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    debug_log("Błąd w comments.php: " . $e->getMessage());
}

/**
 * Dodaj komentarz
 */
function addComment($input) {
    $videoId = $input['video_id'] ?? '';
    $text = trim($input['text'] ?? '');
    $username = $input['username'] ?? 'Anonim';

    if (empty($videoId) || empty($text)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        return;
    }

    if (strlen($text) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Komentarz jest za długi (max 5000 znaków)']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';
    $comments = [];

    if (file_exists($commentsFile)) {
        $comments = json_decode(file_get_contents($commentsFile), true) ?? [];
    }

    $commentId = uniqid('comment_', true);

    $newComment = [
        'id' => $commentId,
        'video_id' => $videoId,
        'username' => htmlspecialchars($username),
        'text' => htmlspecialchars($text),
        'created_at' => time(),
        'edited_at' => null,
        'likes' => 0,
        'dislikes' => 0,
        'replies_count' => 0,
        'is_pinned' => false,
        'is_edited' => false
    ];

    $comments[] = $newComment;

    if (!file_put_contents($commentsFile, json_encode($comments, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się zapisać komentarza');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Komentarz dodany',
        'comment' => $newComment
    ]);
}

/**
 * Pobierz komentarze
 */
function getComments($input) {
    $videoId = $input['video_id'] ?? '';
    $sort = $input['sort'] ?? 'newest'; // newest, oldest, top
    $limit = (int)($input['limit'] ?? 50);
    $offset = (int)($input['offset'] ?? 0);

    if (empty($videoId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID filmu']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';
    $comments = [];

    if (file_exists($commentsFile)) {
        $allComments = json_decode(file_get_contents($commentsFile), true) ?? [];

        // Filtruj komentarze dla danego filmu (tylko główne, bez odpowiedzi)
        $comments = array_filter($allComments, function($comment) use ($videoId) {
            return $comment['video_id'] === $videoId && !isset($comment['parent_id']);
        });
    }

    // Sortowanie
    switch ($sort) {
        case 'oldest':
            usort($comments, function($a, $b) {
                return $a['created_at'] <=> $b['created_at'];
            });
            break;

        case 'top':
            usort($comments, function($a, $b) {
                $scoreA = $a['likes'] - $a['dislikes'];
                $scoreB = $b['likes'] - $b['dislikes'];
                return $scoreB <=> $scoreA;
            });
            break;

        case 'newest':
        default:
            usort($comments, function($a, $b) {
                // Pinned comments first
                if ($a['is_pinned'] !== $b['is_pinned']) {
                    return $b['is_pinned'] <=> $a['is_pinned'];
                }
                return $b['created_at'] <=> $a['created_at'];
            });
            break;
    }

    // Paginacja
    $total = count($comments);
    $comments = array_slice($comments, $offset, $limit);

    // Pobierz reakcje użytkownika
    $userReactions = getUserCommentReactions();

    foreach ($comments as &$comment) {
        $comment['user_reaction'] = $userReactions[$comment['id']] ?? null;
        $comment['time_ago'] = timeAgo($comment['created_at']);
    }

    echo json_encode([
        'success' => true,
        'comments' => array_values($comments),
        'total' => $total,
        'has_more' => ($offset + $limit) < $total
    ]);
}

/**
 * Usuń komentarz
 */
function deleteComment($input) {
    $commentId = $input['comment_id'] ?? '';

    if (empty($commentId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID komentarza']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';

    if (!file_exists($commentsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Komentarz nie istnieje']);
        return;
    }

    $comments = json_decode(file_get_contents($commentsFile), true) ?? [];

    // Usuń komentarz i wszystkie odpowiedzi
    $comments = array_filter($comments, function($comment) use ($commentId) {
        return $comment['id'] !== $commentId &&
               ($comment['parent_id'] ?? null) !== $commentId;
    });

    if (!file_put_contents($commentsFile, json_encode(array_values($comments), JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się usunąć komentarza');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Komentarz usunięty'
    ]);
}

/**
 * Edytuj komentarz
 */
function editComment($input) {
    $commentId = $input['comment_id'] ?? '';
    $text = trim($input['text'] ?? '');

    if (empty($commentId) || empty($text)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        return;
    }

    if (strlen($text) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Komentarz jest za długi (max 5000 znaków)']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';

    if (!file_exists($commentsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Komentarz nie istnieje']);
        return;
    }

    $comments = json_decode(file_get_contents($commentsFile), true) ?? [];
    $found = false;

    foreach ($comments as &$comment) {
        if ($comment['id'] === $commentId) {
            $comment['text'] = htmlspecialchars($text);
            $comment['edited_at'] = time();
            $comment['is_edited'] = true;
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Komentarz nie znaleziony']);
        return;
    }

    if (!file_put_contents($commentsFile, json_encode($comments, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się edytować komentarza');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Komentarz zaktualizowany'
    ]);
}

/**
 * Reaguj na komentarz (like/dislike)
 */
function reactToComment($input) {
    $commentId = $input['comment_id'] ?? '';
    $reaction = $input['reaction'] ?? ''; // 'like' or 'dislike'

    if (empty($commentId) || !in_array($reaction, ['like', 'dislike'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nieprawidłowe dane']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';

    if (!file_exists($commentsFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Komentarz nie istnieje']);
        return;
    }

    $comments = json_decode(file_get_contents($commentsFile), true) ?? [];
    $found = false;

    // Pobierz obecne reakcje użytkownika
    $userReactions = getUserCommentReactions();
    $previousReaction = $userReactions[$commentId] ?? null;

    foreach ($comments as &$comment) {
        if ($comment['id'] === $commentId) {
            // Usuń poprzednią reakcję
            if ($previousReaction === 'like') {
                $comment['likes'] = max(0, $comment['likes'] - 1);
            } elseif ($previousReaction === 'dislike') {
                $comment['dislikes'] = max(0, $comment['dislikes'] - 1);
            }

            // Dodaj nową reakcję (jeśli nie jest taka sama)
            if ($previousReaction !== $reaction) {
                if ($reaction === 'like') {
                    $comment['likes']++;
                } else {
                    $comment['dislikes']++;
                }
                $userReactions[$commentId] = $reaction;
            } else {
                // Jeśli klikniemy tę samą reakcję, usuń ją
                unset($userReactions[$commentId]);
            }

            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Komentarz nie znaleziony']);
        return;
    }

    // Zapisz komentarze
    if (!file_put_contents($commentsFile, json_encode($comments, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się zaktualizować reakcji');
    }

    // Zapisz reakcje użytkownika
    saveUserCommentReactions($userReactions);

    echo json_encode([
        'success' => true,
        'message' => 'Reakcja zapisana',
        'reaction' => $userReactions[$commentId] ?? null
    ]);
}

/**
 * Odpowiedz na komentarz
 */
function replyToComment($input) {
    $parentId = $input['parent_id'] ?? '';
    $videoId = $input['video_id'] ?? '';
    $text = trim($input['text'] ?? '');
    $username = $input['username'] ?? 'Anonim';

    if (empty($parentId) || empty($videoId) || empty($text)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        return;
    }

    if (strlen($text) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Odpowiedź jest za długa (max 5000 znaków)']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';
    $comments = [];

    if (file_exists($commentsFile)) {
        $comments = json_decode(file_get_contents($commentsFile), true) ?? [];
    }

    // Sprawdź czy rodzic istnieje
    $parentExists = false;
    foreach ($comments as &$comment) {
        if ($comment['id'] === $parentId) {
            $comment['replies_count']++;
            $parentExists = true;
            break;
        }
    }

    if (!$parentExists) {
        http_response_code(404);
        echo json_encode(['error' => 'Komentarz rodzic nie istnieje']);
        return;
    }

    $replyId = uniqid('reply_', true);

    $newReply = [
        'id' => $replyId,
        'parent_id' => $parentId,
        'video_id' => $videoId,
        'username' => htmlspecialchars($username),
        'text' => htmlspecialchars($text),
        'created_at' => time(),
        'edited_at' => null,
        'likes' => 0,
        'dislikes' => 0,
        'is_edited' => false
    ];

    $comments[] = $newReply;

    if (!file_put_contents($commentsFile, json_encode($comments, JSON_PRETTY_PRINT))) {
        throw new Exception('Nie udało się dodać odpowiedzi');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Odpowiedź dodana',
        'reply' => $newReply
    ]);
}

/**
 * Pobierz odpowiedzi do komentarza
 */
function getReplies($input) {
    $parentId = $input['parent_id'] ?? '';

    if (empty($parentId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak ID komentarza rodzica']);
        return;
    }

    $commentsFile = DATA_PATH . '/comments.json';
    $replies = [];

    if (file_exists($commentsFile)) {
        $allComments = json_decode(file_get_contents($commentsFile), true) ?? [];

        // Filtruj odpowiedzi
        $replies = array_filter($allComments, function($comment) use ($parentId) {
            return ($comment['parent_id'] ?? null) === $parentId;
        });

        // Sortuj po dacie (najstarsze pierwsze)
        usort($replies, function($a, $b) {
            return $a['created_at'] <=> $b['created_at'];
        });
    }

    // Pobierz reakcje użytkownika
    $userReactions = getUserCommentReactions();

    foreach ($replies as &$reply) {
        $reply['user_reaction'] = $userReactions[$reply['id']] ?? null;
        $reply['time_ago'] = timeAgo($reply['created_at']);
    }

    echo json_encode([
        'success' => true,
        'replies' => array_values($replies)
    ]);
}

/**
 * Pobierz reakcje użytkownika na komentarze
 */
function getUserCommentReactions(): array {
    $reactionsFile = DATA_PATH . '/user_comment_reactions.json';

    if (!file_exists($reactionsFile)) {
        return [];
    }

    return json_decode(file_get_contents($reactionsFile), true) ?? [];
}

/**
 * Zapisz reakcje użytkownika na komentarze
 */
function saveUserCommentReactions(array $reactions): void {
    $reactionsFile = DATA_PATH . '/user_comment_reactions.json';
    file_put_contents($reactionsFile, json_encode($reactions, JSON_PRETTY_PRINT));
}

/**
 * Zamień timestamp na czytelny czas (np. "2 godziny temu")
 */
function timeAgo(int $timestamp): string {
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'przed chwilą';
    }

    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' ' . pluralize($mins, 'minuta', 'minuty', 'minut') . ' temu';
    }

    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . pluralize($hours, 'godzina', 'godziny', 'godzin') . ' temu';
    }

    if ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' ' . pluralize($days, 'dzień', 'dni', 'dni') . ' temu';
    }

    if ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' ' . pluralize($months, 'miesiąc', 'miesiące', 'miesięcy') . ' temu';
    }

    $years = floor($diff / 31536000);
    return $years . ' ' . pluralize($years, 'rok', 'lata', 'lat') . ' temu';
}

/**
 * Odmiana liczebnika
 */
function pluralize(int $n, string $singular, string $few, string $many): string {
    if ($n == 1) {
        return $singular;
    }

    $mod10 = $n % 10;
    $mod100 = $n % 100;

    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
        return $few;
    }

    return $many;
}
