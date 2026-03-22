# Docker Production Setup for PlayRKE

This guide explains how to deploy PlayRKE in production using Docker and Docker Compose.

## 🏗️ Architecture

The production setup includes:

- **Nginx**: Web server and reverse proxy
- **PHP-FPM**: Laravel application server
- **MySQL 8.0**: Database server
- **Redis 7**: Caching and session storage
- **Laravel Reverb**: WebSocket server for real-time features
- **Queue Worker**: Background job processing
- **Scheduler**: Cron job scheduler

## 📋 Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- At least 2GB RAM
- 10GB+ disk space

## 🚀 Quick Start

1. **Clone and navigate to the backend directory:**
   ```bash
   cd backend
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env.production
   # Edit .env.production with your production settings
   ```

3. **Deploy with the automated script:**
   ```bash
   chmod +x deploy.sh
   ./deploy.sh
   ```

## ⚙️ Manual Deployment

If you prefer to deploy manually:

1. **Build and start services:**
   ```bash
   docker-compose -f docker-compose.prod.yml up --build -d
   ```

2. **Run migrations:**
   ```bash
   docker-compose -f docker-compose.prod.yml exec php php artisan migrate --force
   ```

3. **Optimize application:**
   ```bash
   docker-compose -f docker-compose.prod.yml exec php php artisan config:cache
   docker-compose -f docker-compose.prod.yml exec php php artisan route:cache
   docker-compose -f docker-compose.prod.yml exec php php artisan view:cache
   ```

## 🔧 Configuration

### Environment Variables

Key production settings in `.env.production`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_HOST=mysql`
- `REDIS_HOST=redis`
- `SESSION_DRIVER=redis`
- `CACHE_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

### SSL/HTTPS Setup

1. Place SSL certificates in `docker/nginx/ssl/`:
   ```
   docker/nginx/ssl/cert.pem
   docker/nginx/ssl/key.pem
   ```

2. Update `nginx.conf` to use HTTPS (uncomment SSL section)

### Database Configuration

The setup uses MySQL 8.0 with optimized settings for production. Default credentials:
- Database: `playrke`
- User: `playrke_user`
- Password: `playrke_password`

Change these in `.env.production` and the docker-compose file.

## 📊 Monitoring

### View Logs
```bash
# All services
docker-compose -f docker-compose.prod.yml logs -f

# Specific service
docker-compose -f docker-compose.prod.yml logs -f php
docker-compose -f docker-compose.prod.yml logs -f nginx
```

### Check Container Status
```bash
docker-compose -f docker-compose.prod.yml ps
```

### Access Application Shell
```bash
docker-compose -f docker-compose.prod.yml exec php sh
```

## 🔒 Security Considerations

1. **Change default passwords** before deploying to production
2. **Use HTTPS** by configuring SSL certificates
3. **Set up firewall** rules to restrict access
4. **Regular updates** of Docker images
5. **Backup strategy** for database and storage

## 📁 Important Files

- `docker-compose.prod.yml`: Production services configuration
- `docker/nginx/nginx.conf`: Nginx web server configuration
- `docker/mysql/my.cnf`: MySQL optimization settings
- `docker/redis/redis.conf`: Redis configuration
- `docker/entrypoint.sh`: Container startup script
- `deploy.sh`: Automated deployment script

## 🔄 Common Commands

### Stop Services
```bash
docker-compose -f docker-compose.prod.yml down
```

### Rebuild Services
```bash
docker-compose -f docker-compose.prod.yml up --build -d
```

### Clear Caches
```bash
docker-compose -f docker-compose.prod.yml exec php php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec php php artisan config:clear
```

### Backup Database
```bash
docker-compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p playrke > backup.sql
```

### Restore Database
```bash
docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u root -p playrke < backup.sql
```

## 🐛 Troubleshooting

### Permission Issues
```bash
docker-compose -f docker-compose.prod.yml exec php chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec php chmod -R 775 storage bootstrap/cache
```

### Queue Issues
```bash
docker-compose -f docker-compose.prod.yml restart queue
```

### Migration Issues
```bash
docker-compose -f docker-compose.prod.yml exec php php artisan migrate:rollback
docker-compose -f docker-compose.prod.yml exec php php artisan migrate --force
```

## 📈 Performance Optimization

The setup includes several optimizations:

- **Nginx**: Gzip compression, static asset caching
- **PHP-FPM**: Optimized for production
- **MySQL**: InnoDB buffer pool, query logging
- **Redis**: Memory management, AOF persistence
- **Laravel**: Config/route/view caching

## 🌐 Domain Configuration

1. Update `APP_URL` and `FRONTEND_URL` in `.env.production`
2. Update `server_name` in `nginx.conf`
3. Update `SESSION_DOMAIN` in `.env.production`
4. Update `SANCTUM_STATEFUL_DOMAINS` in `.env.production`

## 📞 Support

For issues related to:
- **Docker setup**: Check this README first
- **Laravel application**: Check Laravel documentation
- **Database**: Check MySQL logs
- **Web server**: Check Nginx logs
