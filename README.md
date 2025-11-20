# 🎬 Offline Video App

**Zaawansowana lokalna aplikacja webowa do zarządzania i odtwarzania filmów offline**

Elegancka, w pełni funkcjonalna aplikacja w stylu YouTube, działająca całkowicie lokalnie bez potrzeby połączenia z internetem (po pierwszym załadowaniu). Wykorzystuje AI do automatycznego analizowania filmów, generowania tytułów, opisów, tagów i inteligentnego wyboru miniaturek.

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?logo=tailwind-css)
![FFmpeg](https://img.shields.io/badge/FFmpeg-Required-007808?logo=ffmpeg)

## ✨ Główne funkcje

### 🎥 Zarządzanie filmami
- **Automatyczne skanowanie** folderu z filmami
- **Generowanie miniaturek** z najbardziej interesujących momentów
- **AI analiza** - automatyczne tworzenie tytułów, opisów i tagów
- **Metadata** - czas trwania, rozdzielczość, codec, FPS
- **Edycja danych** - ręczna modyfikacja tytułów, opisów i tagów

### 🔍 Wyszukiwanie i filtrowanie
- **Wyszukiwarka** po tytułach, opisach i tagach
- **Filtrowanie po tagach** - możliwość wyboru wielu tagów jednocześnie
- **Sortowanie** - po dacie, tytule, czasie trwania
- **Responsywna siatka** filmów z podglądem

### 🎬 Odtwarzacz wideo
- **HTML5 Video Player** z pełną obsługą kontroli
- **Podobne filmy** - inteligentne rekomendacje na podstawie tagów
- **Pełne informacje** o filmie i metadata techniczne
- **Edycja inline** - szybka edycja bez opuszczania strony

### 🎨 Interfejs
- **Dark mode** - elegancki ciemny motyw inspirowany YouTube
- **TailwindCSS** - nowoczesny, responsywny design
- **Animacje** - płynne przejścia i efekty hover
- **Mobile-friendly** - w pełni responsywny

### 🤖 AI Integration
- **OpenAI GPT-4o-mini** - inteligentna analiza filmów
- **Automatyczne tagowanie** - rozpoznawanie typu treści
- **Kreatywne tytuły** - atrakcyjne nazwy w stylu YouTube
- **Kategoryzacja** - automatyczne przypisywanie kategorii

## 📋 Wymagania

### Wymagane
- **PHP 8.0+** z rozszerzeniami:
  - `json`
  - `curl`
  - `fileinfo`
- **FFmpeg** - do generowania miniaturek i analizy wideo
- **Serwer WWW** - Apache/Nginx lub PHP built-in server

### Opcjonalne
- **OpenAI API Key** - dla funkcji AI (działa też bez tego, z fallbackiem)

## 🚀 Instalacja

### 1. Klonowanie projektu

```bash
git clone <repository-url>
cd MyDream-Project
```

### 2. Instalacja zależności

#### Instalacja FFmpeg

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

Sprawdź instalację:
```bash
ffmpeg -version
```

### 3. Konfiguracja

#### Edytuj plik `config.php`

```php
// Ustaw klucz OpenAI API (opcjonalnie)
define('OPENAI_API_KEY', 'twój-klucz-api');

// Możesz też ustawić jako zmienną środowiskową
// export OPENAI_API_KEY='twój-klucz'
```

#### Uprawnienia

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

### 5. Uruchomienie serwera

#### Opcja A: PHP Built-in Server (najszybsze)

```bash
php -S localhost:8000 -t public
```

Otwórz: http://localhost:8000

#### Opcja B: Apache

1. Skonfiguruj Virtual Host:

```apache
<VirtualHost *:80>
    ServerName video-app.local
    DocumentRoot "/path/to/MyDream-Project/public"

    <Directory "/path/to/MyDream-Project/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. Dodaj do `/etc/hosts`:
```
127.0.0.1 video-app.local
```

3. Restart Apache:
```bash
sudo systemctl restart apache2
```

#### Opcja C: Nginx

```nginx
server {
    listen 80;
    server_name video-app.local;
    root /path/to/MyDream-Project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /videos/ {
        internal;
        alias /path/to/MyDream-Project/videos/;
    }
}
```

### 6. Pierwsze uruchomienie

1. Otwórz aplikację w przeglądarce
2. Kliknij **"Odśwież bibliotekę"** w górnym menu
3. Aplikacja automatycznie:
   - Zeskanuje folder `videos/`
   - Wygeneruje miniatury
   - Stworzy opisy i tagi (używając AI jeśli skonfigurowane)
   - Zapisze wszystko do `data/videos.json`

## 📁 Struktura projektu

```
MyDream-Project/
├── config.php                  # Konfiguracja główna
├── README.md                   # Ten plik
│
├── data/                       # Dane aplikacji
│   ├── videos.json            # Baza danych filmów
│   ├── thumbnails/            # Wygenerowane miniatury
│   └── cache/                 # Cache (opcjonalnie)
│
├── videos/                     # TUTAJ WRZUĆ SWOJE FILMY
│   └── (twoje pliki wideo)
│
├── includes/
│   ├── helpers/
│   │   ├── AIHelper.php       # Integracja z OpenAI
│   │   ├── JsonHelper.php     # Zarządzanie JSON
│   │   └── VideoHelper.php    # Obsługa FFmpeg, miniatury
│   │
│   └── templates/
│       ├── header.php         # Header z nawigacją
│       └── footer.php         # Footer
│
└── public/                     # Document root
    ├── index.php              # Strona główna z listą filmów
    ├── watch.php              # Odtwarzacz wideo
    ├── settings.php           # Panel ustawień
    │
    ├── js/
    │   └── app.js             # Główny JavaScript
    │
    └── api/                   # REST API
        ├── scan.php           # Skanowanie biblioteki
        ├── update-video.php   # Aktualizacja filmu
        ├── regenerate-thumbnail.php
        ├── delete-video.php
        ├── get-videos.php
        ├── reset-database.php
        └── delete-all-thumbnails.php
```

## 🎯 Użytkowanie

### Podstawowe operacje

#### Dodawanie nowych filmów
1. Skopiuj pliki do folderu `videos/`
2. Kliknij "Odśwież bibliotekę"
3. Poczekaj na zakończenie skanowania

#### Wyszukiwanie filmów
- Wpisz frazę w pole wyszukiwania
- Kliknij tagi aby filtrować
- Użyj sortowania w rozwijanej liście

#### Edycja filmu
1. Otwórz film (kliknij miniaturkę)
2. Kliknij "Edytuj"
3. Zmień tytuł, opis, tagi lub kategorię
4. Zapisz

#### Regenerowanie miniatury
- Na stronie filmu kliknij "Regeneruj miniaturę"
- Aplikacja wybierze nowy interesujący kadr

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

## 🤖 Konfiguracja AI

### Uzyskiwanie klucza OpenAI API

1. Zarejestruj się na [platform.openai.com](https://platform.openai.com)
2. Przejdź do API Keys
3. Stwórz nowy klucz
4. Dodaj do `config.php`:

```php
define('OPENAI_API_KEY', 'sk-...');
```

Lub ustaw jako zmienną środowiskową:

```bash
export OPENAI_API_KEY='sk-...'
```

### Bez klucza API

Aplikacja działa również **bez OpenAI API**! W takim przypadku:
- Tytuły generowane są z nazw plików
- Tagi ekstrahowane z nazw
- Opisy tworzone automatycznie

Jakość będzie niższa, ale aplikacja jest w pełni funkcjonalna.

## 🔧 Rozwiązywanie problemów

### FFmpeg nie działa

```bash
# Sprawdź czy FFmpeg jest zainstalowany
ffmpeg -version

# Sprawdź ścieżkę
which ffmpeg

# Jeśli nie działa, zainstaluj ponownie
```

### Miniatury się nie generują

- Sprawdź uprawnienia do `data/thumbnails/`
- Upewnij się, że FFmpeg działa
- Sprawdź logi PHP (włącz DEBUG_MODE w config.php)

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

### OpenAI API timeout

- Zwiększ timeout w `AIHelper.php`:
```php
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 sekund
```

## 🔒 Bezpieczeństwo

### Ważne uwagi

1. **Aplikacja jest przeznaczona do użytku lokalnego/prywatnego**
2. Nie wystawiaj jej publicznie bez odpowiedniego zabezpieczenia
3. Dodaj `.htaccess` lub nginx basic auth jeśli musisz
4. Klucz OpenAI API trzymaj w bezpiecznym miejscu

### Przykładowa ochrona .htaccess

```apache
AuthType Basic
AuthName "Video Library"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

## 🎨 Dostosowanie

### Zmiana kolorów

Edytuj `includes/templates/header.php` - sekcja Tailwind Config:

```javascript
colors: {
    dark: {
        bg: '#0f0f0f',        // Główne tło
        secondary: '#1a1a1a', // Sekundarne tło
        tertiary: '#272727',  // Karty
        // ...
    }
}
```

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

## 📝 TODO / Przyszłe funkcje

- [ ] Playlists
- [ ] Historia odtwarzania
- [ ] Ulubione filmy
- [ ] Statystyki oglądalności
- [ ] Import/Export bazy danych
- [ ] Batch operations (masowa edycja)
- [ ] Widok listy (alternatywa dla siatki)
- [ ] Dark/Light mode toggle
- [ ] PWA support (offline app)
- [ ] Multi-language support

## 📄 Licencja

Projekt open-source - możesz swobodnie używać, modyfikować i dystrybuować.

## 🤝 Wsparcie

Jeśli napotkasz problemy:

1. Sprawdź sekcję "Rozwiązywanie problemów"
2. Włącz DEBUG_MODE w `config.php`
3. Sprawdź logi PHP i błędy w konsoli przeglądarki
4. Upewnij się, że wszystkie wymagania są spełnione

## 🌟 Cechy techniczne

- **Zero dependencies** - tylko PHP, TailwindCSS z CDN
- **JSON database** - szybka, prosta, łatwa do backupu
- **FFmpeg integration** - profesjonalne przetwarzanie wideo
- **AI-powered** - inteligentna analiza treści
- **Modern UI** - TailwindCSS, responsywne, animacje
- **REST API** - czysta architektura
- **Clean code** - komentarze, PSR-12 style

---

**Enjoy your private video library! 🎬**
