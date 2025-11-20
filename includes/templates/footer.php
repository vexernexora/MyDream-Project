    </main>

    <!-- Footer -->
    <footer class="bg-dark-secondary border-t border-dark-border mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Info -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Moja Biblioteka Wideo</h3>
                    <p class="text-dark-textSecondary text-sm">
                        Prywatna, lokalna aplikacja do zarządzania i odtwarzania filmów offline.
                    </p>
                </div>

                <!-- Stats -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Statystyki</h3>
                    <p class="text-dark-textSecondary text-sm">
                        Filmów w bibliotece: <span class="text-dark-text font-semibold" id="totalVideos">0</span>
                    </p>
                </div>

                <!-- Tech -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Technologie</h3>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 bg-dark-tertiary rounded text-xs">PHP 8+</span>
                        <span class="px-2 py-1 bg-dark-tertiary rounded text-xs">TailwindCSS</span>
                        <span class="px-2 py-1 bg-dark-tertiary rounded text-xs">FFmpeg (opcjonalnie)</span>
                        <span class="px-2 py-1 bg-dark-tertiary rounded text-xs">JSON Database</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-dark-border text-center text-dark-textSecondary text-sm">
                <p>&copy; <?= date('Y') ?> Offline Video App. Wszystkie prawa zastrzeżone.</p>
            </div>
        </div>
    </footer>

    <!-- Loading Modal -->
    <div id="loadingModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
        <div class="bg-dark-secondary rounded-lg p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-red-600 mb-4"></div>
                <h3 class="text-xl font-bold mb-2" id="loadingTitle">Przetwarzanie...</h3>
                <p class="text-dark-textSecondary" id="loadingMessage">Proszę czekać</p>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="hidden fixed bottom-8 right-8 bg-dark-secondary border border-dark-border rounded-lg shadow-lg p-4 max-w-sm z-50">
        <div class="flex items-start space-x-3">
            <div id="toastIcon" class="flex-shrink-0"></div>
            <div class="flex-1">
                <p id="toastMessage" class="text-sm"></p>
            </div>
            <button onclick="hideToast()" class="flex-shrink-0 text-dark-textSecondary hover:text-dark-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Main JavaScript -->
    <script src="/public/js/app.js"></script>
</body>
</html>
