#!/usr/bin/env bash
set -euo pipefail

if php -m | grep -Eiq '^(pcov|xdebug)$'; then
  echo "Coverage driver already available."
  php -m | grep -Ei '^(pcov|xdebug)$'
  exit 0
fi

if ! command -v apt-get >/dev/null 2>&1; then
  echo "apt-get is not available in this environment."
  echo "Install PCOV or Xdebug manually, then run: bash scripts/test_coverage_wsl.sh 85"
  exit 1
fi

sudo apt-get update
if sudo apt-get install -y php-pcov; then
  echo "Installed PCOV."
elif sudo apt-get install -y php-xdebug; then
  echo "Installed Xdebug as fallback."
else
  echo "Unable to install php-pcov or php-xdebug automatically."
  exit 1
fi

if php -m | grep -Eiq '^(pcov|xdebug)$'; then
  echo "Coverage driver is now available:"
  php -m | grep -Ei '^(pcov|xdebug)$'
  echo "Run: bash scripts/test_coverage_wsl.sh 85"
  exit 0
fi

echo "Coverage driver still not detected."
echo "Run 'php -m' and verify pcov or xdebug is loaded for CLI PHP."
exit 1
