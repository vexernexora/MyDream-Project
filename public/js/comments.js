/**
 * Comments System - YouTube-like comments with reactions
 */

let currentVideoId = null;
let commentsSort = 'newest';
let commentsOffset = 0;
let commentsLimit = 20;
let hasMoreComments = true;
let loadingComments = false;

/**
 * Initialize comments system
 */
async function initComments(videoId) {
    currentVideoId = videoId;
    commentsOffset = 0;
    hasMoreComments = true;

    await loadComments();
    setupCommentsEvents();
    setupInfiniteScroll();
}

/**
 * Load comments for current video
 */
async function loadComments(append = false) {
    if (loadingComments || !hasMoreComments) return;

    loadingComments = true;

    try {
        showCommentsLoading();

        const response = await fetch('/public/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get',
                video_id: currentVideoId,
                sort: commentsSort,
                limit: commentsLimit,
                offset: commentsOffset
            })
        });

        const data = await response.json();

        if (data.success) {
            if (append) {
                appendComments(data.comments);
            } else {
                renderComments(data.comments);
            }

            updateCommentsCount(data.total);
            hasMoreComments = data.has_more;
            commentsOffset += data.comments.length;
        } else {
            showToast('Błąd ładowania komentarzy: ' + data.error, 'error');
        }

    } catch (error) {
        console.error('Error loading comments:', error);
        showToast('Błąd połączenia z serwerem', 'error');
    } finally {
        hideCommentsLoading();
        loadingComments = false;
    }
}

/**
 * Render comments in container
 */
function renderComments(comments) {
    const container = document.getElementById('commentsContainer');

    if (!container) return;

    if (comments.length === 0 && commentsOffset === 0) {
        container.innerHTML = `
            <div class="empty-state" style="text-align: center; padding: 40px 0; color: var(--text-secondary);">
                <svg class="mx-auto mb-4" width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p style="font-size: 16px;">Brak komentarzy</p>
                <p style="font-size: 14px; margin-top: 8px;">Bądź pierwszą osobą, która skomentuje!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = comments.map(comment => createCommentHTML(comment)).join('');
}

/**
 * Append comments to container
 */
function appendComments(comments) {
    const container = document.getElementById('commentsContainer');
    if (!container) return;

    const html = comments.map(comment => createCommentHTML(comment)).join('');
    container.insertAdjacentHTML('beforeend', html);
}

/**
 * Create HTML for comment
 */
function createCommentHTML(comment) {
    const avatarLetter = comment.username.charAt(0).toUpperCase();
    const avatarColor = getAvatarColor(comment.username);

    const isLiked = comment.user_reaction === 'like';
    const isDisliked = comment.user_reaction === 'dislike';

    return `
        <div class="comment-item" data-comment-id="${comment.id}">
            <div class="comment-avatar" style="background: ${avatarColor};">
                ${avatarLetter}
            </div>
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author">${escapeHtml(comment.username)}</span>
                    <span class="comment-date">${comment.time_ago}</span>
                    ${comment.is_edited ? '<span class="comment-edited" style="font-size: 11px; color: var(--text-tertiary);">(edytowany)</span>' : ''}
                    ${comment.is_pinned ? '<span class="badge badge-primary" style="margin-left: 8px;">Przypięty</span>' : ''}
                </div>
                <div class="comment-text">${escapeHtml(comment.text).replace(/\n/g, '<br>')}</div>
                <div class="comment-actions">
                    <button class="comment-action comment-like-btn ${isLiked ? 'active' : ''}" onclick="toggleCommentReaction('${comment.id}', 'like')">
                        <svg fill="${isLiked ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                        <span>${comment.likes > 0 ? comment.likes : ''}</span>
                    </button>
                    <button class="comment-action comment-dislike-btn ${isDisliked ? 'active' : ''}" onclick="toggleCommentReaction('${comment.id}', 'dislike')">
                        <svg fill="${isDisliked ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(180deg);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                    </button>
                    <button class="comment-action" onclick="showReplyForm('${comment.id}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span>Odpowiedz</span>
                    </button>
                    <button class="comment-action" onclick="editComment('${comment.id}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button class="comment-action" onclick="deleteComment('${comment.id}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                ${comment.replies_count > 0 ? `
                    <button class="comment-show-replies" onclick="toggleReplies('${comment.id}')" style="margin-top: 12px; display: flex; align-items: center; gap: 6px; background: transparent; border: none; color: var(--brand-blue); font-size: 13px; font-weight: 600; cursor: pointer; padding: 6px 0;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span>${comment.replies_count} ${comment.replies_count === 1 ? 'odpowiedź' : 'odpowiedzi'}</span>
                    </button>
                    <div class="comment-replies" id="replies-${comment.id}" style="display: none;"></div>
                ` : ''}
                <div class="reply-form-container" id="reply-form-${comment.id}" style="display: none; margin-top: 12px;"></div>
            </div>
        </div>
    `;
}

/**
 * Setup comment events
 */
function setupCommentsEvents() {
    // Comment form submit
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await submitComment();
        });
    }

    // Sort change
    const sortSelect = document.getElementById('commentsSort');
    if (sortSelect) {
        sortSelect.addEventListener('change', async (e) => {
            commentsSort = e.target.value;
            commentsOffset = 0;
            hasMoreComments = true;
            await loadComments();
        });
    }
}

/**
 * Setup infinite scroll for comments
 */
function setupInfiniteScroll() {
    const container = document.getElementById('commentsContainer');
    if (!container) return;

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMoreComments && !loadingComments) {
            loadComments(true);
        }
    }, { rootMargin: '200px' });

    // Create and observe sentinel element
    const sentinel = document.createElement('div');
    sentinel.id = 'comments-sentinel';
    sentinel.style.height = '1px';
    container.parentElement.appendChild(sentinel);
    observer.observe(sentinel);
}

/**
 * Submit new comment
 */
async function submitComment() {
    const textarea = document.getElementById('commentInput');
    const submitBtn = document.getElementById('commentSubmitBtn');
    const usernameInput = document.getElementById('commentUsername');

    if (!textarea || !submitBtn) return;

    const text = textarea.value.trim();
    const username = usernameInput ? usernameInput.value.trim() : 'Anonim';

    if (!text) {
        showToast('Komentarz nie może być pusty', 'warning');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Wysyłanie...';

    try {
        const response = await fetch('/public/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                video_id: currentVideoId,
                text: text,
                username: username || 'Anonim'
            })
        });

        const data = await response.json();

        if (data.success) {
            textarea.value = '';
            showToast('Komentarz dodany!', 'success');

            // Reload comments
            commentsOffset = 0;
            hasMoreComments = true;
            await loadComments();
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }

    } catch (error) {
        console.error('Error submitting comment:', error);
        showToast('Błąd wysyłania komentarza', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Komentuj';
    }
}

/**
 * Toggle comment reaction (like/dislike)
 */
async function toggleCommentReaction(commentId, reaction) {
    try {
        const response = await fetch('/public/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'react',
                comment_id: commentId,
                reaction: reaction
            })
        });

        const data = await response.json();

        if (data.success) {
            // Reload comments to update counts
            commentsOffset = 0;
            hasMoreComments = true;
            await loadComments();
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }

    } catch (error) {
        console.error('Error reacting to comment:', error);
        showToast('Błąd zapisywania reakcji', 'error');
    }
}

/**
 * Show reply form for comment
 */
function showReplyForm(commentId) {
    const container = document.getElementById(`reply-form-${commentId}`);
    if (!container) return;

    // Hide all other reply forms
    document.querySelectorAll('.reply-form-container').forEach(el => {
        if (el.id !== `reply-form-${commentId}`) {
            el.style.display = 'none';
            el.innerHTML = '';
        }
    });

    if (container.style.display === 'none') {
        container.style.display = 'block';
        container.innerHTML = `
            <div class="reply-form" style="background: var(--bg-tertiary); padding: 16px; border-radius: 8px;">
                <textarea
                    id="reply-input-${commentId}"
                    placeholder="Dodaj odpowiedź..."
                    class="comment-input"
                    style="min-height: 60px; margin-bottom: 12px;"
                ></textarea>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button onclick="hideReplyForm('${commentId}')" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                        Anuluj
                    </button>
                    <button onclick="submitReply('${commentId}')" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                        Odpowiedz
                    </button>
                </div>
            </div>
        `;

        // Focus textarea
        setTimeout(() => {
            document.getElementById(`reply-input-${commentId}`)?.focus();
        }, 100);
    } else {
        hideReplyForm(commentId);
    }
}

/**
 * Hide reply form
 */
function hideReplyForm(commentId) {
    const container = document.getElementById(`reply-form-${commentId}`);
    if (container) {
        container.style.display = 'none';
        container.innerHTML = '';
    }
}

/**
 * Submit reply to comment
 */
async function submitReply(parentId) {
    const textarea = document.getElementById(`reply-input-${parentId}`);
    if (!textarea) return;

    const text = textarea.value.trim();

    if (!text) {
        showToast('Odpowiedź nie może być pusta', 'warning');
        return;
    }

    try {
        const response = await fetch('/public/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'reply',
                parent_id: parentId,
                video_id: currentVideoId,
                text: text,
                username: 'Anonim'
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Odpowiedź dodana!', 'success');
            hideReplyForm(parentId);

            // Reload comments
            commentsOffset = 0;
            hasMoreComments = true;
            await loadComments();

            // Auto-expand replies
            setTimeout(() => {
                toggleReplies(parentId);
            }, 300);
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }

    } catch (error) {
        console.error('Error submitting reply:', error);
        showToast('Błąd wysyłania odpowiedzi', 'error');
    }
}

/**
 * Toggle showing replies for a comment
 */
async function toggleReplies(parentId) {
    const repliesContainer = document.getElementById(`replies-${parentId}`);
    if (!repliesContainer) return;

    if (repliesContainer.style.display === 'none') {
        // Load and show replies
        try {
            const response = await fetch('/public/api/comments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'get_replies',
                    parent_id: parentId
                })
            });

            const data = await response.json();

            if (data.success) {
                repliesContainer.innerHTML = data.replies.map(reply => createReplyHTML(reply)).join('');
                repliesContainer.style.display = 'block';
            } else {
                showToast('Błąd ładowania odpowiedzi', 'error');
            }

        } catch (error) {
            console.error('Error loading replies:', error);
            showToast('Błąd połączenia z serwerem', 'error');
        }
    } else {
        repliesContainer.style.display = 'none';
    }
}

/**
 * Create HTML for reply
 */
function createReplyHTML(reply) {
    const avatarLetter = reply.username.charAt(0).toUpperCase();
    const avatarColor = getAvatarColor(reply.username);

    const isLiked = reply.user_reaction === 'like';
    const isDisliked = reply.user_reaction === 'dislike';

    return `
        <div class="comment-item" data-comment-id="${reply.id}" style="margin-bottom: 12px;">
            <div class="comment-avatar" style="background: ${avatarColor}; width: 32px; height: 32px; font-size: 13px;">
                ${avatarLetter}
            </div>
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author">${escapeHtml(reply.username)}</span>
                    <span class="comment-date">${reply.time_ago}</span>
                    ${reply.is_edited ? '<span class="comment-edited" style="font-size: 11px; color: var(--text-tertiary);">(edytowany)</span>' : ''}
                </div>
                <div class="comment-text" style="font-size: 13px;">${escapeHtml(reply.text).replace(/\n/g, '<br>')}</div>
                <div class="comment-actions" style="margin-top: 8px;">
                    <button class="comment-action comment-like-btn ${isLiked ? 'active' : ''}" onclick="toggleCommentReaction('${reply.id}', 'like')">
                        <svg fill="${isLiked ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                        <span>${reply.likes > 0 ? reply.likes : ''}</span>
                    </button>
                    <button class="comment-action comment-dislike-btn ${isDisliked ? 'active' : ''}" onclick="toggleCommentReaction('${reply.id}', 'dislike')">
                        <svg fill="${isDisliked ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(180deg);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
}

/**
 * Edit comment
 */
async function editComment(commentId) {
    const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!commentItem) return;

    const commentText = commentItem.querySelector('.comment-text');
    const originalText = commentText.textContent;

    const newText = prompt('Edytuj komentarz:', originalText);

    if (newText === null || newText.trim() === originalText) {
        return;
    }

    try {
        const response = await fetch('/public/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'edit',
                comment_id: commentId,
                text: newText.trim()
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Komentarz zaktualizowany', 'success');

            // Reload comments
            commentsOffset = 0;
            hasMoreComments = true;
            await loadComments();
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }

    } catch (error) {
        console.error('Error editing comment:', error);
        showToast('Błąd edycji komentarza', 'error');
    }
}

/**
 * Delete comment
 */
async function deleteComment(commentId) {
    if (!confirm('Czy na pewno chcesz usunąć ten komentarz?')) {
        return;
    }

    try {
        const response = await fetch('/public/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete',
                comment_id: commentId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Komentarz usunięty', 'success');

            // Reload comments
            commentsOffset = 0;
            hasMoreComments = true;
            await loadComments();
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }

    } catch (error) {
        console.error('Error deleting comment:', error);
        showToast('Błąd usuwania komentarza', 'error');
    }
}

/**
 * Update comments count display
 */
function updateCommentsCount(count) {
    const countEl = document.getElementById('commentsCount');
    if (countEl) {
        countEl.textContent = `${count} ${count === 1 ? 'komentarz' : 'komentarzy'}`;
    }
}

/**
 * Show comments loading state
 */
function showCommentsLoading() {
    const container = document.getElementById('commentsContainer');
    if (!container || commentsOffset > 0) return;

    container.innerHTML = `
        <div class="comments-loading" style="display: flex; flex-direction: column; gap: 16px;">
            ${Array(3).fill('').map(() => `
                <div class="comment-skeleton" style="display: flex; gap: 12px;">
                    <div class="skeleton" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                    <div style="flex: 1;">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text" style="width: 60%;"></div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

/**
 * Hide comments loading state
 */
function hideCommentsLoading() {
    const loading = document.querySelector('.comments-loading');
    if (loading) {
        loading.remove();
    }
}

/**
 * Get consistent avatar color for username
 */
function getAvatarColor(username) {
    const colors = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%)',
    ];

    let hash = 0;
    for (let i = 0; i < username.length; i++) {
        hash = username.charCodeAt(i) + ((hash << 5) - hash);
    }

    return colors[Math.abs(hash) % colors.length];
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Export functions to window
 */
window.initComments = initComments;
window.toggleCommentReaction = toggleCommentReaction;
window.showReplyForm = showReplyForm;
window.hideReplyForm = hideReplyForm;
window.submitReply = submitReply;
window.toggleReplies = toggleReplies;
window.editComment = editComment;
window.deleteComment = deleteComment;
