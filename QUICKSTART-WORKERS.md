# 🚀 Quick Start - Download Workers

## TL;DR

```bash
# 1. Zainstaluj megatools (dla Mega.nz)
sudo apt install megatools

# 2. Uruchom workera
./workers/worker-control.sh start

# 3. Sprawdź status
./workers/worker-control.sh status

# 4. Dodaj link w UI - worker pobierze automatycznie!
```

## Komendy

```bash
./workers/worker-control.sh start    # ⚡ Uruchom
./workers/worker-control.sh stop     # ⛔ Zatrzymaj
./workers/worker-control.sh status   # 📊 Status
./workers/worker-control.sh logs     # 📝 Logi na żywo
./workers/worker-control.sh restart  # 🔄 Restart
```

## W Screen (Recommended)

```bash
# Uruchom
screen -dmS download-worker ./workers/download-worker.sh

# Podłącz się
screen -r download-worker

# Odłącz się (zostaw działający)
Ctrl+A, potem D
```

## Jak Działa

1. **Dodajesz link** w UI (Mega.nz lub URL)
2. **API tworzy zadanie** w kolejce (`data/queue/*.json`)
3. **Worker pobiera** plik w tle (`videos/`)
4. **Automatycznie** dodaje do bazy + generuje miniaturkę
5. **UI pokazuje postęp** pobierania

## Sprawdź Logi

```bash
tail -f data/logs/worker.log
```

## Troubleshooting

### Worker nie startuje?
```bash
# Usuń stary PID
rm -f data/worker.pid
# Spróbuj ponownie
./workers/worker-control.sh start
```

### Mega.nz nie działa?
```bash
# Zainstaluj megatools
sudo apt install megatools
# Lub
sudo yum install megatools
```

### Zadania nie są pobierane?
```bash
# Sprawdź czy worker działa
./workers/worker-control.sh status

# Zobacz kolejkę
ls -l data/queue/

# Sprawdź logi
tail -20 data/logs/worker.log
```

## Pełna Dokumentacja

Zobacz: `workers/README.md`

---

**Gotowe!** Teraz możesz pobierać pliki z Mega.nz i URL w tle 🎉
