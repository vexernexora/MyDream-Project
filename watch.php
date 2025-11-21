<?php
/**
 * Strona odtwarzania wideo - Mobile-First YouTube Style
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
$similarVideos = JsonHelper::getSimilarVideos($videoId, 12);

// Dodaj URL miniaturek
foreach ($similarVideos as &$similar) {
    $similar['thumbnail_url'] = VideoHelper::getThumbnailUrl($similar['id']);
}
unset($similar);

$currentPage = 'watch';
$pageTitle = $video['title'];

// Ścieżka do filmu
$videoPath = '/public/stream.php?id=' . urlencode($videoId);
$thumbnailUrl = VideoHelper::getThumbnailUrl($videoId);

include INCLUDES_PATH . '/templates/header.php';
?>

<style>
/* Mobile-First Player Container */
.player-wrapper {
    position: relative;
    width: 100%;
    background: #000;
    margin: 0;
    padding: 0;
}

.player-wrapper video {
    width: 100%;
    height: auto;
    max-height: 70vh;
    display: block;
    object-fit: contain;
}

/* Video Info Section - Clean YouTube Style */
.video-info-section {
    background: var(--bg-secondary);
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
}

.video-title {
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
    margin-bottom: 12px;
    color: var(--text-primary);
}

.video-meta-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 16px;
}

.video-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Action Buttons - Simple & Clean */
.action-buttons-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    color: var(--text-primary);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: var(--bg-hover);
}

.action-btn svg {
    width: 18px;
    height: 18px;
}

.action-btn-danger {
    background: rgba(255, 0, 0, 0.1);
    border-color: rgba(255, 0, 0, 0.3);
    color: #ff4444;
}

.action-btn-danger:hover {
    background: rgba(255, 0, 0, 0.2);
}

/* Description Collapsible */
.description-section {
    background: var(--bg-tertiary);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 16px;
}

.description-header {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
}

.description-content {
    margin-top: 12px;
    font-size: 13px;
    line-height: 1.6;
    color: var(--text-secondary);
}

.description-content.collapsed {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Tags - Compact */
.tags-compact {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
}

.tag-compact {
    padding: 4px 12px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    font-size: 12px;
    color: var(--text-secondary);
}

/* Recommended Videos - YouTube Grid */
.recommended-section {
    padding: 16px;
}

.recommended-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--text-primary);
}

.recommended-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

@media (min-width: 640px) {
    .recommended-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .recommended-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1280px) {
    .recommended-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Compact Video Card */
.compact-video-card {
    background: var(--bg-secondary);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: all 0.2s;
    cursor: pointer;
}

.compact-video-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 0, 0, 0.3);
}

.compact-thumbnail {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--bg-tertiary);
}

.compact-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.compact-video-card:hover .compact-thumbnail img {
    transform: scale(1.1);
}

.compact-duration {
    position: absolute;
    bottom: 6px;
    right: 6px;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.compact-info {
    padding: 12px;
}

.compact-title {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: var(--text-primary);
}

.compact-meta {
    font-size: 12px;
    color: var(--text-secondary);
}

/* Gradient Overlay Effect */
.gradient-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 100px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}

.compact-video-card:hover .gradient-overlay {
    opacity: 1;
}

/* Loading Animation */
@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.skeleton {
    background: linear-gradient(
        90deg,
        var(--bg-tertiary) 0%,
        var(--bg-hover) 50%,
        var(--bg-tertiary) 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

/* Mobile Optimizations */
@media (max-width: 640px) {
    .video-title {
        font-size: 16px;
    }

    .video-info-section {
        padding: 12px;
    }

    .action-btn {
        font-size: 12px;
        padding: 6px 12px;
    }

    .player-wrapper video {
        max-height: 50vh;
    }
}
</style>

<!-- Player Container -->
<div class="player-wrapper">
    <video
        id="videoPlayer"
        controls
        preload="metadata"
        poster="<?= htmlspecialchars($thumbnailUrl) ?>"
        data-video-id="<?= htmlspecialchars($videoId) ?>"
        playsinline
    >
        <source src="<?= htmlspecialchars($videoPath) ?>" type="video/mp4">
        Twoja przeglądarka nie obsługuje odtwarzania wideo.
    </video>
</div>

<!-- Video Info Section -->
<div class="video-info-section">
    <!-- Title -->
    <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>

    <!-- Meta Row -->
    <div class="video-meta-row">
        <div class="video-meta-item">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($video['duration_formatted']) ?></span>
        </div>
        <div class="video-meta-item">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <span><?= htmlspecialchars($video['size_formatted']) ?></span>
        </div>
        <?php if (isset($video['width'], $video['height'])): ?>
        <div class="video-meta-item">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <span><?= $video['width'] ?>x<?= $video['height'] ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons-row">
        <button class="action-btn" onclick="window.history.back()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Wróć</span>
        </button>

        <button class="action-btn action-btn-danger" onclick="deleteVideo()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <span>Usuń</span>
        </button>
    </div>
</div>

<!-- Description Section (Collapsible) -->
<div class="video-info-section">
    <div class="description-section">
        <div class="description-header" onclick="toggleDescription()">
            <span>Opis</span>
            <svg id="descIcon" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div id="descContent" class="description-content collapsed">
            <?= nl2br(htmlspecialchars($video['description'])) ?>

            <?php if (!empty($video['tags'])): ?>
            <div class="tags-compact">
                <?php foreach ($video['tags'] as $tag): ?>
                    <a href="/public/index.php?tags=<?= urlencode($tag) ?>" class="tag-compact">
                        #<?= htmlspecialchars($tag) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recommended Videos -->
<div class="recommended-section">
    <h2 class="recommended-title">Polecane dla Ciebie</h2>

    <?php if (empty($similarVideos)): ?>
        <p style="color: var(--text-secondary); font-size: 14px;">Brak polecanych filmów</p>
    <?php else: ?>
        <div class="recommended-grid">
            <?php foreach ($similarVideos as $similar): ?>
                <a href="/public/watch.php?id=<?= htmlspecialchars($similar['id']) ?>" class="compact-video-card">
                    <div class="compact-thumbnail">
                        <img
                            src="<?= htmlspecialchars($similar['thumbnail_url']) ?>"
                            alt="<?= htmlspecialchars($similar['title']) ?>"
                            loading="lazy"
                            onerror="this.src='/public/img/no-thumbnail.jpg'"
                        >
                        <div class="gradient-overlay"></div>
                        <div class="compact-duration"><?= htmlspecialchars($similar['duration_formatted']) ?></div>
                    </div>
                    <div class="compact-info">
                        <div class="compact-title"><?= htmlspecialchars($similar['title']) ?></div>
                        <div class="compact-meta"><?= htmlspecialchars($similar['size_formatted']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/public/js/useractions.js"></script>
<script src="/public/js/player-controls.js"></script>
<script>
    const videoId = <?= json_encode($videoId) ?>;

    // Toggle description
    function toggleDescription() {
        const content = document.getElementById('descContent');
        const icon = document.getElementById('descIcon');

        if (content.classList.contains('collapsed')) {
            content.classList.remove('collapsed');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('collapsed');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    // Delete video
    function deleteVideo() {
        if (!confirm('Czy na pewno chcesz usunąć ten film z biblioteki?\n\nPlik wideo NIE zostanie usunięty - tylko wpis z biblioteki.')) {
            return;
        }

        showLoading('Usuwanie', 'Proszę czekać...');

        deleteVideoFromLibrary(videoId);
    }

    // Initialize player
    document.addEventListener('DOMContentLoaded', () => {
        const videoPlayer = document.getElementById('videoPlayer');

        if (videoPlayer) {
            // Auto-adjust player height based on video aspect ratio
            videoPlayer.addEventListener('loadedmetadata', () => {
                const aspectRatio = videoPlayer.videoWidth / videoPlayer.videoHeight;

                // Set max-height based on aspect ratio
                if (aspectRatio > 1.9) {
                    // Ultra-wide video
                    videoPlayer.style.maxHeight = '60vh';
                } else if (aspectRatio < 1) {
                    // Portrait video
                    videoPlayer.style.maxHeight = '80vh';
                } else {
                    // Standard video
                    videoPlayer.style.maxHeight = '70vh';
                }

                console.log('Video loaded:', videoPlayer.videoWidth + 'x' + videoPlayer.videoHeight);
            });

            // Start progress tracking
            startProgressTracking(videoId, videoPlayer);

            // Check for existing progress
            checkAndPromptContinueWatching(videoId, videoPlayer);

            // Add to history when video starts
            videoPlayer.addEventListener('play', () => {
                addToHistory(videoId);
            }, { once: true });

            // Error handling
            videoPlayer.addEventListener('error', () => {
                showToast('Błąd podczas odtwarzania wideo', 'error');
            });
        }
    });
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
