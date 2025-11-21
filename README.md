# 🎬 MyDream Video App

**Profesjonalna aplikacja do zarządzania filmami z systemem importu z Mega.nz i URL**

Elegancka aplikacja w stylu YouTube z zaawansowanym systemem importu. Automatycznie pobiera filmy z Mega.nz i URL w tle (background worker), generuje metadata z AI, pozwala wygodnie przeglądać i odtwarzać swoją kolekcję. Auto-update z GitHub.

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?logo=tailwind-css)

## ✨ Główne funkcje

### 📥 Import i Pobieranie
- **Import z Mega.nz** - automatyczne pobieranie w tle przez worker
- **Import z URL** - dowolne bezpośrednie linki do filmów
- **Upload plików** - przez przeglądarkę (drag & drop)
- **Background Worker** - asynchroniczne pobieranie, działa w screen/nohup
- **Queue System** - kolejkowanie i monitoring pobierań

### 🎥 Zarządzanie filmami
- **Automatyczne skanowanie** folderu z filmami
- **AI Metadata** - tytuły, opisy i tagi generowane automatycznie
- **FFmpeg Metadata** - rozdzielczość, długość, codec, FPS
- **Automatyczne miniatury** - FFmpeg z fallback do placeholder
- **Edycja danych** - ręczna modyfikacja tytułów, opisów i tagów
- **Inteligentne tagowanie** - automatyczne wykrywanie słów kluczowych

### 🔍 Wyszukiwanie i filtrowanie
- **Wyszukiwarka** po tytułach, opisach i tagach
- **Filtrowanie po tagach** - możliwość wyboru wielu tagów jednocześnie
- **Sortowanie** - po dacie, tytule, czasie trwania
- **Responsywna siatka** filmów z podglądem

### 🎬 Odtwarzacz wideo
- **Streaming z Range Requests** - pełna obsługa seekowania
- **Keyboard Shortcuts** - jak na YouTube (J/K/L, Space, strzałki)
- **Theater Mode** - rozszerzony widok
- **Picture-in-Picture** - mini player podczas pracy
- **Playback Speed** - 0.25x do 2x
- **Cinema Mode** - zgaś światła
- **Screenshot** - zrób zrzut z filmu
- **Stats for Nerds** - szczegółowe statystyki
- **Podobne filmy** - rekomendacje na podstawie tagów
- **Historia i kontynuacja** - wróć dokładnie tam gdzie skończyłeś

### 🎨 Interfejs
- **Dark mode** - elegancki ciemny motyw inspirowany YouTube
- **TailwindCSS** - nowoczesny, responsywny design
- **Animacje** - płynne przejścia i efekty hover
- **Mobile-friendly** - w pełni responsywny na telefonie
- **Sidebar menu** - wygodna nawigacja na mobile
- **Auto-Update Banner** - automatyczne sprawdzanie aktualizacji z GitHub co 5min
- **Ulubione & Watch Later** - organizacja biblioteki
- **Rating System** - oceń filmy gwiazdkami

## 📋 Wymagania

### Wymagane
- **PHP 8.0+** z rozszerzeniami:
  - `json`
  - `fileinfo`
- **Serwer WWW** - Apache/Nginx lub PHP built-in server

### Wymagane dla importu
- **megatools** - do pobierania z Mega.nz: `sudo apt install megatools`
- **wget lub curl** - do pobierania z URL (zwykle już zainstalowane)

### Opcjonalne
- **FFmpeg** - do generowania miniaturek i metadata: `sudo apt install ffmpeg`
- **screen** - do uruchamiania workera w sesji (opcjonalne, działa też bez)

## 🚀 Instalacja

### 1. Sklonuj projekt

```bash
git clone <repository-url>
cd MyDream-Project
```

### 2. (Opcjonalnie) Zainstaluj FFmpeg

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install ffmpeg
```

**macOS (Homebrew):**
```bash
brew install ffmpeg
```

**Windows:**
Pobierz z [ffmpeg.org](https://ffmpeg.org/download.html) i dodaj do PATH.

> **Uwaga:** FFmpeg jest opcjonalny! Aplikacja działa bez niego, tylko nie będzie generować miniaturek.

### 3. Uprawnienia

```bash
chmod -R 755 data/
chmod -R 755 videos/
chmod 644 data/videos.json
```

### 4. Dodaj filmy

Skopiuj swoje pliki wideo do folderu `videos/`:

```bash
cp /path/to/your/videos/* videos/
```

Obsługiwane formaty:
- MP4, MKV, AVI, MOV, WMV, FLV
- WebM, MPEG, MPG, M4V, 3GP

### 5. Uruchom download worker

**WAŻNE:** Worker musi działać aby import z Mega.nz i URL działał!

```bash
# Uruchom workera w tle
./workers/worker-control.sh start

# Sprawdź status
./workers/worker-control.sh status
```

### 6. Uruchom serwer

```bash
php -S localhost:8000
```

Otwórz: **http://localhost:8000/public/**

### 7. Pierwsze użycie

**Opcja A: Import z Mega.nz lub URL**
1. Otwórz aplikację
2. Kliknij zakładkę "Mega.nz" lub "URL"
3. Wklej link
4. Kliknij "Importuj"
5. Worker pobierze film w tle

**Opcja B: Skanowanie istniejących filmów**
1. Skopiuj filmy do `videos/`
2. Kliknij **"Skanuj folder"** w UI
3. Aplikacja automatycznie:
   - Zeskanuje folder `videos/`
   - Wygeneruje tytuły z nazw plików
   - Wykryje tagi ze słów kluczowych
   - Wygeneruje miniatury (jeśli FFmpeg dostępny)
   - Zapisze wszystko do `data/videos.json`

## 📁 Struktura projektu

```
MyDream-Project/
├── config.php             # Główna konfiguracja
├── watch.php              # Strona odtwarzacza
├── README.md              # Ten plik
├── USAGE.md               # Pełny przewodnik użytkownika
│
├── public/                # Aplikacja webowa
│   ├── index.php         # Strona główna z biblioteką
│   ├── stream.php        # Streaming endpoint (range requests)
│   ├── thumbnail.php     # Thumbnail serving endpoint
│   ├── js/
│   │   ├── app.js        # Główna logika UI
│   │   ├── import.js     # System importu
│   │   ├── auto-update.js # Auto-update z GitHub
│   │   ├── player-controls.js # Kontrolki odtwarzacza
│   │   └── useractions.js # Ulubione, historia, rating
│   └── api/              # REST API
│       ├── import-download.php  # Dodaj do kolejki pobierania
│       ├── auto-update.php      # System aktualizacji
│       ├── check-tools.php      # Sprawdź dostępne narzędzia
│       └── ...                  # Inne endpointy
│
├── workers/              # System workerów (background jobs)
│   ├── download-worker.sh       # Główny daemon
│   ├── worker-control.sh        # Zarządzanie (start/stop/status)
│   ├── add-video-helper.php     # Helper do dodawania do bazy
│   ├── README.md                # Pełna dokumentacja workerów
│   └── QUICKSTART-WORKERS.md    # Szybki start
│
├── data/                 # Dane aplikacji
│   ├── videos.json      # Baza filmów (JSON)
│   ├── thumbnails/      # Miniatury (generowane)
│   ├── queue/           # Kolejka zadań dla workera
│   ├── downloads/       # Status pobierania
│   └── logs/            # Logi workera
│       └── worker.log
│
├── videos/              # TUTAJ SĄ TWOJE FILMY
│
└── includes/
    ├── helpers/
    │   ├── AIHelper.php       # AI metadata z nazw plików
    │   ├── JsonHelper.php     # Zarządzanie JSON DB
    │   └── VideoHelper.php    # FFmpeg, miniatury, streaming
    └── templates/
        ├── header.php         # Header z nawigacją
        └── footer.php         # Footer
```

## 🎯 Użytkowanie

**📖 Pełna dokumentacja:** [USAGE.md](USAGE.md)

### Szybki Start

#### 1. Uruchom Worker
```bash
./workers/worker-control.sh start
```

#### 2. Dodawanie filmów

**Metoda A: Import z Mega.nz**
1. Kliknij zakładkę "Mega.nz" w UI
2. Wklej link (np. `https://mega.nz/file/...`)
3. Kliknij "Importuj"
4. Worker pobierze w tle

**Metoda B: Import z URL**
1. Kliknij zakładkę "URL"
2. Wklej bezpośredni link do filmu
3. Kliknij "Importuj"

**Metoda C: Skanowanie lokalnych plików**
1. Skopiuj pliki do folderu `videos/`
2. Kliknij ikonę ↻ "Skanuj folder"
3. Poczekaj na zakończenie skanowania

#### Wyszukiwanie filmów
- Wpisz frazę w pole wyszukiwania (górny pasek)
- Kliknij tagi aby filtrować (chips pod wyszukiwarką)
- Użyj sortowania w menu rozwijanym

#### Edycja filmu
1. Otwórz film (kliknij miniaturkę)
2. Kliknij "Edytuj"
3. Zmień tytuł, opis, tagi lub kategorię
4. Zapisz

#### Regenerowanie miniatury
- Na stronie filmu kliknij "Regeneruj miniaturę"
- Wymaga FFmpeg

### Zarządzanie Workerem

```bash
./workers/worker-control.sh start    # Uruchom
./workers/worker-control.sh stop     # Zatrzymaj
./workers/worker-control.sh restart  # Restart
./workers/worker-control.sh status   # Sprawdź status
./workers/worker-control.sh logs     # Zobacz logi na żywo
```

### Auto-Update

System automatycznie:
- Sprawdza aktualizacje z GitHub co 5 minut
- Pokazuje banner gdy dostępna nowa wersja
- Pozwala zaktualizować jednym klikiem

### Organizacja Biblioteki

- **❤️ Ulubione** - dodaj do ulubionych klikając serce
- **🕒 Watch Later** - dodaj do kolejki "obejrzyj później"
- **⭐ Rating** - oceń filmy gwiazdkami
- **📜 Historia** - system pamięta gdzie skończyłeś
- **🏷️ Tagi** - kliknij tag aby filtrować

## ⌨️ Skróty klawiszowe

**W odtwarzaczu:**
- `Space / K` - Play/Pause
- `J / L` - Przewiń -10s / +10s
- `← / →` - Przewiń -5s / +5s
- `↑ / ↓` - Głośność
- `M` - Wycisz
- `F` - Pełny ekran
- `T` - Theater mode
- `I` - Picture-in-Picture
- `C` - Cinema mode (zgaś światła)
- `S` - Screenshot
- `P` - Stats for nerds
- `U` - Udostępnij z timestampem
- `>` / `<` - Zwiększ/zmniejsz prędkość
- `R` - Reset prędkości (1x)
- `0-9` - Przeskocz do % filmu
- `?` - Pokaż wszystkie skróty

**Globalnie:**
- `Ctrl/Cmd + K` - Focus na wyszukiwarkę
- `Escape` - Zamknij modale

## 🔧 Konfiguracja

### Zmiana liczby filmów na stronę

W `config.php`:

```php
define('VIDEOS_PER_PAGE', 12); // Zmień na dowolną liczbę
```

### Dodanie nowych formatów wideo

W `config.php`:

```php
define('SUPPORTED_VIDEO_FORMATS', [
    'mp4', 'mkv', 'avi', 'mov', // ...
    'twoj_format' // Dodaj tutaj
]);
```

### Wyłączenie FFmpeg

Aplikacja automatycznie wykrywa czy FFmpeg jest dostępny. Jeśli `exec()` jest wyłączony w PHP lub FFmpeg nie jest zainstalowany - aplikacja używa domyślnych placeholderów dla miniaturek.

## 🔧 Rozwiązywanie problemów

### Worker nie działa

```bash
# Restart workera
./workers/worker-control.sh restart

# Zobacz błędy
tail -f data/logs/worker.log

# Sprawdź czy proces działa
./workers/worker-control.sh status
```

### Import z Mega.nz nie działa

```bash
# Sprawdź megatools
which megatools

# Zainstaluj jeśli brakuje
sudo apt install megatools

# Sprawdź logi workera
tail -f data/logs/worker.log
```

### FFmpeg nie działa

```bash
# Sprawdź czy FFmpeg jest zainstalowany
ffmpeg -version

# Zainstaluj jeśli brakuje
sudo apt install ffmpeg

# Sprawdź czy exec() nie jest wyłączony
php -r "echo function_exists('exec') ? 'OK' : 'DISABLED';"
```

### Miniatury się nie generują

- Sprawdź uprawnienia do `data/thumbnails/`
- Upewnij się, że FFmpeg działa (lub będzie używany placeholder)
- Sprawdź logi PHP

### Filmy się nie odtwarzają (ikona przerwany papier)

- Sprawdź czy `public/stream.php` istnieje
- Sprawdź uprawnienia: `chmod 755 videos/`
- Sprawdź czy plik istnieje: `ls -la videos/`
- Sprawdź console przeglądarki (F12) dla błędów
- Sprawdź czy format jest obsługiwany przez przeglądarkę

### Błąd "Permission denied"

```bash
# Nadaj odpowiednie uprawnienia
chmod -R 755 data/
chmod -R 755 videos/
```

### Błąd "exec() disabled"

To normalne na niektórych hostingach. Aplikacja automatycznie wykrywa to i działa bez FFmpeg (używa placeholderów dla miniaturek).

## 🎨 Dostosowanie

### Zmiana kolorów

Edytuj `includes/templates/header.php` - sekcja Tailwind Config:

```javascript
colors: {
    dark: {
        bg: '#0f0f0f',        // Główne tło
        secondary: '#1f1f1f', // Sekundarne tło
        tertiary: '#272727',  // Karty
        // ...
    }
}
```

## 🌟 Cechy techniczne

- **Background Workers** - Bash daemons z kolejkowaniem
- **Streaming z Range Requests** - pełna obsługa seekowania
- **Queue System** - asynchroniczne pobieranie
- **Auto-Update** - aktualizacje z GitHub
- **JSON database** - szybka, prosta, łatwa do backupu
- **Opcjonalny FFmpeg** - działa też bez niego!
- **AI Metadata** - ekstrahuje metadata z nazw plików
- **Modern UI** - TailwindCSS, YouTube style, responsywne
- **REST API** - czysta architektura
- **Zero frontend dependencies** - tylko PHP, TailwindCSS z CDN
- **Clean code** - komentarze, czytelne, modułowe

## 📝 Automatyczne tagowanie

Aplikacja rozpoznaje słowa kluczowe w nazwach plików:

**Przykłady:**
- `travel_vlog_2024.mp4` → Tagi: Vlog, Podróże
- `gaming_tutorial_fortnite.mp4` → Tagi: Gaming, Tutorial
- `music_concert_live.mkv` → Tagi: Muzyka, Live, Koncert
- `funny_cats_compilation.mp4` → Tagi: Śmieszne

**Rozpoznawane kategorie:**
- Gaming, Muzyka, Edukacja, Sport, Vlog, Film, Komedia

## 📊 System Kolejkowania

Proces pobierania:
1. UI dodaje link → tworzy `data/queue/JOB_ID.json`
2. Worker wykrywa zadanie → rozpoczyna pobieranie
3. Status aktualizowany: `queued` → `downloading` → `completed`
4. Worker dodaje film do bazy + generuje miniaturę
5. Film pojawia się w bibliotece

Monitorowanie:
```bash
# Status workera
./workers/worker-control.sh status

# Zadania w kolejce
ls -la data/queue/

# Status pobierania
cat data/downloads/*.json

# Logi na żywo
tail -f data/logs/worker.log
```

## 🚀 Zaawansowane

### API Endpoints

- `GET /public/stream.php?id=VIDEO_ID` - Stream wideo
- `GET /public/thumbnail.php?id=VIDEO_ID` - Miniatura
- `POST /public/api/import-download.php` - Dodaj do kolejki
- `GET /public/api/auto-update.php?action=check` - Sprawdź aktualizacje
- `GET /public/api/check-tools.php` - Sprawdź narzędzia

### Sprawdź Status Systemu

```bash
# Dostępne narzędzia
php -r "require 'public/api/check-tools.php';"

# Status workera
./workers/worker-control.sh status

# Auto-update
php -r "\$_GET['action']='check'; require 'public/api/auto-update.php';"
```

## 📚 Dodatkowa Dokumentacja

- **[USAGE.md](USAGE.md)** - Pełny przewodnik użytkownika
- **[QUICKSTART-WORKERS.md](QUICKSTART-WORKERS.md)** - Szybki start z workerami
- **[workers/README.md](workers/README.md)** - Szczegółowa dokumentacja workerów

## 📄 Licencja

Projekt prywatny.

---

**Ciesz się swoją biblioteką filmów! 🎬**
