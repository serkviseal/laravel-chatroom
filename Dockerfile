# syntax=docker/dockerfile:1

# ─── Base: PHP 8.4-fpm with extensions ───────────────────────────────────────
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    bash curl git unzip libpng-dev libzip-dev oniguruma-dev \
    postgresql-dev redis mysql-client \
    && docker-php-ext-install \
       pdo pdo_mysql pdo_pgsql mbstring zip gd bcmath pcntl \
    && docker-php-ext-enable opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─── Development: adds Node.js + Xdebug ──────────────────────────────────────
FROM base AS dev

RUN apk add --no-cache nodejs npm \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

CMD ["php-fpm"]

# ─── Builder: install all deps and build assets ───────────────────────────────
FROM base AS builder

COPY package*.json ./
RUN apk add --no-cache nodejs npm && npm ci

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && npm run build

# ─── Production ───────────────────────────────────────────────────────────────
FROM base AS prod

COPY --from=builder /var/www/html /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

CMD ["php-fpm"]
