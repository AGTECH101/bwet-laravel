FROM php:8.2-fpm-alpine

WORKDIR /var/www

RUN apk add --no-cache \
    bash \
    nginx \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    pkgconf \
    sqlite \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite gd zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock* ./
COPY . /var/www

RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader \
    && composer dump-autoload --optimize \
    && mkdir -p /var/www/database /var/www/storage /var/www/bootstrap/cache \
    && touch /var/www/database/database.sqlite \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database /var/www/public \
    && mkdir -p /run/nginx \
    && cp /var/www/docker/nginx/default.conf.template /etc/nginx/conf.d/default.conf \
    && rm -f /etc/nginx/http.d/default.conf

EXPOSE 8080

CMD ["/bin/sh", "/var/www/docker/start.sh"]
