# ============================================
# Stage 1: Base
# ============================================
FROM php:8.4-fpm-alpine AS base

# Dependencias del sistema y extensiones
RUN apk add --no-cache \
    git curl zip unzip \
    libpng-dev libzip-dev oniguruma-dev icu-dev \
    freetype-dev libjpeg-turbo-dev libwebp-dev libxpm-dev \
    mysql-client nodejs npm

# Configuración de extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Redis via PECL
RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# pnpm global
RUN npm install -g pnpm@10.25.0

WORKDIR /var/www

# ============================================
# Stage 2: Dependencies
# ============================================
FROM base AS dependencies

# Composer
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

# Node dependencies
COPY package.json* pnpm-lock.yaml* ./
RUN if [ -f package.json ]; then pnpm install --frozen-lockfile; fi

# ============================================
# Stage 3: Development
# ============================================
FROM base AS development

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    vendor \
 && chown -R www-data:www-data storage bootstrap/cache vendor \
 && chmod -R 775 storage bootstrap/cache vendor

EXPOSE 8000 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ============================================
# Stage 4: Production
# ============================================
FROM base AS production

WORKDIR /var/www

# Copiar proyecto excepto node_modules/vendor
COPY . .

# Copiar dependencias desde stage anterior
COPY --from=dependencies /var/www/vendor ./vendor
COPY --from=dependencies /var/www/node_modules ./node_modules

# Build + cache optimizado
RUN composer dump-autoload --optimize --classmap-authoritative \
 && pnpm run build \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

# Permisos necesarios
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 8000 8080

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
