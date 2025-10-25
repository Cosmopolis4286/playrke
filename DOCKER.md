# 🐳 Guía Docker - PlayRKE Monorepo

## 📋 Tabla de Contenidos
- [Requisitos Previos](#requisitos-previos)
- [Arquitectura](#arquitectura)
- [Configuración Inicial](#configuración-inicial)
- [Comandos Básicos](#comandos-básicos)
- [Servicios Disponibles](#servicios-disponibles)
- [Desarrollo](#desarrollo)
- [Producción](#producción)
- [Troubleshooting](#troubleshooting)

---

## 🔧 Requisitos Previos

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git
- mínimo 4GB RAM disponible
- Puerto disponibles: 3000, 3306, 6379, 8000, 8080, 8081

## 🏗️ Arquitectura

El proyecto está containerizado con los siguientes servicios:

```
┌─────────────────────────────────────────────────────┐
│                   PlayRKE Monorepo                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐         │
│  │ Frontend │  │ Backend  │  │  Reverb  │         │
│  │  Nuxt 3  │  │ Laravel  │  │WebSocket │         │
│  │  :3000   │  │  :8000   │  │  :8080   │         │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘         │
│       │             │             │                │
│       └─────────────┴─────────────┘                │
│                     │                              │
│       ┌─────────────┴─────────────┐                │
│       │                           │                │
│  ┌────▼────┐  ┌────────┐  ┌──────▼──────┐         │
│  │  MySQL  │  │ Redis  │  │ PhpMyAdmin  │         │
│  │  :3306  │  │ :6379  │  │    :8081    │         │
│  └─────────┘  └────────┘  └─────────────┘         │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Servicios

1. **MySQL 8.0** - Base de datos principal
2. **Redis 7** - Cache y colas
3. **Backend Laravel** - API REST + Inertia
4. **Reverb** - Servidor WebSocket
5. **Frontend Nuxt 3** - SPA con SSR
6. **PhpMyAdmin** - Administración de BD (opcional)
7. **Queue Worker** - Procesador de colas (opcional)

---

## ⚙️ Configuración Inicial

### 1. Clonar el repositorio

```bash
git clone <repository-url>
cd playrke
```

### 2. Configurar variables de entorno

```bash
# Copiar configuración de ejemplo
cp .env.docker.example .env

# Editar según necesidades
nano .env
```

### 3. Configurar backend Laravel

```bash
# Copiar .env del backend
cp backend/.env.example backend/.env

# Generar APP_KEY (se puede hacer después del primer build)
```

### 4. Configurar frontend Nuxt

```bash
# Copiar .env del frontend
cp frontend/.env.example frontend/.env
```

---

## 🚀 Comandos Básicos

### Iniciar todos los servicios

```bash
# Modo desarrollo (con hot reload)
docker-compose up -d

# Ver logs en tiempo real
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f backend
docker-compose logs -f frontend
```

### Detener servicios

```bash
# Detener sin eliminar
docker-compose stop

# Detener y eliminar contenedores
docker-compose down

# Detener, eliminar contenedores y volúmenes
docker-compose down -v
```

### Reconstruir contenedores

```bash
# Rebuild completo
docker-compose build --no-cache

# Rebuild de un servicio específico
docker-compose build backend
docker-compose build frontend

# Rebuild y reiniciar
docker-compose up -d --build
```

---

## 🛠️ Servicios Disponibles

### Frontend (Nuxt 3)

- **URL**: http://localhost:3000
- **Hot Reload**: Activado
- **Comando**: `pnpm run dev`

```bash
# Acceder al contenedor
docker-compose exec frontend sh

# Instalar dependencias adicionales
docker-compose exec frontend pnpm add <package>

# Ejecutar tests
docker-compose exec frontend pnpm test
```

### Backend (Laravel)

- **URL**: http://localhost:8000
- **API**: http://localhost:8000/api
- **Comando**: `php artisan serve`

```bash
# Acceder al contenedor
docker-compose exec backend sh

# Ejecutar migraciones
docker-compose exec backend php artisan migrate

# Ejecutar seeders
docker-compose exec backend php artisan db:seed

# Limpiar cache
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear

# Crear controlador
docker-compose exec backend php artisan make:controller UserController

# Instalar dependencias Composer
docker-compose exec backend composer require <package>
```

### Laravel Reverb (WebSocket)

- **URL**: ws://localhost:8080
- **Comando**: `php artisan reverb:start`

```bash
# Ver logs de Reverb
docker-compose logs -f reverb

# Reiniciar Reverb
docker-compose restart reverb
```

### MySQL

- **Host**: localhost
- **Puerto**: 3306
- **Base de datos**: playrke
- **Usuario**: playrke
- **Contraseña**: playrke

```bash
# Conectarse desde el host
mysql -h 127.0.0.1 -u playrke -pplayrke playrke

# Conectarse desde otro contenedor
mysql -h mysql -u playrke -pplayrke playrke

# Ejecutar backup
docker-compose exec mysql mysqldump -u root -proot playrke > backup.sql

# Restaurar backup
docker-compose exec -T mysql mysql -u root -proot playrke < backup.sql
```

### Redis

- **Host**: localhost
- **Puerto**: 6379

```bash
# Conectarse a Redis CLI
docker-compose exec redis redis-cli

# Ver todas las keys
docker-compose exec redis redis-cli KEYS '*'

# Limpiar cache
docker-compose exec redis redis-cli FLUSHALL
```

### PhpMyAdmin

- **URL**: http://localhost:8081
- **Usuario**: root
- **Contraseña**: root

---

## 💻 Desarrollo

### Setup inicial completo

```bash
# 1. Iniciar servicios
docker-compose up -d

# 2. Esperar a que MySQL esté listo (30s aprox)
docker-compose logs -f mysql | grep "ready for connections"

# 3. Generar APP_KEY de Laravel
docker-compose exec backend php artisan key:generate

# 4. Ejecutar migraciones
docker-compose exec backend php artisan migrate --seed

# 5. Instalar dependencias frontend (si no están)
docker-compose exec frontend pnpm install

# 6. Build de assets del backend
docker-compose exec backend pnpm install
docker-compose exec backend pnpm run build
```

### Workflow diario

```bash
# Iniciar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f backend frontend

# Hacer cambios en el código (hot reload automático)

# Ejecutar tests
docker-compose exec backend php artisan test
docker-compose exec frontend pnpm test

# Detener al finalizar
docker-compose stop
```

### Ejecutar comandos personalizados

```bash
# Backend
docker-compose exec backend php artisan <comando>

# Frontend
docker-compose exec frontend pnpm run <script>

# Shell interactivo
docker-compose exec backend bash
docker-compose exec frontend sh
```

---

## 🏭 Producción

### Build para producción

```bash
# Build con target production
docker-compose -f docker-compose.prod.yml build

# Iniciar en modo producción
docker-compose -f docker-compose.prod.yml up -d
```

### Optimizaciones Laravel

```bash
# Cache de configuración
docker-compose exec backend php artisan config:cache

# Cache de rutas
docker-compose exec backend php artisan route:cache

# Cache de vistas
docker-compose exec backend php artisan view:cache

# Optimizar autoload
docker-compose exec backend composer dump-autoload --optimize --classmap-authoritative
```

### Optimizaciones Nuxt

```bash
# Build optimizado
docker-compose exec frontend pnpm run build

# Preview de producción
docker-compose exec frontend pnpm run preview
```

---

## 🔍 Troubleshooting

### El frontend no puede conectarse al backend

```bash
# Verificar que backend está ejecutándose
docker-compose ps

# Verificar logs
docker-compose logs backend

# Verificar red
docker network inspect playrke_playrke-network
```

### Error de permisos en Laravel

```bash
# Dar permisos a storage y cache
docker-compose exec backend chmod -R 775 storage bootstrap/cache
docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

### MySQL no inicia

```bash
# Ver logs
docker-compose logs mysql

# Eliminar volúmenes y reiniciar
docker-compose down -v
docker-compose up -d mysql
```

### Puerto ya en uso

```bash
# Ver qué proceso usa el puerto
# Windows
netstat -ano | findstr :3000

# Linux/Mac
lsof -i :3000

# Cambiar puerto en .env
FRONTEND_PORT=3001
```

### Reinstalar dependencias

```bash
# Frontend
docker-compose exec frontend rm -rf node_modules .nuxt
docker-compose exec frontend pnpm install

# Backend
docker-compose exec backend rm -rf vendor
docker-compose exec backend composer install
```

### Reiniciar desde cero

```bash
# Detener todo
docker-compose down -v

# Eliminar volúmenes manualmente
docker volume prune

# Rebuild completo
docker-compose build --no-cache
docker-compose up -d

# Ejecutar setup
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate:fresh --seed
```

---

## 📊 Monitoreo

### Ver recursos consumidos

```bash
# Stats en tiempo real
docker stats

# Espacio en disco
docker system df

# Ver logs de tamaño
du -sh $(docker volume inspect -f '{{ .Mountpoint }}' playrke_mysql_data)
```

### Healthchecks

```bash
# Ver estado de salud de servicios
docker-compose ps

# Inspeccionar health de un contenedor
docker inspect --format='{{json .State.Health}}' playrke-backend | jq
```

---

## 🔒 Seguridad

### Recomendaciones

1. **Cambiar credenciales por defecto** en producción
2. **No commitear** archivos `.env` con datos sensibles
3. **Usar secrets de Docker** para información sensible en producción
4. **Actualizar imágenes** regularmente:

```bash
docker-compose pull
docker-compose up -d --build
```

5. **Escanear vulnerabilidades**:

```bash
docker scan playrke-backend
docker scan playrke-frontend
```

---

## 📚 Referencias

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Documentation](https://laravel.com/docs)
- [Nuxt 3 Documentation](https://nuxt.com/docs)
- [Laravel Reverb](https://laravel.com/docs/reverb)

---

## 📝 Notas Adicionales

### Volúmenes persistentes

Los siguientes datos persisten entre reinicios:

- `mysql_data`: Base de datos
- `redis_data`: Cache Redis
- `backend_vendor`: Dependencias PHP
- `backend_node_modules`: Dependencias Node del backend
- `frontend_node_modules`: Dependencias Node del frontend

### Red

Todos los servicios están en la red `playrke-network` (172.20.0.0/16) y pueden comunicarse entre sí usando sus nombres de servicio como hostname.

### Performance

Para mejor performance en desarrollo:

1. Usar volúmenes nombrados para `node_modules` y `vendor`
2. Aumentar RAM asignada a Docker (mínimo 4GB)
3. Usar WSL2 en Windows para mejor rendimiento

---

**Última actualización**: 2025-10-25
