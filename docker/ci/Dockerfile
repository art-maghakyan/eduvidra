# syntax=docker/dockerfile:1.7
#
# CI image used ONLY by the GitLab `phpunit` job.
# Rebuilt and pushed by `.gitlab-ci.yml` whenever this file changes.
#
# Goal: pre-install everything the test suite needs so CI doesn't have to
# run apt-get and docker-php-ext-install on every pipeline (that was
# costing ~90s/run).
#
# Not used at runtime — production PHP image is in ./Dockerfile (app stage).

FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
        libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" \
        intl \
        zip \
        gd \
        mbstring \
        bcmath \
        pcntl \
        pdo_sqlite \
        pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1
