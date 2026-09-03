#!/usr/bin/env bash
# Run after `git pull` on the server. Safe to re-run.
set -euo pipefail
cd "$(dirname "$0")"
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Deployed. Admin URL: $(php artisan wiki:admin-prefix | sed 's/.*: //')"
