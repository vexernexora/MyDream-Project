# 🎬 Offline Video App

**Prosta, lokalna aplikacja webowa do zarządzania i odtwarzania filmów offline**

Elegancka aplikacja w stylu YouTube, działająca całkowicie lokalnie bez potrzeby połączenia z internetem. Automatycznie wykrywa filmy w folderze, generuje metadata z nazw plików i pozwala wygodnie przeglądać i odtwarzać swoją kolekcję.

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?logo=tailwind-css)

## ✨ Główne funkcje

### 🎥 Zarządzanie filmami
- **Automatyczne skanowanie** folderu z filmami
- **Automatyczne metadata** - tytuły, opisy i tagi z nazw plików
- **Miniatury** - opcjonalnie z FFmpeg lub domyślne placeholdery
- **Edycja danych** - ręczna modyfikacja tytułów, opisów i tagów
- **Inteligentne tagowanie** - automatyczne wykrywanie słów kluczowych

### 🔍 Wyszukiwanie i filtrowanie
- **Wyszukiwarka** po tytułach, opisach i tagach
- **Filtrowanie po tagach** - możliwość wyboru wielu tagów jednocześnie
- **Sortowanie** - po dacie, tytule, czasie trwania
- **Responsywna siatka** filmów z podglądem

### 🎬 Odtwarzacz wideo
- **HTML5 Video Player** z pełną obsługą kontroli
- **Podobne filmy** - rekomendacje na podstawie tagów
- **Pełne informacje** o filmie i metadata techniczne
- **Edycja inline** - szybka edycja bez opuszczania strony

### 🎨 Interfejs
- **Dark mode** - elegancki ciemny motyw inspirowany YouTube
- **TailwindCSS** - nowoczesny, responsywny design
- **Animacje** - płynne przejścia i efekty hover
- **Mobile-friendly** - w pełni responsywny na telefonie
- **Sidebar menu** - wygodna nawigacja na mobile

## 📋 Wymagania

### Wymagane
- **PHP 8.0+** z rozszerzeniami:
  - `json`
  - `fileinfo`
- **Serwer WWW** - Apache/Nginx lub PHP built-in server

### Opcjonalne
- **FFmpeg** - do generowania miniaturek i metadata (aplikacja działa też bez tego!)

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

### 5. Uruchom serwer

```bash
php -S localhost:8000
```

Otwórz: **http://localhost:8000**

### 6. Pierwsze uruchomienie

1. Otwórz aplikację w przeglądarce
2. Kliknij **"Odśwież bibliotekę"** (ikona ↻ w prawym górnym rogu)
3. Aplikacja automatycznie:
   - Zeskanuje folder `videos/`
   - Wygeneruje tytuły z nazw plików
   - Wykryje tagi ze słów kluczowych
   - (Opcjonalnie) Wygeneruje miniatury jeśli FFmpeg dostępny
   - Zapisze wszystko do `data/videos.json`

## 📁 Struktura projektu

```
MyDream-Project/
├── index.php              # Strona główna z listą filmów
├── watch.php              # Odtwarzacz wideo
├── settings.php           # Panel ustawień
├── config.php             # Konfiguracja
│
├── data/
│   ├── videos.json       # Baza danych filmów
│   ├── thumbnails/       # Wygenerowane miniatury
│   └── cache/            # Cache
│
├── videos/               # TUTAJ WRZUĆ SWOJE FILMY
│
├── includes/
│   ├── helpers/
│   │   ├── AIHelper.php      # Generowanie metadata z nazw plików
│   │   ├── JsonHelper.php    # Zarządzanie JSON
│   │   └── VideoHelper.php   # Obsługa FFmpeg, miniatury
│   │
│   └── templates/
│       ├── header.php    # Header z nawigacją
│       └── footer.php    # Footer
│
└── public/
    ├── js/app.js         # Główny JavaScript
    └── api/              # REST API
```

## 🎯 Użytkowanie

### Podstawowe operacje

#### Dodawanie nowych filmów
1. Skopiuj pliki do folderu `videos/`
2. Kliknij ikonę ↻ "Odśwież bibliotekę"
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

### Panel ustawień

**Statystyki:**
- Liczba filmów
- Całkowity rozmiar
- Łączny czas wszystkich filmów
- Lista tagów

**Zarządzanie:**
- Skanuj bibliotekę
- Regeneruj wszystkie miniatury
- Eksportuj dane (backup JSON)
- Wyczyść cache

**Strefa niebezpieczna:**
- Reset bazy danych (zachowuje pliki wideo)
- Usuń wszystkie miniatury

## ⌨️ Skróty klawiszowe

- `Ctrl/Cmd + K` - Focus na wyszukiwarkę
- `Spacja` - Play/Pause wideo (na stronie watch)
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

### FFmpeg nie działa

```bash
# Sprawdź czy FFmpeg jest zainstalowany
ffmpeg -version

# Sprawdź czy exec() nie jest wyłączony
php -r "echo function_exists('exec') ? 'OK' : 'DISABLED';"
```

### Miniatury się nie generują

- Sprawdź uprawnienia do `data/thumbnails/`
- Upewnij się, że FFmpeg działa (lub będzie używany placeholder)
- Sprawdź logi PHP

### Filmy się nie odtwarzają

- Sprawdź czy format jest obsługiwany
- Upewnij się, że przeglądarka wspiera codec
- Sprawdź ścieżkę do pliku w konsoli przeglądarki

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

- **Zero dependencies** - tylko PHP, TailwindCSS z CDN
- **JSON database** - szybka, prosta, łatwa do backupu
- **Opcjonalny FFmpeg** - działa też bez niego!
- **Proste tagowanie** - ekstrahuje słowa kluczowe z nazw plików
- **Modern UI** - TailwindCSS, YouTube style, responsywne
- **REST API** - czysta architektura
- **Clean code** - komentarze, czytelne

## 📝 Automatyczne tagowanie

Aplikacja rozpoznaje słowa kluczowe w nazwach plików:

**Przykłady:**
- `travel_vlog_2024.mp4` → Tagi: Vlog, Podróże
- `gaming_tutorial_fortnite.mp4` → Tagi: Gaming, Tutorial
- `music_concert_live.mkv` → Tagi: Muzyka, Live, Koncert
- `funny_cats_compilation.mp4` → Tagi: Śmieszne

**Rozpoznawane kategorie:**
- Gaming, Muzyka, Edukacja, Sport, Vlog, Film, Komedia

## 📄 Licencja

Projekt open-source - możesz swobodnie używać, modyfikować i dystrybuować.

---

**Enjoy your private video library! 🎬**
