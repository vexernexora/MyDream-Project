# 🔽 Download Workers - System Pobierania w Tle

Profesjonalny system kolejki i workerów do pobierania filmów z Mega.nz i URL.

## 📋 Wymagania

- **Dla Mega.nz**: `megatools`
  ```bash
  sudo apt install megatools
  # lub
  sudo yum install megatools
  ```

- **Dla URL**: `wget` lub `curl` (zazwyczaj już zainstalowane)

- **Opcjonalnie**: `screen` (do uruchamiania w tle)
  ```bash
  sudo apt install screen
  ```

## 🚀 Szybki Start

### 1. Uruchom Workera

```bash
# Automatyczne uruchomienie (z screen jeśli dostępny)
./workers/worker-control.sh start

# Lub ręcznie w screen
screen -dmS download-worker ./workers/download-worker.sh

# Podłącz się do sesji screen
screen -r download-worker

# Odłącz się od screen (pozostaw działający)
# Naciśnij: Ctrl+A, potem D
```

### 2. Sprawdź Status

```bash
./workers/worker-control.sh status
```

Pokaże:
- Czy worker działa
- Liczba zadań w kolejce
- Ostatnie logi

### 3. Zobacz Logi na Żywo

```bash
./workers/worker-control.sh logs

# Lub bezpośrednio
tail -f data/logs/worker.log
```

### 4. Zatrzymaj Workera

```bash
./workers/worker-control.sh stop
```

## 📖 Jak to Działa

### Architektura

```
┌─────────────┐
│   Browser   │  Dodaje zadanie
│     UI      │  przez API
└──────┬──────┘
       │ POST /api/import-download.php
       ▼
┌─────────────────────────────────────┐
│          API Endpoint               │
│  Tworzy plik w data/queue/*.json   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│     Download Worker (daemon)        │
│  - Sprawdza kolejkę co 2 sek       │
│  - Pobiera pliki (mega/wget/curl)  │
│  - Dodaje do bazy danych           │
│  - Generuje miniatury              │
└─────────────────────────────────────┘
```

### Przepływ Danych

1. **Użytkownik** dodaje link w UI (Mega.nz lub URL)
2. **API** tworzy:
   - `data/queue/{id}.json` - zadanie dla workera
   - `data/downloads/{id}.json` - status pobierania
3. **Worker** (działa w tle):
   - Znajduje zadanie w kolejce
   - Pobiera plik do `videos/`
   - Aktualizuje status
   - Dodaje film do bazy danych
   - Generuje miniaturkę
   - Usuwa zadanie z kolejki
4. **UI** poll'uje status i pokazuje postęp

## 📁 Struktura Plików

```
workers/
├── download-worker.sh      # Główny daemon workera
├── worker-control.sh       # Start/Stop/Status
├── add-video-helper.php    # Helper do dodawania filmów
└── README.md              # Ta dokumentacja

data/
├── queue/                 # Zadania czekające (*.json)
├── downloads/             # Statusy pobierania (*.json)
├── logs/                  # Logi workera
│   └── worker.log
└── worker.pid            # PID działającego workera

videos/                    # Pobrane filmy trafiają tutaj
```

## 🔧 Komendy

### Worker Control

```bash
./workers/worker-control.sh start    # Uruchom workera
./workers/worker-control.sh stop     # Zatrzymaj workera
./workers/worker-control.sh restart  # Restart workera
./workers/worker-control.sh status   # Status i statystyki
./workers/worker-control.sh logs     # Logi na żywo
```

### Ręczne Testowanie

```bash
# Test dodawania filmu do bazy
./workers/add-video-helper.php videos/test.mp4

# Ręczne uruchomienie workera (do debugowania)
./workers/download-worker.sh
```

## 📊 Logi

Worker loguje wszystko do `data/logs/worker.log`:

```
[2025-11-20 23:30:15] [INFO] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[2025-11-20 23:30:15] [INFO] Przetwarzanie zadania: dl_xxx.json
[2025-11-20 23:30:15] [INFO] Typ: mega
[2025-11-20 23:30:15] [INFO] Plik: video.mp4
[2025-11-20 23:30:15] [INFO] 🔽 Pobieranie z Mega.nz: video.mp4
[2025-11-20 23:30:45] [SUCCESS] ✓ Pobrano: video.mp4 (120MB)
[2025-11-20 23:30:50] [SUCCESS] ✓ Film video.mp4 dodany do bazy
```

## 🐛 Rozwiązywanie Problemów

### Worker Nie Startuje

```bash
# Sprawdź czy już działa
ps aux | grep download-worker

# Sprawdź logi
cat data/logs/worker.log

# Usuń stary PID jeśli proces nie działa
rm -f data/worker.pid
```

### Mega.nz Nie Działa

```bash
# Sprawdź czy megatools jest zainstalowany
which megatools

# Zainstaluj
sudo apt install megatools

# Testuj ręcznie
megatools dl --path ./videos "https://mega.nz/file/..."
```

### Zadania Nie są Pobierane

1. Sprawdź czy worker działa: `./workers/worker-control.sh status`
2. Zobacz kolejkę: `ls -l data/queue/`
3. Sprawdź logi: `tail -50 data/logs/worker.log`
4. Sprawdź uprawnienia: `ls -la videos/`

## 🎯 Przykłady Użycia

### Podstawowe Pobieranie

Po prostu użyj UI - worker automatycznie przetworzy zadanie jeśli działa.

### Masowe Pobieranie

Worker obsługuje wiele zadań kolejno:

1. Dodaj wszystkie linki przez UI
2. Zadania trafią do kolejki
3. Worker będzie pobierał je po kolei
4. Sprawdzaj postęp: `./workers/worker-control.sh status`

### Długie Pobieranie

Dla dużych plików:

```bash
# Uruchom w screen aby przetrwało rozłączenie SSH
screen -dmS download-worker ./workers/download-worker.sh

# Odłącz się od serwera - worker będzie działał
# Później sprawdź:
screen -r download-worker
```

## ⚙️ Konfiguracja

W `download-worker.sh` możesz zmienić:

```bash
CHECK_INTERVAL=2    # Jak często sprawdzać kolejkę (sekundy)
MAX_RETRIES=3       # Ile razy retry przy błędzie
```

## 🔐 Bezpieczeństwo

- Worker działa jako użytkownik który go uruchomił
- Pliki są zapisywane do `videos/` z uprawnieniami użytkownika
- Nie wymaga sudo (chyba że do instalacji megatools)
- Logi zawierają URL ale nie hasła

## 📝 Notatki

- Worker obsługuje jedno zadanie na raz (sequential)
- Zadania z błędami są oznaczane i pomijane
- Pobrane pliki są automatycznie dodawane do bazy
- Miniatury są generowane automatycznie
- Worker działa w nieskończonej pętli - zatrzymaj go ręcznie

## 🆘 Pomoc

Problemy? Sprawdź:

1. Logi workera: `cat data/logs/worker.log`
2. Logi PHP: `tail -f /var/log/php_errors.log`
3. Status workera: `./workers/worker-control.sh status`
4. Czy megatools działa: `megatools --version`

## 🎉 Tips & Tricks

**Autostart przy reboot**: Dodaj do crontab:
```bash
@reboot cd /path/to/project && ./workers/worker-control.sh start
```

**Monitoruj worker**:
```bash
watch -n1 './workers/worker-control.sh status'
```

**Czyść stare logi**:
```bash
# Zachowaj ostatnie 1000 linii
tail -1000 data/logs/worker.log > data/logs/worker.log.tmp
mv data/logs/worker.log.tmp data/logs/worker.log
```
