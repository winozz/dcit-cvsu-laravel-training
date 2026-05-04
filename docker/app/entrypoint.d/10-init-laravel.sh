#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    database \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

# Abort early if APP_KEY is missing — never auto-generate in staging/production
if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY environment variable is not set. Set it via Jenkins credentials." >&2
    exit 1
fi

php artisan config:clear
php artisan migrate --force --no-interaction

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=.\+" .env; then
    php artisan key:generate --force
fi

php artisan migrate --force --no-interaction 2>/dev/null || true
