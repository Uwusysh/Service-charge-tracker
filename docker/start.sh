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

# Keep .env in sync so artisan can boot cleanly
if [ ! -f .env ]; then
  cp .env.example .env
fi
php -r "
\$env = file_get_contents('.env');
\$env = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . getenv('APP_KEY'), \$env);
file_put_contents('.env', \$env);
"

echo "Waiting for database..."
for i in $(seq 1 30); do
  if php -r '
    try {
      \$dsn = getenv("DB_URL") ?: null;
      if (\$dsn) {
        \$url = parse_url(\$dsn);
        \$host = \$url["host"] ?? "127.0.0.1";
        \$port = \$url["port"] ?? 5432;
        \$db = ltrim(\$url["path"] ?? "/postgres", "/");
        \$user = \$url["user"] ?? "postgres";
        \$pass = \$url["pass"] ?? "";
        new PDO(sprintf("pgsql:host=%s;port=%s;dbname=%s;sslmode=require", \$host, \$port, \$db), \$user, \$pass);
        exit(0);
      }
      \$host = getenv("DB_HOST") ?: "127.0.0.1";
      \$port = getenv("DB_PORT") ?: "5432";
      \$db = getenv("DB_DATABASE") ?: "postgres";
      \$user = getenv("DB_USERNAME") ?: "postgres";
      \$pass = getenv("DB_PASSWORD") ?: "";
      new PDO(sprintf("pgsql:host=%s;port=%s;dbname=%s;sslmode=%s", \$host, \$port, \$db, getenv("DB_SSLMODE") ?: "require"), \$user, \$pass);
      exit(0);
    } catch (Throwable \$e) {
      fwrite(STDERR, \$e->getMessage() . PHP_EOL);
      exit(1);
    }
  '; then
    echo "Database is ready"
    break
  fi
  if [ "$i" -eq 30 ]; then
    echo "Database not reachable after waiting"
    exit 1
  fi
  sleep 2
done

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force --no-interaction

# Seed only when empty so restarts do not fail
USER_COUNT="$(php artisan tinker --execute='echo \\App\\Models\\User::query()->count();' | tr -d '\r')"
if [ "${USER_COUNT}" = "0" ]; then
  echo "Seeding demo data..."
  php artisan db:seed --force --no-interaction
else
  echo "Users already present (${USER_COUNT}) — skipping seed"
fi

php artisan config:cache || true
php artisan route:cache || true

echo "Starting SetK on port ${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
