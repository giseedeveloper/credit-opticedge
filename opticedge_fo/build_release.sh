#!/usr/bin/env bash
# Production release builds with TLS pinning for credit.opticedgeafrica.net
set -euo pipefail
cd "$(dirname "$0")"

PIN="${API_CERT_SHA256:-9aee56891d6d8451d74cc2ad139a8576648e8ce63d3d3d813fa419ee31d1ae48ff}"
DEFINE="--dart-define=API_CERT_SHA256=${PIN}"

flutter pub get

case "${1:-apk}" in
  apk)
    flutter build apk --release $DEFINE "${@:2}"
    ;;
  ios)
    flutter build ios --release $DEFINE "${@:2}"
    ;;
  *)
    echo "Usage: ./build_release.sh [apk|ios] [extra flutter build args...]"
    exit 1
    ;;
esac

echo "Built with certificate pinning enabled."
