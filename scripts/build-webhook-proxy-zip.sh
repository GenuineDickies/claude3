#!/usr/bin/env bash
set -euo pipefail

# Builds a small deployment ZIP suitable for shared hosting uploads.
#
# Goal: upload ONE file to shared hosting, extract it, then run composer install on-server.
#
# Output:
#   storage/app/deploy/webhook-proxy.zip
#
# Notes:
# - Excludes vendor/ and node_modules/ to keep ZIP small.
# - If you do NOT have SSH access, you can remove the vendor exclusion
#   (but the ZIP will be much larger).

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/storage/app/deploy"
OUT_ZIP="$OUT_DIR/webhook-proxy.zip"

mkdir -p "$OUT_DIR"
rm -f "$OUT_ZIP"

cd "$ROOT_DIR"

if ! command -v zip >/dev/null 2>&1; then
  echo "zip not found. Install it first: sudo apt-get update && sudo apt-get install -y zip" >&2
  exit 1
fi

zip -r "$OUT_ZIP" . \
  -x ".git/*" \
  -x ".github/*" \
  -x "node_modules/*" \
  -x "vendor/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x "storage/app/deploy/*" \
  -x ".env" \
  -x ".phpunit.result.cache" \
  -x "tests/*"

echo "Built: $OUT_ZIP"