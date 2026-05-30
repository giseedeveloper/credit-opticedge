#!/usr/bin/env bash
# Run on the OPTICEDGE VPS inside the project directory (where docker-compose.yml lives).
# Example: ssh user@your-vps 'cd /path/to/Opticedge_credit && ./scripts/deploy-vps.sh'

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BRANCH="${DEPLOY_BRANCH:-main}"

echo "==> Opticedge Credit deploy (branch: ${BRANCH})"
echo "==> Working directory: ${ROOT}"

if [[ -d .git ]]; then
    echo "==> git fetch & pull"
    git fetch origin
    git checkout "$BRANCH"
    git pull --ff-only origin "$BRANCH"
else
    echo "WARN: Not a git repo — skipping pull"
fi

COMPOSE=(docker compose)
if ! docker compose version &>/dev/null; then
    COMPOSE=(docker-compose)
fi

echo "==> Stopping containers (volumes kept: db, redis, storage)"
"${COMPOSE[@]}" down --remove-orphans

echo "==> Building images (app + face_match)"
"${COMPOSE[@]}" build --pull

echo "==> Starting stack"
"${COMPOSE[@]}" up -d

echo "==> Container status"
"${COMPOSE[@]}" ps

echo "==> Recent app logs"
"${COMPOSE[@]}" logs --tail=40 app

echo "==> Deploy finished. Site: https://credit.opticedgeafrica.net"
