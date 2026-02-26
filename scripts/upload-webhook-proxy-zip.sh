#!/usr/bin/env bash
set -euo pipefail

# Upload the deployment ZIP to shared hosting via scp.
#
# Usage (from repo root):
#   bash scripts/upload-webhook-proxy-zip.sh
#
# Optional overrides:
#   DEPLOY_SSH_HOST=... DEPLOY_SSH_USER=... DEPLOY_SSH_PORT=... bash scripts/upload-webhook-proxy-zip.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ZIP_PATH="$ROOT_DIR/storage/app/deploy/webhook-proxy.zip"

DEPLOY_SSH_HOST="${DEPLOY_SSH_HOST:?Set DEPLOY_SSH_HOST env var (e.g. ssh.example.com)}"
DEPLOY_SSH_USER="${DEPLOY_SSH_USER:?Set DEPLOY_SSH_USER env var}"
DEPLOY_SSH_PORT="${DEPLOY_SSH_PORT:-18765}"
DEPLOY_SSH_IDENTITY_FILE="${DEPLOY_SSH_IDENTITY_FILE:-}"

if [[ ! -f "$ZIP_PATH" ]]; then
  echo "Missing ZIP: $ZIP_PATH" >&2
  echo "Build it first: bash scripts/build-webhook-proxy-zip.sh" >&2
  exit 1
fi

scp_args=(
  -P "$DEPLOY_SSH_PORT"
)

if [[ -n "$DEPLOY_SSH_IDENTITY_FILE" ]]; then
  scp_args+=( -i "$DEPLOY_SSH_IDENTITY_FILE" )
fi

exec scp "${scp_args[@]}" "$ZIP_PATH" "$DEPLOY_SSH_USER@$DEPLOY_SSH_HOST:~/webhook-proxy.zip"
