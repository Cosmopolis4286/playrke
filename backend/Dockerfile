# ============================================
# Stage 1: Base
# ============================================
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    git curl libpng-dev libzip-dev zip unzip oniguruma-dev \
    freetype-dev libjpeg-turbo-dev libwebp-dev libxpm-dev icu-dev \
    mysql-client nodejs npm

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN npm install -g pnpm@10.25.0

WORKDIR /var/www

# ============================================
# Stage 2: Dependencies
# ============================================
FROM base AS dependencies

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

COPY package.json* ./
RUN if [ -f package.json ]; then pnpm install; fi

# ============================================
# Stage 3: Development
# ============================================
FROM base AS development

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache vendor \
 && chown -R www-data:www-data storage bootstrap/cache vendor \
 && chmod -R 775 storage bootstrap/cache vendor

EXPOSE 8000 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ============================================
# Stage 4: Production
# ============================================
FROM base AS production

WORKDIR /var/www

COPY . .

COPY --from=dependencies /var/www/vendor ./vendor
COPY --from=dependencies /var/www/node_modules ./node_modules

RUN composer dump-autoload --optimize --classmap-authoritative \
 && pnpm run build \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 8000 8080

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
