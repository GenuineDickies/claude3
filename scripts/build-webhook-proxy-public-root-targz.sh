#!/usr/bin/env bash
set -euo pipefail

# Build a single .tar.gz that can be extracted directly into:
#   public_html/webhook-proxy/
# with Laravel's "public" files at the archive root.
#
# This is the same layout as build-shared hosting-webhook-proxy-public-root-zip.sh,
# but using tar.gz for hosts/control-panels that won't accept/extract .zip.
#
# Output:
#   storage/app/deploy/webhook-proxy-public-root.tar.gz

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/storage/app/deploy"
OUT_TGZ="$OUT_DIR/webhook-proxy-public-root.tar.gz"
BUILD_DIR=""

mkdir -p "$OUT_DIR"
rm -f "$OUT_TGZ"

# IMPORTANT: build outside storage/ so copying storage/ can't recurse into itself.
BUILD_DIR="$(mktemp -d -t webhook-proxy-public-root-XXXXXXXX)"
trap 'rm -rf "$BUILD_DIR"' EXIT

cd "$ROOT_DIR"

if [[ ! -d "$ROOT_DIR/vendor" ]]; then
  echo "vendor/ not found. Run: composer install" >&2
  exit 1
fi

# 1) Copy Laravel public/ files to the build root.
cp -a "$ROOT_DIR/public/." "$BUILD_DIR/"

# 2) Copy the rest of the app (excluding public/ itself).
cp -a "$ROOT_DIR/app" "$BUILD_DIR/"
cp -a "$ROOT_DIR/bootstrap" "$BUILD_DIR/"
cp -a "$ROOT_DIR/config" "$BUILD_DIR/"
cp -a "$ROOT_DIR/database" "$BUILD_DIR/"
cp -a "$ROOT_DIR/routes" "$BUILD_DIR/"
cp -a "$ROOT_DIR/resources" "$BUILD_DIR/"

# Copy storage/ but exclude deploy artifacts and logs.
mkdir -p "$BUILD_DIR/storage"
# Use tar pipeline to preserve paths and handle excludes cleanly.
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

sed -i \
  -e "s#__DIR__\\s*\\.\\s*'\\/\\.\\.\\/vendor\\/autoload\\.php'#__DIR__ . '\\vendor\\/autoload.php'#" \
  -e "s#__DIR__\\s*\\.\\s*'\\/\\.\\.\\/bootstrap\\/app\\.php'#__DIR__ . '\\bootstrap\\/app.php'#" \
  "$INDEX_FILE"

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

mkdir -p "$BUILD_DIR/storage/framework/cache" \
         "$BUILD_DIR/storage/framework/sessions" \
         "$BUILD_DIR/storage/framework/views" \
         "$BUILD_DIR/bootstrap/cache"

# 4) Create the tar.gz.
# Use --owner/--group for portability; shared hosting ignores these mostly.
tar -C "$BUILD_DIR" -czf "$OUT_TGZ" .

echo "Built: $OUT_TGZ"
