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
                <div class="group cursor-pointer">
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

<script>
    // Store selected tags
    let selectedTags = <?= json_encode($selectedTags) ?>;

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
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
