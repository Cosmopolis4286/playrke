#!/bin/sh

# Production entrypoint script for Laravel

set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
while ! nc -z mysql 3306; do
    sleep 1
done
echo "Database is ready!"

# Wait for Redis to be ready
echo "Waiting for Redis to be ready..."
while ! nc -z redis 6379; do
    sleep 1
done
echo "Redis is ready!"

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Run Laravel optimizations if not already cached
if [ ! -f /var/www/bootstrap/cache/config.php ]; then
    echo "Running Laravel optimizations..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Execute the main command
exec "$@"
