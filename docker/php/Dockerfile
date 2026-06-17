# syntax=docker/dockerfile:1

FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    bash \
    openssl

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    opcache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/backend

COPY backend/composer.json backend/composer.lock ./

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY backend/ ./

COPY backend/.env.example ./.env
RUN sed -i 's/^APP_ENV=dev/APP_ENV=prod/' .env \
    && sed -i 's/^APP_DEBUG=1/APP_DEBUG=0/' .env || true

RUN composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p var/cache var/log config/jwt \
    && openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 \
    && openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem \
    && chmod 644 config/jwt/private.pem config/jwt/public.pem \
    && chown -R www-data:www-data var

COPY docker/php/docker-entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]
