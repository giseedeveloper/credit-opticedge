#!/usr/bin/env bash
# Run Flutter without auto-picking a device. Use: ./run.sh --ios to boot Simulator first.
set -euo pipefail
cd "$(dirname "$0")"

ios_runtime_ok() {
  xcrun simctl runtime list 2>/dev/null | grep -qE 'iOS 26\.4.*Ready'
}

warn_ios_sdk_mismatch() {
  if ios_runtime_ok; then
    return 0
  fi
  if xcodebuild -showsdks 2>/dev/null | grep -q 'iphonesimulator26.4'; then
    echo ""
    echo "⚠️  Xcode inahitaji iOS 26.4 Simulator, lakini runtime haijasakinishwa."
    echo "    Pakua: Xcode → Settings → Platforms → iOS 26.4"
    echo "    Au terminal: xcodebuild -downloadPlatform iOS"
    echo ""
  fi
}

if [[ "${1:-}" == "--ios" ]]; then
  shift
  warn_ios_sdk_mismatch
  if ! flutter devices 2>/dev/null | grep -qE 'ios.*simulator'; then
    echo "Opening Simulator (chagua device ndani ya Simulator app)..."
    open -a Simulator
    for _ in $(seq 1 45); do
      if flutter devices 2>/dev/null | grep -qE 'ios.*simulator'; then
        break
      fi
      sleep 1
    done
  fi
fi

warn_ios_sdk_mismatch
exec flutter run "$@"
