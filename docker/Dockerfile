# Build astrowebmaps
FROM usgsastro/node:12 as awm-builder

RUN git clone https://github.com/USGS-Astrogeology/AstroWebMaps.git /AstroWebMaps
RUN cd /AstroWebMaps && make

# Run pilot
FROM usgsastro/php:5-fpm

SHELL ["/bin/bash", "-c"]

RUN apt-get update && \
    apt-get install -y \
        git \
        libpq-dev \
        make \
    && \
    rm -rf /var/lib/apt/lists/* /var/cache/apt/* && \
    docker-php-ext-install pgsql

COPY docker/php.ini.d/ $PHP_INI_DIR/conf.d/
COPY . /var/www/pilot
WORKDIR /var/www/pilot

COPY --from=awm-builder /AstroWebMaps /var/www/pilot/AstroWebMaps

RUN mkdir log tmp && chown www-data:www-data log tmp
