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
// EXPORT
// ============================================

window.setPlaybackSpeed = setPlaybackSpeed;
window.toggleSpeedMenu = toggleSpeedMenu;
window.createSpeedMenu = createSpeedMenu;
window.toggleTheaterMode = toggleTheaterMode;
window.toggleAutoplay = toggleAutoplay;
window.toggleMute = toggleMute;
window.toggleFullscreen = toggleFullscreen;
window.savePreference = savePreference;
window.loadPreference = loadPreference;
