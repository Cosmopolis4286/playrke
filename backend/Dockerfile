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
    # PostgreSQL dependencies
    postgresql-dev postgresql-libs \
    # Dependencias adicionales
    mysql-client postgresql-client nodejs npm supervisor \
    # Herramientas del sistema
    bash htop nano \
    # Librerías para mejor rendimiento
    libxml2-dev libxslt-dev readline-dev sqlite-dev \
    # gmp: aritmética de curva elíptica para derivación BIP32 de direcciones
    # TRON en App\Domains\Payments (módulo de pagos USDT-TRC20)
    gmp-dev

# Configuración de extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
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
    simplexml \
    gmp

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

# Xdebug para profiling/debugging
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
 && pecl install xdebug \
 && docker-php-ext-enable xdebug \
 && apk del $PHPIZE_DEPS

# OPcache en modo dev: revalida en cada request
RUN { \
        echo "opcache.memory_consumption=128"; \
        echo "opcache.interned_strings_buffer=8"; \
        echo "opcache.max_accelerated_files=4000"; \
        echo "opcache.revalidate_freq=0"; \
        echo "opcache.validate_timestamps=1"; \
        echo "opcache.enable_cli=1"; \
    } > /usr/local/etc/php/conf.d/opcache-dev.ini

# Xdebug config
RUN { \
        echo "xdebug.mode=debug,coverage"; \
        echo "xdebug.start_with_request=yes"; \
        echo "xdebug.client_host=host.docker.internal"; \
        echo "xdebug.client_port=9003"; \
        echo "xdebug.log_level=0"; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# PHP-FPM pool liviano para dev
RUN { \
        echo "[www]"; \
        echo "user = www-data"; \
        echo "group = www-data"; \
        echo "listen = 9000"; \
        echo "listen.owner = www-data"; \
        echo "listen.group = www-data"; \
        echo "pm = dynamic"; \
        echo "pm.max_children = 10"; \
        echo "pm.start_servers = 2"; \
        echo "pm.min_spare_servers = 1"; \
        echo "pm.max_spare_servers = 4"; \
        echo "catch_workers_output = yes"; \
        echo "clear_env = no"; \
    } > /usr/local/etc/php-fpm.d/www.conf

# Composer con deps de desarrollo
COPY composer.json composer.lock* ./
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Guardar vendor en ruta alternativa como respaldo para el entrypoint
# (el bind mount de /var/www pisa /var/www/vendor en runtime)
RUN cp -r vendor /var/www-image-vendor

RUN mkdir -p node_modules
COPY package.json* pnpm-lock.yaml* ./
RUN if [ -f package.json ]; then pnpm install --no-frozen-lockfile; fi

# Directorios de runtime (el código llega por bind mount)
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint-dev.sh
RUN chmod +x /usr/local/bin/entrypoint-dev.sh \
 && sed -i 's|/var/www-image/vendor|/var/www-image-vendor|g' /usr/local/bin/entrypoint-dev.sh

EXPOSE 9000 8080

ENTRYPOINT ["/usr/local/bin/entrypoint-dev.sh"]
CMD ["php-fpm"]

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
