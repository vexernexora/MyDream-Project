<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= $pageTitle ?? 'Moja Biblioteka Wideo' ?></title>

    <!-- PWA Meta Tags -->
    <meta name="application-name" content="MyDream Video">
    <meta name="description" content="Lokalny odtwarzacz wideo offline w stylu YouTube">
    <meta name="theme-color" content="#0f0f0f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MyDream Video">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/public/manifest.json">

    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/public/img/icon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/public/img/icon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/img/icon-180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/public/img/icon-152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/public/img/icon-144.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/public/img/icon-120.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/public/img/icon-114.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/public/img/icon-76.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/public/img/icon-72.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/public/img/icon-60.png">
    <link rel="apple-touch-icon" sizes="57x57" href="/public/img/icon-57.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#0f0f0f',
                            secondary: '#1f1f1f',
                            tertiary: '#272727',
                            border: '#3f3f3f',
                            text: '#f1f1f1',
                            textSecondary: '#aaaaaa'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Advanced CSS Framework -->
    <link rel="stylesheet" href="/public/css/app.css">

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/public/sw.js')
                    .then(registration => {
                        console.log('[PWA] Service Worker registered:', registration.scope);

                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available
                                    if (confirm('Dostępna jest nowa wersja aplikacji. Czy chcesz odświeżyć stronę?')) {
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(error => {
                        console.log('[PWA] Service Worker registration failed:', error);
                    });
            });

            // Show install prompt
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;

                // Show install button/banner
                const installBanner = document.getElementById('installBanner');
                if (installBanner) {
                    installBanner.style.display = 'block';
                }
            });

            // Handle install button click
            window.installPWA = function() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('[PWA] User accepted the install prompt');
                        }
                        deferredPrompt = null;

                        const installBanner = document.getElementById('installBanner');
                        if (installBanner) {
                            installBanner.style.display = 'none';
                        }
                    });
                }
            };
        }
    </script>

    <!-- Auto Update System -->
    <script src="/public/js/auto-update.js"></script>
</head>
<body class="bg-dark-bg text-dark-text min-h-screen">
    <!-- PWA Install Banner -->
    <div id="installBanner" class="hidden fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:max-w-sm z-50 bg-dark-secondary border border-dark-border rounded-lg shadow-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-10 h-10 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold mb-1">Zainstaluj aplikację</h3>
                <p class="text-sm text-dark-textSecondary mb-3">Dodaj MyDream Video do ekranu głównego i używaj jak natywnej aplikacji</p>
                <div class="flex gap-2">
                    <button onclick="installPWA()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition">
                        Zainstaluj
                    </button>
                    <button onclick="document.getElementById('installBanner').style.display='none'" class="px-4 py-2 bg-dark-tertiary hover:bg-dark-border rounded-lg text-sm transition">
                        Później
                    </button>
                </div>
            </div>
            <button onclick="document.getElementById('installBanner').style.display='none'" class="text-dark-textSecondary hover:text-dark-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    <!-- Navigation - YouTube Style -->
    <nav class="bg-dark-bg border-b border-dark-border sticky top-0 z-50">
        <div class="flex items-center justify-between h-14 lg:h-16 px-4 lg:px-6">
            <!-- Left: Menu + Logo -->
            <div class="flex items-center gap-2 lg:gap-4">
                <!-- Menu Button -->
                <button onclick="toggleMobileMenu()" class="lg:hidden w-10 h-10 flex items-center justify-center hover:bg-dark-tertiary rounded-full transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Logo -->
                <a href="/index.php" class="flex items-center gap-1 lg:gap-2 hover:opacity-80 transition">
                    <svg class="w-7 h-7 lg:w-9 lg:h-9 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
                    </svg>
                    <span class="hidden sm:block text-lg lg:text-xl font-bold">MyTube</span>
                </a>
            </div>

            <!-- Center: Search (Desktop Only) -->
            <div class="hidden lg:flex flex-1 max-w-2xl mx-8">
                <div class="relative w-full">
                    <input
                        type="text"
                        id="searchInput"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        placeholder="Szukaj"
                        class="w-full bg-dark-secondary text-dark-text border border-dark-border rounded-l-full px-6 py-2 focus:outline-none focus:border-blue-500"
                        onkeyup="handleSearch(event)"
                    >
                    <button class="absolute right-0 top-0 h-full px-6 bg-dark-tertiary border border-dark-border border-l-0 rounded-r-full hover:bg-dark-border transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2">
                <!-- Theme Toggle -->
                <button
                    id="themeToggle"
                    onclick="toggleTheme()"
                    class="w-10 h-10 flex items-center justify-center hover:bg-dark-tertiary rounded-full transition"
                    title="Przełącz tryb jasny/ciemny"
                >
                    <svg id="themeIconDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg id="themeIconLight" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                <!-- Refresh Button -->
                <button
                    onclick="scanLibrary()"
                    class="w-10 h-10 flex items-center justify-center hover:bg-dark-tertiary rounded-full transition"
                    title="Odśwież bibliotekę"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

                <!-- Import -->
                <a
                    href="/import.php"
                    class="w-10 h-10 flex items-center justify-center hover:bg-dark-tertiary rounded-full transition"
                    title="Import filmów"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </a>

                <!-- Settings -->
                <a
                    href="/settings.php"
                    class="w-10 h-10 flex items-center justify-center hover:bg-dark-tertiary rounded-full transition"
                    title="Ustawienia"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar Menu -->
    <div id="mobileSidebar" class="hidden fixed inset-0 z-50 lg:hidden">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-75" onclick="toggleMobileMenu()"></div>

        <!-- Sidebar -->
        <div class="absolute left-0 top-0 bottom-0 w-64 bg-dark-secondary overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center gap-2 p-4 border-b border-dark-border">
                <button onclick="toggleMobileMenu()" class="w-10 h-10 flex items-center justify-center hover:bg-dark-tertiary rounded-full transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
                </svg>
                <span class="text-lg font-bold">MyTube</span>
            </div>

            <!-- Menu Items -->
            <div class="p-2">
                <a href="/index.php" class="flex items-center gap-4 px-4 py-3 hover:bg-dark-tertiary rounded-lg transition <?= $currentPage === 'home' ? 'bg-dark-tertiary' : '' ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Strona główna</span>
                </a>

                <a href="/settings.php" class="flex items-center gap-4 px-4 py-3 hover:bg-dark-tertiary rounded-lg transition <?= $currentPage === 'settings' ? 'bg-dark-tertiary' : '' ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Ustawienia</span>
                </a>

                <button onclick="scanLibrary()" class="w-full flex items-center gap-4 px-4 py-3 hover:bg-dark-tertiary rounded-lg transition text-left">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Odśwież bibliotekę</span>
                </button>
            </div>

            <!-- Footer -->
            <div class="p-4 mt-4 border-t border-dark-border">
                <p class="text-xs text-dark-textSecondary">
                    Filmów: <span id="sidebarTotalVideos">0</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="min-h-screen">
