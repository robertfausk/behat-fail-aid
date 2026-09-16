ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli

RUN apt-get update && apt-get install -y git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer update --no-interaction --prefer-dist

CMD ["./bin/run-tests.sh"]
