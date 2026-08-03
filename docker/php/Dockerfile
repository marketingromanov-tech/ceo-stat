FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash git icu-dev libzip-dev linux-headers oniguruma-dev unzip \
    && docker-php-ext-install bcmath intl opcache pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/ceo-stat-entrypoint

RUN chmod +x /usr/local/bin/ceo-stat-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["ceo-stat-entrypoint"]
CMD ["php-fpm"]
