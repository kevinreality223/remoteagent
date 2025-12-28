FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libonig-dev libxml2-dev \
    libssl-dev libpq-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql bcmath intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader || true
RUN php artisan key:generate --force || true

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
