# syntax=docker/dockerfile:experimental
FROM jobilla/php:fpm-7.4 as base

ENV COMPOSER_HOME ./.composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

FROM base as prod

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock

RUN --mount=type=ssh composer install --no-dev --no-autoloader --no-scripts
COPY --chown=www-data:www-data . /app
RUN composer dump-autoload --optimize
RUN php artisan route:cache
