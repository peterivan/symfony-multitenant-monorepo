FROM dunglas/frankenphp:1-php8.5-alpine

ENV SERVER_NAME=:80
ENV APP_ENV=dev
ENV APP_DEBUG=1
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

RUN apk add --no-cache unzip postgresql-dev \
    && install-php-extensions pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
