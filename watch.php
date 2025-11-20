<?php
/**
 * Strona odtwarzania wideo
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

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

// Ścieżka do filmu
$videoPath = '/videos/' . $video['relative_path'];
$thumbnailUrl = VideoHelper::getThumbnailUrl($videoId);

include INCLUDES_PATH . '/templates/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Video Column -->
    <div class="lg:col-span-2">
        <!-- Video Player -->
        <div class="bg-dark-secondary rounded-lg overflow-hidden border border-dark-border mb-6">
            <video
                id="videoPlayer"
                class="w-full"
                controls
                preload="metadata"
                poster="<?= htmlspecialchars($thumbnailUrl) ?>"
            >
                <source src="<?= htmlspecialchars($videoPath) ?>" type="video/<?= htmlspecialchars($video['codec'] ?? 'mp4') ?>">
                Twoja przeglądarka nie obsługuje odtwarzania wideo.
            </video>
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
        <div class="bg-dark-secondary rounded-lg border border-dark-border">
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

<script>
    const videoId = <?= json_encode($videoId) ?>;

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
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
