#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private/invoices bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

if [ -z "${APP_KEY:-}" ] || [[ "${APP_KEY}" != base64:* ]]; then
  export APP_KEY="$(php -r 'echo "base64:" . base64_encode(random_bytes(32));')"
  echo "Generated APP_KEY for this instance"
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
