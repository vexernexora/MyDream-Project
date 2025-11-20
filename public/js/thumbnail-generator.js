/**
 * Automatyczne generowanie miniaturek z losowego momentu filmu
 * Działa po stronie klienta (bez FFmpeg)
 */

class ThumbnailGenerator {
    constructor() {
        this.queue = [];
        this.processing = false;
        this.canvas = document.createElement('canvas');
        this.video = document.createElement('video');
        this.video.crossOrigin = 'anonymous';
        this.video.muted = true;
        this.video.playsInline = true;
        this.video.preload = 'metadata';

        // Ukryj elementy
        this.canvas.style.display = 'none';
        this.video.style.display = 'none';
        document.body.appendChild(this.canvas);
        document.body.appendChild(this.video);
    }

    /**
     * Dodaj filmy do kolejki generowania
     */
    addVideos(videos) {
        videos.forEach(video => {
            // Dodaj tylko te bez miniaturki
            if (!video.thumbnail_generated) {
                this.queue.push(video);
            }
        });

        if (!this.processing) {
            this.processNext();
        }
    }

    /**
     * Przetwórz następny film w kolejce
     */
    async processNext() {
        if (this.queue.length === 0) {
            this.processing = false;
            console.log('[ThumbnailGen] Wszystkie miniatury wygenerowane');
            return;
        }

        this.processing = true;
        const video = this.queue.shift();

        console.log(`[ThumbnailGen] Generowanie miniatury dla: ${video.title}`);

        try {
            await this.generateThumbnail(video);
            console.log(`[ThumbnailGen] ✓ ${video.title}`);
        } catch (error) {
            console.error(`[ThumbnailGen] ✗ ${video.title}:`, error.message);
        }

        // Poczekaj chwilę przed następnym (żeby nie blokować przeglądarki)
        setTimeout(() => this.processNext(), 500);
    }

    /**
     * Wygeneruj miniaturkę dla filmu
     */
    async generateThumbnail(videoData) {
        return new Promise((resolve, reject) => {
            const videoUrl = `/videos/${videoData.relative_path}`;

            // Reset
            this.video.src = '';
            this.video.currentTime = 0;

            const timeoutId = setTimeout(() => {
                this.cleanup();
                reject(new Error('Timeout'));
            }, 15000); // 15 sekund timeout

            // Event listeners
            const onLoadedMetadata = () => {
                const duration = this.video.duration;

                // Losowy moment między 10% a 30% długości filmu
                // (pomija intro i outro)
                const minTime = Math.max(2, duration * 0.1);
                const maxTime = Math.min(duration * 0.3, duration - 2);
                const randomTime = minTime + Math.random() * (maxTime - minTime);

                this.video.currentTime = randomTime;
            };

            const onSeeked = async () => {
                try {
                    // Ustaw rozmiar canvas
                    this.canvas.width = this.video.videoWidth;
                    this.canvas.height = this.video.videoHeight;

                    // Rysuj klatkę
                    const ctx = this.canvas.getContext('2d');
                    ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

                    // Konwertuj do base64
                    const imageData = this.canvas.toDataURL('image/jpeg', 0.85);

                    // Wyślij do serwera
                    await this.saveThumbnail(videoData.id, imageData);

                    clearTimeout(timeoutId);
                    this.cleanup();
                    resolve();
                } catch (error) {
                    clearTimeout(timeoutId);
                    this.cleanup();
                    reject(error);
                }
            };

            const onError = (error) => {
                clearTimeout(timeoutId);
                this.cleanup();
                reject(new Error('Błąd ładowania wideo'));
            };

            this.video.addEventListener('loadedmetadata', onLoadedMetadata, { once: true });
            this.video.addEventListener('seeked', onSeeked, { once: true });
            this.video.addEventListener('error', onError, { once: true });

            // Załaduj wideo
            this.video.src = videoUrl;
            this.video.load();
        });
    }

    /**
     * Wyślij miniaturkę do serwera
     */
    async saveThumbnail(videoId, imageData) {
        const response = await fetch('/public/api/save-thumbnail.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                video_id: videoId,
                image_data: imageData
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Nie udało się zapisać miniatury');
        }

        // Zaktualizuj miniaturkę na stronie
        this.updateThumbnailOnPage(videoId, data.thumbnail_url);
    }

    /**
     * Zaktualizuj miniaturkę na stronie bez przeładowania
     */
    updateThumbnailOnPage(videoId, thumbnailUrl) {
        const card = document.querySelector(`[data-video-id="${videoId}"]`);
        if (card) {
            const img = card.querySelector('img');
            if (img) {
                // Dodaj timestamp żeby wymusić reload
                img.src = thumbnailUrl + '?t=' + Date.now();
            }
        }
    }

    /**
     * Wyczyść event listenery
     */
    cleanup() {
        this.video.pause();
        this.video.removeAttribute('src');
        this.video.load();
    }
}

// Globalna instancja
window.thumbnailGenerator = new ThumbnailGenerator();

// Auto-start przy załadowaniu strony
if (typeof window.videos !== 'undefined' && window.videos.length > 0) {
    console.log('[ThumbnailGen] Rozpoczynam generowanie miniaturek...');
    window.thumbnailGenerator.addVideos(window.videos);
}
