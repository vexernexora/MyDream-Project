<?php
/**
 * Strona główna - Lista filmów
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$currentPage = 'home';
$pageTitle = 'Moja Biblioteka Wideo';

// Pobierz parametry filtrowania
$search = $_GET['search'] ?? '';
$selectedTags = isset($_GET['tags']) ? explode(',', $_GET['tags']) : [];
$sortBy = $_GET['sort'] ?? 'date';
$order = $_GET['order'] ?? 'desc';

// Pobierz filmy
$videos = JsonHelper::getVideos();
$allTags = JsonHelper::getAllTags();

// Filtruj
if (!empty($search) || !empty($selectedTags)) {
    $videos = JsonHelper::searchVideos($search, !empty($selectedTags) ? $selectedTags : null);
}

// Sortuj
$videos = JsonHelper::sortVideos($videos, $sortBy, $order);

// Dodaj URL miniaturek
foreach ($videos as &$video) {
    $video['thumbnail_url'] = VideoHelper::getThumbnailUrl($video['id']);
}
unset($video);

include INCLUDES_PATH . '/templates/header.php';
?>

<!-- Search and Filters -->
<div class="mb-8">
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border">
        <!-- Search Bar -->
        <div class="mb-4">
            <div class="relative">
                <input
                    type="text"
                    id="searchInput"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Szukaj filmów..."
                    class="w-full bg-dark-bg text-dark-text border border-dark-border rounded-lg px-4 py-3 pl-12 focus:outline-none focus:ring-2 focus:ring-red-600"
                    onkeyup="handleSearch(event)"
                >
                <svg class="w-5 h-5 text-dark-textSecondary absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Tags Filter -->
            <div class="flex-1">
                <label class="block text-sm text-dark-textSecondary mb-2">Filtruj po tagach:</label>
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($allTags)): ?>
                        <?php foreach ($allTags as $tag): ?>
                            <?php $isSelected = in_array($tag, $selectedTags); ?>
                            <button
                                onclick="toggleTag('<?= htmlspecialchars($tag) ?>')"
                                class="tag-badge px-3 py-1 rounded-full text-sm <?= $isSelected ? 'bg-red-600 text-white' : 'bg-dark-tertiary text-dark-textSecondary hover:bg-dark-border' ?>"
                            >
                                <?= htmlspecialchars($tag) ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-dark-textSecondary text-sm">Brak tagów</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sort Options -->
            <div class="flex items-center space-x-4">
                <label class="text-sm text-dark-textSecondary">Sortuj:</label>
                <select
                    id="sortSelect"
                    onchange="handleSort()"
                    class="bg-dark-bg text-dark-text border border-dark-border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                >
                    <option value="date-desc" <?= $sortBy === 'date' && $order === 'desc' ? 'selected' : '' ?>>Najnowsze</option>
                    <option value="date-asc" <?= $sortBy === 'date' && $order === 'asc' ? 'selected' : '' ?>>Najstarsze</option>
                    <option value="title-asc" <?= $sortBy === 'title' && $order === 'asc' ? 'selected' : '' ?>>Tytuł A-Z</option>
                    <option value="title-desc" <?= $sortBy === 'title' && $order === 'desc' ? 'selected' : '' ?>>Tytuł Z-A</option>
                    <option value="duration-desc" <?= $sortBy === 'duration' && $order === 'desc' ? 'selected' : '' ?>>Najdłuższe</option>
                    <option value="duration-asc" <?= $sortBy === 'duration' && $order === 'asc' ? 'selected' : '' ?>>Najkrótsze</option>
                </select>
            </div>
        </div>

        <!-- Active Filters -->
        <?php if (!empty($search) || !empty($selectedTags)): ?>
            <div class="mt-4 pt-4 border-t border-dark-border">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm">
                        <span class="text-dark-textSecondary">Aktywne filtry:</span>
                        <?php if (!empty($search)): ?>
                            <span class="px-2 py-1 bg-dark-tertiary rounded">
                                Wyszukiwanie: <strong><?= htmlspecialchars($search) ?></strong>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($selectedTags)): ?>
                            <span class="px-2 py-1 bg-dark-tertiary rounded">
                                Tagi: <strong><?= count($selectedTags) ?></strong>
                            </span>
                        <?php endif; ?>
                    </div>
                    <button onclick="clearFilters()" class="text-sm text-red-600 hover:text-red-500">
                        Wyczyść filtry
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Video Grid -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">
            <?php if (!empty($search) || !empty($selectedTags)): ?>
                Wyniki wyszukiwania (<?= count($videos) ?>)
            <?php else: ?>
                Wszystkie filmy (<?= count($videos) ?>)
            <?php endif; ?>
        </h2>
    </div>

    <?php if (empty($videos)): ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <svg class="w-24 h-24 mx-auto text-dark-textSecondary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
            </svg>
            <h3 class="text-xl font-bold mb-2">Brak filmów</h3>
            <p class="text-dark-textSecondary mb-6">
                <?php if (!empty($search) || !empty($selectedTags)): ?>
                    Nie znaleziono filmów pasujących do wybranych filtrów
                <?php else: ?>
                    Dodaj pliki wideo do folderu /videos i kliknij "Odśwież bibliotekę"
                <?php endif; ?>
            </p>
            <?php if (!empty($search) || !empty($selectedTags)): ?>
                <button onclick="clearFilters()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg transition">
                    Wyczyść filtry
                </button>
            <?php else: ?>
                <button onclick="scanLibrary()" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg transition">
                    Skanuj bibliotekę
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Video Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <?php foreach ($videos as $video): ?>
                <a href="/public/watch.php?id=<?= htmlspecialchars($video['id']) ?>" class="video-card">
                    <!-- Thumbnail Container -->
                    <div class="video-thumbnail">
                        <img
                            src="<?= htmlspecialchars($video['thumbnail_url']) ?>"
                            alt="<?= htmlspecialchars($video['title']) ?>"
                            loading="lazy"
                            onerror="this.src='/public/img/no-thumbnail.jpg'"
                        >

                        <!-- Hover Overlay -->
                        <div class="video-thumbnail-overlay">
                            <div class="video-thumbnail-play">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Duration Badge -->
                        <div class="video-duration-badge">
                            <?= htmlspecialchars($video['duration_formatted']) ?>
                        </div>
                    </div>

                    <!-- Video Info -->
                    <div class="video-card-info">
                        <h3 class="video-card-title">
                            <?= htmlspecialchars($video['title']) ?>
                        </h3>

                        <div class="video-card-meta">
                            <div class="video-card-meta-item">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span><?= htmlspecialchars($video['size_formatted']) ?></span>
                            </div>
                            <?php if (isset($video['width'], $video['height'])): ?>
                            <div class="video-card-meta-item">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span><?= $video['width'] ?>x<?= $video['height'] ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tags (Compact) -->
                        <?php if (!empty($video['tags'])): ?>
                            <div class="video-tags">
                                <?php foreach (array_slice($video['tags'], 0, 2) as $tag): ?>
                                    <span class="tag-badge" onclick="event.preventDefault(); window.location.href='/public/index.php?tags=<?= urlencode($tag) ?>'">
                                        <?= htmlspecialchars($tag) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($video['tags']) > 2): ?>
                                    <span class="tag-badge">
                                        +<?= count($video['tags']) - 2 ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Store selected tags
    let selectedTags = <?= json_encode($selectedTags) ?>;

    // Update total videos in footer
    document.getElementById('totalVideos').textContent = <?= count(JsonHelper::getVideos()) ?>;
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
