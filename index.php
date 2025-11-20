<?php
/**
 * Strona główna - Lista filmów w stylu YouTube
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$currentPage = 'home';
$pageTitle = 'Strona główna';

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

<!-- Mobile: Search Bar - zawsze widoczny -->
<div class="sticky top-16 z-40 bg-dark-bg border-b border-dark-border lg:hidden px-4 py-3">
    <div class="relative">
        <input
            type="text"
            id="mobileSearchInput"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Szukaj..."
            class="w-full bg-dark-secondary text-dark-text border border-dark-border rounded-full px-4 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-red-600"
            onkeyup="handleSearch(event)"
        >
        <svg class="w-5 h-5 text-dark-textSecondary absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>
</div>

<!-- Filters Bar - mobilnie zwijane -->
<div class="bg-dark-secondary border-b border-dark-border sticky top-[136px] lg:top-16 z-30">
    <div class="max-w-screen-2xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <!-- Sort Button -->
            <div class="flex items-center gap-2">
                <button
                    onclick="toggleFilters()"
                    class="lg:hidden px-3 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span class="text-sm">Filtry</span>
                </button>

                <select
                    id="sortSelect"
                    onchange="handleSort()"
                    class="bg-dark-tertiary text-dark-text border-0 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600"
                >
                    <option value="date-desc" <?= $sortBy === 'date' && $order === 'desc' ? 'selected' : '' ?>>Najnowsze</option>
                    <option value="date-asc" <?= $sortBy === 'date' && $order === 'asc' ? 'selected' : '' ?>>Najstarsze</option>
                    <option value="title-asc" <?= $sortBy === 'title' && $order === 'asc' ? 'selected' : '' ?>>A-Z</option>
                    <option value="title-desc" <?= $sortBy === 'title' && $order === 'desc' ? 'selected' : '' ?>>Z-A</option>
                    <option value="duration-desc" <?= $sortBy === 'duration' && $order === 'desc' ? 'selected' : '' ?>>Najdłuższe</option>
                    <option value="duration-asc" <?= $sortBy === 'duration' && $order === 'asc' ? 'selected' : '' ?>>Najkrótsze</option>
                </select>
            </div>

            <!-- Desktop: Chips Filter -->
            <div class="hidden lg:flex flex-wrap gap-2 flex-1">
                <?php if (!empty($allTags)): ?>
                    <?php foreach (array_slice($allTags, 0, 8) as $tag): ?>
                        <?php $isSelected = in_array($tag, $selectedTags); ?>
                        <button
                            onclick="toggleTag('<?= htmlspecialchars($tag) ?>')"
                            class="tag-badge px-3 py-1.5 rounded-full text-sm font-medium transition-all <?= $isSelected ? 'bg-white text-black' : 'bg-dark-tertiary text-dark-text hover:bg-dark-border' ?>"
                        >
                            <?= htmlspecialchars($tag) ?>
                        </button>
                    <?php endforeach; ?>
                    <?php if (count($allTags) > 8): ?>
                        <button
                            onclick="showAllTags()"
                            class="px-3 py-1.5 rounded-full text-sm bg-dark-tertiary hover:bg-dark-border transition"
                        >
                            +<?= count($allTags) - 8 ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Active Filters Badge -->
            <?php if (!empty($search) || !empty($selectedTags)): ?>
                <button
                    onclick="clearFilters()"
                    class="text-sm text-red-600 hover:text-red-500 flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>Wyczyść</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Mobile Filters Panel (zwijane) -->
        <div id="filtersPanel" class="hidden lg:hidden mt-3 pt-3 border-t border-dark-border">
            <div class="flex flex-wrap gap-2">
                <?php if (!empty($allTags)): ?>
                    <?php foreach ($allTags as $tag): ?>
                        <?php $isSelected = in_array($tag, $selectedTags); ?>
                        <button
                            onclick="toggleTag('<?= htmlspecialchars($tag) ?>')"
                            class="tag-badge px-3 py-1.5 rounded-full text-sm <?= $isSelected ? 'bg-white text-black' : 'bg-dark-tertiary hover:bg-dark-border' ?>"
                        >
                            <?= htmlspecialchars($tag) ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Sections (only when not filtering) -->
<?php if (empty($search) && empty($selectedTags)): ?>
    <div class="max-w-screen-2xl mx-auto px-2 sm:px-4 lg:px-6 py-4 lg:py-6">
        <!-- Continue Watching -->
        <div id="continueWatchingSection" class="mb-8" style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl lg:text-2xl font-bold">Kontynuuj oglądanie</h2>
            </div>
            <div id="continueWatchingGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 lg:gap-4">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Watch Later -->
        <div id="watchLaterSection" class="mb-8" style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl lg:text-2xl font-bold">Do obejrzenia</h2>
                <a href="/collections.php?view=watch-later" class="text-sm text-red-600 hover:text-red-500">Zobacz wszystkie</a>
            </div>
            <div id="watchLaterGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 lg:gap-4">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Favorites -->
        <div id="favoritesSection" class="mb-8" style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl lg:text-2xl font-bold">Ulubione</h2>
                <a href="/collections.php?view=favorites" class="text-sm text-red-600 hover:text-red-500">Zobacz wszystkie</a>
            </div>
            <div id="favoritesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 lg:gap-4">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Top Rated -->
        <div id="topRatedSection" class="mb-8" style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl lg:text-2xl font-bold">Najwyżej ocenione</h2>
            </div>
            <div id="topRatedGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 lg:gap-4">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Video Grid -->
<div class="max-w-screen-2xl mx-auto px-2 sm:px-4 lg:px-6 py-4 lg:py-6">
    <?php if (empty($videos)): ?>
        <!-- Empty State -->
        <div class="text-center py-12 lg:py-20">
            <div class="w-24 h-24 lg:w-32 lg:h-32 mx-auto mb-4 rounded-full bg-dark-secondary flex items-center justify-center">
                <svg class="w-12 h-12 lg:w-16 lg:h-16 text-dark-textSecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-xl lg:text-2xl font-bold mb-2">
                <?php if (!empty($search) || !empty($selectedTags)): ?>
                    Brak wyników
                <?php else: ?>
                    Brak filmów
                <?php endif; ?>
            </h3>
            <p class="text-dark-textSecondary mb-6 text-sm lg:text-base px-4">
                <?php if (!empty($search) || !empty($selectedTags)): ?>
                    Nie znaleziono filmów pasujących do wybranych filtrów
                <?php else: ?>
                    Dodaj pliki wideo do folderu /videos i kliknij "Odśwież"
                <?php endif; ?>
            </p>
            <?php if (!empty($search) || !empty($selectedTags)): ?>
                <button onclick="clearFilters()" class="px-6 py-3 bg-white text-black hover:bg-gray-200 rounded-full transition font-medium">
                    Wyczyść filtry
                </button>
            <?php else: ?>
                <button onclick="scanLibrary()" class="px-6 py-3 bg-white text-black hover:bg-gray-200 rounded-full transition font-medium">
                    Skanuj bibliotekę
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Video Cards Grid - YouTube style -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 lg:gap-4">
            <?php foreach ($videos as $video): ?>
                <div class="group cursor-pointer video-card" data-video-id="<?= htmlspecialchars($video['id']) ?>">
                    <a href="/watch.php?id=<?= htmlspecialchars($video['id']) ?>" class="block">
                        <!-- Thumbnail -->
                        <div class="relative rounded-xl overflow-hidden mb-3 bg-dark-secondary">
                            <div class="aspect-video w-full">
                                <img
                                    src="<?= htmlspecialchars($video['thumbnail_url']) ?>"
                                    alt="<?= htmlspecialchars($video['title']) ?>"
                                    class="w-full h-full object-cover group-hover:opacity-90 transition-opacity"
                                    onerror="this.src='/public/img/no-thumbnail.jpg'"
                                    loading="lazy"
                                >
                            </div>
                            <!-- Duration Badge -->
                            <span class="absolute bottom-2 right-2 bg-black bg-opacity-90 text-white text-xs font-semibold px-2 py-0.5 rounded">
                                <?= htmlspecialchars($video['duration_formatted']) ?>
                            </span>

                            <!-- Delete Button -->
                            <button
                                onclick="event.preventDefault(); event.stopPropagation(); confirmDeleteVideo('<?= htmlspecialchars($video['id']) ?>', '<?= htmlspecialchars(addslashes($video['title'])) ?>')"
                                class="absolute top-2 right-2 w-8 h-8 bg-red-600 hover:bg-red-700 rounded-full opacity-0 group-hover:opacity-100 transition flex items-center justify-center shadow-lg"
                                title="Usuń film"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>

                            <!-- Progress Bar (added by JS) -->
                            <div class="video-progress-bar hidden absolute bottom-0 left-0 right-0 h-1 bg-gray-700 bg-opacity-50">
                                <div class="h-full bg-red-600" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Video Info -->
                        <div class="flex gap-3">
                            <!-- Avatar placeholder (opcjonalnie) -->
                            <div class="hidden sm:block flex-shrink-0 w-9 h-9 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
                                </svg>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-sm lg:text-base mb-1 line-clamp-2 group-hover:text-white transition leading-snug">
                                    <?= htmlspecialchars($video['title']) ?>
                                </h3>

                                <!-- Meta -->
                                <div class="text-dark-textSecondary text-xs lg:text-sm space-y-0.5">
                                    <?php if (!empty($video['category'])): ?>
                                        <p class="truncate"><?= htmlspecialchars($video['category']) ?></p>
                                    <?php endif; ?>
                                    <p>
                                        <?= htmlspecialchars($video['size_formatted']) ?>
                                        <?php if (isset($video['width'], $video['height'])): ?>
                                            • <?= $video['width'] ?>x<?= $video['height'] ?>
                                        <?php endif; ?>
                                    </p>
                                    <!-- Rating (added by JS) -->
                                    <p class="video-rating-display text-yellow-500" style="display: none;"></p>
                                    <!-- Progress (added by JS) -->
                                    <p class="video-progress-text text-red-500" style="display: none;"></p>
                                </div>

                                <!-- Tags (tylko desktop) -->
                                <?php if (!empty($video['tags'])): ?>
                                    <div class="hidden lg:flex flex-wrap gap-1 mt-2">
                                        <?php foreach (array_slice($video['tags'], 0, 2) as $tag): ?>
                                            <span class="text-xs px-2 py-0.5 bg-dark-tertiary rounded">
                                                <?= htmlspecialchars($tag) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Menu Button (opcjonalnie) -->
                            <button class="hidden sm:block opacity-0 group-hover:opacity-100 flex-shrink-0 w-8 h-8 hover:bg-dark-tertiary rounded-full transition" onclick="event.preventDefault(); event.stopPropagation();">
                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Results Count -->
        <div class="text-center text-dark-textSecondary text-sm mt-8 pb-4">
            Wyświetlono <?= count($videos) ?> <?= count($videos) === 1 ? 'film' : 'filmów' ?>
        </div>
    <?php endif; ?>
</div>

<!-- All Tags Modal -->
<div id="tagsModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-dark-secondary rounded-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <div class="p-6 border-b border-dark-border flex items-center justify-between">
            <h2 class="text-xl lg:text-2xl font-bold">Wszystkie tagi</h2>
            <button onclick="hideTagsModal()" class="w-10 h-10 hover:bg-dark-tertiary rounded-full transition flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(80vh-80px)]">
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allTags as $tag): ?>
                    <?php $isSelected = in_array($tag, $selectedTags); ?>
                    <button
                        onclick="toggleTag('<?= htmlspecialchars($tag) ?>'); hideTagsModal();"
                        class="px-4 py-2 rounded-full transition <?= $isSelected ? 'bg-white text-black' : 'bg-dark-tertiary hover:bg-dark-border' ?>"
                    >
                        <?= htmlspecialchars($tag) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="/public/js/useractions.js"></script>
<script>
    // Store selected tags
    let selectedTags = <?= json_encode($selectedTags) ?>;
    const hasActiveFilters = <?= json_encode(!empty($search) || !empty($selectedTags)) ?>;

    // Toggle filters panel on mobile
    function toggleFilters() {
        const panel = document.getElementById('filtersPanel');
        panel.classList.toggle('hidden');
    }

    function showAllTags() {
        document.getElementById('tagsModal').classList.remove('hidden');
    }

    function hideTagsModal() {
        document.getElementById('tagsModal').classList.add('hidden');
    }

    // Update total videos in footer
    const totalEl = document.getElementById('totalVideos');
    if (totalEl) {
        totalEl.textContent = <?= count(JsonHelper::getVideos()) ?>;
    }

    // Load dashboard sections on page load
    if (!hasActiveFilters) {
        document.addEventListener('DOMContentLoaded', async () => {
            await loadDashboardSections();
        });
    }

    // Load all dashboard sections
    async function loadDashboardSections() {
        try {
            // Load Continue Watching
            await loadContinueWatching();

            // Load Watch Later
            await loadWatchLater();

            // Load Favorites
            await loadFavorites();

            // Load Top Rated
            await loadTopRated();
        } catch (error) {
            console.error('Error loading dashboard sections:', error);
        }
    }

    // Load Continue Watching section
    async function loadContinueWatching() {
        try {
            const response = await fetch('/public/api/progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_continue_watching', limit: 5 })
            });
            const data = await response.json();

            if (data.success && data.continue_watching && data.continue_watching.length > 0) {
                const section = document.getElementById('continueWatchingSection');
                const grid = document.getElementById('continueWatchingGrid');
                grid.innerHTML = data.continue_watching.map(item => createVideoCard(item.video, item.progress)).join('');
                section.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading continue watching:', error);
        }
    }

    // Load Watch Later section
    async function loadWatchLater() {
        try {
            const response = await fetch('/public/api/watch-later.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_all' })
            });
            const data = await response.json();

            if (data.success && data.watch_later && data.watch_later.length > 0) {
                const section = document.getElementById('watchLaterSection');
                const grid = document.getElementById('watchLaterGrid');
                grid.innerHTML = data.watch_later.slice(0, 5).map(item => createVideoCard(item.video)).join('');
                section.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading watch later:', error);
        }
    }

    // Load Favorites section
    async function loadFavorites() {
        try {
            const response = await fetch('/public/api/favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_all' })
            });
            const data = await response.json();

            if (data.success && data.favorites && data.favorites.length > 0) {
                const section = document.getElementById('favoritesSection');
                const grid = document.getElementById('favoritesGrid');
                grid.innerHTML = data.favorites.slice(0, 5).map(item => createVideoCard(item.video)).join('');
                section.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading favorites:', error);
        }
    }

    // Load Top Rated section
    async function loadTopRated() {
        try {
            const response = await fetch('/public/api/rating.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_top_rated', limit: 5 })
            });
            const data = await response.json();

            if (data.success && data.top_rated && data.top_rated.length > 0) {
                const section = document.getElementById('topRatedSection');
                const grid = document.getElementById('topRatedGrid');
                grid.innerHTML = data.top_rated.map(item => createVideoCard(item.video, null, item.rating)).join('');
                section.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading top rated:', error);
        }
    }

    // Create video card HTML
    function createVideoCard(video, progress = null, rating = null) {
        const thumbnailUrl = `/public/thumbnails/${video.id}.jpg`;
        const progressPercentage = progress ? progress.percentage : 0;
        const stars = rating ? '★'.repeat(rating) + '☆'.repeat(5 - rating) : '';

        return `
            <div class="group cursor-pointer">
                <a href="/watch.php?id=${encodeURIComponent(video.id)}" class="block">
                    <!-- Thumbnail -->
                    <div class="relative rounded-xl overflow-hidden mb-3 bg-dark-secondary">
                        <div class="aspect-video w-full">
                            <img
                                src="${thumbnailUrl}"
                                alt="${escapeHtml(video.title)}"
                                class="w-full h-full object-cover group-hover:opacity-90 transition-opacity"
                                onerror="this.src='/public/img/no-thumbnail.jpg'"
                                loading="lazy"
                            >
                        </div>
                        <!-- Duration Badge -->
                        <span class="absolute bottom-2 right-2 bg-black bg-opacity-90 text-white text-xs font-semibold px-2 py-0.5 rounded">
                            ${escapeHtml(video.duration_formatted)}
                        </span>
                        ${progress ? `
                            <!-- Progress Bar -->
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-700 bg-opacity-50">
                                <div class="h-full bg-red-600" style="width: ${progressPercentage}%"></div>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Video Info -->
                    <div class="flex gap-3">
                        <!-- Avatar -->
                        <div class="hidden sm:block flex-shrink-0 w-9 h-9 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
                            </svg>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-sm lg:text-base mb-1 line-clamp-2 group-hover:text-white transition leading-snug">
                                ${escapeHtml(video.title)}
                            </h3>

                            <!-- Meta -->
                            <div class="text-dark-textSecondary text-xs lg:text-sm space-y-0.5">
                                ${video.category ? `<p class="truncate">${escapeHtml(video.category)}</p>` : ''}
                                <p>
                                    ${escapeHtml(video.size_formatted)}
                                    ${video.width && video.height ? `• ${video.width}x${video.height}` : ''}
                                </p>
                                ${rating ? `<p class="text-yellow-500">${stars}</p>` : ''}
                                ${progress ? `<p class="text-red-500">${progressPercentage.toFixed(0)}% obejrzane</p>` : ''}
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        `;
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load progress and ratings for video cards in the main grid
    document.addEventListener('DOMContentLoaded', async () => {
        await loadVideoCardEnhancements();
    });

    async function loadVideoCardEnhancements() {
        try {
            // Get all video IDs from cards
            const videoCards = document.querySelectorAll('.video-card');
            if (videoCards.length === 0) return;

            const videoIds = Array.from(videoCards).map(card => card.dataset.videoId);

            // Load all progress data
            const progressResponse = await fetch('/public/api/progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_continue_watching', limit: 100 })
            });
            const progressData = await progressResponse.json();

            // Load all ratings
            const ratingsResponse = await fetch('/public/api/rating.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_all' })
            });
            const ratingsData = await ratingsResponse.json();

            // Create lookup maps
            const progressMap = new Map();
            if (progressData.success && progressData.continue_watching) {
                progressData.continue_watching.forEach(item => {
                    progressMap.set(item.video_id, item.progress);
                });
            }

            const ratingsMap = new Map();
            if (ratingsData.success && ratingsData.ratings) {
                ratingsData.ratings.forEach(item => {
                    ratingsMap.set(item.video_id, item.rating);
                });
            }

            // Update each video card
            videoCards.forEach(card => {
                const videoId = card.dataset.videoId;

                // Add progress bar if exists
                const progress = progressMap.get(videoId);
                if (progress) {
                    const progressBar = card.querySelector('.video-progress-bar');
                    const progressText = card.querySelector('.video-progress-text');
                    if (progressBar && progressText) {
                        progressBar.classList.remove('hidden');
                        progressBar.querySelector('div').style.width = `${progress.percentage}%`;
                        progressText.textContent = `${Math.round(progress.percentage)}% obejrzane`;
                        progressText.style.display = 'block';
                    }
                }

                // Add rating if exists
                const rating = ratingsMap.get(videoId);
                if (rating) {
                    const ratingDisplay = card.querySelector('.video-rating-display');
                    if (ratingDisplay) {
                        const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
                        ratingDisplay.textContent = stars;
                        ratingDisplay.style.display = 'block';
                    }
                }
            });
        } catch (error) {
            console.error('Error loading video card enhancements:', error);
        }
    }
</script>

<!-- Thumbnail Generator -->
<script>
    // Przekaż dane filmów do generatora miniaturek
    window.videos = <?= json_encode($videos) ?>;
</script>
<script src="/public/js/thumbnail-generator.js"></script>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-dark-secondary rounded-2xl max-w-md w-full overflow-hidden">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-red-600 bg-opacity-20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-dark-text">Usuń film?</h3>
                </div>
            </div>

            <p class="text-dark-textSecondary mb-4" id="deleteModalText">
                Czy na pewno chcesz usunąć ten film?
            </p>

            <div class="space-y-3 mb-6">
                <label class="flex items-start gap-3 p-3 bg-dark-tertiary rounded-lg cursor-pointer hover:bg-dark-border transition">
                    <input type="radio" name="deleteType" value="library" checked class="mt-1">
                    <div class="flex-1">
                        <div class="font-medium text-dark-text">Tylko z biblioteki</div>
                        <div class="text-sm text-dark-textSecondary">Ukryj film - plik pozostanie na dysku. Nie pojawi się ponownie po skanowaniu.</div>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-3 bg-dark-tertiary rounded-lg cursor-pointer hover:bg-dark-border transition">
                    <input type="radio" name="deleteType" value="permanent" class="mt-1">
                    <div class="flex-1">
                        <div class="font-medium text-red-400">Usuń plik z dysku</div>
                        <div class="text-sm text-dark-textSecondary">Kasuj na stałe - plik wideo zostanie usunięty (nieodwracalne!).</div>
                    </div>
                </label>
            </div>

            <div class="flex gap-3">
                <button
                    onclick="closeDeleteModal()"
                    class="flex-1 px-4 py-3 bg-dark-tertiary hover:bg-dark-border rounded-lg font-medium transition"
                >
                    Anuluj
                </button>
                <button
                    onclick="deleteVideo()"
                    class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-medium transition"
                >
                    Usuń
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let videoToDelete = null;

function confirmDeleteVideo(videoId, videoTitle) {
    videoToDelete = videoId;
    document.getElementById('deleteModalText').textContent =
        `Film: "${videoTitle}"`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    videoToDelete = null;
    document.getElementById('deleteModal').classList.add('hidden');
}

async function deleteVideo() {
    if (!videoToDelete) return;

    const videoId = videoToDelete;

    // Pobierz wybrany typ usuwania
    const deleteType = document.querySelector('input[name="deleteType"]:checked').value;
    const deletePermanent = deleteType === 'permanent';

    closeDeleteModal();

    showLoading('Usuwanie', deletePermanent ? 'Usuwanie pliku z dysku...' : 'Usuwanie z biblioteki...');

    try {
        const response = await fetch('/public/api/delete-video.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: videoId,
                delete_file: deletePermanent
            })
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showToast(deletePermanent ? 'Film usunięty z dysku' : 'Film ukryty', 'success');

            // Usuń kartę filmu z DOM
            const card = document.querySelector(`[data-video-id="${videoId}"]`);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => card.remove(), 300);
            }

            // Odśwież po 1 sekundzie
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Błąd połączenia: ' + error.message, 'error');
    }
}

// Close modal on ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('deleteModal').classList.contains('hidden') === false) {
        closeDeleteModal();
    }
});
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
