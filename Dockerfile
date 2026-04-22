# syntax=docker/dockerfile:1.7

# =========================================================
# Stage: vendor — installs PHP deps (production-only).
#        Used by the prod "app" target and by "frontend"
#        (because Ziggy ships its JS under vendor/).
# =========================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev


# =========================================================
# Stage: frontend — builds Vite/Vue/Tailwind assets.
#        Pulls vendor/ from the "vendor" stage so imports
#        like `../../vendor/tightenco/ziggy` resolve.
# =========================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build


# =========================================================
# Stage: base — PHP-FPM + extensions (shared by dev + app).
# =========================================================
FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        zip \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        opcache \
        intl \
        zip

RUN pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www


# =========================================================
# Stage: dev — adds Composer + Node for local development.
#        Source code is bind-mounted at runtime.
# =========================================================
FROM base AS dev

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*


# =========================================================
# Stage: app — production PHP-FPM runtime.
#        Code + vendor + built assets are baked in.
# =========================================================
FROM base AS app

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --chown=www-data:www-data --from=vendor /app /var/www
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/public/build

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && find /var/www/storage -type d -exec chmod 775 {} \; \
    && find /var/www/bootstrap/cache -type d -exec chmod 775 {} \;

USER www-data

EXPOSE 9000
CMD ["php-fpm"]


# =========================================================
# Stage: nginx — production nginx (public/ + built assets baked in).
# =========================================================
FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/public
COPY --from=frontend /app/public/build /var/www/public/build

EXPOSE 80
