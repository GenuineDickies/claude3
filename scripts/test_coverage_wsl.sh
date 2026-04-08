#!/usr/bin/env bash
set -euo pipefail

MIN_COVERAGE="${1:-85}"

if ! php -m | grep -Eiq '^(pcov|xdebug)$'; then
  echo "No code coverage driver found (pcov/xdebug)."
  echo "Run: bash scripts/setup_wsl_coverage.sh"
  exit 1
fi

php artisan test --coverage --min="${MIN_COVERAGE}"
