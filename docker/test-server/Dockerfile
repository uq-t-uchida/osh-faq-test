FROM php:7.3-fpm

RUN apt-get update \
 && apt-get install -y p7zip p7zip-full zip unzip zlib1g-dev libzip-dev
RUN apt-get install -y zlib1g-dev \
    && docker-php-ext-install zip

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app
