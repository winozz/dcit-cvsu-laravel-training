# syntax=docker/dockerfile:1.6

# ---------- Stage 1: Build frontend assets ----------
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 2: Install PHP dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# ---------- Stage 3: Final runtime image ----------
FROM serversideup/php:8.4-fpm-nginx

USER root

RUN install-php-extensions \
    bcmath \
    intl \
    pdo_sqlite

WORKDIR /var/www/html

# Copy application source
COPY --chown=www-data:www-data . /var/www/html

# Copy installed composer dependencies from vendor stage
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from frontend stage
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Generate autoloader and ensure required directories exist with proper permissions
RUN composer dump-autoload --optimize --no-dev --working-dir=/var/www/html \
    && mkdir -p \
        /var/www/html/bootstrap/cache \
        /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache /var/www/html/storage /var/www/html/database \
    && chmod -R 775 /var/www/html/bootstrap/cache /var/www/html/storage

COPY --chmod=755 docker/app/entrypoint.d/ /etc/entrypoint.d/

USER www-data
