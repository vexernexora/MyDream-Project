# MyDream Video App - Przewodnik Użytkownika

## 🚀 Szybki Start

### 1. Wymagania
Aplikacja wymaga następujących narzędzi:
- ✅ **PHP** (z włączonym `exec()`)
- ✅ **megatools** - do pobierania z Mega.nz
- ✅ **ffmpeg** - do miniatur i metadanych wideo
- ✅ **wget/curl** - do pobierania z URL
- ✅ **Git** - do auto-update

### 2. Uruchomienie Workera

Worker pobiera filmy w tle. Uruchom go na starcie:

```bash
# Start workera
./workers/worker-control.sh start

# Sprawdź status
./workers/worker-control.sh status

# Zobacz logi na żywo
./workers/worker-control.sh logs

# Restart
./workers/worker-control.sh restart

# Stop
./workers/worker-control.sh stop
```

**WAŻNE:** Worker musi działać aby pobierać filmy z Mega.nz i URL!

---

## 📥 Importowanie Filmów

### Metoda 1: Import z Mega.nz

1. Otwórz stronę główną aplikacji
2. Kliknij zakładkę **"Mega.nz"**
3. Wklej link Mega.nz (np. `https://mega.nz/file/...`)
4. Wprowadź nazwę pliku (opcjonalnie)
5. Kliknij **"Importuj"**

✅ Film zostanie dodany do kolejki i pobrany przez workera w tle

### Metoda 2: Import z URL

1. Otwórz stronę główną aplikacji
2. Kliknij zakładkę **"URL"**
3. Wklej bezpośredni link do filmu (np. `https://example.com/video.mp4`)
4. Wprowadź nazwę pliku
5. Kliknij **"Importuj"**

### Metoda 3: Upload Pliku

1. Kliknij zakładkę **"Upload"**
2. Przeciągnij plik lub kliknij aby wybrać
3. Poczekaj na upload
4. Film zostanie automatycznie dodany

### Metoda 4: Skanowanie Folderu

Jeśli masz już filmy w folderze `videos/`:

1. Kliknij przycisk **"Skanuj folder"** w prawym górnym rogu
2. System automatycznie znajdzie wszystkie filmy
3. Wygeneruje miniatury i metadane

---

## 🎬 Odtwarzanie Filmów

### Podstawowe Kontrolki

- **Spacja / K** - Odtwórz/Pauza
- **J** - Przewiń -10s
- **L** - Przewiń +10s
- **← →** - Przewiń -5s / +5s
- **↑ ↓** - Głośność
- **M** - Wycisz
- **F** - Pełny ekran

### Zaawansowane Funkcje

- **T** - Tryb kinowy (szerszy player)
- **I** - Picture-in-Picture (mini player)
- **C** - Cinema Mode (zgaś światła)
- **S** - Screenshot z filmu
- **P** - Stats for Nerds
- **U** - Udostępnij z timestampem
- **?** - Pokaż wszystkie skróty

### Prędkość Odtwarzania

Kliknij przycisk prędkości i wybierz:
- 0.25x, 0.5x, 0.75x (wolniej)
- 1x (normalnie)
- 1.25x, 1.5x, 1.75x, 2x (szybciej)

Lub użyj:
- **>** - Zwiększ prędkość
- **<** - Zmniejsz prędkość
- **R** - Reset (1x)

---

## ⚙️ Zarządzanie Biblioteką

### Edycja Filmu

1. Otwórz film
2. Kliknij **"Edytuj"**
3. Zmień tytuł, opis, tagi, kategorię
4. Zapisz

### Regeneracja Miniatury

1. Otwórz film
2. Kliknij **"Regeneruj miniaturę"**
3. Wybierz timestamp z filmu
4. Zapisz

### Usuwanie z Biblioteki

1. Otwórz film
2. Kliknij **"Usuń z biblioteki"**
3. Potwierdź

**UWAGA:** Plik wideo NIE zostanie usunięty z dysku!

---

## 📚 Organizacja

### Ulubione

- Kliknij ❤️ przy filmie
- Zobacz wszystkie: filtruj po "Ulubione"

### Obejrzyj Później

- Kliknij 🕒 przy filmie
- Zobacz listę w menu

### Historia

- System automatycznie śledzi oglądane filmy
- Możesz wrócić dokładnie tam gdzie skończyłeś

### Wyszukiwanie i Filtry

- 🔍 Wyszukaj po tytule
- 🏷️ Filtruj po tagach (kliknij tag)
- 📁 Filtruj po kategorii
- ⏱️ Filtruj po długości
- 📅 Sortuj po dacie dodania

---

## 🔄 Auto-Update

System automatycznie:
- Sprawdza aktualizacje na GitHub co 5 minut
- Pokazuje banner gdy dostępna nowa wersja
- Pozwala zaktualizować jednym klikiem

Aby zaktualizować ręcznie:
```bash
git pull origin główna-gałąź
```

---

## 🛠️ Monitorowanie Workera

### Sprawdź co robi worker:

```bash
# Status
./workers/worker-control.sh status

# Logi na żywo
tail -f data/logs/worker.log

# Zadania w kolejce
ls -la data/queue/

# Status pobierania
cat data/downloads/*.json
```

### Typowy Flow:

1. Dodajesz link w UI → tworzy się `data/queue/JOB_ID.json`
2. Worker widzi zadanie → rozpoczyna pobieranie
3. Status: `queued` → `downloading` → `completed`
4. Worker dodaje film do bazy + generuje miniaturę
5. Film pojawia się w bibliotece

---

## 📁 Struktura Katalogów

```
MyDream-Project/
├── videos/              # Twoje filmy (dodawane automatycznie)
├── data/
│   ├── videos.json      # Baza danych filmów
│   ├── thumbnails/      # Miniatury (generowane automatycznie)
│   ├── queue/           # Kolejka zadań workera
│   ├── downloads/       # Status pobierania
│   └── logs/            # Logi workera
├── workers/             # System workerów
│   ├── download-worker.sh
│   └── worker-control.sh
└── public/              # Aplikacja web
    ├── index.php
    ├── stream.php       # Streaming wideo
    └── thumbnail.php    # Serwowanie miniatur
```

---

## 🐛 Rozwiązywanie Problemów

### Worker nie działa?

```bash
# Sprawdź czy działa
./workers/worker-control.sh status

# Restart
./workers/worker-control.sh restart

# Zobacz błędy
tail -50 data/logs/worker.log
```

### Filmy nie odtwarzają się?

- Sprawdź czy endpoint `public/stream.php` istnieje
- Sprawdź uprawnienia do folderu `videos/`
- Sprawdź czy plik faktycznie istnieje: `ls -la videos/`

### Brak miniatur?

- Sprawdź czy ffmpeg jest zainstalowany: `which ffmpeg`
- Regeneruj miniaturę w UI
- Sprawdź logi: `tail data/logs/worker.log`

### Import z Mega.nz nie działa?

- Sprawdź megatools: `which megatools`
- Jeśli brakuje: `sudo apt install megatools`
- Sprawdź logi workera

### Auto-update nie działa?

- Sprawdź czy exec() jest włączone: `php -r "var_dump(function_exists('exec'));"`
- Zobacz console przeglądarki (F12)
- System automatycznie wyłącza się gdy exec() niedostępne

---

## 📊 API Endpoints

Dla zaawansowanych użytkowników:

- `GET /public/stream.php?id=VIDEO_ID` - Stream wideo (z range requests)
- `GET /public/thumbnail.php?id=VIDEO_ID` - Miniatura
- `POST /public/api/import-download.php` - Dodaj do kolejki pobierania
- `GET /public/api/auto-update.php?action=check` - Sprawdź aktualizacje
- `POST /public/api/auto-update.php?action=update` - Wykonaj update
- `GET /public/api/check-tools.php` - Sprawdź dostępne narzędzia

---

## 🎯 Najlepsze Praktyki

1. **Zawsze uruchamiaj workera** - inaczej import nie działa
2. **Regularnie sprawdzaj logi** - wykryjesz problemy wcześnie
3. **Używaj tagów** - łatwiej organizować bibliotekę
4. **Twórz kopie zapasowe** `data/videos.json` - to Twoja baza danych
5. **Nie usuwaj ręcznie plików** z `videos/` - użyj UI

---

## 💡 Pro Tips

- **Bulk Import:** Wklej kilka linków po kolei - worker pobierze wszystkie
- **Keyboard Ninja:** Naciśnij `?` w playerze - poznaj wszystkie skróty
- **Quick Seek:** Naciśnij 0-9 aby przeskoczyć do % filmu (5 = 50%)
- **Theater Mode:** Klawisz `T` - lepsze doświadczenie oglądania
- **PiP:** Klawisz `I` - oglądaj podczas pracy
- **Screenshot:** Klawisz `S` - zrób zrzut z filmu

---

## 📞 Pomoc

Więcej informacji:
- `QUICKSTART-WORKERS.md` - Szybki start z workerami
- `workers/README.md` - Pełna dokumentacja workerów
- Logi: `data/logs/worker.log`

---

**Ciesz się swoją biblioteką filmów! 🎬**
