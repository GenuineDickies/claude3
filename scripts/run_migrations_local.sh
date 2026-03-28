#!/bin/bash
set -euo pipefail
cd /var/www/html/claude3
php artisan migrate --force
php artisan migrate:status | head -40
