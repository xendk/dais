FROM composer:2 AS composer
FROM php:7.3-cli-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /src

COPY . /src

RUN composer install --no-dev --no-interaction --no-progress

ENTRYPOINT ["/src/entrypoint.sh"]
