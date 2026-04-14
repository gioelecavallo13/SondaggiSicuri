#!/usr/bin/env bash
# Deploy rsync + docker compose (overlay produzione).
# Non esegue mai `docker compose down -v`. Non sincronizza nulla sotto /data/ sulla VPS.
#
# Uso:
#   cp deploy.env.example deploy.env   # una tantum
#   ./scripts/deploy-vps.sh
#   ./scripts/deploy-vps.sh --dry-run  # solo rsync a secco + niente compose remoto
#
# Prerequisito: sulla VPS esistono `DEPLOY_PATH`, `.env` applicativo e directory
# /data/sondaggi/media e /data/sondaggi/logs (vedi README).

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DEPLOY_ENV="${DEPLOY_ENV:-$ROOT/deploy.env}"
if [[ ! -f "$DEPLOY_ENV" ]]; then
  echo "Manca $DEPLOY_ENV — copia deploy.env.example in deploy.env e compila DEPLOY_HOST." >&2
  exit 1
fi

# shellcheck disable=SC1090
source "$DEPLOY_ENV"

: "${DEPLOY_HOST:?Imposta DEPLOY_HOST in deploy.env}"
DEPLOY_PATH="${DEPLOY_PATH:-/opt/sondaggi}"

if [[ "$DEPLOY_PATH" == /data/* ]]; then
  echo "DEPLOY_PATH non deve essere sotto /data/ (rsync solo sul tree applicativo, es. /opt/sondaggi)." >&2
  exit 1
fi

DRY_RUN=false
for arg in "$@"; do
  if [[ "$arg" == "--dry-run" ]]; then
    DRY_RUN=true
  fi
done

SSH_PORT="${SSH_PORT:-22}"
# Solo opzioni ssh (non il binario): evita `ssh ssh -p … host` che risolve "ssh" come hostname.
SSH_OPTS=(-p "$SSH_PORT")
if [[ -n "${SSH_IDENTITY_FILE:-}" ]]; then
  SSH_OPTS+=(-i "$SSH_IDENTITY_FILE")
fi

RSYNC_RSH=$(printf '%q ' ssh "${SSH_OPTS[@]}")
RSYNC_RSH=${RSYNC_RSH%% }

RSYNC_FLAGS=(-az)
if [[ "$DRY_RUN" == true ]]; then
  RSYNC_FLAGS+=(-n --itemize-changes)
fi

# Escludi segreti, dipendenze ricostruibili, VCS, media e log locali (su VPS restano bind /data/ o volumi).
RSYNC_EXCLUDES=(
  --exclude='.git/'
  --exclude='node_modules/'
  --exclude='vendor/'
  --exclude='.env'
  --exclude='deploy.env'
  --exclude='storage/app/public/'
  --exclude='storage/logs/'
  --exclude='*.log'
)

echo "Rsync → ${DEPLOY_HOST}:${DEPLOY_PATH}/"
rsync "${RSYNC_FLAGS[@]}" \
  "${RSYNC_EXCLUDES[@]}" \
  -e "$RSYNC_RSH" \
  ./ "${DEPLOY_HOST}:${DEPLOY_PATH}/"

if [[ "$DRY_RUN" == true ]]; then
  echo "Dry-run: salto compose remoto."
  exit 0
fi

path_q=$(printf '%q' "$DEPLOY_PATH")
if [[ "${USE_SUDO_DOCKER:-false}" == "true" ]]; then
  compose_remote="sudo docker compose"
else
  compose_remote="docker compose"
fi

echo "Compose remoto (${compose_remote})…"
# shellcheck disable=SC2029
ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" \
  "cd ${path_q} && ${compose_remote} -f docker-compose.yml -f docker-compose.prod.yml up -d --build"
