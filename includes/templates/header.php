<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Moja Biblioteka Wideo' ?></title>

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
                            secondary: '#1a1a1a',
                            tertiary: '#272727',
                            border: '#303030',
                            text: '#f1f1f1',
                            textSecondary: '#aaaaaa'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        * {
            scrollbar-width: thin;
            scrollbar-color: #4a4a4a #1a1a1a;
        }

        *::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        *::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        *::-webkit-scrollbar-thumb {
            background-color: #4a4a4a;
            border-radius: 4px;
        }

        *::-webkit-scrollbar-thumb:hover {
            background-color: #5a5a5a;
        }

        .video-card {
            transition: all 0.2s ease;
        }

        .video-card:hover {
            transform: translateY(-4px);
        }

        .video-thumbnail {
            aspect-ratio: 16/9;
            background: #272727;
        }

        .tag-badge {
            transition: all 0.2s ease;
        }

        .tag-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-dark-bg text-dark-text min-h-screen">
    <!-- Navigation -->
    <nav class="bg-dark-secondary border-b border-dark-border sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/public/index.php" class="flex items-center space-x-2 hover:opacity-80 transition">
                        <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21.582,6.186c-0.23-0.86-0.908-1.538-1.768-1.768C18.254,4,12,4,12,4S5.746,4,4.186,4.418 c-0.86,0.23-1.538,0.908-1.768,1.768C2,7.746,2,12,2,12s0,4.254,0.418,5.814c0.23,0.86,0.908,1.538,1.768,1.768 C5.746,20,12,20,12,20s6.254,0,7.814-0.418c0.861-0.23,1.538-0.908,1.768-1.768C22,16.254,22,12,22,12S22,7.746,21.582,6.186z M10,15.464V8.536L16,12L10,15.464z"/>
                        </svg>
                        <span class="text-xl font-bold">Moja Biblioteka</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/public/index.php" class="px-4 py-2 rounded-lg hover:bg-dark-tertiary transition <?= $currentPage === 'home' ? 'bg-dark-tertiary' : '' ?>">
                        Strona główna
                    </a>
                    <a href="/public/settings.php" class="px-4 py-2 rounded-lg hover:bg-dark-tertiary transition <?= $currentPage === 'settings' ? 'bg-dark-tertiary' : '' ?>">
                        Ustawienia
                    </a>
                    <button onclick="scanLibrary()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Odśwież bibliotekę</span>
                    </button>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-dark-tertiary transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-dark-border">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/public/index.php" class="block px-3 py-2 rounded-lg hover:bg-dark-tertiary transition">
                    Strona główna
                </a>
                <a href="/public/settings.php" class="block px-3 py-2 rounded-lg hover:bg-dark-tertiary transition">
                    Ustawienia
                </a>
                <button onclick="scanLibrary()" class="w-full text-left px-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition">
                    Odśwież bibliotekę
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
