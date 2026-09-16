ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-interaction --prefer-dist

CMD ["composer", "tests"]
