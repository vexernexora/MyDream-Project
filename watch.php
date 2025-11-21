<?php
/**
 * Strona odtwarzania wideo
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Pobierz ID filmu
$videoId = $_GET['id'] ?? null;

if (!$videoId) {
    header('Location: /public/index.php');
    exit;
}

// Pobierz dane filmu
$video = JsonHelper::getVideoById($videoId);

if (!$video) {
    header('Location: /public/index.php');
    exit;
}

// Pobierz podobne filmy
$similarVideos = JsonHelper::getSimilarVideos($videoId, 6);

// Dodaj URL miniaturek
foreach ($similarVideos as &$similar) {
    $similar['thumbnail_url'] = VideoHelper::getThumbnailUrl($similar['id']);
}
unset($similar);

$currentPage = 'watch';
$pageTitle = $video['title'];

// Ścieżka do filmu (przez streaming endpoint)
$videoPath = '/public/stream.php?id=' . urlencode($videoId);
$thumbnailUrl = VideoHelper::getThumbnailUrl($videoId);

include INCLUDES_PATH . '/templates/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Video Column -->
    <div class="lg:col-span-2">
        <!-- Video Player -->
        <div id="videoPlayerContainer" class="bg-dark-secondary rounded-lg overflow-hidden border border-dark-border mb-6">
            <video
                id="videoPlayer"
                class="w-full"
                controls
                preload="metadata"
                poster="<?= htmlspecialchars($thumbnailUrl) ?>"
                data-video-id="<?= htmlspecialchars($videoId) ?>"
            >
                <source src="<?= htmlspecialchars($videoPath) ?>" type="video/mp4">
                Twoja przeglądarka nie obsługuje odtwarzania wideo.
            </video>

            <!-- Player Controls Overlay -->
            <div class="bg-dark-bg p-3 border-t border-dark-border flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <!-- Playback Speed -->
                    <div class="relative" id="speedControlContainer">
                        <button
                            id="speedControlBtn"
                            class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition flex items-center space-x-1"
                            title="Prędkość odtwarzania"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span id="speedValue">1x</span>
                        </button>
                        <div id="speedMenu" class="hidden absolute bottom-full mb-1 left-0 bg-dark-secondary border border-dark-border rounded-lg shadow-lg py-1 min-w-[100px]">
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="0.25">0.25x</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="0.5">0.5x</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="0.75">0.75x</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm font-semibold" data-speed="1">Normalna (1x)</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="1.25">1.25x</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="1.5">1.5x</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="1.75">1.75x</button>
                            <button class="speed-option w-full px-4 py-2 text-left hover:bg-dark-tertiary text-sm" data-speed="2">2x</button>
                        </div>
                    </div>

                    <!-- Theater Mode -->
                    <button
                        id="theaterModeBtn"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition flex items-center space-x-1"
                        title="Tryb kinowy (T)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                        <span>Kinowy</span>
                    </button>

                    <!-- Autoplay -->
                    <button
                        id="autoplayBtn"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition flex items-center space-x-1"
                        title="Automatyczne odtwarzanie"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Autoplay: <span id="autoplayStatus">Wyłączone</span></span>
                    </button>

                    <!-- Loop -->
                    <button
                        id="loopBtn"
                        onclick="toggleLoop()"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Zapętl wideo"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Mini Player (PiP) -->
                    <button
                        onclick="togglePiP()"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Mini player (I)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                    </button>

                    <!-- Screenshot -->
                    <button
                        onclick="captureScreenshot()"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Zrób screenshot (S)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>

                    <!-- Stats for Nerds -->
                    <button
                        onclick="toggleStats()"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Stats for Nerds (P)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </button>

                    <!-- Cinema Mode -->
                    <button
                        onclick="toggleCinemaMode()"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Tryb kina - zgaś światła (C)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </button>

                    <!-- Share with Timestamp -->
                    <button
                        onclick="shareWithTimestamp()"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Udostępnij z timestampem (U)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </button>

                    <!-- Keyboard Shortcuts Help -->
                    <button
                        id="keyboardHelpBtn"
                        class="px-3 py-1.5 bg-dark-tertiary hover:bg-dark-border rounded text-sm transition"
                        title="Skróty klawiszowe"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Video Info -->
        <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border mb-6">
            <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($video['title']) ?></h1>

            <div class="flex flex-wrap items-center gap-4 text-sm text-dark-textSecondary mb-4">
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($video['duration_formatted']) ?></span>
                </span>
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span><?= htmlspecialchars($video['size_formatted']) ?></span>
                </span>
                <?php if (isset($video['width'], $video['height'])): ?>
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span><?= $video['width'] ?>x<?= $video['height'] ?></span>
                    </span>
                <?php endif; ?>
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span><?= htmlspecialchars($video['added_at_formatted']) ?></span>
                </span>
            </div>

            <!-- Tags -->
            <?php if (!empty($video['tags'])): ?>
                <div class="mb-4">
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($video['tags'] as $tag): ?>
                            <a
                                href="/public/index.php?tags=<?= urlencode($tag) ?>"
                                class="tag-badge px-3 py-1 bg-dark-tertiary text-dark-textSecondary hover:bg-red-600 hover:text-white rounded-full text-sm transition"
                            >
                                <?= htmlspecialchars($tag) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">Opis:</h3>
                <p class="text-dark-textSecondary">
                    <?= nl2br(htmlspecialchars($video['description'])) ?>
                </p>
            </div>

            <!-- Engagement Section - YouTube Style -->
            <div class="mb-6 pb-6 border-b border-dark-border">
                <div class="engagement-buttons">
                    <!-- Like/Dislike Container -->
                    <div class="like-dislike-container">
                        <button id="videoLikeBtn" class="like-btn" title="Lubię to">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                            <span id="likeCount">0</span>
                        </button>
                        <div class="like-dislike-separator"></div>
                        <button id="videoDislikeBtn" class="dislike-btn" title="Nie lubię tego">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(180deg);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                            <span id="dislikeCount"></span>
                        </button>
                    </div>

                    <!-- Favorite Button -->
                    <button
                        id="favoriteBtn"
                        onclick="toggleFavorite('<?= htmlspecialchars($videoId) ?>', this)"
                        class="btn btn-secondary"
                        style="padding: 10px 16px;"
                        title="Dodaj do ulubionych"
                    >
                        <svg id="favoriteIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span id="favoriteText">Ulubione</span>
                    </button>

                    <!-- Watch Later Button -->
                    <button
                        id="watchLaterBtn"
                        onclick="toggleWatchLater('<?= htmlspecialchars($videoId) ?>', this)"
                        class="btn btn-secondary"
                        style="padding: 10px 16px;"
                        title="Dodaj do obejrzenia później"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="watchLaterText">Później</span>
                    </button>

                    <!-- Share Button -->
                    <button
                        onclick="shareVideo()"
                        class="btn btn-secondary"
                        style="padding: 10px 16px;"
                        title="Udostępnij"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span>Udostępnij</span>
                    </button>

                    <!-- Add to Playlist Button -->
                    <button
                        onclick="showPlaylistModal()"
                        class="btn btn-secondary"
                        style="padding: 10px 16px;"
                        title="Zapisz"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Zapisz</span>
                    </button>
                </div>

                <!-- Like Ratio Bar -->
                <div id="likeRatioBar" class="progress-bar" style="margin-top: 12px;">
                    <div class="like-ratio-fill progress-bar-fill"></div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3">
                <button
                    onclick="openEditModal()"
                    class="px-4 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edytuj</span>
                </button>
                <button
                    onclick="regenerateThumbnail()"
                    class="px-4 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Regeneruj miniaturę</span>
                </button>
                <button
                    onclick="deleteVideo()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition flex items-center space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Usuń z biblioteki</span>
                </button>
            </div>

            <!-- AI Generated Badge -->
            <?php if ($video['ai_generated'] ?? false): ?>
                <div class="mt-4 pt-4 border-t border-dark-border">
                    <span class="inline-flex items-center space-x-2 text-xs text-dark-textSecondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span>Metadata wygenerowana przez AI</span>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Technical Info (Collapsible) -->
        <div class="bg-dark-secondary rounded-lg border border-dark-border mb-6">
            <button
                onclick="toggleTechnicalInfo()"
                class="w-full px-6 py-4 flex items-center justify-between hover:bg-dark-tertiary transition"
            >
                <span class="font-semibold">Informacje techniczne</span>
                <svg id="technicalInfoIcon" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="technicalInfo" class="hidden px-6 pb-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-dark-textSecondary">Nazwa pliku:</span>
                        <p class="font-mono text-xs break-all"><?= htmlspecialchars($video['filename']) ?></p>
                    </div>
                    <div>
                        <span class="text-dark-textSecondary">Kategoria:</span>
                        <p><?= htmlspecialchars($video['category'] ?? 'Brak') ?></p>
                    </div>
                    <?php if ($video['codec']): ?>
                        <div>
                            <span class="text-dark-textSecondary">Kodek:</span>
                            <p><?= htmlspecialchars($video['codec']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($video['fps']): ?>
                        <div>
                            <span class="text-dark-textSecondary">FPS:</span>
                            <p><?= htmlspecialchars($video['fps']) ?></p>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="text-dark-textSecondary">Czas trwania (sekundy):</span>
                        <p><?= htmlspecialchars($video['duration_seconds']) ?></p>
                    </div>
                    <div>
                        <span class="text-dark-textSecondary">Rozmiar (bajty):</span>
                        <p><?= number_format($video['size']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <div class="comments-header">
                <h2 id="commentsCount" class="comments-count">0 komentarzy</h2>
                <select id="commentsSort" class="comments-sort">
                    <option value="newest">Najnowsze</option>
                    <option value="top">Najpopularniejsze</option>
                    <option value="oldest">Najstarsze</option>
                </select>
            </div>

            <!-- Comment Form -->
            <form id="commentForm" class="comment-form">
                <textarea
                    id="commentInput"
                    class="comment-input"
                    placeholder="Dodaj komentarz..."
                    rows="3"
                ></textarea>
                <div class="comment-form-actions">
                    <input
                        type="text"
                        id="commentUsername"
                        placeholder="Twoje imię (opcjonalne)"
                        style="flex: 1; padding: 8px 12px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-primary); font-size: 13px;"
                    >
                    <button type="submit" id="commentSubmitBtn" class="btn btn-primary" style="padding: 8px 20px; font-size: 14px;">
                        Komentuj
                    </button>
                </div>
            </form>

            <!-- Comments Container -->
            <div id="commentsContainer" class="mt-6">
                <!-- Comments will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-dark-secondary rounded-lg p-4 border border-dark-border sticky top-20">
            <h2 class="text-lg font-bold mb-4">Podobne filmy</h2>

            <?php if (empty($similarVideos)): ?>
                <p class="text-dark-textSecondary text-sm">Brak podobnych filmów</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($similarVideos as $similar): ?>
                        <a href="/public/watch.php?id=<?= htmlspecialchars($similar['id']) ?>" class="block group">
                            <div class="flex space-x-3">
                                <!-- Thumbnail -->
                                <div class="flex-shrink-0 w-40 relative">
                                    <div class="video-thumbnail rounded overflow-hidden">
                                        <img
                                            src="<?= htmlspecialchars($similar['thumbnail_url']) ?>"
                                            alt="<?= htmlspecialchars($similar['title']) ?>"
                                            class="w-full h-full object-cover group-hover:opacity-80 transition"
                                            onerror="this.src='/public/img/no-thumbnail.jpg'"
                                        >
                                        <span class="absolute bottom-1 right-1 bg-black bg-opacity-80 text-white text-xs px-1 rounded">
                                            <?= htmlspecialchars($similar['duration_formatted']) ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-sm mb-1 line-clamp-2 group-hover:text-red-600 transition">
                                        <?= htmlspecialchars($similar['title']) ?>
                                    </h3>
                                    <p class="text-dark-textSecondary text-xs line-clamp-2">
                                        <?= htmlspecialchars($similar['description']) ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts Help Modal -->
<div id="keyboardHelpModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-dark-secondary rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">Skróty klawiszowe</h2>
                <button onclick="closeKeyboardHelp()" class="text-dark-textSecondary hover:text-dark-text">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="space-y-3">
                    <h3 class="font-semibold text-base mb-2">Odtwarzanie</h3>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Odtwórz/Pauza</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">K</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Odtwórz/Pauza</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">Spacja</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Przewiń do tyłu 10s</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">J</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Przewiń do przodu 10s</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">L</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Przewiń -5s</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">←</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Przewiń +5s</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">→</kbd>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="font-semibold text-base mb-2">Prędkość</h3>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Zwiększ prędkość</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">></kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Zmniejsz prędkość</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded"><</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Resetuj prędkość</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">R</kbd>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="font-semibold text-base mb-2">Głośność</h3>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Wycisz/Odcisz</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">M</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Zwiększ głośność</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">↑</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Zmniejsz głośność</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">↓</kbd>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="font-semibold text-base mb-2">Widok</h3>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Pełny ekran</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">F</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Tryb kinowy</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">T</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Przejdź do % filmu (0-9)</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">0-9</kbd>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="font-semibold text-base mb-2">Zaawansowane</h3>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Mini player (PiP)</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">I</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Screenshot</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">S</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Stats for Nerds</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">P</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Cinema Mode (Zgaś światła)</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">C</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-textSecondary">Udostępnij z timestampem</span>
                        <kbd class="px-2 py-1 bg-dark-tertiary rounded">U</kbd>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-dark-border">
                <p class="text-sm text-dark-textSecondary text-center">
                    Naciśnij <kbd class="px-2 py-1 bg-dark-tertiary rounded mx-1">?</kbd> aby pokazać/ukryć tę pomoc
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-dark-secondary rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">Edytuj film</h2>
                <button onclick="closeEditModal()" class="text-dark-textSecondary hover:text-dark-text">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="editForm" onsubmit="saveVideoEdit(event)">
                <!-- Title -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Tytuł</label>
                    <input
                        type="text"
                        id="editTitle"
                        value="<?= htmlspecialchars($video['title']) ?>"
                        class="w-full bg-dark-bg text-dark-text border border-dark-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                        required
                    >
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Opis</label>
                    <textarea
                        id="editDescription"
                        rows="4"
                        class="w-full bg-dark-bg text-dark-text border border-dark-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                    ><?= htmlspecialchars($video['description']) ?></textarea>
                </div>

                <!-- Tags -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Tagi (oddzielone przecinkami)</label>
                    <input
                        type="text"
                        id="editTags"
                        value="<?= htmlspecialchars(implode(', ', $video['tags'])) ?>"
                        class="w-full bg-dark-bg text-dark-text border border-dark-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                    >
                </div>

                <!-- Category -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2">Kategoria</label>
                    <input
                        type="text"
                        id="editCategory"
                        value="<?= htmlspecialchars($video['category'] ?? '') ?>"
                        class="w-full bg-dark-bg text-dark-text border border-dark-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                    >
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        onclick="closeEditModal()"
                        class="px-6 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition"
                    >
                        Anuluj
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition"
                    >
                        Zapisz zmiany
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/public/js/useractions.js"></script>
<script src="/public/js/player-controls.js"></script>
<script src="/public/js/comments.js"></script>
<script src="/public/js/likes.js"></script>
<script>
    const videoId = <?= json_encode($videoId) ?>;
    const similarVideos = <?= json_encode($similarVideos) ?>;

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', async () => {
        const videoPlayer = document.getElementById('videoPlayer');

        // Load user actions state (favorite, watch later)
        await loadUserActionStates();

        // Initialize like/dislike system
        await initLikeSystem(videoId);

        // Initialize comments system
        await initComments(videoId);

        // Create rating widget
        createRatingWidget('ratingWidget', videoId);

        // Start progress tracking
        startProgressTracking(videoId, videoPlayer);

        // Check for existing progress and prompt to continue
        checkAndPromptContinueWatching(videoId, videoPlayer);

        // Initialize player controls
        initializePlayerControls(videoPlayer);

        // Check for timestamp parameter (t=123) in URL
        const urlParams = new URLSearchParams(window.location.search);
        const timestamp = urlParams.get('t');
        if (timestamp) {
            const time = parseInt(timestamp);
            if (!isNaN(time) && time > 0) {
                videoPlayer.addEventListener('loadedmetadata', () => {
                    videoPlayer.currentTime = time;
                    showToast(`Przeskoczono do ${formatVideoTime(time)}`, 'info');
                }, { once: true });
            }
        }

        // Add to history when video starts playing
        videoPlayer.addEventListener('play', () => {
            addToHistory(videoId);
        }, { once: true });

        // Setup keyboard shortcuts help
        setupKeyboardShortcutsHelp();
    });

    // Load favorite and watch later states
    async function loadUserActionStates() {
        try {
            // Check favorite status
            const favResponse = await fetch('/public/api/favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check', video_id: videoId })
            });
            const favData = await favResponse.json();
            if (favData.success && favData.is_favorite) {
                updateFavoriteButton(true);
            }

            // Check watch later status
            const wlResponse = await fetch('/public/api/watch-later.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check', video_id: videoId })
            });
            const wlData = await wlResponse.json();
            if (wlData.success && wlData.is_in_watch_later) {
                updateWatchLaterButton(true);
            }
        } catch (error) {
            console.error('Error loading user action states:', error);
        }
    }

    // Update favorite button appearance
    function updateFavoriteButton(isFavorite) {
        const btn = document.getElementById('favoriteBtn');
        const icon = document.getElementById('favoriteIcon');
        const text = document.getElementById('favoriteText');

        if (isFavorite) {
            btn.classList.add('bg-red-600', 'hover:bg-red-700');
            btn.classList.remove('bg-dark-tertiary', 'hover:bg-dark-border');
            icon.setAttribute('fill', 'currentColor');
            text.textContent = 'Ulubione';
        } else {
            btn.classList.remove('bg-red-600', 'hover:bg-red-700');
            btn.classList.add('bg-dark-tertiary', 'hover:bg-dark-border');
            icon.setAttribute('fill', 'none');
            text.textContent = 'Dodaj do ulubionych';
        }
    }

    // Update watch later button appearance
    function updateWatchLaterButton(isInList) {
        const btn = document.getElementById('watchLaterBtn');
        const text = document.getElementById('watchLaterText');

        if (isInList) {
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.remove('bg-dark-tertiary', 'hover:bg-dark-border');
            text.textContent = 'Na liście';
        } else {
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.add('bg-dark-tertiary', 'hover:bg-dark-border');
            text.textContent = 'Do obejrzenia';
        }
    }

    // Initialize player controls
    function initializePlayerControls(videoPlayer) {
        if (!videoPlayer) return;

        // Setup speed control
        const speedControlBtn = document.getElementById('speedControlBtn');
        const speedMenu = document.getElementById('speedMenu');
        const speedOptions = document.querySelectorAll('.speed-option');

        if (speedControlBtn && speedMenu) {
            speedControlBtn.addEventListener('click', () => {
                speedMenu.classList.toggle('hidden');
            });

            speedOptions.forEach(option => {
                option.addEventListener('click', () => {
                    const speed = parseFloat(option.dataset.speed);
                    videoPlayer.playbackRate = speed;
                    document.getElementById('speedValue').textContent = speed + 'x';
                    speedMenu.classList.add('hidden');
                });
            });

            // Close speed menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!speedControlBtn.contains(e.target) && !speedMenu.contains(e.target)) {
                    speedMenu.classList.add('hidden');
                }
            });
        }

        // Setup theater mode button
        const theaterModeBtn = document.getElementById('theaterModeBtn');
        if (theaterModeBtn) {
            theaterModeBtn.addEventListener('click', () => {
                toggleTheaterMode();
            });
        }

        // Setup autoplay button
        const autoplayBtn = document.getElementById('autoplayBtn');
        if (autoplayBtn) {
            autoplayBtn.addEventListener('click', () => {
                toggleAutoplay();
            });
        }
    }

    // Format time in seconds to MM:SS or HH:MM:SS
    function formatVideoTime(seconds) {
        if (isNaN(seconds)) return '00:00';

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);

        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
        return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    // Setup keyboard shortcuts help modal
    function setupKeyboardShortcutsHelp() {
        const helpBtn = document.getElementById('keyboardHelpBtn');
        helpBtn.addEventListener('click', () => {
            document.getElementById('keyboardHelpModal').classList.remove('hidden');
        });

        // Also allow '?' key to toggle help
        document.addEventListener('keydown', (e) => {
            if (e.key === '?' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                const modal = document.getElementById('keyboardHelpModal');
                modal.classList.toggle('hidden');
            }
        });
    }

    function closeKeyboardHelp() {
        document.getElementById('keyboardHelpModal').classList.add('hidden');
    }

    function toggleTechnicalInfo() {
        const info = document.getElementById('technicalInfo');
        const icon = document.getElementById('technicalInfoIcon');
        info.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    function openEditModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function saveVideoEdit(event) {
        event.preventDefault();

        const title = document.getElementById('editTitle').value;
        const description = document.getElementById('editDescription').value;
        const tags = document.getElementById('editTags').value.split(',').map(t => t.trim()).filter(t => t);
        const category = document.getElementById('editCategory').value;

        updateVideo(videoId, { title, description, tags, category });
    }

    function regenerateThumbnail() {
        if (!confirm('Czy na pewno chcesz wygenerować nową miniaturę?')) {
            return;
        }
        regenerateVideoThumbnail(videoId);
    }

    function deleteVideo() {
        if (!confirm('Czy na pewno chcesz usunąć ten film z biblioteki? (plik wideo nie zostanie usunięty)')) {
            return;
        }
        deleteVideoFromLibrary(videoId);
    }

    // Share video function
    function shareVideo() {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({
                title: <?= json_encode($video['title']) ?>,
                text: 'Sprawdź ten film!',
                url: url
            }).catch(err => console.error('Share failed:', err));
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                showToast('Link skopiowany do schowka!', 'success');
            }).catch(err => {
                prompt('Skopiuj link:', url);
            });
        }
    }

    // Show playlist modal (placeholder)
    function showPlaylistModal() {
        showToast('Funkcja playlist w przygotowaniu!', 'info');
    }
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
