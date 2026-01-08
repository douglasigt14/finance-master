FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN addgroup -g ${GID} appgroup && adduser -D -G appgroup -u ${UID} appuser

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo_mysql mbstring intl zip bcmath opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN chown -R appuser:appgroup /var/www/html

USER appuser

CMD ["php-fpm"]
