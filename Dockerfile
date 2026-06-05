FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install curl mysqli pdo pdo_mysql pdo_sqlite

RUN a2enmod rewrite

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p /var/www/html/uploads /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/storage \
    && chmod 755 /var/www/html/uploads /var/www/html/storage

EXPOSE 80
