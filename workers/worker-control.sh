#!/bin/bash
###############################################################################
# Worker Control - Zarządzanie workerem pobierania
# Użycie: ./workers/worker-control.sh {start|stop|restart|status}
###############################################################################

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
WORKER_SCRIPT="$SCRIPT_DIR/download-worker.sh"
PID_FILE="$PROJECT_DIR/data/worker.pid"
LOG_FILE="$PROJECT_DIR/data/logs/worker.log"

# Kolory
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

start_worker() {
    if [[ -f "$PID_FILE" ]]; then
        local pid=$(cat "$PID_FILE")
        if kill -0 "$pid" 2>/dev/null; then
            echo -e "${YELLOW}Worker już działa (PID: $pid)${NC}"
            return 1
        else
            echo -e "${YELLOW}Usuwam stary plik PID${NC}"
            rm -f "$PID_FILE"
        fi
    fi

    echo -e "${BLUE}Uruchamianie workera...${NC}"

    # Sprawdź czy screen jest dostępny
    if command -v screen &> /dev/null; then
        # Uruchom w screen
        screen -dmS download-worker bash "$WORKER_SCRIPT"
        sleep 1

        if [[ -f "$PID_FILE" ]]; then
            local pid=$(cat "$PID_FILE")
            echo -e "${GREEN}✓ Worker uruchomiony w screen (PID: $pid)${NC}"
            echo -e "${BLUE}  Podłącz się: screen -r download-worker${NC}"
            echo -e "${BLUE}  Odłącz się: Ctrl+A, D${NC}"
            echo -e "${BLUE}  Logi: tail -f $LOG_FILE${NC}"
        else
            echo -e "${RED}✗ Błąd uruchamiania workera${NC}"
            return 1
        fi
    else
        # Uruchom w tle bez screen
        echo -e "${YELLOW}⚠ Screen nie jest dostępny, uruchamiam w tle${NC}"
        nohup bash "$WORKER_SCRIPT" > /dev/null 2>&1 &
        sleep 1

        if [[ -f "$PID_FILE" ]]; then
            local pid=$(cat "$PID_FILE")
            echo -e "${GREEN}✓ Worker uruchomiony (PID: $pid)${NC}"
            echo -e "${BLUE}  Logi: tail -f $LOG_FILE${NC}"
        else
            echo -e "${RED}✗ Błąd uruchamiania workera${NC}"
            return 1
        fi
    fi
}

stop_worker() {
    if [[ ! -f "$PID_FILE" ]]; then
        echo -e "${YELLOW}Worker nie działa${NC}"
        return 1
    fi

    local pid=$(cat "$PID_FILE")

    if ! kill -0 "$pid" 2>/dev/null; then
        echo -e "${YELLOW}Worker nie działa (stary PID: $pid)${NC}"
        rm -f "$PID_FILE"
        return 1
    fi

    echo -e "${BLUE}Zatrzymywanie workera (PID: $pid)...${NC}"
    kill "$pid" 2>/dev/null

    # Czekaj max 5 sekund
    for i in {1..5}; do
        if ! kill -0 "$pid" 2>/dev/null; then
            echo -e "${GREEN}✓ Worker zatrzymany${NC}"
            rm -f "$PID_FILE"
            return 0
        fi
        sleep 1
    done

    # Jeśli dalej działa, wymuś
    if kill -0 "$pid" 2>/dev/null; then
        echo -e "${YELLOW}Wymuszam zatrzymanie...${NC}"
        kill -9 "$pid" 2>/dev/null
        rm -f "$PID_FILE"
        echo -e "${GREEN}✓ Worker zatrzymany (wymuszone)${NC}"
    fi
}

status_worker() {
    echo -e "${BLUE}═══════════════════════════════════════════${NC}"
    echo -e "${BLUE}       Download Worker - Status${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════${NC}"

    if [[ -f "$PID_FILE" ]]; then
        local pid=$(cat "$PID_FILE")
        if kill -0 "$pid" 2>/dev/null; then
            echo -e "Status: ${GREEN}✓ Działa${NC}"
            echo -e "PID: $pid"

            # Pokaż użycie zasobów
            if command -v ps &> /dev/null; then
                local cpu=$(ps -p "$pid" -o %cpu= 2>/dev/null | tr -d ' ')
                local mem=$(ps -p "$pid" -o %mem= 2>/dev/null | tr -d ' ')
                local time=$(ps -p "$pid" -o etime= 2>/dev/null | tr -d ' ')
                echo -e "CPU: ${cpu}%"
                echo -e "RAM: ${mem}%"
                echo -e "Czas działania: $time"
            fi
        else
            echo -e "Status: ${RED}✗ Nie działa${NC} (stary PID: $pid)"
        fi
    else
        echo -e "Status: ${RED}✗ Nie działa${NC}"
    fi

    echo ""

    # Statystyki kolejki
    local queue_count=$(find "$PROJECT_DIR/data/queue" -name "*.json" 2>/dev/null | wc -l)
    echo -e "Zadania w kolejce: ${YELLOW}$queue_count${NC}"

    if [[ $queue_count -gt 0 ]]; then
        echo -e "\nZadania oczekujące:"
        find "$PROJECT_DIR/data/queue" -name "*.json" 2>/dev/null | while read job; do
            local filename=$(grep -o '"filename":"[^"]*"' "$job" | cut -d'"' -f4)
            local type=$(grep -o '"type":"[^"]*"' "$job" | cut -d'"' -f4)
            echo -e "  • $filename ($type)"
        done
    fi

    echo ""

    # Ostatnie logi
    if [[ -f "$LOG_FILE" ]]; then
        local log_size=$(du -h "$LOG_FILE" | cut -f1)
        echo -e "Log: $LOG_FILE ($log_size)"
        echo -e "\nOstatnie 5 wpisów:"
        echo -e "${BLUE}────────────────────────────────────────${NC}"
        tail -5 "$LOG_FILE" 2>/dev/null || echo "  (brak logów)"
        echo -e "${BLUE}────────────────────────────────────────${NC}"
    fi

    echo ""
}

case "${1:-}" in
    start)
        start_worker
        ;;
    stop)
        stop_worker
        ;;
    restart)
        stop_worker
        sleep 1
        start_worker
        ;;
    status)
        status_worker
        ;;
    logs)
        if [[ -f "$LOG_FILE" ]]; then
            tail -f "$LOG_FILE"
        else
            echo "Brak pliku logów"
        fi
        ;;
    *)
        echo "Użycie: $0 {start|stop|restart|status|logs}"
        echo ""
        echo "Komendy:"
        echo "  start   - Uruchom workera"
        echo "  stop    - Zatrzymaj workera"
        echo "  restart - Restart workera"
        echo "  status  - Status workera i kolejki"
        echo "  logs    - Pokaż logi na żywo (Ctrl+C aby wyjść)"
        exit 1
        ;;
esac
