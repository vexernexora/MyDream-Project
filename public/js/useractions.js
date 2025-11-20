/**
 * UserActions - Akcje użytkownika (ulubione, watch later, oceny, progress)
 * Rozbudowane funkcje w stylu YouTube
 */

// ============================================
// FAVORITES (ULUBIONE)
// ============================================

/**
 * Toggle ulubione
 */
async function toggleFavorite(videoId, buttonElement = null) {
    try {
        const response = await fetch('/public/api/favorites.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');

            // Aktualizuj UI jeśli jest przycisk
            if (buttonElement) {
                updateFavoriteButton(buttonElement, data.is_favorite);
            }

            return data.is_favorite;
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Błąd toggle favorite:', error);
        showToast('Błąd podczas aktualizacji ulubionych', 'error');
    }

    return null;
}

/**
 * Aktualizuje wygląd przycisku ulubione
 */
function updateFavoriteButton(button, isFavorite) {
    const icon = button.querySelector('svg');
    const text = button.querySelector('span');

    if (isFavorite) {
        // Wypełnione serduszko
        icon.innerHTML = `<path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>`;
        button.classList.add('text-red-500');
        if (text) text.textContent = 'Ulubione';
    } else {
        // Puste serduszko
        icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>`;
        button.classList.remove('text-red-500');
        if (text) text.textContent = 'Dodaj do ulubionych';
    }
}

// ============================================
// WATCH LATER (DO OBEJRZENIA)
// ============================================

/**
 * Toggle watch later
 */
async function toggleWatchLater(videoId, buttonElement = null) {
    try {
        const response = await fetch('/public/api/watch-later.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');

            // Aktualizuj UI jeśli jest przycisk
            if (buttonElement) {
                updateWatchLaterButton(buttonElement, data.is_in_watch_later);
            }

            return data.is_in_watch_later;
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Błąd toggle watch later:', error);
        showToast('Błąd podczas aktualizacji listy', 'error');
    }

    return null;
}

/**
 * Aktualizuje wygląd przycisku watch later
 */
function updateWatchLaterButton(button, isInList) {
    const icon = button.querySelector('svg');
    const text = button.querySelector('span');

    if (isInList) {
        button.classList.add('bg-white', 'text-black');
        button.classList.remove('bg-dark-tertiary');
        if (text) text.textContent = 'Na liście';
    } else {
        button.classList.remove('bg-white', 'text-black');
        button.classList.add('bg-dark-tertiary');
        if (text) text.textContent = 'Do obejrzenia';
    }
}

// ============================================
// RATING (OCENY)
// ============================================

/**
 * Ustawia ocenę filmu
 */
async function setRating(videoId, rating) {
    try {
        const response = await fetch('/public/api/rating.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set',
                video_id: videoId,
                rating: rating
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Oceniono na ${rating} gwiazdek`, 'success');
            updateRatingDisplay(videoId, rating);
            return true;
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Błąd set rating:', error);
        showToast('Błąd podczas zapisywania oceny', 'error');
    }

    return false;
}

/**
 * Usuwa ocenę filmu
 */
async function removeRating(videoId) {
    try {
        const response = await fetch('/public/api/rating.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'remove',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Ocena usunięta', 'success');
            updateRatingDisplay(videoId, 0);
            return true;
        }
    } catch (error) {
        console.error('Błąd remove rating:', error);
    }

    return false;
}

/**
 * Aktualizuje wyświetlanie gwiazdek
 */
function updateRatingDisplay(videoId, rating) {
    const container = document.querySelector(`[data-rating-container="${videoId}"]`);
    if (!container) return;

    const stars = container.querySelectorAll('.rating-star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('text-yellow-500', 'fill-current');
            star.classList.remove('text-gray-400');
        } else {
            star.classList.remove('text-yellow-500', 'fill-current');
            star.classList.add('text-gray-400');
        }
    });
}

/**
 * Tworzy widget z gwiazdkami
 */
function createRatingWidget(videoId, currentRating = 0) {
    const container = document.createElement('div');
    container.className = 'flex items-center gap-1';
    container.setAttribute('data-rating-container', videoId);

    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('button');
        star.className = `rating-star w-6 h-6 transition hover:scale-110 ${i <= currentRating ? 'text-yellow-500 fill-current' : 'text-gray-400'}`;
        star.innerHTML = `<svg fill="${i <= currentRating ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>`;

        star.addEventListener('click', () => {
            if (i === currentRating) {
                // Jeśli kliknięto tę samą gwiazdkę - usuń ocenę
                removeRating(videoId);
            } else {
                setRating(videoId, i);
            }
        });

        container.appendChild(star);
    }

    return container;
}

// ============================================
// PROGRESS (POSTĘP OGLĄDANIA)
// ============================================

let progressSaveInterval = null;
let currentVideoId = null;

/**
 * Rozpoczyna tracking postępu
 */
function startProgressTracking(videoId, videoElement) {
    currentVideoId = videoId;

    // Zatrzymaj poprzedni tracking jeśli istniał
    if (progressSaveInterval) {
        clearInterval(progressSaveInterval);
    }

    // Wczytaj poprzedni postęp
    loadProgress(videoId, videoElement);

    // Zapisuj postęp co 10 sekund
    progressSaveInterval = setInterval(() => {
        if (!videoElement.paused) {
            saveProgress(videoId, videoElement.currentTime, videoElement.duration);
        }
    }, 10000);

    // Zapisz też przy pauzowaniu
    videoElement.addEventListener('pause', () => {
        saveProgress(videoId, videoElement.currentTime, videoElement.duration);
    });

    // Zapisz przy zakończeniu
    videoElement.addEventListener('ended', () => {
        saveProgress(videoId, videoElement.currentTime, videoElement.duration);
    });
}

/**
 * Zatrzymuje tracking postępu
 */
function stopProgressTracking() {
    if (progressSaveInterval) {
        clearInterval(progressSaveInterval);
        progressSaveInterval = null;
    }
}

/**
 * Zapisuje postęp
 */
async function saveProgress(videoId, currentTime, duration) {
    try {
        const response = await fetch('/public/api/progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save',
                video_id: videoId,
                current_time: currentTime,
                duration: duration
            })
        });

        const data = await response.json();

        if (data.success && data.completed) {
            console.log('Film obejrzany w całości');
        }
    } catch (error) {
        console.error('Błąd save progress:', error);
    }
}

/**
 * Wczytuje postęp
 */
async function loadProgress(videoId, videoElement) {
    try {
        const response = await fetch('/public/api/progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success && data.progress) {
            const { current_time, percentage } = data.progress;

            // Jeśli film był oglądany (5-95%), zaproponuj kontynuację
            if (percentage >= 5 && percentage < 95) {
                showContinueWatchingPrompt(current_time, videoElement);
            }
        }
    } catch (error) {
        console.error('Błąd load progress:', error);
    }
}

/**
 * Pokazuje prompt "Kontynuuj od..."
 */
function showContinueWatchingPrompt(currentTime, videoElement) {
    const minutes = Math.floor(currentTime / 60);
    const seconds = Math.floor(currentTime % 60);
    const timeFormatted = `${minutes}:${seconds.toString().padStart(2, '0')}`;

    const prompt = document.createElement('div');
    prompt.className = 'fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-dark-secondary border border-dark-border rounded-lg p-4 shadow-lg z-50';
    prompt.innerHTML = `
        <p class="text-sm mb-3">Kontynuuj od ${timeFormatted}?</p>
        <div class="flex gap-2">
            <button onclick="continueFromTime(${currentTime})" class="px-4 py-2 bg-white text-black rounded-lg hover:bg-gray-200 text-sm">
                Kontynuuj
            </button>
            <button onclick="this.closest('div').parentElement.remove()" class="px-4 py-2 bg-dark-tertiary rounded-lg hover:bg-dark-border text-sm">
                Od początku
            </button>
        </div>
    `;

    document.body.appendChild(prompt);

    // Auto-remove po 10 sekundach
    setTimeout(() => {
        if (prompt.parentElement) {
            prompt.remove();
        }
    }, 10000);
}

/**
 * Kontynuuj od określonego czasu
 */
function continueFromTime(time) {
    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer) {
        videoPlayer.currentTime = time;
        videoPlayer.play();
    }

    // Usuń prompt
    const prompt = document.querySelector('.fixed.bottom-20');
    if (prompt) prompt.remove();
}

// ============================================
// HISTORIA
// ============================================

/**
 * Dodaje do historii
 */
async function addToHistory(videoId) {
    try {
        await fetch('/public/api/history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                video_id: videoId
            })
        });
    } catch (error) {
        console.error('Błąd add to history:', error);
    }
}

// ============================================
// EXPORT
// ============================================

window.toggleFavorite = toggleFavorite;
window.toggleWatchLater = toggleWatchLater;
window.setRating = setRating;
window.removeRating = removeRating;
window.createRatingWidget = createRatingWidget;
window.startProgressTracking = startProgressTracking;
window.stopProgressTracking = stopProgressTracking;
window.continueFromTime = continueFromTime;
window.addToHistory = addToHistory;
