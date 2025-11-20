<?php
/**
 * Panel ustawień
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$currentPage = 'settings';
$pageTitle = 'Ustawienia';

// Pobierz statystyki
$videos = JsonHelper::getVideos();
$totalVideos = count($videos);
$totalSize = array_sum(array_column($videos, 'size'));
$totalDuration = array_sum(array_column($videos, 'duration_seconds'));
$allTags = JsonHelper::getAllTags();
$ffmpegAvailable = VideoHelper::isFFmpegAvailable();

// Ostatnie skanowanie
$data = json_decode(file_get_contents(VIDEOS_JSON), true);
$lastScan = $data['last_scan'] ?? null;

include INCLUDES_PATH . '/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Ustawienia</h1>

    <!-- System Status -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border mb-6">
        <h2 class="text-xl font-bold mb-4">Status systemu</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full <?= $ffmpegAvailable ? 'bg-green-500' : 'bg-yellow-500' ?>"></div>
                <div>
                    <p class="text-sm text-dark-textSecondary">FFmpeg (opcjonalnie)</p>
                    <p class="font-semibold"><?= $ffmpegAvailable ? 'Dostępny' : 'Niedostępny' ?></p>
                    <?php if (!$ffmpegAvailable): ?>
                        <p class="text-xs text-dark-textSecondary mt-1">Miniatury nie będą generowane</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <div>
                    <p class="text-sm text-dark-textSecondary">PHP Wersja</p>
                    <p class="font-semibold"><?= phpversion() ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <div>
                    <p class="text-sm text-dark-textSecondary">Ostatnie skanowanie</p>
                    <p class="font-semibold"><?= $lastScan ? date('d.m.Y H:i', strtotime($lastScan)) : 'Nigdy' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Library Statistics -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border mb-6">
        <h2 class="text-xl font-bold mb-4">Statystyki biblioteki</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-4xl font-bold text-red-600 mb-2"><?= $totalVideos ?></div>
                <p class="text-dark-textSecondary">Filmów w bibliotece</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-red-600 mb-2"><?= VideoHelper::formatFileSize($totalSize) ?></div>
                <p class="text-dark-textSecondary">Całkowity rozmiar</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-red-600 mb-2"><?= VideoHelper::formatDuration($totalDuration) ?></div>
                <p class="text-dark-textSecondary">Łączny czas</p>
            </div>
        </div>
        <div class="mt-6 pt-6 border-t border-dark-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-dark-textSecondary">Unikalne tagi</p>
                    <p class="font-semibold"><?= count($allTags) ?></p>
                </div>
                <button onclick="showAllTags()" class="text-sm text-red-600 hover:text-red-500">
                    Zobacz wszystkie tagi
                </button>
            </div>
        </div>
    </div>

    <!-- Library Management -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border mb-6">
        <h2 class="text-xl font-bold mb-4">Zarządzanie biblioteką</h2>
        <div class="space-y-4">
            <!-- Scan Library -->
            <div class="flex items-center justify-between p-4 bg-dark-bg rounded-lg">
                <div>
                    <h3 class="font-semibold mb-1">Skanuj folder wideo</h3>
                    <p class="text-sm text-dark-textSecondary">Wykrywa nowe pliki wideo i dodaje je do biblioteki</p>
                </div>
                <button
                    onclick="scanLibrary()"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition"
                >
                    Skanuj
                </button>
            </div>

            <!-- Regenerate All Thumbnails -->
            <div class="flex items-center justify-between p-4 bg-dark-bg rounded-lg">
                <div>
                    <h3 class="font-semibold mb-1">Regeneruj wszystkie miniatury</h3>
                    <p class="text-sm text-dark-textSecondary">Tworzy nowe miniatury dla wszystkich filmów</p>
                </div>
                <button
                    onclick="regenerateAllThumbnails()"
                    class="px-6 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition"
                >
                    Regeneruj
                </button>
            </div>

            <!-- Export Data -->
            <div class="flex items-center justify-between p-4 bg-dark-bg rounded-lg">
                <div>
                    <h3 class="font-semibold mb-1">Eksportuj dane</h3>
                    <p class="text-sm text-dark-textSecondary">Pobierz kopię zapasową bazy danych JSON</p>
                </div>
                <button
                    onclick="exportData()"
                    class="px-6 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition"
                >
                    Eksportuj
                </button>
            </div>

            <!-- Clear Cache -->
            <div class="flex items-center justify-between p-4 bg-dark-bg rounded-lg">
                <div>
                    <h3 class="font-semibold mb-1">Wyczyść cache</h3>
                    <p class="text-sm text-dark-textSecondary">Usuwa pliki tymczasowe i cache</p>
                </div>
                <button
                    onclick="clearCache()"
                    class="px-6 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition"
                >
                    Wyczyść
                </button>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-red-900 mb-6">
        <h2 class="text-xl font-bold text-red-600 mb-4">Strefa niebezpieczna</h2>
        <div class="space-y-4">
            <!-- Reset Database -->
            <div class="flex items-center justify-between p-4 bg-dark-bg rounded-lg border border-red-900">
                <div>
                    <h3 class="font-semibold mb-1">Zresetuj bazę danych</h3>
                    <p class="text-sm text-dark-textSecondary">Usuwa wszystkie dane o filmach (pliki wideo pozostaną)</p>
                </div>
                <button
                    onclick="resetDatabase()"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition"
                >
                    Reset
                </button>
            </div>

            <!-- Delete All Thumbnails -->
            <div class="flex items-center justify-between p-4 bg-dark-bg rounded-lg border border-red-900">
                <div>
                    <h3 class="font-semibold mb-1">Usuń wszystkie miniatury</h3>
                    <p class="text-sm text-dark-textSecondary">Usuwa wszystkie wygenerowane miniatury</p>
                </div>
                <button
                    onclick="deleteAllThumbnails()"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition"
                >
                    Usuń
                </button>
            </div>
        </div>
    </div>

    <!-- Configuration -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border mb-6">
        <h2 class="text-xl font-bold mb-4">Konfiguracja</h2>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-2">Folder wideo</label>
                <input
                    type="text"
                    value="<?= htmlspecialchars(VIDEOS_PATH) ?>"
                    readonly
                    class="w-full bg-dark-bg text-dark-textSecondary border border-dark-border rounded-lg px-4 py-2 font-mono text-sm"
                >
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Folder miniatur</label>
                <input
                    type="text"
                    value="<?= htmlspecialchars(THUMBNAILS_PATH) ?>"
                    readonly
                    class="w-full bg-dark-bg text-dark-textSecondary border border-dark-border rounded-lg px-4 py-2 font-mono text-sm"
                >
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2">Plik bazy danych</label>
                <input
                    type="text"
                    value="<?= htmlspecialchars(VIDEOS_JSON) ?>"
                    readonly
                    class="w-full bg-dark-bg text-dark-textSecondary border border-dark-border rounded-lg px-4 py-2 font-mono text-sm"
                >
            </div>
            <div class="pt-4 border-t border-dark-border">
                <p class="text-sm text-dark-textSecondary">
                    <strong>Uwaga:</strong> Aby zmienić konfigurację, edytuj plik <code class="px-2 py-1 bg-dark-tertiary rounded">config.php</code>
                </p>
            </div>
        </div>
    </div>

    <!-- Supported Formats -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border">
        <h2 class="text-xl font-bold mb-4">Obsługiwane formaty wideo</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach (SUPPORTED_VIDEO_FORMATS as $format): ?>
                <span class="px-3 py-1 bg-dark-tertiary rounded text-sm font-mono">
                    .<?= htmlspecialchars($format) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- All Tags Modal -->
<div id="tagsModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-dark-secondary rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">Wszystkie tagi (<?= count($allTags) ?>)</h2>
                <button onclick="hideTagsModal()" class="text-dark-textSecondary hover:text-dark-text">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($allTags as $tag): ?>
                    <a
                        href="/public/index.php?tags=<?= urlencode($tag) ?>"
                        class="px-3 py-2 bg-dark-tertiary hover:bg-red-600 rounded-lg transition"
                    >
                        <?= htmlspecialchars($tag) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function showAllTags() {
        document.getElementById('tagsModal').classList.remove('hidden');
    }

    function hideTagsModal() {
        document.getElementById('tagsModal').classList.add('hidden');
    }

    function regenerateAllThumbnails() {
        if (!confirm('Czy na pewno chcesz regenerować wszystkie miniatury? To może zająć dużo czasu.')) {
            return;
        }

        showLoading('Regenerowanie miniatur', 'Proszę czekać...');

        // TODO: Implementacja masowego regenerowania
        setTimeout(() => {
            hideLoading();
            showToast('Funkcja w przygotowaniu', 'info');
        }, 1000);
    }

    function exportData() {
        window.location.href = '/data/videos.json';
        showToast('Eksportowanie danych', 'success');
    }

    function clearCache() {
        if (!confirm('Czy na pewno chcesz wyczyścić cache?')) {
            return;
        }

        showLoading('Czyszczenie cache', 'Proszę czekać...');

        fetch('/public/api/clear-cache.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Cache wyczyszczony', 'success');
            } else {
                showToast('Błąd: ' + data.error, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showToast('Błąd podczas czyszczenia cache', 'error');
        });
    }

    function resetDatabase() {
        const confirmation = prompt('Ta operacja usunie wszystkie dane o filmach.\nWpisz "RESET" aby potwierdzić:');

        if (confirmation !== 'RESET') {
            return;
        }

        showLoading('Resetowanie bazy danych', 'Proszę czekać...');

        fetch('/public/api/reset-database.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Baza danych zresetowana', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Błąd: ' + data.error, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showToast('Błąd podczas resetowania bazy danych', 'error');
        });
    }

    function deleteAllThumbnails() {
        if (!confirm('Czy na pewno chcesz usunąć wszystkie miniatury?')) {
            return;
        }

        showLoading('Usuwanie miniatur', 'Proszę czekać...');

        fetch('/public/api/delete-all-thumbnails.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('Miniatury usunięte', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Błąd: ' + data.error, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showToast('Błąd podczas usuwania miniatur', 'error');
        });
    }
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
