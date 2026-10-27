FROM php:8.4-fpm-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev libicu-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl pdo_mysql pdo_sqlite zip opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --classmap-authoritative

FROM php-base AS runtime
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=vendor /var/www/html/bootstrap/cache ./bootstrap/cache
COPY docker/entrypoint.sh /usr/local/bin/aura-entrypoint
RUN chmod +x /usr/local/bin/aura-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/aura-entrypoint"]
CMD ["php-fpm"]
