# PlayRKE Bingo - Monorepo

Sistema completo de bingo en línea con arquitectura monorepo: **Laravel (Backend)** + **Nuxt 3 (Frontend)**.

## 🏗️ Estructura del Proyecto

```
playrke/
├── backend/                # Laravel API + WebSockets
│   ├── app/               # Código de aplicación
│   ├── config/            # Configuración
│   ├── database/          # Migraciones y seeders
│   ├── routes/            # Rutas API
│   ├── resources/         # Vistas Blade y assets
│   ├── storage/           # Archivos y logs
│   ├── tests/             # Tests PHPUnit
│   ├── .env              # Variables de entorno backend
│   ├── artisan           # CLI Laravel
│   ├── composer.json     # Dependencias PHP
│   ├── package.json      # Dependencias Node (Vite)
│   └── vite.config.ts    # Configuración Vite
│
├── frontend/              # Nuxt 3 SPA
│   ├── layers/           # Arquitectura en capas
│   │   ├── admin/       # Panel administrativo
│   │   ├── player/      # Panel de jugador
│   │   └── shared/      # Componentes compartidos
│   ├── pages/           # Páginas públicas
│   ├── .env             # Variables de entorno frontend
│   ├── nuxt.config.ts   # Configuración Nuxt
│   └── package.json     # Dependencias Node
│
├── docs/                 # Documentación
├── scripts/              # Scripts de deployment
│
├── .gitignore           # Git ignore (monorepo)
├── pnpm-workspace.yaml  # Configuración pnpm workspace
├── package.json         # Scripts principales del monorepo
├── docker-compose.yml   # Orquestación Docker
├── start-dev.bat        # Script inicio Windows
└── start-dev.sh         # Script inicio Linux/Mac
```

## 🚀 Inicio Rápido

### Opción 1: Script Automático (Recomendado)

#### Windows
```bash
start-dev.bat
```

#### Linux/Mac
```bash
chmod +x start-dev.sh
./start-dev.sh
```

El script:
- ✅ Verifica dependencias (Node, pnpm, PHP, Composer)
- ✅ Instala dependencias automáticamente
- ✅ Configura archivos .env
- ✅ Inicia todos los servicios en paralelo

### Opción 2: Manual

#### 1. Instalar Dependencias

```bash
# Instalar pnpm (si no lo tienes)
npm install -g pnpm

# Instalar todas las dependencias
pnpm run install:all

# O manualmente:
pnpm install                    # Dependencias del workspace
cd backend && composer install  # Dependencias PHP
cd ../frontend && pnpm install  # Dependencias frontend
```

#### 2. Configurar Backend

```bash
cd backend

# Copiar .env
cp .env.example .env

# Generar key
php artisan key:generate

# Configurar base de datos en .env
# DB_DATABASE=playrke
# DB_USERNAME=root
# DB_PASSWORD=

# Migrar base de datos
php artisan migrate

# Seeders (opcional)
php artisan db:seed
```

#### 3. Configurar Frontend

```bash
cd frontend
pnpm run setup
```

#### 4. Iniciar Servicios

```bash
# Desde la raíz del proyecto

# Backend API (puerto 8000)
cd backend && php artisan serve

# Frontend (puerto 3000) - en otra terminal
cd frontend && pnpm run dev

# WebSockets (puerto 8080) - en otra terminal
cd backend && php artisan reverb:start
```

### Opción 3: Docker (Desarrollo y Producción)

Para instrucciones completas de Docker, consulta [DOCKER.md](./DOCKER.md)

#### Inicio rápido con Docker

```bash
# 1. Copiar variables de entorno
cp .env.docker.example .env

# 2. Iniciar todos los servicios
docker-compose up -d

# 3. Ver logs
docker-compose logs -f

# 4. Esperar a que MySQL esté listo (30s) y ejecutar migraciones
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate --seed
```

Servicios disponibles:
- **Backend API**: http://localhost:8000
- **Frontend**: http://localhost:3000
- **WebSockets (Reverb)**: ws://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

Comandos útiles:
```bash
# Detener servicios
docker-compose stop

# Detener y eliminar contenedores
docker-compose down

# Rebuild servicios
docker-compose up -d --build

# Ver logs de un servicio
docker-compose logs -f backend
docker-compose logs -f frontend

# Acceder a shell de un contenedor
docker-compose exec backend sh
docker-compose exec frontend sh

# Ejecutar comandos Laravel
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan cache:clear

# Ejecutar comandos npm/pnpm
docker-compose exec frontend pnpm run build
docker-compose exec frontend pnpm test
```

## 📦 Gestión de Dependencias (pnpm)

Este proyecto usa **pnpm workspaces** para gestión eficiente de dependencias.

### Comandos Principales

```bash
# Instalar todo
pnpm run install:all

# Desarrollo (todos los servicios)
pnpm run dev

# Solo backend assets (Vite)
pnpm run dev:assets

# Solo frontend
pnpm run dev:frontend

# Build de producción
pnpm run build

# Limpiar todo
pnpm run clean

# Migraciones
pnpm run migrate
pnpm run migrate:fresh
```

### Agregar Dependencias

```bash
# Agregar al backend
pnpm --filter backend add <paquete>

# Agregar al frontend
pnpm --filter playrke-frontend add <paquete>

# Agregar dependencia PHP (backend)
cd backend && composer require <paquete>
```

## 🛠️ Scripts Disponibles

### Raíz del Proyecto

| Comando | Descripción |
|---------|-------------|
| `pnpm run dev` | Inicia todos los servicios en paralelo |
| `pnpm run dev:backend` | Solo Laravel API |
| `pnpm run dev:frontend` | Solo Nuxt frontend |
| `pnpm run dev:assets` | Solo Vite (assets backend) |
| `pnpm run dev:reverb` | Solo WebSockets |
| `pnpm run build` | Build de producción (todo) |
| `pnpm run install:all` | Instala todas las dependencias |
| `pnpm run clean` | Limpia node_modules |
| `pnpm run lint` | Ejecuta linters |
| `pnpm run test` | Ejecuta todos los tests |

### Backend (Laravel)

```bash
cd backend

# Desarrollo
php artisan serve                    # Servidor dev
php artisan reverb:start             # WebSockets
php artisan queue:work               # Procesar colas

# Base de datos
php artisan migrate                  # Migraciones
php artisan migrate:fresh --seed    # Resetear con datos
php artisan db:seed                  # Solo seeders

# Cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Testing
php artisan test
php artisan test --coverage

# Assets (Vite)
pnpm run dev                        # Desarrollo
pnpm run build                      # Producción
```

### Frontend (Nuxt 3)

```bash
cd frontend

# Desarrollo
pnpm run dev                         # Servidor con hot-reload
pnpm run setup                       # Configurar .env

# Build
pnpm run build                       # Producción
pnpm run preview                     # Preview del build

# Calidad
pnpm run lint                        # ESLint
pnpm run type-check                  # TypeScript check
```

## 🔑 Autenticación

Sistema de autenticación con **Laravel Sanctum** (stateful):

1. Frontend solicita token CSRF: `GET /sanctum/csrf-cookie`
2. Login/Register con credenciales
3. Laravel establece cookie de sesión
4. Todas las peticiones incluyen cookie automáticamente

## 🌐 API

### URLs Base

- **Desarrollo Backend**: `http://localhost:8000`
- **Desarrollo Frontend**: `http://localhost:3000`
- **Producción**: `https://api.playrke.com`

### Endpoints Principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/login` | Iniciar sesión |
| POST | `/api/auth/register` | Registrarse |
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/user` | Usuario actual |
| GET | `/api/bingo/rooms` | Listar salas |
| POST | `/api/bingo/rooms/{id}/purchase` | Comprar cartones |
| GET | `/api/admin/dashboard` | Dashboard admin |

📖 **Documentación completa**: [docs/api-endpoints-required.md](docs/api-endpoints-required.md)

## 🎯 Roles y Permisos

- **Super Admin** - Acceso completo
- **Admin** - Gestión de salas, transacciones, reportes
- **Customer** - Cliente con panel limitado
- **Treasurer** - Transacciones financieras
- **Seller** - Venta de cartones
- **Player** - Jugador estándar

## 🔌 WebSockets (Laravel Reverb)

Eventos en tiempo real:

```javascript
// Frontend (Nuxt)
const { listenToChannel } = useWebSocket()

listenToChannel('bingo.room.123', 'NumberCalled', (data) => {
  console.log('Número:', data.number)
})
```

**Eventos soportados**:
- `NumberCalled` - Nuevo número cantado
- `GameStarted` - Juego iniciado
- `GameEnded` - Juego finalizado
- `WinnerDeclared` - Ganador declarado

**Puerto**: `ws://localhost:8080` (desarrollo)

## 🧪 Testing

### Backend (PHPUnit)

```bash
cd backend
php artisan test
php artisan test --filter=AuthTest
php artisan test --coverage
```

### Frontend (Vitest)

```bash
cd frontend
pnpm run test
pnpm run test:e2e
```

## 🐳 Docker

### Desarrollo

```bash
docker-compose up -d
```

### Producción

Crear `docker-compose.prod.yml` con configuraciones optimizadas.

## 📊 Stack Tecnológico

### Backend
- **Laravel 11** - Framework PHP
- **PHP 8.2+** - Lenguaje
- **MySQL 8.0** - Base de datos
- **Redis** - Cache y colas
- **Laravel Reverb** - WebSockets
- **Laravel Sanctum** - Autenticación
- **Vite** - Build de assets

### Frontend
- **Nuxt 3** - Framework Vue
- **Vue 3** - Framework JavaScript
- **TypeScript** - Tipado estático
- **Tailwind CSS** - Estilos
- **Pinia** - State management
- **VueUse** - Utilidades Vue
- **Nuxt UI** - Componentes UI

### DevOps
- **pnpm** - Gestor de paquetes
- **Docker** - Containerización
- **Git** - Control de versiones

## 🔒 Seguridad

- ✅ Laravel Sanctum (autenticación)
- ✅ CSRF protection
- ✅ XSS protection
- ✅ SQL injection prevention (Eloquent)
- ✅ Rate limiting en API
- ✅ Validación de inputs
- ✅ HTTPS en producción
- ✅ Encriptación de datos sensibles

## 📚 Documentación Adicional

- **[Instalación Completa](docs/installation.md)** - Guía detallada
- **[Quick Start](docs/quick-start.md)** - Inicio en 5 minutos
- **[API Endpoints](docs/api-endpoints-required.md)** - Documentación API
- **[Checklist](docs/checklist-verificacion.md)** - Verificación
- **[Migración](docs/migracion-completada.md)** - Estado del proyecto

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -m 'feat: agregar funcionalidad'`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Pull Request

### Convenciones

**Commits** (Conventional Commits):
```
feat: nueva funcionalidad
fix: corrección de bug
docs: documentación
style: formateo
refactor: refactorización
test: tests
chore: mantenimiento
```

**Code Style**:
- Backend: PSR-12 (PHP)
- Frontend: ESLint + Prettier

## 🐛 Troubleshooting

### Error: "CORS policy"
Verificar `backend/config/cors.php` y `supports_credentials: true`

### Error: "CSRF token mismatch"
- Verificar configuración Sanctum
- Limpiar cookies del navegador

### Error: WebSocket no conecta
- Verificar que Reverb esté corriendo
- Revisar configuración en `.env`

### Error: pnpm no funciona
```bash
npm install -g pnpm
```

### Más ayuda
Ver [docs/installation.md](docs/installation.md)

## 📄 Licencia

Proyecto propietario - PlayRKE © 2025

## 👥 Equipo

Desarrollado por el equipo de PlayRKE

## 📞 Soporte

Para soporte técnico, contactar al equipo de desarrollo.

---

## ✅ Estado del Proyecto

- ✅ **Backend Laravel**: Funcional
- ✅ **Frontend Nuxt 3**: Funcional
- ✅ **API Integration**: Lista
- ✅ **WebSockets**: Implementado
- ✅ **Autenticación**: Funcionando
- ✅ **Monorepo**: Configurado
- ✅ **pnpm Workspaces**: Activo
- ✅ **Docker**: Disponible
- ✅ **Documentación**: Completa

**Versión**: 2.0.0 (Monorepo)
**Última actualización**: 2025-10-07
