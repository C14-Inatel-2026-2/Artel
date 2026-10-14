FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y \
        git \
        libonig-dev \
        libsqlite3-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        pdo_sqlite \
        zip \
    && a2enmod rewrite \
    && sed -ri \
        -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
