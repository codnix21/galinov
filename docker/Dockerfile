# Зависимости PHP: официальный образ Composer уже содержит ext-zip (без apk/docker-php-ext-install).
FROM composer:2 AS composer_vendor

WORKDIR /app

COPY composer.json composer.lock ./
# Образ composer:2 сейчас собирается на PHP 8.5, а проект — под PHP 8.4.
# Игнорируем PHP-платформу на стадии установки зависимостей:
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-req=php

# Сборка фронтенда (Vite)
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY --from=composer_vendor /app/vendor ./vendor
COPY resources ./resources
COPY public ./public

RUN npm run build

# PHP-FPM приложение
FROM php:8.4-fpm-alpine AS app

# $PHPIZE_DEPS — autoconf, gcc, make… нужны для docker-php-ext-install
RUN set -ex \
    && apk update \
    && apk add --no-cache \
        nginx \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=composer_vendor /app/vendor ./vendor
COPY composer.json composer.lock ./

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx

COPY docker/php/local.ini /usr/local/etc/php/conf.d/99-local.ini

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
