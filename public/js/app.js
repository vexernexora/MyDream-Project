/**
 * Główna aplikacja JavaScript
 * Offline Video App
 */

// ============================================
// SAFE FETCH JSON – zabezpieczenie przed błędami PHP
// ============================================

async function safeFetchJSON(url, options = {}) {
    const response = await fetch(url, options);
    const text = await response.text();

    try {
        const data = JSON.parse(text);
        // Loguj błędy API do konsoli
        if (!response.ok || data.error || data.success === false) {
            console.error(`API Error [${url}]:`, {
                status: response.status,
                data: data,
                responseText: text.substring(0, 500)
            });
        }
        return data;
    } catch (err) {
        console.error("Nieprawidłowa odpowiedź z backendu:", text);
        return {
            success: false,
            error: "Serwer zwrócił nieprawidłowy format odpowiedzi"
        };
    }
}

// ============================================
// UI HELPERS
// ============================================

function showLoading(title = 'Przetwarzanie...', message = 'Proszę czekać') {
    const modal = document.getElementById('loadingModal');
    document.getElementById('loadingTitle').textContent = title;
    document.getElementById('loadingMessage').textContent = message;
    modal.classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingModal').classList.add('hidden');
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    document.getElementById('toastMessage').textContent = message;

    const iconEl = document.getElementById('toastIcon');
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
    setTimeout(hideToast, 5000);
}

function hideToast() {
    document.getElementById('toast').classList.add('hidden');
}

function toggleMobileMenu() {
    const sidebar = document.getElementById('mobileSidebar');
    sidebar.classList.toggle('hidden');

    const sidebarTotal = document.getElementById('sidebarTotalVideos');
    const mainTotal = document.getElementById('totalVideos');
    if (sidebarTotal && mainTotal) sidebarTotal.textContent = mainTotal.textContent;
}

// ============================================
// LIBRARY MANAGEMENT
// ============================================

async function scanLibrary() {
    showLoading('Skanowanie biblioteki', 'Wykrywanie nowych filmów...');

    const data = await safeFetchJSON('/public/api/scan.php', {
        method: 'POST'
    });

    hideLoading();

    if (!data.success) return showToast('Błąd: ' + data.error, 'error');

    if (data.new_videos > 0) {
        showToast(`Dodano ${data.new_videos} nowych filmów!`, 'success');
        return setTimeout(() => window.location.reload(), 1500);
    }

    showToast('Nie znaleziono nowych filmów', 'info');
}

async function updateVideo(videoId, updates) {
    showLoading('Aktualizacja', 'Zapisywanie zmian...');

    const data = await safeFetchJSON('/public/api/update-video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: videoId, ...updates })
    });

    hideLoading();

    if (!data.success) return showToast('Błąd: ' + data.error, 'error');

    showToast('Film zaktualizowany!', 'success');
    document.getElementById('editModal')?.classList.add('hidden');
    setTimeout(() => window.location.reload(), 1000);
}

async function regenerateVideoThumbnail(videoId, timestamp = null) {
    showLoading('Generowanie miniatury', 'Proszę czekać...');

    const body = { id: videoId };
    if (timestamp !== null) body.timestamp = timestamp;

    const data = await safeFetchJSON('/public/api/regenerate-thumbnail.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });

    hideLoading();

    if (!data.success) return showToast('Błąd: ' + data.error, 'error');

    showToast('Miniatura wygenerowana!', 'success');
    setTimeout(() => window.location.reload(), 1000);
}

async function deleteVideoFromLibrary(videoId) {
    showLoading('Usuwanie', 'Proszę czekać...');

    const data = await safeFetchJSON('/public/api/delete-video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: videoId })
    });

    hideLoading();

    if (!data.success) return showToast('Błąd: ' + data.error, 'error');

    showToast('Film usunięty z biblioteki', 'success');
    setTimeout(() => (window.location.href = '/public/index.php'), 1000);
}

// ============================================
// SEARCH & FILTERS
// ============================================

function handleSearch(event) {
    if (event.key === 'Enter') {
        const query = event.target.value.trim();
        applyFilters(query);
    }
}

function toggleTag(tag) {
    const index = selectedTags.indexOf(tag);

    if (index > -1) {
        selectedTags.splice(index, 1);
    } else {
        selectedTags.push(tag);
    }

    const searchInput = document.getElementById('searchInput');
    const query = searchInput ? searchInput.value.trim() : '';
    applyFilters(query, selectedTags);
}

function handleSort() {
    const sortSelect = document.getElementById('sortSelect');
    const value = sortSelect.value;
    const [sortBy, order] = value.split('-');

    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortBy);
    url.searchParams.set('order', order);

    window.location.href = url.toString();
}

function applyFilters(query = '', tags = []) {
    const url = new URL(window.location.origin + '/public/index.php');

    if (query) url.searchParams.set('search', query);
    if (tags.length > 0) url.searchParams.set('tags', tags.join(','));

    const currentUrl = new URL(window.location.href);
    const sort = currentUrl.searchParams.get('sort');
    const order = currentUrl.searchParams.get('order');

    if (sort) url.searchParams.set('sort', sort);
    if (order) url.searchParams.set('order', order);

    window.location.href = url.toString();
}

function clearFilters() {
    window.location.href = '/public/index.php';
}

// ============================================
// THEME SWITCHER
// ============================================

/**
 * Toggle between light and dark theme
 */
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';

    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    // Update icons
    const darkIcon = document.getElementById('themeIconDark');
    const lightIcon = document.getElementById('themeIconLight');

    if (newTheme === 'light') {
        darkIcon?.classList.add('hidden');
        lightIcon?.classList.remove('hidden');
        html.classList.remove('dark');
    } else {
        darkIcon?.classList.remove('hidden');
        lightIcon?.classList.add('hidden');
        html.classList.add('dark');
    }

    showToast(`Tryb ${newTheme === 'light' ? 'jasny' : 'ciemny'} włączony`, 'info');
}

/**
 * Load theme preference
 */
function loadThemePreference() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    const html = document.documentElement;

    html.setAttribute('data-theme', savedTheme);

    const darkIcon = document.getElementById('themeIconDark');
    const lightIcon = document.getElementById('themeIconLight');

    if (savedTheme === 'light') {
        darkIcon?.classList.add('hidden');
        lightIcon?.classList.remove('hidden');
        html.classList.remove('dark');
    } else {
        darkIcon?.classList.remove('hidden');
        lightIcon?.classList.add('hidden');
        html.classList.add('dark');
    }
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Offline Video App initialized');

    // Load theme preference
    loadThemePreference();

    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer) {
        videoPlayer.addEventListener('loadedmetadata', () => console.log('Video metadata loaded'));
        videoPlayer.addEventListener('error', () => showToast('Błąd podczas odtwarzania wideo', 'error'));
    }

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
    if (e.key === 'Escape') {
        document.getElementById('editModal')?.classList.add('hidden');
        document.getElementById('tagsModal')?.classList.add('hidden');
        const loadingModal = document.getElementById('loadingModal');
        if (loadingModal && !loadingModal.classList.contains('hidden')) hideLoading();
    }

    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer && document.activeElement !== videoPlayer) {
        if (e.key === ' ' && e.target === document.body) {
            e.preventDefault();
            videoPlayer.paused ? videoPlayer.play() : videoPlayer.pause();
        }
    }
});

// ============================================
// EXPORT
// ============================================

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
window.toggleTheme = toggleTheme;
window.loadThemePreference = loadThemePreference;
