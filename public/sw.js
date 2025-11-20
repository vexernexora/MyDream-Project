/**
 * Service Worker dla offline functionality
 */

const CACHE_NAME = 'mydream-video-v1';
const OFFLINE_CACHE = 'mydream-offline-v1';

// Pliki do cache'owania przy instalacji
const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/settings.php',
    '/public/js/app.js',
    '/public/js/useractions.js',
    '/public/js/player-controls.js',
    '/public/manifest.json',
    '/public/img/no-thumbnail.jpg'
];

// Instalacja Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Caching static assets');
            return cache.addAll(STATIC_ASSETS.map(url => new Request(url, {cache: 'reload'})));
        }).catch(err => {
            console.log('[SW] Cache installation failed:', err);
        })
    );

    // Aktywuj nowy SW natychmiast
    self.skipWaiting();
});

// Aktywacja Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );

    // Przejmij kontrolę nad wszystkimi klientami
    return self.clients.claim();
});

// Strategia cache'owania: Network First, fallback to Cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Pomiń żądania nie-GET
    if (request.method !== 'GET') {
        return;
    }

    // Dla plików wideo - zawsze sieć (nie cache'uj dużych plików)
    if (url.pathname.startsWith('/videos/') || url.pathname.endsWith('.mp4') || url.pathname.endsWith('.mkv') || url.pathname.endsWith('.avi')) {
        return;
    }

    // API requests - Network First
    if (url.pathname.startsWith('/public/api/')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Zapisz do cache tylko success responses
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Fallback do cache
                    return caches.match(request);
                })
        );
        return;
    }

    // Statyczne zasoby - Cache First, fallback to Network
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                // Zwróć z cache, ale zaktualizuj w tle
                fetch(request).then(response => {
                    if (response && response.status === 200) {
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(request, response);
                        });
                    }
                }).catch(() => {});

                return cachedResponse;
            }

            // Nie ma w cache - pobierz z sieci
            return fetch(request).then((response) => {
                // Cache tylko success responses
                if (response && response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            }).catch((error) => {
                console.log('[SW] Fetch failed:', error);

                // Pokaż offline page dla HTML requests
                if (request.headers.get('accept').includes('text/html')) {
                    return caches.match('/offline.html').then(response => {
                        if (response) return response;

                        // Fallback offline response
                        return new Response(
                            '<h1>Brak połączenia</h1><p>Aplikacja działa offline. Sprawdź połączenie internetowe.</p>',
                            { headers: { 'Content-Type': 'text/html' } }
                        );
                    });
                }
            });
        })
    );
});

// Background Sync - do przyszłej implementacji
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-user-data') {
        event.waitUntil(syncUserData());
    }
});

async function syncUserData() {
    // Placeholder - synchronizacja danych użytkownika w tle
    console.log('[SW] Syncing user data...');
}

// Push notifications - do przyszłej implementacji
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');

    const options = {
        body: event.data ? event.data.text() : 'Nowe powiadomienie',
        icon: '/public/img/icon-192.png',
        badge: '/public/img/icon-72.png',
        vibrate: [200, 100, 200]
    };

    event.waitUntil(
        self.registration.showNotification('MyDream Video', options)
    );
});
