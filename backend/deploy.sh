#!/bin/bash

# Production deployment script for PlayRKE

set -e

echo "🚀 Starting PlayRKE Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker first."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    print_error "docker-compose is not installed or not in PATH."
    exit 1
fi

# Create .env.production if it doesn't exist
if [ ! -f .env.production ]; then
    print_warning ".env.production not found. Creating from template..."
    cat > .env.production << EOF
################################
# APPLICATION
################################

APP_NAME="PlayRKE"
APP_ENV=production
APP_KEY=base64:FcQxhyCbxAt3FBAveGKcqDewGqGhjenOWyW4aFWFLP4=
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=America/Caracas

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

################################
# FRONTEND
################################

FRONTEND_URL=https://yourdomain.com
VITE_API_URL=https://yourdomain.com/api
VITE_APP_NAME="\${APP_NAME}"

################################
# SECURITY / SESSION
################################

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=yourdomain.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

################################
# DATABASE
################################

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=playrke
DB_USERNAME=playrke_user
DB_PASSWORD=playrke_password

################################
# CACHE / QUEUE
################################

CACHE_STORE=redis
CACHE_PREFIX=playrke_cache
QUEUE_CONNECTION=redis

################################
# LOGGING
################################

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

################################
# REDIS
################################

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

################################
# FILESYSTEM
################################

FILESYSTEM_DISK=public

################################
# MAIL
################################

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="\${APP_NAME}"

################################
# BROADCASTING (Reverb)
################################

BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=543291
REVERB_APP_KEY=EQ8iBLJ2ncDCSvT2U0HFUrZsT2NNxCht
REVERB_APP_SECRET=3IpJ0KQkurqefr7034MIWQ3VjMlM7y5L

REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=https

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="\${REVERB_HOST}"
VITE_REVERB_PORT="\${REVERB_PORT}"
VITE_REVERB_SCHEME="\${REVERB_SCHEME}"
EOF
    print_warning "Please update .env.production with your actual domain and credentials before running again."
    exit 1
fi

# Make entrypoint script executable
chmod +x docker/entrypoint.sh

# Stop existing containers if they exist
print_status "Stopping existing containers..."
docker-compose -f docker-compose.prod.yml down --remove-orphans || true

# Build and start services
print_status "Building and starting production containers..."
docker-compose -f docker-compose.prod.yml up --build -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Run database migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.prod.yml exec php php artisan migrate --force

# Clear and cache configurations
print_status "Optimizing application..."
docker-compose -f docker-compose.prod.yml exec php php artisan config:clear
docker-compose -f docker-compose.prod.yml exec php php artisan config:cache
docker-compose -f docker-compose.prod.yml exec php php artisan route:clear
docker-compose -f docker-compose.prod.yml exec php php artisan route:cache
docker-compose -f docker-compose.prod.yml exec php php artisan view:clear
docker-compose -f docker-compose.prod.yml exec php php artisan view:cache

# Create storage link
print_status "Creating storage symbolic link..."
docker-compose -f docker-compose.prod.yml exec php php artisan storage:link

# Show running containers
print_status "Deployment completed successfully! 🎉"
echo ""
echo "Running containers:"
docker-compose -f docker-compose.prod.yml ps

echo ""
print_status "Application is now running in production mode!"
echo "🌐 Your app should be available at: http://localhost"
echo "📊 Check logs with: docker-compose -f docker-compose.prod.yml logs -f"
echo "🛑 Stop with: docker-compose -f docker-compose.prod.yml down"
