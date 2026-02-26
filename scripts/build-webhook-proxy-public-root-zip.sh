#!/usr/bin/env bash
set -euo pipefail

# Build a single ZIP that can be extracted directly into:
#   public_html/webhook-proxy/
# with Laravel's "public" files at the ZIP root.
#
# This is a fallback for shared hosting setups where:
# - SSH/scp is painful/unavailable, OR
# - Composer can't be run on the server.
#
# It INCLUDES vendor/ (large ZIP, but still one file).
#
# Output:
#   storage/app/deploy/webhook-proxy-public-root.zip

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/storage/app/deploy"
OUT_ZIP="$OUT_DIR/webhook-proxy-public-root.zip"
BUILD_DIR=""

mkdir -p "$OUT_DIR"
rm -f "$OUT_ZIP"

# IMPORTANT: build outside storage/ so copying storage/ can't recurse into itself.
BUILD_DIR="$(mktemp -d -t webhook-proxy-public-root-XXXXXXXX)"
trap 'rm -rf "$BUILD_DIR"' EXIT

cd "$ROOT_DIR"

if ! command -v zip >/dev/null 2>&1; then
  echo "zip not found. Install it first: sudo apt-get update && sudo apt-get install -y zip" >&2
  exit 1
fi

if [[ ! -d "$ROOT_DIR/vendor" ]]; then
  echo "vendor/ not found. Run: composer install" >&2
  exit 1
fi

# 1) Copy Laravel public/ files to the build root.
cp -a "$ROOT_DIR/public/." "$BUILD_DIR/"

# 2) Copy the rest of the app (excluding public/ itself).
# Keep this list explicit (clarity + predictable output).
cp -a "$ROOT_DIR/app" "$BUILD_DIR/"
cp -a "$ROOT_DIR/bootstrap" "$BUILD_DIR/"
cp -a "$ROOT_DIR/config" "$BUILD_DIR/"
cp -a "$ROOT_DIR/database" "$BUILD_DIR/"
cp -a "$ROOT_DIR/routes" "$BUILD_DIR/"
cp -a "$ROOT_DIR/resources" "$BUILD_DIR/"

# Copy storage/ but exclude deploy artifacts and logs.
mkdir -p "$BUILD_DIR/storage"
tar -C "$ROOT_DIR" \
  --exclude='storage/app/deploy' \
  --exclude='storage/logs' \
  -cf - storage | tar -C "$BUILD_DIR" -xf -
cp -a "$ROOT_DIR/vendor" "$BUILD_DIR/"
cp -a "$ROOT_DIR/artisan" "$BUILD_DIR/"
cp -a "$ROOT_DIR/composer.json" "$BUILD_DIR/"
cp -a "$ROOT_DIR/composer.lock" "$BUILD_DIR/"

# Remove local-only / sensitive / bulky stuff.
rm -rf "$BUILD_DIR/storage/logs" || true
rm -rf "$BUILD_DIR/storage/app/deploy" || true
rm -rf "$BUILD_DIR/tests" || true
rm -f "$BUILD_DIR/.env" || true
rm -f "$BUILD_DIR/.phpunit.result.cache" || true

# 3) Patch index.php at build root so it points to vendor/bootstrap in the same folder.
# Also tell Laravel the public path is this directory (since there is no public/ subfolder).
INDEX_FILE="$BUILD_DIR/index.php"
if [[ ! -f "$INDEX_FILE" ]]; then
  echo "Expected index.php at build root (copied from public/), but not found." >&2
  exit 1
fi

# Update require paths.
# - from: __DIR__.'/../vendor/autoload.php'
# - to:   __DIR__.'/vendor/autoload.php'
# - from: __DIR__.'/../bootstrap/app.php'
# - to:   __DIR__.'/bootstrap/app.php'
sed -i \
  -e "s#__DIR__\s*\.\s*'\/\.\.\/vendor\/autoload\.php'#__DIR__ . '\/vendor\/autoload.php'#" \
  -e "s#__DIR__\s*\.\s*'\/\.\.\/bootstrap\/app\.php'#__DIR__ . '\/bootstrap\/app.php'#" \
  "$INDEX_FILE"

# Insert $app->usePublicPath(__DIR__); right after app bootstrap.
# Keep it idempotent.
if ! grep -q "usePublicPath" "$INDEX_FILE"; then
  awk '
    {print}
    /bootstrap\/app\.php/ {
      print "";
      print "// Shared-hosting layout: public files live at the app root.";
      print "$app->usePublicPath(__DIR__);";
    }
  ' "$INDEX_FILE" > "$INDEX_FILE.tmp" && mv "$INDEX_FILE.tmp" "$INDEX_FILE"
fi

# 4) Ensure a writable storage/cache path exists.
mkdir -p "$BUILD_DIR/storage/framework/cache" \
         "$BUILD_DIR/storage/framework/sessions" \
         "$BUILD_DIR/storage/framework/views" \
         "$BUILD_DIR/bootstrap/cache"

# 5) Create the zip.
cd "$BUILD_DIR"
zip -r "$OUT_ZIP" . \
  -x "storage/logs/*" \
  -x ".git/*" \
  -x ".github/*" \
  -x "node_modules/*" \
  -x "storage/app/deploy/*"

echo "Built: $OUT_ZIP"
