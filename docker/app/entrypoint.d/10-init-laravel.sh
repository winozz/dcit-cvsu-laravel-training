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
