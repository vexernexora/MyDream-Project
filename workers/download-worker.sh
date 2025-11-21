#!/bin/bash
###############################################################################
# Download Worker - Pobieranie filmów w tle z kolejki
# Użycie: ./workers/download-worker.sh
# W screen: screen -dmS download-worker ./workers/download-worker.sh
###############################################################################

# Kolory dla logów
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Ścieżki
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
QUEUE_DIR="$PROJECT_DIR/data/queue"
DOWNLOADS_DIR="$PROJECT_DIR/data/downloads"
VIDEOS_DIR="$PROJECT_DIR/videos"
LOG_FILE="$PROJECT_DIR/data/logs/worker.log"

# Ustawienia
CHECK_INTERVAL=2  # Sprawdzaj kolejkę co 2 sekundy
MAX_RETRIES=3
WORKER_PID_FILE="$PROJECT_DIR/data/worker.pid"

###############################################################################
# Funkcje pomocnicze
###############################################################################

log() {
    local level="$1"
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    case "$level" in
        INFO)  color=$BLUE ;;
        SUCCESS) color=$GREEN ;;
        WARNING) color=$YELLOW ;;
        ERROR) color=$RED ;;
        *) color=$NC ;;
    esac

    echo -e "${color}[${timestamp}] [${level}]${NC} ${message}" | tee -a "$LOG_FILE"
}

update_job_status() {
    local job_file="$1"
    local status="$2"
    local progress="${3:-0}"
    local error="${4:-}"

    if [[ ! -f "$job_file" ]]; then
        return 1
    fi

    # Aktualizuj plik statusu
    local status_file="${DOWNLOADS_DIR}/$(basename "$job_file")"

    if [[ -f "$status_file" ]]; then
        # Użyj PHP do aktualizacji JSON
        php -r "
        \$file = '$status_file';
        \$data = json_decode(file_get_contents(\$file), true);
        \$data['status'] = '$status';
        \$data['progress'] = $progress;
        if ('$error' !== '') {
            \$data['error'] = '$error';
            \$data['completed'] = true;
        }
        if ('$status' === 'completed') {
            \$data['completed'] = true;
            \$data['progress'] = 100;
        }
        \$data['updated_at'] = time();
        file_put_contents(\$file, json_encode(\$data, JSON_PRETTY_PRINT));
        " 2>/dev/null || true
    fi
}

add_to_database() {
    local video_path="$1"
    local filename="$2"

    log "INFO" "Dodawanie $filename do bazy danych..."

    # Wywołaj PHP helper do dodania filmu
    php "$PROJECT_DIR/workers/add-video-helper.php" "$video_path" 2>&1 | tee -a "$LOG_FILE"

    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        log "SUCCESS" "✓ Film $filename dodany do bazy"
        return 0
    else
        log "ERROR" "✗ Błąd dodawania filmu do bazy"
        return 1
    fi
}

###############################################################################
# Obsługa Mega.nz
###############################################################################

download_mega() {
    local job_file="$1"
    local url="$2"
    local filename="$3"
    local job_id=$(basename "$job_file" .json)

    log "INFO" "🔽 Pobieranie z Mega.nz: $filename"
    log "INFO" "URL: $url"

    # Sprawdź czy megatools jest zainstalowany
    if ! command -v megatools &> /dev/null; then
        log "ERROR" "megatools nie jest zainstalowany!"
        log "INFO" "Instalacja: sudo apt install megatools || sudo yum install megatools"
        update_job_status "$job_file" "error" 0 "Brak megatools - zainstaluj: sudo apt install megatools"
        return 1
    fi

    update_job_status "$job_file" "downloading" 10

    local target_file="$VIDEOS_DIR/$filename"

    # Pobierz plik używając megatools
    log "INFO" "Wykonuję: megatools dl --path \"$VIDEOS_DIR\" \"$url\""

    if megatools dl --path "$VIDEOS_DIR" "$url" 2>&1 | tee -a "$LOG_FILE"; then
        update_job_status "$job_file" "processing" 90

        # Znajdź pobrany plik (megatools może użyć oryginalnej nazwy)
        local downloaded_file=$(find "$VIDEOS_DIR" -type f -newermt "1 minute ago" | head -1)

        if [[ -n "$downloaded_file" && -f "$downloaded_file" ]]; then
            # Zmień nazwę jeśli trzeba
            if [[ "$(basename "$downloaded_file")" != "$filename" ]]; then
                mv "$downloaded_file" "$target_file"
            fi

            log "SUCCESS" "✓ Pobrano: $filename ($(du -h "$target_file" | cut -f1))"

            # Dodaj do bazy danych
            add_to_database "$target_file" "$filename"

            update_job_status "$job_file" "completed" 100

            # Usuń zadanie z kolejki
            rm -f "$job_file"
            return 0
        else
            log "ERROR" "Nie znaleziono pobranego pliku"
            update_job_status "$job_file" "error" 0 "Nie znaleziono pobranego pliku"
            return 1
        fi
    else
        log "ERROR" "Błąd pobierania z Mega.nz"
        update_job_status "$job_file" "error" 0 "Błąd pobierania z Mega.nz"
        return 1
    fi
}

###############################################################################
# Obsługa URL
###############################################################################

download_url() {
    local job_file="$1"
    local url="$2"
    local filename="$3"
    local job_id=$(basename "$job_file" .json)

    log "INFO" "🔽 Pobieranie z URL: $filename"
    log "INFO" "URL: $url"

    update_job_status "$job_file" "downloading" 10

    local target_file="$VIDEOS_DIR/$filename"

    # Sprawdź dostępne narzędzia
    if command -v wget &> /dev/null; then
        log "INFO" "Używam wget do pobierania..."

        if wget -O "$target_file" "$url" 2>&1 | tee -a "$LOG_FILE"; then
            log "SUCCESS" "✓ Pobrano: $filename ($(du -h "$target_file" | cut -f1))"

            update_job_status "$job_file" "processing" 90

            # Dodaj do bazy danych
            add_to_database "$target_file" "$filename"

            update_job_status "$job_file" "completed" 100

            # Usuń zadanie z kolejki
            rm -f "$job_file"
            return 0
        else
            log "ERROR" "Błąd pobierania przez wget"
            update_job_status "$job_file" "error" 0 "Błąd pobierania przez wget"
            return 1
        fi

    elif command -v curl &> /dev/null; then
        log "INFO" "Używam curl do pobierania..."

        if curl -L -o "$target_file" "$url" 2>&1 | tee -a "$LOG_FILE"; then
            log "SUCCESS" "✓ Pobrano: $filename ($(du -h "$target_file" | cut -f1))"

            update_job_status "$job_file" "processing" 90

            # Dodaj do bazy danych
            add_to_database "$target_file" "$filename"

            update_job_status "$job_file" "completed" 100

            # Usuń zadanie z kolejki
            rm -f "$job_file"
            return 0
        else
            log "ERROR" "Błąd pobierania przez curl"
            update_job_status "$job_file" "error" 0 "Błąd pobierania przez curl"
            return 1
        fi
    else
        log "ERROR" "Brak wget ani curl!"
        update_job_status "$job_file" "error" 0 "Brak wget/curl"
        return 1
    fi
}

###############################################################################
# Główna pętla workera
###############################################################################

process_queue() {
    # Znajdź wszystkie zadania w kolejce
    local jobs=("$QUEUE_DIR"/*.json)

    if [[ ! -e "${jobs[0]}" ]]; then
        # Brak zadań w kolejce
        return 0
    fi

    for job_file in "${jobs[@]}"; do
        if [[ ! -f "$job_file" ]]; then
            continue
        fi

        log "INFO" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        log "INFO" "Przetwarzanie zadania: $(basename "$job_file")"

        # Odczytaj dane zadania z JSON (użyj PHP do parsowania)
        local job_data=$(php -r "
            \$data = json_decode(file_get_contents('$job_file'), true);
            echo \$data['type'] . '|' . \$data['url'] . '|' . \$data['filename'];
        " 2>/dev/null)

        local job_type=$(echo "$job_data" | cut -d'|' -f1)
        local job_url=$(echo "$job_data" | cut -d'|' -f2)
        local job_filename=$(echo "$job_data" | cut -d'|' -f3)

        log "INFO" "Typ: $job_type"
        log "INFO" "Plik: $job_filename"

        # Sprawdź czy plik już istnieje
        if [[ -f "$VIDEOS_DIR/$job_filename" ]]; then
            log "WARNING" "⚠ Plik już istnieje: $job_filename"
            update_job_status "$job_file" "error" 0 "Plik już istnieje"
            rm -f "$job_file"
            continue
        fi

        # Pobierz plik w zależności od typu
        case "$job_type" in
            mega)
                download_mega "$job_file" "$job_url" "$job_filename"
                ;;
            url)
                download_url "$job_file" "$job_url" "$job_filename"
                ;;
            *)
                log "ERROR" "Nieznany typ zadania: $job_type"
                update_job_status "$job_file" "error" 0 "Nieznany typ zadania"
                rm -f "$job_file"
                ;;
        esac

        log "INFO" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "" >> "$LOG_FILE"
    done
}

###############################################################################
# Uruchomienie workera
###############################################################################

# Sprawdź czy worker już działa
if [[ -f "$WORKER_PID_FILE" ]]; then
    old_pid=$(cat "$WORKER_PID_FILE")
    if kill -0 "$old_pid" 2>/dev/null; then
        log "ERROR" "Worker już działa (PID: $old_pid)"
        exit 1
    else
        log "WARNING" "Usuwam stary plik PID (proces nie działa)"
        rm -f "$WORKER_PID_FILE"
    fi
fi

# Zapisz PID
echo $$ > "$WORKER_PID_FILE"

# Trap do czyszczenia przy wyjściu
cleanup() {
    log "INFO" "Zatrzymywanie workera..."
    rm -f "$WORKER_PID_FILE"
    exit 0
}

trap cleanup SIGINT SIGTERM EXIT

# Upewnij się że foldery istnieją
mkdir -p "$QUEUE_DIR" "$DOWNLOADS_DIR" "$VIDEOS_DIR" "$(dirname "$LOG_FILE")"

log "SUCCESS" "╔════════════════════════════════════════════════════════════╗"
log "SUCCESS" "║          Download Worker uruchomiony!                      ║"
log "SUCCESS" "╚════════════════════════════════════════════════════════════╝"
log "INFO" "PID: $$"
log "INFO" "Kolejka: $QUEUE_DIR"
log "INFO" "Filmy: $VIDEOS_DIR"
log "INFO" "Log: $LOG_FILE"
log "INFO" "Sprawdzam kolejkę co ${CHECK_INTERVAL}s..."
log "INFO" ""

# Główna pętla
while true; do
    process_queue
    sleep "$CHECK_INTERVAL"
done
