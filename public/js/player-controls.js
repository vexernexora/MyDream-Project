/**
 * Player Controls - Zaawansowane kontrolki odtwarzacza
 * Playback speed, theater mode, autoplay, keyboard shortcuts
 */

let currentPlaybackSpeed = 1.0;
let isTheaterMode = false;
let autoplayEnabled = true;

// ============================================
// PLAYBACK SPEED
// ============================================

/**
 * Ustawia prędkość odtwarzania
 */
function setPlaybackSpeed(speed) {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    videoPlayer.playbackRate = speed;
    currentPlaybackSpeed = speed;

    // Zapisz preferencję
    savePreference('playback_speed', speed);

    // Aktualizuj UI
    updateSpeedDisplay(speed);

    showToast(`Prędkość: ${speed}x`, 'info');
}

/**
 * Aktualizuje wyświetlanie prędkości
 */
function updateSpeedDisplay(speed) {
    const speedButton = document.getElementById('speedButton');
    if (speedButton) {
        speedButton.textContent = `${speed}x`;
    }
}

/**
 * Otwiera menu prędkości
 */
function toggleSpeedMenu() {
    const menu = document.getElementById('speedMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

/**
 * Tworzy menu prędkości
 */
function createSpeedMenu() {
    const speeds = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];

    const menu = document.createElement('div');
    menu.id = 'speedMenu';
    menu.className = 'hidden absolute bottom-full right-0 mb-2 bg-dark-secondary border border-dark-border rounded-lg shadow-lg py-1 min-w-[120px]';

    speeds.forEach(speed => {
        const button = document.createElement('button');
        button.className = `w-full px-4 py-2 text-left hover:bg-dark-tertiary transition text-sm ${speed === currentPlaybackSpeed ? 'bg-dark-tertiary text-white' : ''}`;
        button.textContent = speed === 1 ? 'Normalna' : `${speed}x`;
        button.onclick = () => {
            setPlaybackSpeed(speed);
            toggleSpeedMenu();
        };
        menu.appendChild(button);
    });

    return menu;
}

// ============================================
// THEATER MODE
// ============================================

/**
 * Toggle theater mode
 */
function toggleTheaterMode() {
    isTheaterMode = !isTheaterMode;

    const playerContainer = document.getElementById('playerContainer');
    const sidebar = document.getElementById('videoSidebar');
    const theaterButton = document.getElementById('theaterButton');

    if (!playerContainer) return;

    if (isTheaterMode) {
        // Włącz theater mode
        playerContainer.classList.add('theater-mode');
        if (sidebar) sidebar.classList.add('hidden');

        // Aktualizuj przycisk
        if (theaterButton) {
            theaterButton.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                </svg>
            `;
        }

        showToast('Theater Mode włączony', 'info');
    } else {
        // Wyłącz theater mode
        playerContainer.classList.remove('theater-mode');
        if (sidebar) sidebar.classList.remove('hidden');

        // Aktualizuj przycisk
        if (theaterButton) {
            theaterButton.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                </svg>
            `;
        }

        showToast('Theater Mode wyłączony', 'info');
    }

    // Zapisz preferencję
    savePreference('theater_mode', isTheaterMode);
}

// ============================================
// AUTOPLAY
// ============================================

/**
 * Toggle autoplay
 */
function toggleAutoplay() {
    autoplayEnabled = !autoplayEnabled;

    const button = document.getElementById('autoplayButton');
    if (button) {
        if (autoplayEnabled) {
            button.classList.add('bg-white', 'text-black');
            button.classList.remove('bg-dark-tertiary');
            showToast('Autoplay włączony', 'info');
        } else {
            button.classList.remove('bg-white', 'text-black');
            button.classList.add('bg-dark-tertiary');
            showToast('Autoplay wyłączony', 'info');
        }
    }

    // Zapisz preferencję
    savePreference('autoplay', autoplayEnabled);
}

/**
 * Odtwarza następny film (jeśli autoplay włączony)
 */
function playNextVideo() {
    if (!autoplayEnabled) return;

    const nextVideoLink = document.querySelector('[data-next-video]');
    if (nextVideoLink) {
        const nextVideoId = nextVideoLink.getAttribute('data-next-video');
        showToast('Odtwarzanie następnego filmu...', 'info');
        setTimeout(() => {
            window.location.href = `/watch.php?id=${nextVideoId}`;
        }, 3000);
    }
}

// ============================================
// VOLUME & MUTE
// ============================================

let previousVolume = 1.0;

/**
 * Toggle mute
 */
function toggleMute() {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    if (videoPlayer.muted) {
        videoPlayer.muted = false;
        videoPlayer.volume = previousVolume;
    } else {
        previousVolume = videoPlayer.volume;
        videoPlayer.muted = true;
    }

    updateVolumeIcon(videoPlayer.muted);
}

/**
 * Aktualizuje ikonę głośności
 */
function updateVolumeIcon(muted) {
    const volumeButton = document.getElementById('volumeButton');
    if (!volumeButton) return;

    if (muted) {
        volumeButton.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
            </svg>
        `;
    } else {
        volumeButton.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
            </svg>
        `;
    }
}

// ============================================
// FULLSCREEN
// ============================================

/**
 * Toggle fullscreen
 */
function toggleFullscreen() {
    const playerContainer = document.getElementById('playerContainer') || document.getElementById('videoPlayer');
    if (!playerContainer) return;

    if (!document.fullscreenElement) {
        playerContainer.requestFullscreen().catch(err => {
            console.error('Błąd fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// ============================================
// KEYBOARD SHORTCUTS
// ============================================

/**
 * Zaawansowane skróty klawiszowe dla odtwarzacza
 */
document.addEventListener('keydown', (e) => {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    // Ignoruj jeśli focus na input/textarea
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }

    switch (e.key.toLowerCase()) {
        case ' ':
        case 'k':
            // Spacja lub K - play/pause
            e.preventDefault();
            if (videoPlayer.paused) {
                videoPlayer.play();
            } else {
                videoPlayer.pause();
            }
            break;

        case 'arrowleft':
            // Strzałka w lewo - cofnij 5s
            e.preventDefault();
            videoPlayer.currentTime = Math.max(0, videoPlayer.currentTime - 5);
            showToast('-5s', 'info');
            break;

        case 'arrowright':
            // Strzałka w prawo - przewiń 5s
            e.preventDefault();
            videoPlayer.currentTime = Math.min(videoPlayer.duration, videoPlayer.currentTime + 5);
            showToast('+5s', 'info');
            break;

        case 'j':
            // J - cofnij 10s
            e.preventDefault();
            videoPlayer.currentTime = Math.max(0, videoPlayer.currentTime - 10);
            showToast('-10s', 'info');
            break;

        case 'l':
            // L - przewiń 10s
            e.preventDefault();
            videoPlayer.currentTime = Math.min(videoPlayer.duration, videoPlayer.currentTime + 10);
            showToast('+10s', 'info');
            break;

        case 'arrowup':
            // Strzałka w górę - zwiększ głośność
            e.preventDefault();
            videoPlayer.volume = Math.min(1, videoPlayer.volume + 0.1);
            showToast(`Głośność: ${Math.round(videoPlayer.volume * 100)}%`, 'info');
            break;

        case 'arrowdown':
            // Strzałka w dół - zmniejsz głośność
            e.preventDefault();
            videoPlayer.volume = Math.max(0, videoPlayer.volume - 0.1);
            showToast(`Głośność: ${Math.round(videoPlayer.volume * 100)}%`, 'info');
            break;

        case 'm':
            // M - mute/unmute
            e.preventDefault();
            toggleMute();
            break;

        case 'f':
            // F - fullscreen
            e.preventDefault();
            toggleFullscreen();
            break;

        case 't':
            // T - theater mode
            e.preventDefault();
            toggleTheaterMode();
            break;

        case '0':
        case '1':
        case '2':
        case '3':
        case '4':
        case '5':
        case '6':
        case '7':
        case '8':
        case '9':
            // 0-9 - przejdź do % filmu
            e.preventDefault();
            const percentage = parseInt(e.key) / 10;
            videoPlayer.currentTime = videoPlayer.duration * percentage;
            showToast(`${percentage * 100}%`, 'info');
            break;

        case '<':
        case ',':
            // < - zmniejsz prędkość
            e.preventDefault();
            const newSlowerSpeed = Math.max(0.25, currentPlaybackSpeed - 0.25);
            setPlaybackSpeed(newSlowerSpeed);
            break;

        case '>':
        case '.':
            // > - zwiększ prędkość
            e.preventDefault();
            const newFasterSpeed = Math.min(2, currentPlaybackSpeed + 0.25);
            setPlaybackSpeed(newFasterSpeed);
            break;
    }
});

// ============================================
// PREFERENCJE
// ============================================

/**
 * Zapisuje preferencję użytkownika
 */
async function savePreference(key, value) {
    try {
        await fetch('/public/api/preferences.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set',
                key: key,
                value: value
            })
        });
    } catch (error) {
        console.error('Błąd save preference:', error);
    }
}

/**
 * Wczytuje preferencję użytkownika
 */
async function loadPreference(key, defaultValue = null) {
    try {
        const response = await fetch('/public/api/preferences.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get',
                key: key
            })
        });

        const data = await response.json();
        return data.success ? data.value : defaultValue;
    } catch (error) {
        console.error('Błąd load preference:', error);
        return defaultValue;
    }
}

/**
 * Wczytuje wszystkie preferencje przy starcie
 */
async function loadAllPreferences() {
    currentPlaybackSpeed = await loadPreference('playback_speed', 1.0);
    isTheaterMode = await loadPreference('theater_mode', false);
    autoplayEnabled = await loadPreference('autoplay', true);

    // Zastosuj preferencje
    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer) {
        videoPlayer.playbackRate = currentPlaybackSpeed;
    }

    if (isTheaterMode) {
        toggleTheaterMode();
    }

    updateSpeedDisplay(currentPlaybackSpeed);
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Wczytaj preferencje
    loadAllPreferences();

    // Setup video player events
    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer) {
        // Na zakończenie filmu - autoplay next
        videoPlayer.addEventListener('ended', () => {
            playNextVideo();
        });

        // Update volume icon
        videoPlayer.addEventListener('volumechange', () => {
            updateVolumeIcon(videoPlayer.muted);
        });
    }
});

// ============================================
// LOOP VIDEO
// ============================================

let isLooping = false;

/**
 * Toggle loop mode
 */
function toggleLoop() {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    isLooping = !isLooping;
    videoPlayer.loop = isLooping;

    const button = document.getElementById('loopButton');
    if (button) {
        if (isLooping) {
            button.classList.add('bg-white', 'text-black');
            button.classList.remove('bg-dark-tertiary');
            showToast('Zapętlanie włączone', 'info');
        } else {
            button.classList.remove('bg-white', 'text-black');
            button.classList.add('bg-dark-tertiary');
            showToast('Zapętlanie wyłączone', 'info');
        }
    }

    savePreference('loop_mode', isLooping);
}

// ============================================
// PICTURE-IN-PICTURE (MINI PLAYER)
// ============================================

/**
 * Toggle Picture-in-Picture mode
 */
async function togglePiP() {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    try {
        if (document.pictureInPictureElement) {
            await document.exitPictureInPicture();
            showToast('Mini player wyłączony', 'info');
        } else {
            await videoPlayer.requestPictureInPicture();
            showToast('Mini player włączony', 'info');
        }
    } catch (error) {
        console.error('PiP error:', error);
        showToast('Picture-in-Picture niedostępny', 'error');
    }
}

// ============================================
// SCREENSHOT CAPTURE
// ============================================

/**
 * Przechwytuje screenshot z aktualnej klatki
 */
function captureScreenshot() {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    try {
        // Utwórz canvas
        const canvas = document.createElement('canvas');
        canvas.width = videoPlayer.videoWidth;
        canvas.height = videoPlayer.videoHeight;

        // Narysuj aktualną klatkę
        const ctx = canvas.getContext('2d');
        ctx.drawImage(videoPlayer, 0, 0, canvas.width, canvas.height);

        // Pobierz jako PNG
        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `screenshot-${Date.now()}.png`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showToast('Screenshot zapisany', 'success');
        });
    } catch (error) {
        console.error('Screenshot error:', error);
        showToast('Nie udało się zrobić screenshota', 'error');
    }
}

// ============================================
// STATS FOR NERDS
// ============================================

let statsInterval = null;
let statsVisible = false;

/**
 * Toggle Stats for Nerds overlay
 */
function toggleStats() {
    statsVisible = !statsVisible;

    if (statsVisible) {
        showStatsOverlay();
        statsInterval = setInterval(updateStats, 1000);
    } else {
        hideStatsOverlay();
        if (statsInterval) {
            clearInterval(statsInterval);
            statsInterval = null;
        }
    }
}

/**
 * Pokazuje overlay ze statystykami
 */
function showStatsOverlay() {
    let overlay = document.getElementById('statsOverlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'statsOverlay';
        overlay.className = 'absolute top-4 left-4 bg-black bg-opacity-90 text-white p-4 rounded-lg font-mono text-xs z-50 max-w-md';
        overlay.style.pointerEvents = 'none';

        const playerContainer = document.getElementById('playerContainer');
        if (playerContainer) {
            playerContainer.style.position = 'relative';
            playerContainer.appendChild(overlay);
        }
    }

    overlay.classList.remove('hidden');
    updateStats();
}

/**
 * Ukrywa overlay ze statystykami
 */
function hideStatsOverlay() {
    const overlay = document.getElementById('statsOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

/**
 * Aktualizuje statystyki
 */
function updateStats() {
    const videoPlayer = document.getElementById('videoPlayer');
    const overlay = document.getElementById('statsOverlay');

    if (!videoPlayer || !overlay) return;

    const stats = {
        'Resolution': `${videoPlayer.videoWidth}x${videoPlayer.videoHeight}`,
        'Current Time': formatTime(videoPlayer.currentTime),
        'Duration': formatTime(videoPlayer.duration),
        'Playback Rate': `${videoPlayer.playbackRate}x`,
        'Volume': `${Math.round(videoPlayer.volume * 100)}%`,
        'Buffered': getBufferedPercentage(videoPlayer),
        'Network State': getNetworkState(videoPlayer.networkState),
        'Ready State': getReadyState(videoPlayer.readyState),
        'Paused': videoPlayer.paused ? 'Yes' : 'No',
        'Muted': videoPlayer.muted ? 'Yes' : 'No',
        'Loop': videoPlayer.loop ? 'Yes' : 'No',
    };

    let html = '<div class="font-bold mb-2 text-red-500">Stats for Nerds</div>';
    for (const [key, value] of Object.entries(stats)) {
        html += `<div><span class="text-gray-400">${key}:</span> ${value}</div>`;
    }

    overlay.innerHTML = html;
}

/**
 * Pobiera procent buforowania
 */
function getBufferedPercentage(video) {
    if (video.buffered.length === 0) return '0%';
    const buffered = video.buffered.end(video.buffered.length - 1);
    const percentage = (buffered / video.duration) * 100;
    return `${Math.round(percentage)}%`;
}

/**
 * Pobiera nazwę stanu sieci
 */
function getNetworkState(state) {
    const states = ['NETWORK_EMPTY', 'NETWORK_IDLE', 'NETWORK_LOADING', 'NETWORK_NO_SOURCE'];
    return states[state] || 'Unknown';
}

/**
 * Pobiera nazwę stanu gotowości
 */
function getReadyState(state) {
    const states = ['HAVE_NOTHING', 'HAVE_METADATA', 'HAVE_CURRENT_DATA', 'HAVE_FUTURE_DATA', 'HAVE_ENOUGH_DATA'];
    return states[state] || 'Unknown';
}

/**
 * Formatuje czas w sekundach do MM:SS lub HH:MM:SS
 */
function formatTime(seconds) {
    if (isNaN(seconds)) return '00:00';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

// ============================================
// CINEMA MODE (LIGHTS OFF)
// ============================================

let cinemaMode = false;

/**
 * Toggle Cinema Mode - przyciemnia wszystko poza odtwarzaczem
 */
function toggleCinemaMode() {
    cinemaMode = !cinemaMode;

    let overlay = document.getElementById('cinemaOverlay');

    if (cinemaMode) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'cinemaOverlay';
            overlay.className = 'fixed inset-0 bg-black z-40 transition-opacity duration-300';
            overlay.style.opacity = '0.85';
            document.body.appendChild(overlay);

            // Ustaw player na wierzchu
            const playerContainer = document.getElementById('playerContainer');
            if (playerContainer) {
                playerContainer.style.position = 'relative';
                playerContainer.style.zIndex = '50';
            }
        }

        showToast('Światła zgaszone', 'info');
    } else {
        if (overlay) {
            overlay.remove();
        }

        const playerContainer = document.getElementById('playerContainer');
        if (playerContainer) {
            playerContainer.style.zIndex = '';
        }

        showToast('Światła włączone', 'info');
    }

    savePreference('cinema_mode', cinemaMode);
}

// ============================================
// SHARE WITH TIMESTAMP
// ============================================

/**
 * Kopiuje link z aktualnym timestampem
 */
function shareWithTimestamp() {
    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    const currentTime = Math.floor(videoPlayer.currentTime);
    const url = new URL(window.location.href);
    url.searchParams.set('t', currentTime);

    // Kopiuj do schowka
    navigator.clipboard.writeText(url.toString()).then(() => {
        showToast(`Link skopiowany (od ${formatTime(currentTime)})`, 'success');
    }).catch(() => {
        // Fallback - pokaż w prompt
        prompt('Skopiuj link:', url.toString());
    });
}

// ============================================
// KEYBOARD SHORTCUTS (EXTENDED)
// ============================================

// Rozszerzenie istniejących skrótów
document.addEventListener('keydown', (e) => {
    // Ignoruj jeśli focus na input/textarea
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }

    const videoPlayer = document.getElementById('videoPlayer');
    if (!videoPlayer) return;

    switch (e.key.toLowerCase()) {
        case 'i':
            // I - Toggle Mini Player (PiP)
            e.preventDefault();
            togglePiP();
            break;

        case 's':
            // S - Screenshot
            e.preventDefault();
            captureScreenshot();
            break;

        case 'p':
            // P - Toggle Stats for Nerds
            e.preventDefault();
            toggleStats();
            break;

        case 'c':
            // C - Cinema Mode
            e.preventDefault();
            toggleCinemaMode();
            break;

        case 'u':
            // U - Share with timestamp
            e.preventDefault();
            shareWithTimestamp();
            break;
    }
}, true); // Use capture to run after the main keyboard handler

// ============================================
// EXPORT
// ============================================

window.setPlaybackSpeed = setPlaybackSpeed;
window.toggleSpeedMenu = toggleSpeedMenu;
window.createSpeedMenu = createSpeedMenu;
window.toggleTheaterMode = toggleTheaterMode;
window.toggleAutoplay = toggleAutoplay;
window.toggleMute = toggleMute;
window.toggleFullscreen = toggleFullscreen;
window.toggleLoop = toggleLoop;
window.togglePiP = togglePiP;
window.captureScreenshot = captureScreenshot;
window.toggleStats = toggleStats;
window.toggleCinemaMode = toggleCinemaMode;
window.shareWithTimestamp = shareWithTimestamp;
window.savePreference = savePreference;
window.loadPreference = loadPreference;
