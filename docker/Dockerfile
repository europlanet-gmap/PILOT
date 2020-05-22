FROM usgsastro/php:5-fpm

SHELL ["/bin/bash", "-c"]

RUN apt-get update && \
    apt-get install -y \
        git \
        libpq-dev \
    && \
    rm -rf /var/lib/apt/lists/* /var/cache/apt/* && \
    docker-php-ext-install pgsql

COPY docker/php.ini.d/ $PHP_INI_DIR/conf.d/
COPY . /var/www/pilot

WORKDIR /var/www/pilot
RUN mkdir log tmp && chown www-data:www-data log tmp
