#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private/invoices bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

# Prefer Render connection string if present
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
  export DB_URL="$DATABASE_URL"
fi

if [ -z "${APP_KEY:-}" ] || [[ "${APP_KEY}" != base64:* ]]; then
  export APP_KEY="$(php -r 'echo "base64:" . base64_encode(random_bytes(32));')"
  echo "Generated APP_KEY for this instance"
fi

if [ ! -f .env ]; then
  cp .env.example .env
fi

php <<'PHP'
$env = file_get_contents('.env');
$key = getenv('APP_KEY') ?: '';
$env = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, $env);
file_put_contents('.env', $env);
PHP

echo "Waiting for database..."
for i in $(seq 1 45); do
  if php docker/wait-for-db.php; then
    echo "Database is ready"
    break
  fi
  if [ "$i" -eq 45 ]; then
    echo "Database not reachable after waiting"
    exit 1
  fi
  sleep 2
done

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

php artisan config:cache || true
php artisan route:cache || true

echo "Starting SetK on port ${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
