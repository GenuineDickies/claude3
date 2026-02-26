#!/usr/bin/env bash
set -euo pipefail

# Builds a tiny deploy folder for shared hosting:
# - webhook.php
# - config.example.php
# - README.md

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="$ROOT_DIR/deploy/lite-webhook-proxy/public_html/webhook-proxy"
OUT_DIR="$ROOT_DIR/storage/app/deploy"

mkdir -p "$OUT_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
ZIP_PATH="$OUT_DIR/webhook-proxy-lite-$STAMP.zip"

if ! command -v zip >/dev/null 2>&1; then
  echo "zip command not found. Install zip or just upload files from deploy/lite-webhook-proxy/public_html/webhook-proxy/" >&2
  exit 1
fi

pushd "$SRC_DIR" >/dev/null
zip -r "$ZIP_PATH" ./webhook.php ./config.example.php >/dev/null
popd >/dev/null

echo "Built: $ZIP_PATH"
