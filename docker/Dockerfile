FROM php:5-fpm

RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
    && \
    rm -rf /var/lib/apt/lists/* /var/cache/apt/* && \
    docker-php-ext-install pgsql

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY docker/php.ini.d/ $PHP_INI_DIR/conf.d/
COPY . /var/www/pilot;
