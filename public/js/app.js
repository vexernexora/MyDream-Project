/**
 * Główna aplikacja JavaScript
 * Offline Video App
 */

// ============================================
// UI HELPERS
// ============================================

/**
 * Pokazuje modal z loadingiem
 */
function showLoading(title = 'Przetwarzanie...', message = 'Proszę czekać') {
    const modal = document.getElementById('loadingModal');
    const titleEl = document.getElementById('loadingTitle');
    const messageEl = document.getElementById('loadingMessage');

    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;

    modal.classList.remove('hidden');
}

/**
 * Ukrywa modal z loadingiem
 */
function hideLoading() {
    const modal = document.getElementById('loadingModal');
    modal.classList.add('hidden');
}

/**
 * Pokazuje toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const messageEl = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');

    messageEl.textContent = message;

    // Ikona w zależności od typu
    let icon = '';
    switch (type) {
        case 'success':
            icon = `<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>`;
            break;
        case 'error':
            icon = `<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>`;
            break;
        case 'warning':
            icon = `<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>`;
            break;
        default:
            icon = `<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>`;
    }

    iconEl.innerHTML = icon;
    toast.classList.remove('hidden');

    // Auto hide po 5 sekundach
    setTimeout(hideToast, 5000);
}

/**
 * Ukrywa toast
 */
function hideToast() {
    const toast = document.getElementById('toast');
    toast.classList.add('hidden');
}

/**
 * Toggle mobile menu
 */
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('hidden');
}

// ============================================
// LIBRARY MANAGEMENT
// ============================================

/**
 * Skanuje bibliotekę wideo
 */
async function scanLibrary() {
    showLoading('Skanowanie biblioteki', 'Wykrywanie nowych filmów...');

    try {
        const response = await fetch('/public/api/scan.php', {
            method: 'POST'
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            if (data.new_videos > 0) {
                showToast(`Dodano ${data.new_videos} nowych filmów!`, 'success');
                // Odśwież stronę po 1.5s
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Nie znaleziono nowych filmów', 'info');
            }
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Błąd podczas skanowania:', error);
        showToast('Błąd podczas skanowania biblioteki', 'error');
    }
}

/**
 * Aktualizuje dane filmu
 */
async function updateVideo(videoId, updates) {
    showLoading('Aktualizacja', 'Zapisywanie zmian...');

    try {
        const response = await fetch('/public/api/update-video.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: videoId,
                ...updates
            })
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showToast('Film zaktualizowany!', 'success');
            // Zamknij modal jeśli jest otwarty
            const editModal = document.getElementById('editModal');
            if (editModal) {
                editModal.classList.add('hidden');
            }
            // Odśwież po 1s
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Błąd podczas aktualizacji:', error);
        showToast('Błąd podczas aktualizacji filmu', 'error');
    }
}

/**
 * Regeneruje miniaturę filmu
 */
async function regenerateVideoThumbnail(videoId, timestamp = null) {
    showLoading('Generowanie miniatury', 'Proszę czekać...');

    try {
        const body = { id: videoId };
        if (timestamp !== null) {
            body.timestamp = timestamp;
        }

        const response = await fetch('/public/api/regenerate-thumbnail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showToast('Miniatura wygenerowana!', 'success');
            // Odśwież miniaturę na stronie
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Błąd podczas generowania miniatury:', error);
        showToast('Błąd podczas generowania miniatury', 'error');
    }
}

/**
 * Usuwa film z biblioteki
 */
async function deleteVideoFromLibrary(videoId) {
    showLoading('Usuwanie', 'Proszę czekać...');

    try {
        const response = await fetch('/public/api/delete-video.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: videoId })
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showToast('Film usunięty z biblioteki', 'success');
            // Przekieruj do strony głównej po 1s
            setTimeout(() => {
                window.location.href = '/public/index.php';
            }, 1000);
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Błąd podczas usuwania:', error);
        showToast('Błąd podczas usuwania filmu', 'error');
    }
}

// ============================================
// SEARCH & FILTERS
// ============================================

/**
 * Obsługa wyszukiwania
 */
function handleSearch(event) {
    if (event.key === 'Enter') {
        const query = event.target.value.trim();
        applyFilters(query);
    }
}

/**
 * Toggle tag filter
 */
function toggleTag(tag) {
    // Pobierz aktualne tagi z tablicy
    const index = selectedTags.indexOf(tag);

    if (index > -1) {
        // Usuń tag
        selectedTags.splice(index, 1);
    } else {
        // Dodaj tag
        selectedTags.push(tag);
    }

    // Zastosuj filtry
    const searchInput = document.getElementById('searchInput');
    const query = searchInput ? searchInput.value.trim() : '';
    applyFilters(query, selectedTags);
}

/**
 * Obsługa sortowania
 */
function handleSort() {
    const sortSelect = document.getElementById('sortSelect');
    const value = sortSelect.value;
    const [sortBy, order] = value.split('-');

    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortBy);
    url.searchParams.set('order', order);

    window.location.href = url.toString();
}

/**
 * Zastosuj filtry
 */
function applyFilters(query = '', tags = []) {
    const url = new URL(window.location.origin + '/public/index.php');

    if (query) {
        url.searchParams.set('search', query);
    }

    if (tags && tags.length > 0) {
        url.searchParams.set('tags', tags.join(','));
    }

    // Zachowaj sortowanie
    const currentUrl = new URL(window.location.href);
    const sort = currentUrl.searchParams.get('sort');
    const order = currentUrl.searchParams.get('order');

    if (sort) url.searchParams.set('sort', sort);
    if (order) url.searchParams.set('order', order);

    window.location.href = url.toString();
}

/**
 * Wyczyść wszystkie filtry
 */
function clearFilters() {
    window.location.href = '/public/index.php';
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Offline Video App initialized');

    // Jeśli jesteśmy na stronie watch.php, dodaj event listener do video
    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer) {
        videoPlayer.addEventListener('loadedmetadata', () => {
            console.log('Video metadata loaded');
        });

        videoPlayer.addEventListener('error', (e) => {
            console.error('Video playback error:', e);
            showToast('Błąd podczas odtwarzania wideo', 'error');
        });
    }

    // Auto-hide alerts po czasie
    const alerts = document.querySelectorAll('[data-auto-hide]');
    alerts.forEach(alert => {
        const delay = parseInt(alert.getAttribute('data-auto-hide')) || 5000;
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, delay);
    });
});

// ============================================
// KEYBOARD SHORTCUTS
// ============================================

document.addEventListener('keydown', (e) => {
    // ESC - zamyka modale
    if (e.key === 'Escape') {
        // Zamknij edit modal
        const editModal = document.getElementById('editModal');
        if (editModal && !editModal.classList.contains('hidden')) {
            editModal.classList.add('hidden');
        }

        // Zamknij tags modal
        const tagsModal = document.getElementById('tagsModal');
        if (tagsModal && !tagsModal.classList.contains('hidden')) {
            tagsModal.classList.add('hidden');
        }

        // Ukryj loading modal
        const loadingModal = document.getElementById('loadingModal');
        if (loadingModal && !loadingModal.classList.contains('hidden')) {
            hideLoading();
        }
    }

    // Ctrl/Cmd + K - focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    // Spacja na video player
    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer && document.activeElement !== videoPlayer) {
        if (e.key === ' ' && e.target === document.body) {
            e.preventDefault();
            if (videoPlayer.paused) {
                videoPlayer.play();
            } else {
                videoPlayer.pause();
            }
        }
    }
});

// ============================================
// EXPORT
// ============================================

// Eksportuj funkcje globalnie aby były dostępne w inline handlers
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.showToast = showToast;
window.hideToast = hideToast;
window.toggleMobileMenu = toggleMobileMenu;
window.scanLibrary = scanLibrary;
window.updateVideo = updateVideo;
window.regenerateVideoThumbnail = regenerateVideoThumbnail;
window.deleteVideoFromLibrary = deleteVideoFromLibrary;
window.handleSearch = handleSearch;
window.toggleTag = toggleTag;
window.handleSort = handleSort;
window.clearFilters = clearFilters;
