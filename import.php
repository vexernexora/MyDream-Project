<?php
/**
 * Import Filmów - Upload i pobieranie z zewnętrznych źródeł
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/templates/header.php';
?>

<div class="min-h-screen bg-dark-bg py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-dark-text mb-2">
                <svg class="w-8 h-8 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Import Filmów
            </h1>
            <p class="text-dark-textSecondary">Dodaj filmy przez upload lub pobierz z zewnętrznych źródeł</p>
        </div>

        <!-- Tab Navigation -->
        <div class="flex gap-2 mb-6 border-b border-dark-border">
            <button onclick="switchTab('upload')" id="tab-upload" class="px-6 py-3 font-medium border-b-2 border-red-600 text-red-500">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Upload Lokalny
            </button>
            <button onclick="switchTab('mega')" id="tab-mega" class="px-6 py-3 font-medium border-b-2 border-transparent text-dark-textSecondary hover:text-dark-text">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                </svg>
                Mega.nz
            </button>
            <button onclick="switchTab('url')" id="tab-url" class="px-6 py-3 font-medium border-b-2 border-transparent text-dark-textSecondary hover:text-dark-text">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Link URL
            </button>
        </div>

        <!-- Upload Tab -->
        <div id="content-upload" class="tab-content">
            <div class="bg-dark-card rounded-lg p-6 border border-dark-border">
                <h2 class="text-xl font-semibold text-dark-text mb-4">Upload Plików z Komputera</h2>

                <div class="mb-6">
                    <div id="dropZone" class="border-2 border-dashed border-dark-border rounded-lg p-12 text-center hover:border-red-600 transition cursor-pointer">
                        <svg class="w-16 h-16 mx-auto mb-4 text-dark-textSecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-lg text-dark-text mb-2">Przeciągnij i upuść pliki wideo tutaj</p>
                        <p class="text-sm text-dark-textSecondary mb-4">lub</p>
                        <input type="file" id="fileInput" multiple accept="video/*" class="hidden">
                        <button onclick="document.getElementById('fileInput').click()" class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-medium transition">
                            Wybierz Pliki
                        </button>
                        <p class="text-xs text-dark-textSecondary mt-4">
                            Obsługiwane formaty: MP4, MKV, AVI, MOV, WebM, FLV, MPEG
                        </p>
                    </div>
                </div>

                <!-- Upload Queue -->
                <div id="uploadQueue" class="hidden space-y-3"></div>
            </div>
        </div>

        <!-- Mega Tab -->
        <div id="content-mega" class="tab-content hidden">
            <div class="bg-dark-card rounded-lg p-6 border border-dark-border">
                <h2 class="text-xl font-semibold text-dark-text mb-4">Pobierz z Mega.nz</h2>

                <form id="megaForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-dark-text mb-2">Link do pliku/folderu Mega.nz</label>
                        <input type="text" id="megaUrl" placeholder="https://mega.nz/file/..." class="w-full px-4 py-3 bg-dark-bg border border-dark-border rounded-lg text-dark-text placeholder-dark-textSecondary focus:outline-none focus:border-red-600">
                        <p class="text-xs text-dark-textSecondary mt-2">
                            Wklej link do pliku lub folderu z Mega.nz (np. https://mega.nz/file/xxx lub https://mega.nz/folder/xxx)
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark-text mb-2">Nazwa pliku (opcjonalne)</label>
                        <input type="text" id="megaFilename" placeholder="Automatycznie wykryte..." class="w-full px-4 py-3 bg-dark-bg border border-dark-border rounded-lg text-dark-text placeholder-dark-textSecondary focus:outline-none focus:border-red-600">
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-medium transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Rozpocznij Pobieranie
                    </button>
                </form>
            </div>
        </div>

        <!-- URL Tab -->
        <div id="content-url" class="tab-content hidden">
            <div class="bg-dark-card rounded-lg p-6 border border-dark-border">
                <h2 class="text-xl font-semibold text-dark-text mb-4">Pobierz z Linku URL</h2>

                <form id="urlForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-dark-text mb-2">Bezpośredni link do pliku wideo</label>
                        <input type="url" id="videoUrl" placeholder="https://example.com/video.mp4" class="w-full px-4 py-3 bg-dark-bg border border-dark-border rounded-lg text-dark-text placeholder-dark-textSecondary focus:outline-none focus:border-red-600">
                        <p class="text-xs text-dark-textSecondary mt-2">
                            Link musi prowadzić bezpośrednio do pliku wideo (kończy się na .mp4, .mkv, .avi, itp.)
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-dark-text mb-2">Nazwa pliku (opcjonalne)</label>
                        <input type="text" id="urlFilename" placeholder="Automatycznie z URL..." class="w-full px-4 py-3 bg-dark-bg border border-dark-border rounded-lg text-dark-text placeholder-dark-textSecondary focus:outline-none focus:border-red-600">
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-medium transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Rozpocznij Pobieranie
                    </button>
                </form>
            </div>
        </div>

        <!-- Download Progress Section -->
        <div id="downloadProgress" class="hidden mt-6">
            <div class="bg-dark-card rounded-lg p-6 border border-dark-border">
                <h3 class="text-lg font-semibold text-dark-text mb-4">Postęp Pobierania</h3>
                <div id="downloadList" class="space-y-3"></div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-8 text-center">
            <a href="/" class="inline-flex items-center gap-2 text-dark-textSecondary hover:text-dark-text transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Powrót do biblioteki
            </a>
        </div>
    </div>
</div>

<script src="/public/js/import.js"></script>

<?php require_once __DIR__ . '/includes/templates/footer.php'; ?>
