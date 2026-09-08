# Annual Service Charge Budget & Spend Tracker (POC)

UK residential block management POC — Laravel, Blade, Bootstrap 5.

Live login is a single **Enter demo** button (Block Manager + Maple Court sample data).

## Local run

```bash
composer install
cp .env.example .env
php artisan key:generate
# SQLite default — create empty file if needed:
#   type nul > database\database.sqlite   (Windows)
#   touch database/database.sqlite        (macOS/Linux)
php artisan migrate:fresh --seed
php artisan serve
```

Open http://127.0.0.1:8000 → **Enter demo**.

## Deploy on Render

Repo: [Uwusysh/Service-charge-tracker](https://github.com/Uwusysh/Service-charge-tracker)

### Option A — Blueprint (recommended)

1. Push this repo to GitHub (already done if you followed the agent push).
2. In [Render Dashboard](https://dashboard.render.com/) → **New** → **Blueprint**.
3. Connect the `Service-charge-tracker` repository.
4. Render reads `render.yaml` and creates:
   - a **Docker web service**
   - a **PostgreSQL** database (`setk-db`)
5. Before first deploy, open the web service → **Environment** and set:
   - `APP_URL` = your Render URL, e.g. `https://service-charge-tracker.onrender.com`
   - Leave `APP_KEY` empty (startup generates a valid Laravel key), **or** paste output of `php artisan key:generate --show`
6. Deploy. First boot runs `migrate` + `db:seed`.
7. Open the site → click **Enter demo**.

### Option B — Manual

1. **New** → **PostgreSQL** (Free) — note host, database, user, password.
2. **New** → **Web Service** → connect this GitHub repo.
3. Settings:
   - **Runtime**: Docker
   - **Dockerfile path**: `./Dockerfile`
   - **Instance**: Free
4. Environment variables:

| Key | Value |
|-----|--------|
| `APP_NAME` | `SetK SC Tracker` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://YOUR-SERVICE.onrender.com` |
| `APP_KEY` | *(leave blank — auto-generated on boot)* |
| `LOG_CHANNEL` | `stderr` |
| `SESSION_DRIVER` | `file` |
| `CACHE_STORE` | `file` |
| `QUEUE_CONNECTION` | `sync` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | from Postgres |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | from Postgres |
| `DB_USERNAME` | from Postgres |
| `DB_PASSWORD` | from Postgres |

5. Deploy and open the URL → **Enter demo**.

### Notes

- Free Render web services **spin down** after idle time; first request can take ~30–60s.
- Free Postgres is fine for the POC; SQLite is not recommended on Render (ephemeral disk).
- Seeded demo user used by the button: `manager@setk.test` / `password` (no typing needed — use **Enter demo**).

## Roles (POC)

| Email | Role |
|-------|------|
| `manager@setk.test` | Block Manager (default demo) |
| `accountant@setk.test` | Accountant |
| `admin@setk.test` | Administrator |

Password for all: `password`

## Disclaimer

Management information only — not a service-charge demand, payment system, or statutory year-end account.
