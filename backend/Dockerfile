# ============================================
# Dockerfile Multi-Stage para Laravel Backend
# ============================================

# Stage 1: Base con PHP y extensiones necesarias
FROM php:8.2-fpm-alpine AS base

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    icu-dev \
    mysql-client \
    nodejs \
    npm

# Instalar extensiones PHP requeridas por Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar pnpm globalmente
RUN npm install -g pnpm@10.18.1

# Configurar directorio de trabajo
WORKDIR /var/www

# ============================================
# Stage 2: Dependencias (para cache)
# ============================================
FROM base AS dependencies

COPY composer.json composer.lock* ./

# Instalar dependencias de Composer (sin autoload para aprovechar cache)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copiar package.json para assets (si existe)
COPY package.json* ./
RUN if [ -f package.json ]; then pnpm install; fi

# ============================================
# Stage 3: Desarrollo
# ============================================
FROM base AS development

# Copiar dependencias desde stage anterior
COPY --from=dependencies /var/www/vendor ./vendor
COPY --from=dependencies /var/www/node_modules ./node_modules

# Copiar el código de la aplicación
COPY . .

# Generar autoload optimizado
RUN composer dump-autoload --optimize

# Crear directorios necesarios con permisos correctos
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Establecer permisos
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Exponer puertos (8000 para Laravel, 8080 para Reverb)
EXPOSE 8000 8080

# Script de inicio
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=8000"]

# ============================================
# Stage 4: Producción
# ============================================
FROM base AS production

# Copiar dependencias optimizadas
COPY --from=dependencies /var/www/vendor ./vendor
COPY --from=dependencies /var/www/node_modules ./node_modules

# Copiar aplicación
COPY . .

# Generar autoload optimizado para producción
RUN composer dump-autoload --optimize --classmap-authoritative

# Build assets
RUN pnpm run build

# Optimizaciones de Laravel
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Crear directorios necesarios
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Establecer permisos
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Exponer puertos
EXPOSE 8000 8080

# Comando de inicio para producción
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
