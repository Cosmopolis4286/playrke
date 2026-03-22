#!/bin/sh

# Production entrypoint script for Laravel

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to wait for service
wait_for_service() {
    local host=$1
    local port=$2
    local service=$3
    local timeout=${4:-30}
    
    print_status "Waiting for $service to be ready..."
    
    for i in $(seq 1 $timeout); do
        if nc -z $host $port; then
            print_status "$service is ready!"
            return 0
        fi
        sleep 1
    done
    
    print_error "$service is not ready after ${timeout}s"
    exit 1
}

# Wait for database to be ready
wait_for_service mysql 3306 "MySQL" 60

# Wait for Redis to be ready
wait_for_service redis 6379 "Redis" 30

# Set proper permissions
print_status "Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Create log directories
mkdir -p /var/log/supervisor

# Run Laravel optimizations if not already cached
if [ ! -f /var/www/bootstrap/cache/config.php ]; then
    print_status "Running Laravel optimizations..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Run database migrations only if explicitly requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    print_status "Running database migrations..."
    php artisan migrate --force
fi

# Clear and warmup caches
print_status "Clearing and warming up caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Create storage link if not exists
if [ ! -L /var/www/public/storage ]; then
    print_status "Creating storage symbolic link..."
    php artisan storage:link
fi

# Optimize autoloader
print_status "Optimizing autoloader..."
composer dump-autoload --optimize --classmap-authoritative --apcu

print_status "PlayRKE Backend is ready! 🚀"

# Execute the main command
exec "$@"
