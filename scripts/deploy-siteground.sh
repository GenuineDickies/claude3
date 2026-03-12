#!/usr/bin/env bash
set -euo pipefail

# Deploy the Laravel app to SiteGround shared hosting.
#
# The live app is at: ~/www/wkrllc.com/public_html/webhook-proxy/
# URL: https://wkrllc.com/webhook-proxy/
#
# This script ONLY syncs application code directories into the deploy
# target. It never touches .env, vendor/, storage/, or hosting paths
# outside the app directory.
#
# Usage:
#   bash scripts/deploy-siteground.sh            # full deploy
#   bash scripts/deploy-siteground.sh --dry-run  # preview only

SSH_ALIAS="siteground"
REMOTE_APP="www/wkrllc.com/public_html/webhook-proxy"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DRY_RUN=""
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN="--dry-run"
  echo "=== DRY RUN — no files will be changed ==="
  echo
fi

# ---------------------------------------------------------------------------
# Directories to deploy.  --delete is safe here because these are
# entirely managed by our repo — nothing hosting-specific lives inside them.
# ---------------------------------------------------------------------------
SYNC_DIRS=(
  app
  bootstrap
  config
  database
  resources
  routes
)

# Individual files at the repo root that should be synced.
SYNC_FILES=(
  artisan
  composer.json
  composer.lock
)

# Files/dirs on the remote that must NEVER be touched.
# (Listed here for documentation — the script simply never syncs them.)
# .env, .env.bak, vendor/, storage/, node_modules/, public/storage

# ---------------------------------------------------------------------------
# Pre-flight checks
# ---------------------------------------------------------------------------
echo "=== SiteGround Deploy ==="
echo "Source:  $ROOT_DIR"
echo "Target:  $SSH_ALIAS:~/$REMOTE_APP/"
echo

# Verify SSH connectivity before doing anything.
if ! ssh -o ConnectTimeout=10 -o BatchMode=yes "$SSH_ALIAS" 'echo ok' >/dev/null 2>&1; then
  echo "ERROR: Cannot connect to $SSH_ALIAS. Check your SSH keys and config." >&2
  exit 1
fi
echo "SSH connection: OK"

# Verify the remote app directory exists and has a .env (sanity check).
if ! ssh "$SSH_ALIAS" "test -f ~/$REMOTE_APP/.env"; then
  echo "ERROR: ~/$REMOTE_APP/.env not found on remote. Is the path correct?" >&2
  exit 1
fi
echo "Remote app dir: OK"

# Verify we're in the right repo (has artisan + composer.json).
if [[ ! -f "$ROOT_DIR/artisan" || ! -f "$ROOT_DIR/composer.json" ]]; then
  echo "ERROR: $ROOT_DIR does not look like the Laravel project root." >&2
  exit 1
fi

# Show what will be deployed.
echo
echo "Directories to sync (with --delete within each):"
for dir in "${SYNC_DIRS[@]}"; do
  echo "  $dir/"
done
echo
echo "Files to sync:"
for f in "${SYNC_FILES[@]}"; do
  echo "  $f"
done
echo

if [[ -z "$DRY_RUN" ]]; then
  read -r -p "Proceed with deploy? [y/N]: " confirm
  if [[ "${confirm,,}" != "y" ]]; then
    echo "Aborted."
    exit 0
  fi
  echo
fi

# ---------------------------------------------------------------------------
# Sync directories (--delete is scoped to each directory, not the app root)
# ---------------------------------------------------------------------------
for dir in "${SYNC_DIRS[@]}"; do
  echo "Syncing $dir/ ..."
  rsync -az --delete $DRY_RUN \
    -e ssh \
    "$ROOT_DIR/$dir/" "$SSH_ALIAS:~/$REMOTE_APP/$dir/"
done

# ---------------------------------------------------------------------------
# Sync individual root files (no --delete, just overwrite)
# ---------------------------------------------------------------------------
echo "Syncing root files ..."
for f in "${SYNC_FILES[@]}"; do
  if [[ -f "$ROOT_DIR/$f" ]]; then
    rsync -az $DRY_RUN -e ssh "$ROOT_DIR/$f" "$SSH_ALIAS:~/$REMOTE_APP/$f"
  fi
done

if [[ -n "$DRY_RUN" ]]; then
  echo
  echo "=== DRY RUN complete — no changes made ==="
  exit 0
fi

# ---------------------------------------------------------------------------
# Post-deploy: composer install + migrations
# ---------------------------------------------------------------------------
echo
echo "Running composer install (no-dev) ..."
ssh "$SSH_ALIAS" "cd ~/$REMOTE_APP && php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1" || \
ssh "$SSH_ALIAS" "cd ~/$REMOTE_APP && composer install --no-dev --optimize-autoloader --no-interaction 2>&1"

echo
echo "Running migrations ..."
ssh "$SSH_ALIAS" "cd ~/$REMOTE_APP && php artisan migrate --force 2>&1"

echo
echo "Clearing caches ..."
ssh "$SSH_ALIAS" "cd ~/$REMOTE_APP && php artisan config:cache && php artisan route:cache && php artisan view:cache 2>&1"

echo
echo "=== Deploy complete ==="
echo "Live at: https://wkrllc.com/webhook-proxy/"
