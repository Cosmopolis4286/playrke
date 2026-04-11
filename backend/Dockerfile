# ============================================
# Stage 1: Base
# ============================================
FROM php:8.4-fpm-alpine AS base

# Labels para metadata
LABEL maintainer="PlayRKE Team" \
      version="1.0" \
      description="PlayRKE Backend - Laravel Application"

# Variables de entorno
ENV PHP_MEMORY_LIMIT=512M \
    PHP_UPLOAD_MAX_FILESIZE=100M \
    PHP_POST_MAX_SIZE=100M \
    PHP_MAX_EXECUTION_TIME=300 \
    NODE_ENV=production

# Dependencias del sistema y extensiones
RUN apk add --no-cache \
    # Dependencias básicas
    git curl zip unzip wget ca-certificates \
    # Dependencias de imágenes
    libpng-dev libzip-dev oniguruma-dev icu-dev \
    freetype-dev libjpeg-turbo-dev libwebp-dev libxpm-dev \
    # Dependencias adicionales
    mysql-client nodejs npm supervisor \
    # Herramientas del sistema
    bash htop nano \
    # Librerías para mejor rendimiento
    libxml2-dev libxslt-dev readline-dev sqlite-dev

# Configuración de extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    pdo_sqlite \
    xsl \
    xml \
    xmlwriter \
    simplexml

# Redis via PECL
RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# Configuración de OPcache para producción
RUN { \
        echo "opcache.memory_consumption=128"; \
        echo "opcache.interned_strings_buffer=8"; \
        echo "opcache.max_accelerated_files=4000"; \
        echo "opcache.revalidate_freq=2"; \
        echo "opcache.fast_shutdown=1"; \
        echo "opcache.enable_cli=1"; \
        echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Configuración de PHP-FPM
RUN { \
        echo "[www]"; \
        echo "user = www-data"; \
        echo "group = www-data"; \
        echo "listen = 9000"; \
        echo "listen.owner = www-data"; \
        echo "listen.group = www-data"; \
        echo "pm = dynamic"; \
        echo "pm.max_children = 50"; \
        echo "pm.start_servers = 5"; \
        echo "pm.min_spare_servers = 2"; \
        echo "pm.max_spare_servers = 10"; \
        echo "pm.max_requests = 500"; \
        echo "catch_workers_output = yes"; \
        echo "clear_env = no"; \
    } > /usr/local/etc/php-fpm.d/www.conf

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# pnpm global
RUN npm install -g pnpm@10.25.0

# Directorio de trabajo
WORKDIR /var/www

# ============================================
# Stage 2: Dependencies
# ============================================
FROM base AS dependencies

# Composer con optimización
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader \
    --apcu-autoloader

# Node dependencies con cache optimizado
COPY package.json* pnpm-lock.yaml* ./
RUN if [ -f package.json ]; then \
        pnpm install --frozen-lockfile --prod; \
        pnpm store prune; \
    fi

# ============================================
# Stage 3: Development
# ============================================
FROM base AS development

# Instalar dependencias de desarrollo
COPY --from=dependencies /var/www/vendor ./vendor
RUN mkdir -p node_modules
COPY --from=dependencies /var/www/node_modules* ./node_modules/

# Copiar archivos de la aplicación
COPY . .

# Permisos necesarios
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache vendor \
 && chmod -R 775 storage bootstrap/cache vendor

# Exponer puertos
EXPOSE 8000 8080

# Comando de desarrollo
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ============================================
# Stage 4: Production
# ============================================
FROM base AS production

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm -t || exit 1

# Copiar dependencias primero para mejor cache
COPY --from=dependencies /var/www/vendor ./vendor
RUN mkdir -p node_modules
COPY --from=dependencies /var/www/node_modules* ./node_modules/

# Copiar proyecto excluyendo archivos innecesarios
COPY --chown=www-data:www-data . .

# Optimización de composer
RUN composer dump-autoload --optimize --classmap-authoritative --apcu

# Build de assets frontend
RUN if [ -f package.json ]; then \
        pnpm run build; \
        pnpm store prune; \
    fi

# Cache de Laravel optimizado
RUN php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && php artisan event:cache

# Limpiar archivos innecesarios
RUN rm -rf \
    node_modules \
    .git \
    tests \
    docs \
    *.md \
    docker-compose.*.yml \
    Dockerfile* \
    .dockerignore \
    .env.example \
    .gitignore \
    .phpunit.cache \
    .php-cs-fixer.cache \
    .phpstan.cache \
    build/ \
    coverage.xml \
    phpunit.xml

# Permisos necesarios
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Configuración de Supervisor para múltiples procesos
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Exponer puertos
EXPOSE 9000 8000 8080

# Usar entrypoint personalizado
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Comando por defecto (usará Supervisor)
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
