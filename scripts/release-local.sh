#!/usr/bin/env bash
# Local: commit (if needed), push, then SSH to VPS and run deploy-vps.sh
# Usage:
#   ./scripts/release-local.sh "Your commit message"
#   VPS_HOST=user@1.2.3.4 VPS_PATH=/var/www/Opticedge_credit ./scripts/release-local.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

MSG="${1:-Deploy: landing page, console theme, API hardening, and mobile updates}"
BRANCH="${DEPLOY_BRANCH:-main}"
VPS_HOST="${VPS_HOST:-}"
VPS_PATH="${VPS_PATH:-}"

echo "==> Local release → origin/${BRANCH}"

if [[ -n "$(git status --porcelain)" ]]; then
    echo "==> Staging changes (excluding .DS_Store and launcher copy PNGs)"
    git add -A
    git reset HEAD -- .DS_Store 2>/dev/null || true
    git reset HEAD -- 'opticedge_fo/ic_launcher-web copy.png' 'opticedge_fo/ic_launcher-web copy 2.png' 2>/dev/null || true

    if [[ -n "$(git diff --cached --stat)" ]]; then
        git commit -m "$(cat <<EOF
${MSG}
EOF
)"
    else
        echo "==> Nothing to commit after exclusions"
    fi
else
    echo "==> Working tree clean — nothing to commit"
fi

echo "==> Pushing to origin/${BRANCH}"
git push origin "$BRANCH"

if [[ -n "$VPS_HOST" && -n "$VPS_PATH" ]]; then
    echo "==> Deploying on VPS ${VPS_HOST}:${VPS_PATH}"
    ssh "$VPS_HOST" "cd ${VPS_PATH} && chmod +x scripts/deploy-vps.sh && ./scripts/deploy-vps.sh"
else
    echo ""
    echo "Next on OPTICEDGE VPS:"
    echo "  cd /path/to/Opticedge_credit"
    echo "  git pull origin ${BRANCH}"
    echo "  ./scripts/deploy-vps.sh"
    echo ""
    echo "Or set VPS_HOST and VPS_PATH and re-run this script."
fi
