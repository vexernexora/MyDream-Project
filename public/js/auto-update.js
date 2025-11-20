/**
 * Automatyczna aktualizacja projektu z GitHub
 */

class AutoUpdater {
    constructor() {
        this.checkInterval = 5 * 60 * 1000; // 5 minut
        this.intervalId = null;
        this.isChecking = false;
        this.isUpdating = false;
        this.lastCheck = null;
        this.updateAvailable = false;
        this.updateInfo = null;
    }

    /**
     * Inicjalizacja auto-updatera
     */
    init() {
        console.log('🔄 Auto-updater initialized');

        // Dodaj UI dla powiadomień
        this.createUI();

        // Sprawdź natychmiast przy starcie
        this.checkForUpdates();

        // Uruchom sprawdzanie cykliczne
        this.startAutoCheck();

        // Sprawdź przy przywróceniu focus strony
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.shouldCheckAgain()) {
                this.checkForUpdates();
            }
        });
    }

    /**
     * Tworzy UI dla powiadomień o aktualizacjach
     */
    createUI() {
        // Container na powiadomienie o aktualizacji (na górze strony)
        const updateBanner = document.createElement('div');
        updateBanner.id = 'update-banner';
        updateBanner.className = 'hidden fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg';
        updateBanner.innerHTML = `
            <div class="max-w-7xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <svg class="w-6 h-6 flex-shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold" id="update-message">Dostępna jest nowa aktualizacja!</p>
                            <p class="text-sm opacity-90 truncate" id="update-details"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button onclick="autoUpdater.showUpdateDetails()" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-medium transition">
                            Szczegóły
                        </button>
                        <button onclick="autoUpdater.performUpdate()" class="px-4 py-2 bg-white text-blue-700 hover:bg-blue-50 rounded-lg text-sm font-medium transition">
                            Aktualizuj teraz
                        </button>
                        <button onclick="autoUpdater.dismissUpdate()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertBefore(updateBanner, document.body.firstChild);

        // Modal ze szczegółami aktualizacji
        const updateModal = document.createElement('div');
        updateModal.id = 'update-modal';
        updateModal.className = 'hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-75';
        updateModal.innerHTML = `
            <div class="bg-dark-card rounded-lg max-w-2xl w-full max-h-[80vh] overflow-hidden border border-dark-border shadow-2xl">
                <div class="p-6 border-b border-dark-border">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-dark-text flex items-center gap-2">
                            <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Dostępna Aktualizacja
                        </h2>
                        <button onclick="autoUpdater.hideUpdateDetails()" class="p-2 hover:bg-dark-bg rounded-lg transition">
                            <svg class="w-6 h-6 text-dark-textSecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(80vh-180px)]" id="update-modal-content">
                    <!-- Content will be populated dynamically -->
                </div>
                <div class="p-6 border-t border-dark-border flex gap-3 justify-end">
                    <button onclick="autoUpdater.hideUpdateDetails()" class="px-6 py-2 bg-dark-bg hover:bg-dark-tertiary rounded-lg font-medium transition">
                        Później
                    </button>
                    <button onclick="autoUpdater.performUpdate()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition">
                        Aktualizuj Teraz
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(updateModal);

        // Dodaj przycisk sprawdzania aktualizacji w headerze (opcjonalnie)
        this.addUpdateButtonToHeader();
    }

    /**
     * Dodaje przycisk sprawdzania aktualizacji do headera
     */
    addUpdateButtonToHeader() {
        // Znajdź odpowiednie miejsce w headerze (możesz dostosować)
        const header = document.querySelector('header') || document.querySelector('nav');
        if (!header) return;

        const updateBtn = document.createElement('button');
        updateBtn.id = 'header-update-btn';
        updateBtn.className = 'p-2 hover:bg-dark-tertiary rounded-lg transition relative';
        updateBtn.title = 'Sprawdź aktualizacje';
        updateBtn.onclick = () => this.checkForUpdates(true);
        updateBtn.innerHTML = `
            <svg class="w-6 h-6 text-dark-textSecondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span id="update-badge" class="hidden absolute -top-1 -right-1 w-3 h-3 bg-blue-600 rounded-full animate-pulse"></span>
        `;

        // Dodaj do headera (dostosuj selector do swojego layoutu)
        const headerContainer = header.querySelector('.flex') || header;
        headerContainer.appendChild(updateBtn);
    }

    /**
     * Rozpoczyna automatyczne sprawdzanie
     */
    startAutoCheck() {
        this.intervalId = setInterval(() => {
            if (!this.isChecking && !this.isUpdating) {
                this.checkForUpdates();
            }
        }, this.checkInterval);
    }

    /**
     * Zatrzymuje automatyczne sprawdzanie
     */
    stopAutoCheck() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    /**
     * Sprawdza czy powinno sprawdzić ponownie
     */
    shouldCheckAgain() {
        if (!this.lastCheck) return true;
        const timeSinceLastCheck = Date.now() - this.lastCheck;
        return timeSinceLastCheck > this.checkInterval;
    }

    /**
     * Sprawdza czy są dostępne aktualizacje
     */
    async checkForUpdates(showMessage = false) {
        if (this.isChecking) return;

        this.isChecking = true;
        console.log('🔍 Checking for updates...');

        try {
            const response = await fetch('/public/api/auto-update.php?action=check');
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Błąd sprawdzania aktualizacji');
            }

            this.lastCheck = Date.now();
            this.updateAvailable = data.updates_available;
            this.updateInfo = data;

            if (data.updates_available) {
                console.log(`✨ Update available! ${data.commits_behind} commits behind`);
                this.showUpdateNotification();
            } else {
                console.log('✓ Already up to date');
                if (showMessage) {
                    this.showToast('Projekt jest aktualny!', 'success');
                }
            }

        } catch (error) {
            console.error('❌ Error checking for updates:', error);
            if (showMessage) {
                this.showToast('Błąd sprawdzania aktualizacji: ' + error.message, 'error');
            }
        } finally {
            this.isChecking = false;
        }
    }

    /**
     * Pokazuje powiadomienie o dostępnej aktualizacji
     */
    showUpdateNotification() {
        const banner = document.getElementById('update-banner');
        const badge = document.getElementById('update-badge');
        const message = document.getElementById('update-message');
        const details = document.getElementById('update-details');

        if (banner) {
            banner.classList.remove('hidden');

            const commits = this.updateInfo.commits_behind;
            message.textContent = commits === 1
                ? 'Dostępna jest 1 nowa aktualizacja!'
                : `Dostępne są ${commits} nowe aktualizacje!`;

            if (this.updateInfo.commit_messages && this.updateInfo.commit_messages.length > 0) {
                details.textContent = this.updateInfo.commit_messages[0].message;
            }
        }

        if (badge) {
            badge.classList.remove('hidden');
        }
    }

    /**
     * Ukrywa powiadomienie o aktualizacji
     */
    dismissUpdate() {
        const banner = document.getElementById('update-banner');
        if (banner) {
            banner.classList.add('hidden');
        }
    }

    /**
     * Pokazuje szczegóły aktualizacji
     */
    showUpdateDetails() {
        const modal = document.getElementById('update-modal');
        const content = document.getElementById('update-modal-content');

        if (!this.updateInfo) return;

        let html = `
            <div class="space-y-4">
                <div class="bg-dark-bg rounded-lg p-4">
                    <h3 class="font-semibold text-dark-text mb-2">Informacje o aktualizacji</h3>
                    <div class="space-y-2 text-sm text-dark-textSecondary">
                        <p>Gałąź: <span class="text-dark-text font-mono">${this.updateInfo.current_branch}</span></p>
                        <p>Aktualny commit: <span class="text-dark-text font-mono">${this.updateInfo.local_commit}</span></p>
                        <p>Nowy commit: <span class="text-dark-text font-mono">${this.updateInfo.remote_commit}</span></p>
                        <p>Nowych zmian: <span class="text-blue-500 font-semibold">${this.updateInfo.commits_behind}</span></p>
                    </div>
                </div>

                ${this.updateInfo.commit_messages && this.updateInfo.commit_messages.length > 0 ? `
                    <div>
                        <h3 class="font-semibold text-dark-text mb-3">Najnowsze zmiany:</h3>
                        <div class="space-y-2">
                            ${this.updateInfo.commit_messages.map(commit => `
                                <div class="bg-dark-bg rounded-lg p-3 border-l-4 border-blue-600">
                                    <div class="flex items-start gap-3">
                                        <code class="text-xs text-blue-500 font-mono mt-1">${commit.hash}</code>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-dark-text">${this.escapeHtml(commit.message)}</p>
                                            <p class="text-xs text-dark-textSecondary mt-1">${commit.time}</p>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        content.innerHTML = html;
        modal.classList.remove('hidden');
    }

    /**
     * Ukrywa szczegóły aktualizacji
     */
    hideUpdateDetails() {
        const modal = document.getElementById('update-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Wykonuje aktualizację
     */
    async performUpdate() {
        if (this.isUpdating) return;

        this.isUpdating = true;
        this.hideUpdateDetails();
        this.dismissUpdate();

        this.showLoading('Aktualizacja w toku', 'Pobieranie najnowszych zmian z GitHub...');

        try {
            const response = await fetch('/public/api/auto-update.php?action=update', {
                method: 'POST'
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Błąd aktualizacji');
            }

            this.hideLoading();

            if (data.updated) {
                // Pokaż sukces i przeładuj stronę
                this.showToast('✅ ' + data.message, 'success');

                // Poczekaj chwilę i przeładuj
                setTimeout(() => {
                    console.log('🔄 Reloading page after update...');
                    window.location.reload();
                }, 2000);
            } else {
                this.showToast('ℹ️ ' + data.message, 'info');
            }

            this.updateAvailable = false;

        } catch (error) {
            this.hideLoading();
            console.error('❌ Error performing update:', error);
            this.showToast('Błąd aktualizacji: ' + error.message, 'error');
        } finally {
            this.isUpdating = false;
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Pokazuje toast (używa globalnej funkcji jeśli istnieje)
     */
    showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Pokazuje loading (używa globalnej funkcji jeśli istnieje)
     */
    showLoading(title, message) {
        if (typeof window.showLoading === 'function') {
            window.showLoading(title, message);
        }
    }

    /**
     * Ukrywa loading (używa globalnej funkcji jeśli istnieje)
     */
    hideLoading() {
        if (typeof window.hideLoading === 'function') {
            window.hideLoading();
        }
    }
}

// Inicjalizacja przy załadowaniu strony
let autoUpdater;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        autoUpdater = new AutoUpdater();
        autoUpdater.init();
    });
} else {
    autoUpdater = new AutoUpdater();
    autoUpdater.init();
}
