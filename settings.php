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

    <!-- Mobile App Builder -->
    <div class="bg-dark-secondary rounded-lg p-6 border border-dark-border mb-6">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Aplikacja mobilna (Android)
        </h2>

        <div class="space-y-6">
            <!-- PWA Info -->
            <div class="bg-dark-bg rounded-lg p-4 border border-green-900">
                <div class="flex items-start gap-3 mb-3">
                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-green-400 mb-2">PWA (Progressive Web App) - GOTOWE!</h3>
                        <p class="text-sm text-dark-textSecondary mb-3">
                            Aplikacja jest już dostępna jako PWA. Możesz zainstalować ją na telefonie jak normalną aplikację:
                        </p>
                        <ul class="text-sm text-dark-textSecondary space-y-2 mb-4 list-disc list-inside">
                            <li><strong>Android Chrome:</strong> Otwórz stronę → Menu (⋮) → "Dodaj do ekranu głównego"</li>
                            <li><strong>iOS Safari:</strong> Otwórz stronę → Udostępnij → "Dodaj do ekranu głównego"</li>
                            <li><strong>Desktop:</strong> Ikona instalacji w pasku adresu przeglądarki</li>
                        </ul>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 bg-green-900 bg-opacity-20 text-green-400 rounded text-sm">✓ Offline</span>
                            <span class="px-3 py-1 bg-green-900 bg-opacity-20 text-green-400 rounded text-sm">✓ Ikony</span>
                            <span class="px-3 py-1 bg-green-900 bg-opacity-20 text-green-400 rounded text-sm">✓ Service Worker</span>
                            <span class="px-3 py-1 bg-green-900 bg-opacity-20 text-green-400 rounded text-sm">✓ Manifest</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- APK Builder -->
            <div class="bg-dark-bg rounded-lg p-4">
                <div class="flex items-start gap-3 mb-4">
                    <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-blue-400 mb-2">Generator APK (Plik instalacyjny Android)</h3>
                        <p class="text-sm text-dark-textSecondary mb-4">
                            Wygeneruj plik APK który możesz zainstalować bezpośrednio na Androidzie.
                            Nie potrzebujesz Android Studio - wszystko dzieje się automatycznie!
                        </p>

                        <!-- App Config -->
                        <div class="space-y-3 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Nazwa aplikacji</label>
                                <input
                                    type="text"
                                    id="appName"
                                    value="MyDream Video"
                                    class="w-full bg-dark-secondary text-dark-text border border-dark-border rounded px-3 py-2 text-sm"
                                    placeholder="MyDream Video Player"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Package ID</label>
                                <input
                                    type="text"
                                    id="packageId"
                                    value="com.mydream.videoplayer"
                                    class="w-full bg-dark-secondary text-dark-text border border-dark-border rounded px-3 py-2 text-sm font-mono"
                                    placeholder="com.example.app"
                                >
                                <p class="text-xs text-dark-textSecondary mt-1">Format: com.twoja.aplikacja (tylko małe litery, cyfry i kropki)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Wersja</label>
                                <input
                                    type="text"
                                    id="appVersion"
                                    value="1.0.0"
                                    class="w-full bg-dark-secondary text-dark-text border border-dark-border rounded px-3 py-2 text-sm"
                                    placeholder="1.0.0"
                                >
                            </div>
                        </div>

                        <!-- Build Button -->
                        <div class="flex flex-wrap gap-3">
                            <button
                                onclick="buildAPK()"
                                id="buildAPKBtn"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Generuj APK
                            </button>

                            <button
                                onclick="downloadConfigOnly()"
                                class="px-6 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg transition flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Pobierz konfigurację
                            </button>
                        </div>

                        <!-- Build Progress -->
                        <div id="buildProgress" class="hidden mt-4 p-4 bg-dark-tertiary rounded-lg">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
                                <span class="text-sm font-medium" id="buildStatusText">Przygotowanie...</span>
                            </div>
                            <div class="w-full bg-dark-bg rounded-full h-2 overflow-hidden">
                                <div id="buildProgressBar" class="bg-blue-500 h-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Build Result -->
                        <div id="buildResult" class="hidden mt-4 p-4 rounded-lg"></div>
                    </div>
                </div>

                <!-- Info -->
                <div class="mt-4 pt-4 border-t border-dark-border">
                    <details class="text-sm">
                        <summary class="cursor-pointer text-dark-textSecondary hover:text-dark-text font-medium">Jak to działa?</summary>
                        <div class="mt-3 text-dark-textSecondary space-y-2">
                            <p><strong>1. PWA (Zainstaluj teraz):</strong> Najszybsza metoda - zainstaluj stronę jak aplikację bezpośrednio z przeglądarki. Działa offline!</p>
                            <p><strong>2. Generator APK:</strong> Tworzy prawdziwy plik APK używając Cordova/Capacitor. Możesz go zainstalować na dowolnym Androidzie.</p>
                            <p class="text-xs pt-2 border-t border-dark-border"><strong>Wymagania:</strong> Generator APK wymaga Node.js i Cordova zainstalowanych na serwerze. Jeśli nie są dostępne, pobierz konfigurację i zbuduj lokalnie.</p>
                        </div>
                    </details>
                </div>
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

    // ===== Mobile App Builder Functions =====

    async function buildAPK() {
        const appName = document.getElementById('appName').value.trim();
        const packageId = document.getElementById('packageId').value.trim();
        const appVersion = document.getElementById('appVersion').value.trim();

        // Walidacja
        if (!appName) {
            showToast('Podaj nazwę aplikacji', 'error');
            return;
        }

        if (!packageId || !/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/.test(packageId)) {
            showToast('Nieprawidłowy Package ID (przykład: com.mydream.app)', 'error');
            return;
        }

        if (!appVersion || !/^\d+\.\d+\.\d+$/.test(appVersion)) {
            showToast('Nieprawidłowa wersja (przykład: 1.0.0)', 'error');
            return;
        }

        // Pokaż progress
        const progressDiv = document.getElementById('buildProgress');
        const resultDiv = document.getElementById('buildResult');
        const buildBtn = document.getElementById('buildAPKBtn');
        const statusText = document.getElementById('buildStatusText');
        const progressBar = document.getElementById('buildProgressBar');

        progressDiv.classList.remove('hidden');
        resultDiv.classList.add('hidden');
        buildBtn.disabled = true;
        buildBtn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            // Step 1: Przygotowanie (10%)
            statusText.textContent = 'Przygotowanie struktury projektu...';
            progressBar.style.width = '10%';
            await sleep(500);

            // Step 2: Tworzenie konfiguracji (30%)
            statusText.textContent = 'Generowanie plików konfiguracyjnych...';
            progressBar.style.width = '30%';

            const response = await fetch('/public/api/build-apk.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'build',
                    app_name: appName,
                    package_id: packageId,
                    version: appVersion
                })
            });

            const data = await response.json();

            // Step 3: Budowanie (60%)
            statusText.textContent = 'Budowanie APK...';
            progressBar.style.width = '60%';
            await sleep(1000);

            // Step 4: Finalizacja (90%)
            statusText.textContent = 'Finalizacja...';
            progressBar.style.width = '90%';
            await sleep(500);

            // Step 5: Gotowe (100%)
            progressBar.style.width = '100%';
            await sleep(300);

            // Ukryj progress
            progressDiv.classList.add('hidden');
            buildBtn.disabled = false;
            buildBtn.classList.remove('opacity-50', 'cursor-not-allowed');

            // Pokaż wynik
            resultDiv.classList.remove('hidden');

            if (data.success) {
                // Sukces
                resultDiv.className = 'mt-4 p-4 rounded-lg bg-green-900 bg-opacity-20 border border-green-900';
                resultDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold text-green-400 mb-2">APK został wygenerowany!</h4>
                            <p class="text-sm text-dark-textSecondary mb-3">${data.message || 'Aplikacja została zbudowana pomyślnie.'}</p>
                            ${data.apk_url ? `
                                <a href="${data.apk_url}" download class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Pobierz APK
                                </a>
                            ` : `
                                <p class="text-sm text-dark-textSecondary">Sprawdź folder build/ lub logi serwera aby pobrać APK.</p>
                            `}
                        </div>
                    </div>
                `;
            } else if (data.download_config) {
                // Cordova nie jest dostępna - pobierz konfigurację
                resultDiv.className = 'mt-4 p-4 rounded-lg bg-yellow-900 bg-opacity-20 border border-yellow-900';
                resultDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-yellow-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold text-yellow-400 mb-2">Cordova niedostępna</h4>
                            <p class="text-sm text-dark-textSecondary mb-3">
                                ${data.message || 'Node.js/Cordova nie są zainstalowane. Pobierz konfigurację aby zbudować APK lokalnie.'}
                            </p>
                            <button onclick="downloadConfigOnly()" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm font-medium transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Pobierz konfigurację
                            </button>
                        </div>
                    </div>
                `;
            } else {
                // Błąd
                resultDiv.className = 'mt-4 p-4 rounded-lg bg-red-900 bg-opacity-20 border border-red-900';
                resultDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold text-red-400 mb-2">Błąd podczas budowania</h4>
                            <p class="text-sm text-dark-textSecondary">${data.error || 'Nie udało się zbudować APK'}</p>
                        </div>
                    </div>
                `;
            }

        } catch (error) {
            progressDiv.classList.add('hidden');
            buildBtn.disabled = false;
            buildBtn.classList.remove('opacity-50', 'cursor-not-allowed');

            resultDiv.classList.remove('hidden');
            resultDiv.className = 'mt-4 p-4 rounded-lg bg-red-900 bg-opacity-20 border border-red-900';
            resultDiv.innerHTML = `
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-red-400 mb-2">Błąd połączenia</h4>
                        <p class="text-sm text-dark-textSecondary">${error.message}</p>
                    </div>
                </div>
            `;
        }
    }

    function downloadConfigOnly() {
        const appName = document.getElementById('appName').value.trim();
        const packageId = document.getElementById('packageId').value.trim();
        const appVersion = document.getElementById('appVersion').value.trim();

        if (!appName || !packageId || !appVersion) {
            showToast('Wypełnij wszystkie pola', 'error');
            return;
        }

        // Generuj i pobierz config.xml oraz instrukcje
        const configXml = '<\x3Fxml version=\'1.0\' encoding=\'utf-8\'\x3F>\n' +
            '<widget id="' + packageId + '" version="' + appVersion + '" xmlns="http://www.w3.org/ns/widgets" xmlns:cdv="http://cordova.apache.org/ns/1.0">\n' +
            '    <name>' + appName + '</name>\n' +
            '    <description>Lokalny odtwarzacz wideo offline w stylu YouTube</description>\n' +
            '    <author email="dev@mydream.com" href="https://mydream.local">MyDream Team</author>\n' +
            '    <content src="index.html" />\n' +
            '    <allow-intent href="http://*/*" />\n' +
            '    <allow-intent href="https://*/*" />\n' +
            '    <allow-intent href="tel:*" />\n' +
            '    <allow-intent href="sms:*" />\n' +
            '    <allow-intent href="mailto:*" />\n' +
            '    <allow-intent href="geo:*" />\n' +
            '    <platform name="android">\n' +
            '        <allow-intent href="market:*" />\n' +
            '        <preference name="AndroidMinSdkVersion" value="22" />\n' +
            '        <preference name="AndroidTargetSdkVersion" value="34" />\n' +
            '    </platform>\n' +
            '    <preference name="DisallowOverscroll" value="true" />\n' +
            '    <preference name="BackupWebStorage" value="local" />\n' +
            '    <preference name="Orientation" value="default" />\n' +
            '</widget>';

        const readme = '# Instrukcja budowania APK - ' + appName + '\n\n' +
            '## Wymagania:\n' +
            '- Node.js (https://nodejs.org/)\n' +
            '- Android Studio lub Android SDK\n\n' +
            '## Kroki:\n\n' +
            '1. Zainstaluj Cordova globalnie:\n' +
            '   npm install -g cordova\n\n' +
            '2. Utwórz nowy projekt Cordova:\n' +
            '   cordova create mydream-app ' + packageId + ' "' + appName + '"\n' +
            '   cd mydream-app\n\n' +
            '3. Zastąp plik config.xml dołączonym plikiem config.xml\n\n' +
            '4. Dodaj platformę Android:\n' +
            '   cordova platform add android\n\n' +
            '5. Skopiuj wszystkie pliki z twojej aplikacji webowej do folderu www/\n\n' +
            '6. Zbuduj APK:\n' +
            '   cordova build android\n\n' +
            '7. APK znajdziesz w:\n' +
            '   platforms/android/app/build/outputs/apk/debug/app-debug.apk\n\n' +
            '## Opcjonalnie (wersja release):\n' +
            'cordova build android --release\n\n' +
            'Potem podpisz APK używając keytool i jarsigner.\n';

        // Pobierz oba pliki jako ZIP
        const zip = '\nconfig.xml:\n' + configXml + '\n\n---\n\nREADME.txt:\n' + readme + '\n';

        // Utwórz i pobierz plik
        const blob = new Blob([zip], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = packageId.replace(/\./g, '_') + '_config.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showToast('Konfiguracja pobrana - sprawdź plik TXT z instrukcjami', 'success');
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
