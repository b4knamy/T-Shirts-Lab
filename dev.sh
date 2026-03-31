#!/usr/bin/env bash
# =============================================================================
# T-Shirts Lab — Dev Environment Starter
# =============================================================================
# Starts everything needed for local development:
#   • Docker services  → PostgreSQL + Redis (via docker-compose)
#   • Laravel backend  → php artisan serve  (port 8000)
#   • Laravel queue    → php artisan queue:listen
#   • Laravel logs     → php artisan pail
#   • React frontend   → vite dev server   (port 5173)
#
# Usage:
#   ./dev.sh            — start all services (default)
#   ./dev.sh --no-docker — skip Docker step (if DB/Redis already running)
#   ./dev.sh --stop     — stop Docker services and all background processes
# =============================================================================

set -euo pipefail

# ── Paths ─────────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$SCRIPT_DIR/backend"
FRONTEND_DIR="$SCRIPT_DIR/frontend"
PID_FILE="$SCRIPT_DIR/.dev-pids"

# ── Colours ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
RESET='\033[0m'

# ── Helpers ───────────────────────────────────────────────────────────────────
log()  { echo -e "${BOLD}${BLUE}[dev]${RESET} $*"; }
ok()   { echo -e "${GREEN}  ✔${RESET}  $*"; }
warn() { echo -e "${YELLOW}  ⚠${RESET}  $*"; }
err()  { echo -e "${RED}  ✖${RESET}  $*" >&2; }
sep()  { echo -e "${CYAN}──────────────────────────────────────────────${RESET}"; }

# ── Argument parsing ──────────────────────────────────────────────────────────
USE_DOCKER=true
STOP_MODE=false

for arg in "$@"; do
  case "$arg" in
    --no-docker) USE_DOCKER=false ;;
    --stop)      STOP_MODE=true  ;;
  esac
done

# ── Stop mode ─────────────────────────────────────────────────────────────────
if $STOP_MODE; then
  log "Stopping dev environment…"

  if [[ -f "$PID_FILE" ]]; then
    while IFS= read -r pid; do
      if kill -0 "$pid" 2>/dev/null; then
        kill "$pid" 2>/dev/null && ok "Killed process $pid"
      fi
    done < "$PID_FILE"
    rm -f "$PID_FILE"
  else
    warn "No PID file found — nothing to kill via PID file."
  fi

  # Also stop the Docker services
  if command -v docker compose &>/dev/null; then
    log "Stopping Docker services…"
    docker compose -f "$SCRIPT_DIR/docker-compose.yml" stop postgres redis
    ok "Docker services stopped."
  fi

  ok "Dev environment stopped."
  exit 0
fi

# ── Pre-flight checks ─────────────────────────────────────────────────────────
sep
echo -e "${BOLD}  🎽  T-Shirts Lab — Dev Environment${RESET}"
sep

check_command() {
  if ! command -v "$1" &>/dev/null; then
    err "Required command not found: ${BOLD}$1${RESET}"
    err "Please install it and try again."
    exit 1
  fi
}

log "Checking required tools…"
check_command php
check_command composer
check_command node
check_command npm
ok "All required tools found."

# ── .env setup ────────────────────────────────────────────────────────────────
sep
log "Checking backend .env…"
if [[ ! -f "$BACKEND_DIR/.env" ]]; then
  cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
  ok ".env created from .env.example"
  warn "Remember to review ${BOLD}backend/.env${RESET} and set your credentials."
else
  ok ".env already exists."
fi

# ── Composer install (only if vendor is missing) ──────────────────────────────
if [[ ! -d "$BACKEND_DIR/vendor" ]]; then
  sep
  log "Running ${BOLD}composer install${RESET}…"
  (cd "$BACKEND_DIR" && composer install --no-interaction)
  ok "Composer dependencies installed."
fi

# ── npm install (only if node_modules is missing) ─────────────────────────────
if [[ ! -d "$FRONTEND_DIR/node_modules" ]]; then
  sep
  log "Running ${BOLD}npm install${RESET} in frontend…"
  (cd "$FRONTEND_DIR" && npm install)
  ok "npm dependencies installed."
fi

# ── App key (only if not set) ─────────────────────────────────────────────────
APP_KEY=$(grep '^APP_KEY=' "$BACKEND_DIR/.env" | cut -d'=' -f2)
if [[ -z "$APP_KEY" ]]; then
  sep
  log "Generating application key…"
  (cd "$BACKEND_DIR" && php artisan key:generate --ansi)
  ok "App key generated."
fi

# ── Docker — PostgreSQL + Redis ───────────────────────────────────────────────
if $USE_DOCKER; then
  sep
  log "Starting Docker services (postgres + redis)…"

  if ! command -v docker &>/dev/null; then
    warn "Docker not found — skipping. Make sure PostgreSQL and Redis are running manually."
  else
    COMPOSE_CMD="docker compose"
    if ! docker compose version &>/dev/null 2>&1; then
      COMPOSE_CMD="docker-compose"
    fi

    $COMPOSE_CMD -f "$SCRIPT_DIR/docker-compose.yml" up -d postgres redis

    log "Waiting for PostgreSQL to be healthy…"
    for i in $(seq 1 30); do
      if $COMPOSE_CMD -f "$SCRIPT_DIR/docker-compose.yml" exec -T postgres \
          pg_isready -U tshirtslab -d tshirtslab_db &>/dev/null; then
        ok "PostgreSQL is ready."
        break
      fi
      if [[ $i -eq 30 ]]; then
        err "PostgreSQL did not become healthy in time."
        exit 1
      fi
      sleep 1
    done

    log "Waiting for Redis to be healthy…"
    for i in $(seq 1 20); do
      if $COMPOSE_CMD -f "$SCRIPT_DIR/docker-compose.yml" exec -T redis \
          redis-cli ping &>/dev/null; then
        ok "Redis is ready."
        break
      fi
      if [[ $i -eq 20 ]]; then
        err "Redis did not become healthy in time."
        exit 1
      fi
      sleep 1
    done
  fi
fi

# ── Migrations ────────────────────────────────────────────────────────────────
sep
log "Running database migrations…"
(cd "$BACKEND_DIR" && php artisan migrate --no-interaction)
ok "Migrations complete."

# ── Cleanup PID file ──────────────────────────────────────────────────────────
rm -f "$PID_FILE"

# ── Start background processes ────────────────────────────────────────────────
sep
log "Starting development processes…"
echo ""

# 1. Laravel dev server
(cd "$BACKEND_DIR" && php artisan serve --host=0.0.0.0 --port=8000) &
echo $! >> "$PID_FILE"
ok "Laravel API       → ${BOLD}http://localhost:8000${RESET}"

# 2. Queue worker
(cd "$BACKEND_DIR" && php artisan queue:listen --tries=1 --timeout=0 2>&1 \
  | sed "s/^/  ${CYAN}[queue]${RESET}  /") &
echo $! >> "$PID_FILE"
ok "Queue worker      → running"

# 3. Log watcher (pail — only if available, gracefully skip otherwise)
if (cd "$BACKEND_DIR" && php artisan list 2>/dev/null | grep -q 'pail'); then
  (cd "$BACKEND_DIR" && php artisan pail --timeout=0 2>&1 \
    | sed "s/^/  ${YELLOW}[logs]${RESET}   /") &
  echo $! >> "$PID_FILE"
  ok "Log watcher      → running (pail)"
else
  warn "laravel/pail not installed — skipping log watcher."
fi

# 4. Vite frontend
(cd "$FRONTEND_DIR" && npm run dev 2>&1 \
  | sed "s/^/  ${GREEN}[vite]${RESET}   /") &
echo $! >> "$PID_FILE"
ok "Vite frontend     → ${BOLD}http://localhost:5173${RESET}"

# ── Summary ───────────────────────────────────────────────────────────────────
sep
echo -e ""
echo -e "  ${BOLD}${GREEN}All services are running!${RESET}"
echo -e ""
echo -e "  ${BOLD}Frontend${RESET}   http://localhost:5173"
echo -e "  ${BOLD}Backend${RESET}    http://localhost:8000"
echo -e "  ${BOLD}API Base${RESET}   http://localhost:8000/api/v1"
echo -e ""
echo -e "  Stop with:  ${BOLD}./dev.sh --stop${RESET}   or press ${BOLD}Ctrl+C${RESET}"
echo -e ""
sep

# ── Trap Ctrl+C to clean up ───────────────────────────────────────────────────
cleanup() {
  echo ""
  log "Shutting down dev processes…"
  if [[ -f "$PID_FILE" ]]; then
    while IFS= read -r pid; do
      kill "$pid" 2>/dev/null || true
    done < "$PID_FILE"
    rm -f "$PID_FILE"
  fi
  ok "All processes stopped. Goodbye!"
}
trap cleanup INT TERM

# Keep script alive so Ctrl+C works
wait
