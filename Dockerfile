ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

RUN apt-get update && apt-get install -y git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

ARG SCENARIO=
RUN git config --global --add safe.directory /app \
    && if [ -n "$SCENARIO" ] && [ -f ".scenarios.lock/$SCENARIO/composer.lock" ]; then \
           cp ".scenarios.lock/$SCENARIO/composer.lock" composer.lock \
           && composer install --no-interaction --prefer-dist; \
       else \
           composer install --no-interaction --prefer-dist; \
       fi

CMD ["./bin/run-tests.sh"]
