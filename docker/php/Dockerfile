FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libpq-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    intl \
    pdo \
    pdo_pgsql \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app
