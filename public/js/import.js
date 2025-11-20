/**
 * Import filmów - Upload i pobieranie z zewnętrznych źródeł
 */

// ============================================
// TAB SWITCHING
// ============================================

function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));

    // Reset all tab buttons
    document.querySelectorAll('[id^="tab-"]').forEach(btn => {
        btn.classList.remove('border-red-600', 'text-red-500');
        btn.classList.add('border-transparent', 'text-dark-textSecondary');
    });

    // Show selected tab
    document.getElementById(`content-${tab}`).classList.remove('hidden');

    // Activate tab button
    const activeBtn = document.getElementById(`tab-${tab}`);
    activeBtn.classList.add('border-red-600', 'text-red-500');
    activeBtn.classList.remove('border-transparent', 'text-dark-textSecondary');
}

// ============================================
// FILE UPLOAD - Drag & Drop
// ============================================

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadQueue = document.getElementById('uploadQueue');

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight drop zone when item is dragged over it
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.add('border-red-600', 'bg-red-900', 'bg-opacity-10');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.remove('border-red-600', 'bg-red-900', 'bg-opacity-10');
    }, false);
});

// Handle dropped files
dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}, false);

// Handle file input
fileInput.addEventListener('change', (e) => {
    handleFiles(e.target.files);
});

function handleFiles(files) {
    if (files.length === 0) return;

    uploadQueue.classList.remove('hidden');

    Array.from(files).forEach(file => {
        if (!file.type.startsWith('video/')) {
            showToast(`${file.name} nie jest plikiem wideo`, 'error');
            return;
        }

        uploadFile(file);
    });
}

function uploadFile(file) {
    const uploadId = 'upload-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

    // Create upload item UI
    const uploadItem = document.createElement('div');
    uploadItem.id = uploadId;
    uploadItem.className = 'bg-dark-bg rounded-lg p-4 border border-dark-border';
    uploadItem.innerHTML = `
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-red-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-dark-text truncate">${file.name}</p>
                <p class="text-sm text-dark-textSecondary">${formatFileSize(file.size)}</p>

                <div class="mt-3">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="upload-status text-dark-textSecondary">Przygotowanie...</span>
                        <span class="upload-percent text-dark-textSecondary">0%</span>
                    </div>
                    <div class="w-full bg-dark-border rounded-full h-2">
                        <div class="upload-progress bg-red-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <button onclick="cancelUpload('${uploadId}')" class="text-dark-textSecondary hover:text-red-500 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    `;

    uploadQueue.appendChild(uploadItem);

    // Start upload
    const formData = new FormData();
    formData.append('video', file);

    const xhr = new XMLHttpRequest();

    // Track upload progress
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            updateUploadProgress(uploadId, percent, 'Wysyłanie...');
        }
    });

    // Upload complete
    xhr.addEventListener('load', () => {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateUploadProgress(uploadId, 100, 'Zakończono!');
                    setTimeout(() => {
                        uploadItem.classList.add('opacity-50');
                    }, 1000);
                    showToast(`${file.name} został dodany!`, 'success');
                } else {
                    updateUploadProgress(uploadId, 0, 'Błąd: ' + response.error);
                    uploadItem.classList.add('border-red-600');
                }
            } catch (e) {
                updateUploadProgress(uploadId, 0, 'Błąd parsowania odpowiedzi');
                uploadItem.classList.add('border-red-600');
            }
        } else {
            updateUploadProgress(uploadId, 0, 'Błąd serwera: ' + xhr.status);
            uploadItem.classList.add('border-red-600');
        }
    });

    // Upload error
    xhr.addEventListener('error', () => {
        updateUploadProgress(uploadId, 0, 'Błąd połączenia');
        uploadItem.classList.add('border-red-600');
    });

    // Start request
    xhr.open('POST', '/public/api/import-upload.php');
    xhr.send(formData);

    // Store XHR for cancellation
    uploadItem.dataset.xhr = xhr;
}

function updateUploadProgress(uploadId, percent, status) {
    const item = document.getElementById(uploadId);
    if (!item) return;

    item.querySelector('.upload-progress').style.width = percent + '%';
    item.querySelector('.upload-percent').textContent = percent + '%';
    item.querySelector('.upload-status').textContent = status;
}

function cancelUpload(uploadId) {
    const item = document.getElementById(uploadId);
    if (!item) return;

    // Cancel XHR if exists
    if (item.dataset.xhr) {
        item.dataset.xhr.abort();
    }

    item.remove();

    // Hide queue if empty
    if (uploadQueue.children.length === 0) {
        uploadQueue.classList.add('hidden');
    }
}

// ============================================
// MEGA.NZ DOWNLOAD
// ============================================

document.getElementById('megaForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const url = document.getElementById('megaUrl').value.trim();
    const filename = document.getElementById('megaFilename').value.trim();

    if (!url) {
        showToast('Podaj link do Mega.nz', 'error');
        return;
    }

    if (!url.includes('mega.nz')) {
        showToast('To nie jest poprawny link Mega.nz', 'error');
        return;
    }

    startDownload('mega', url, filename);
});

// ============================================
// URL DOWNLOAD
// ============================================

document.getElementById('urlForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const url = document.getElementById('videoUrl').value.trim();
    const filename = document.getElementById('urlFilename').value.trim();

    if (!url) {
        showToast('Podaj link URL', 'error');
        return;
    }

    if (!isValidVideoUrl(url)) {
        showToast('Link nie prowadzi do pliku wideo', 'error');
        return;
    }

    startDownload('url', url, filename);
});

function isValidVideoUrl(url) {
    const videoExtensions = ['mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp'];
    const urlLower = url.toLowerCase();
    return videoExtensions.some(ext => urlLower.includes('.' + ext));
}

// ============================================
// DOWNLOAD MANAGEMENT
// ============================================

async function startDownload(type, url, filename) {
    showLoading('Rozpoczynanie pobierania', 'Przygotowanie...');

    try {
        const response = await fetch('/public/api/import-download.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: type,
                url: url,
                filename: filename || null
            })
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showToast('Pobieranie rozpoczęte!', 'success');

            // Show download progress section
            document.getElementById('downloadProgress').classList.remove('hidden');

            // Add download to list
            addDownloadItem(data.download_id, data.filename || 'Pobieranie...', type);

            // Start polling for progress
            pollDownloadProgress(data.download_id);

            // Clear forms
            if (type === 'mega') {
                document.getElementById('megaUrl').value = '';
                document.getElementById('megaFilename').value = '';
            } else {
                document.getElementById('videoUrl').value = '';
                document.getElementById('urlFilename').value = '';
            }
        } else {
            showToast('Błąd: ' + data.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Błąd połączenia: ' + error.message, 'error');
    }
}

function addDownloadItem(downloadId, filename, type) {
    const downloadList = document.getElementById('downloadList');

    const typeIcon = type === 'mega'
        ? '<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>'
        : '<svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>';

    const item = document.createElement('div');
    item.id = 'download-' + downloadId;
    item.className = 'bg-dark-bg rounded-lg p-4 border border-dark-border';
    item.innerHTML = `
        <div class="flex items-start gap-3">
            ${typeIcon}
            <div class="flex-1 min-w-0">
                <p class="font-medium text-dark-text truncate">${filename}</p>
                <p class="text-sm text-dark-textSecondary download-source">${type === 'mega' ? 'Mega.nz' : 'URL'}</p>

                <div class="mt-3">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="download-status text-dark-textSecondary">Pobieranie...</span>
                        <span class="download-percent text-dark-textSecondary">0%</span>
                    </div>
                    <div class="w-full bg-dark-border rounded-full h-2">
                        <div class="download-progress bg-green-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    downloadList.appendChild(item);
}

function pollDownloadProgress(downloadId) {
    const interval = setInterval(async () => {
        try {
            const response = await fetch(`/public/api/import-progress.php?id=${downloadId}`);
            const data = await response.json();

            if (data.success) {
                updateDownloadProgress(downloadId, data.progress, data.status);

                if (data.completed || data.error) {
                    clearInterval(interval);

                    if (data.completed) {
                        showToast('Pobieranie zakończone!', 'success');
                        setTimeout(() => {
                            document.getElementById('download-' + downloadId).classList.add('opacity-50');
                        }, 2000);
                    } else if (data.error) {
                        showToast('Błąd pobierania: ' + data.error, 'error');
                        document.getElementById('download-' + downloadId).classList.add('border-red-600');
                    }
                }
            }
        } catch (error) {
            console.error('Error polling progress:', error);
        }
    }, 1000);
}

function updateDownloadProgress(downloadId, percent, status) {
    const item = document.getElementById('download-' + downloadId);
    if (!item) return;

    item.querySelector('.download-progress').style.width = percent + '%';
    item.querySelector('.download-percent').textContent = percent + '%';
    item.querySelector('.download-status').textContent = status;
}

// ============================================
// UTILITIES
// ============================================

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}

function showLoading(title, message) {
    if (typeof window.showLoading === 'function') {
        window.showLoading(title, message);
    }
}

function hideLoading() {
    if (typeof window.hideLoading === 'function') {
        window.hideLoading();
    }
}
