#!/usr/bin/env sh
set -e

cd /app

# Only manage a SQLite file when SQLite is the chosen driver. On a managed
# Postgres/MySQL (recommended for the cloud) this block is skipped.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_DATABASE="${DB_DATABASE:-/data/database.sqlite}"
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi

# Config is read live from the environment (never cached), so just be safe.
php artisan config:clear >/dev/null 2>&1 || true

# Only the web service runs the installer. It always migrates and seeds the demo
# data exactly once (when the database is still empty) — idempotent and
# database-agnostic, so it is safe on every deploy and on managed databases.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    php artisan app:install --no-interaction
fi

exec "$@"
