FROM php:8.4-fpm

# System dependencies + PHP extensions Laravel/kost-app needs
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip xml dom simplexml curl

# Redis extension (used by your broadcasting setup)
RUN pecl install redis && docker-php-ext-enable redis

#Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer 

WORKDIR /var/www

# Copy dependency manifests first (better layer caching — code changes
# won't force a full composer re-install every rebuild)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Now copy the rest of the app 
COPY . .
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]