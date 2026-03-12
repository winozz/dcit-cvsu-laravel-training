FROM serversideup/php:8.4-fpm-nginx

USER root

RUN install-php-extensions \
    bcmath \
    intl \
    pdo_sqlite

WORKDIR /var/www/html

COPY --chmod=755 docker/app/entrypoint.d/ /etc/entrypoint.d/

USER www-data
