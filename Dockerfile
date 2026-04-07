FROM php:8.4-fpm

# Install system dependencies for Ubuntu 24.04
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libpq-dev

# Install PHP extensions for Postgres and Laravel
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js (LTS version) and NPM
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www