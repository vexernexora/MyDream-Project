/**
 * Like/Dislike System for Videos
 * YouTube-style engagement buttons
 */

let currentVideoLikes = {
    likes: 0,
    dislikes: 0,
    userReaction: null
};

/**
 * Initialize like/dislike system for video
 */
async function initLikeSystem(videoId) {
    await loadLikeStats(videoId);
    setupLikeButtons();
}

/**
 * Load like/dislike stats for video
 */
async function loadLikeStats(videoId) {
    try {
        const response = await fetch('/public/api/likes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_stats',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            currentVideoLikes.likes = data.likes;
            currentVideoLikes.dislikes = data.dislikes;

            // Get user reaction
            await loadUserReaction(videoId);

            // Update UI
            updateLikeButtons();
        }

    } catch (error) {
        console.error('Error loading like stats:', error);
    }
}

/**
 * Load user's reaction to video
 */
async function loadUserReaction(videoId) {
    try {
        const response = await fetch('/public/api/likes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_user_reaction',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            currentVideoLikes.userReaction = data.reaction;
        }

    } catch (error) {
        console.error('Error loading user reaction:', error);
    }
}

/**
 * Setup like/dislike button event listeners
 */
function setupLikeButtons() {
    const likeBtn = document.getElementById('videoLikeBtn');
    const dislikeBtn = document.getElementById('videoDislikeBtn');

    if (likeBtn) {
        likeBtn.addEventListener('click', () => toggleVideoLike());
    }

    if (dislikeBtn) {
        dislikeBtn.addEventListener('click', () => toggleVideoDislike());
    }
}

/**
 * Update like/dislike button UI
 */
function updateLikeButtons() {
    const likeBtn = document.getElementById('videoLikeBtn');
    const dislikeBtn = document.getElementById('videoDislikeBtn');
    const likeCount = document.getElementById('likeCount');
    const dislikeCount = document.getElementById('dislikeCount');

    if (!likeBtn || !dislikeBtn) return;

    // Update counts
    if (likeCount) {
        likeCount.textContent = formatNumber(currentVideoLikes.likes);
    }

    if (dislikeCount) {
        // Some platforms hide dislike count (YouTube style)
        // dislikeCount.textContent = formatNumber(currentVideoLikes.dislikes);
        dislikeCount.textContent = ''; // Hide count like YouTube
    }

    // Update button states
    const isLiked = currentVideoLikes.userReaction === 'like';
    const isDisliked = currentVideoLikes.userReaction === 'dislike';

    // Like button
    if (isLiked) {
        likeBtn.classList.add('active');
        likeBtn.querySelector('svg').setAttribute('fill', 'currentColor');
    } else {
        likeBtn.classList.remove('active');
        likeBtn.querySelector('svg').setAttribute('fill', 'none');
    }

    // Dislike button
    if (isDisliked) {
        dislikeBtn.classList.add('active');
        dislikeBtn.querySelector('svg').setAttribute('fill', 'currentColor');
    } else {
        dislikeBtn.classList.remove('active');
        dislikeBtn.querySelector('svg').setAttribute('fill', 'none');
    }

    // Update like ratio bar
    updateLikeRatioBar();
}

/**
 * Update like ratio visualization bar
 */
function updateLikeRatioBar() {
    const likeBar = document.getElementById('likeRatioBar');
    if (!likeBar) return;

    const total = currentVideoLikes.likes + currentVideoLikes.dislikes;
    const likeRatio = total > 0 ? (currentVideoLikes.likes / total) * 100 : 50;

    const fill = likeBar.querySelector('.like-ratio-fill');
    if (fill) {
        fill.style.width = likeRatio + '%';
    }
}

/**
 * Toggle like for video
 */
async function toggleVideoLike() {
    const videoId = document.getElementById('videoPlayer')?.dataset?.videoId;
    if (!videoId) return;

    const likeBtn = document.getElementById('videoLikeBtn');

    // Optimistic UI update
    const wasLiked = currentVideoLikes.userReaction === 'like';
    const wasDisliked = currentVideoLikes.userReaction === 'dislike';

    if (wasLiked) {
        currentVideoLikes.likes--;
        currentVideoLikes.userReaction = null;
    } else {
        if (wasDisliked) {
            currentVideoLikes.dislikes--;
        }
        currentVideoLikes.likes++;
        currentVideoLikes.userReaction = 'like';

        // Animation
        animateLikeButton(likeBtn);
    }

    updateLikeButtons();

    try {
        const response = await fetch('/public/api/likes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_like',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update with server response
            currentVideoLikes.likes = data.likes;
            currentVideoLikes.dislikes = data.dislikes;
            currentVideoLikes.userReaction = data.user_reaction;
            updateLikeButtons();
        } else {
            // Revert on error
            await loadLikeStats(videoId);
            showToast('Błąd zapisywania reakcji', 'error');
        }

    } catch (error) {
        console.error('Error toggling like:', error);
        await loadLikeStats(videoId);
        showToast('Błąd połączenia z serwerem', 'error');
    }
}

/**
 * Toggle dislike for video
 */
async function toggleVideoDislike() {
    const videoId = document.getElementById('videoPlayer')?.dataset?.videoId;
    if (!videoId) return;

    const dislikeBtn = document.getElementById('videoDislikeBtn');

    // Optimistic UI update
    const wasLiked = currentVideoLikes.userReaction === 'like';
    const wasDisliked = currentVideoLikes.userReaction === 'dislike';

    if (wasDisliked) {
        currentVideoLikes.dislikes--;
        currentVideoLikes.userReaction = null;
    } else {
        if (wasLiked) {
            currentVideoLikes.likes--;
        }
        currentVideoLikes.dislikes++;
        currentVideoLikes.userReaction = 'dislike';

        // Animation
        animateDislikeButton(dislikeBtn);
    }

    updateLikeButtons();

    try {
        const response = await fetch('/public/api/likes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_dislike',
                video_id: videoId
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update with server response
            currentVideoLikes.likes = data.likes;
            currentVideoLikes.dislikes = data.dislikes;
            currentVideoLikes.userReaction = data.user_reaction;
            updateLikeButtons();
        } else {
            // Revert on error
            await loadLikeStats(videoId);
            showToast('Błąd zapisywania reakcji', 'error');
        }

    } catch (error) {
        console.error('Error toggling dislike:', error);
        await loadLikeStats(videoId);
        showToast('Błąd połączenia z serwerem', 'error');
    }
}

/**
 * Animate like button
 */
function animateLikeButton(button) {
    button.style.animation = 'heartbeat 0.5s';
    setTimeout(() => {
        button.style.animation = '';
    }, 500);
}

/**
 * Animate dislike button
 */
function animateDislikeButton(button) {
    button.style.animation = 'pulse 0.3s';
    setTimeout(() => {
        button.style.animation = '';
    }, 300);
}

/**
 * Format number for display (1000 -> 1K)
 */
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

/**
 * Export functions
 */
window.initLikeSystem = initLikeSystem;
window.toggleVideoLike = toggleVideoLike;
window.toggleVideoDislike = toggleVideoDislike;
