# PlayRKE Bingo - Monorepo

Sistema completo de bingo en línea con Laravel (backend) y Nuxt 3 (frontend).

## 🏗️ Estructura del Proyecto

```
playrke/
├── app/                    # Laravel backend
├── config/                 # Configuración Laravel
├── database/               # Migraciones y seeders
├── routes/                 # Rutas API Laravel
├── resources/              # Assets Laravel (legacy)
├── storage/                # Storage Laravel
├── public/                 # Public assets Laravel
│
├── frontend/               # Nuxt 3 frontend (SPA)
│   ├── layers/
│   │   ├── admin/         # Panel administrativo
│   │   ├── player/        # Panel de jugador
│   │   └── shared/        # Código compartido
│   ├── pages/             # Páginas públicas
│   └── ...
│
├── docs/                   # Documentación del proyecto
│   ├── installation.md
│   ├── quick-start.md
│   ├── api-endpoints-required.md
│   ├── checklist-verificacion.md
│   └── migracion-completada.md
│
├── .env                    # Variables de entorno Laravel
├── composer.json           # Dependencias PHP
└── artisan                 # CLI Laravel
```

## 🚀 Inicio Rápido

### Prerequisitos

- PHP >= 8.1
- Composer
- Node.js >= 18.0.0
- MySQL/PostgreSQL
- Redis (opcional)

### Instalación

#### 1. Backend Laravel

```bash
# Instalar dependencias PHP
composer install

# Copiar archivo de entorno
cp .env.example .env

# Generar key de aplicación
php artisan key:generate

# Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_DATABASE=playrke
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (opcional)
php artisan db:seed

# Iniciar servidor
php artisan serve
```

El backend estará disponible en `http://localhost:8000`

#### 2. Frontend Nuxt 3

```bash
# Navegar al directorio frontend
cd frontend

# Instalar dependencias
npm install

# Configurar variables de entorno
npm run setup

# Iniciar servidor de desarrollo
npm run dev
```

El frontend estará disponible en `http://localhost:3000`

#### 3. WebSockets (Laravel Reverb)

```bash
# En una nueva terminal
php artisan reverb:start
```

WebSockets estarán disponibles en `ws://localhost:6001`

## 📚 Documentación Completa

- **[Guía de Instalación Completa](docs/installation.md)** - Instalación paso a paso del frontend
- **[Quick Start](docs/quick-start.md)** - Inicio rápido en 5 minutos
- **[API Endpoints](docs/api-endpoints-required.md)** - Documentación completa de la API
- **[Checklist de Verificación](docs/checklist-verificacion.md)** - Verificar instalación
- **[Resumen de Migración](docs/migracion-completada.md)** - Estado del proyecto

## 🛠️ Scripts Útiles

### Backend (Laravel)

```bash
# Desarrollo
php artisan serve                    # Iniciar servidor
php artisan reverb:start             # Iniciar WebSockets
php artisan queue:work               # Procesar colas

# Base de datos
php artisan migrate                  # Ejecutar migraciones
php artisan migrate:fresh --seed    # Resetear DB con seeders
php artisan db:seed                  # Ejecutar seeders

# Cache
php artisan config:clear             # Limpiar cache de config
php artisan cache:clear              # Limpiar cache general
php artisan route:clear              # Limpiar cache de rutas

# Testing
php artisan test                     # Ejecutar tests
php artisan test --coverage          # Tests con coverage
```

### Frontend (Nuxt 3)

```bash
cd frontend

# Desarrollo
npm run dev                          # Servidor con hot-reload
npm run setup                        # Configurar .env

# Build
npm run build                        # Build de producción
npm run preview                      # Preview del build

# Calidad
npm run lint                         # ESLint
npm run type-check                   # TypeScript check
```

## 🔑 Autenticación

El sistema usa **Laravel Sanctum** para autenticación stateful:

1. Frontend solicita token CSRF: `GET /sanctum/csrf-cookie`
2. Login/Register con credenciales
3. Laravel establece cookie de sesión
4. Todas las peticiones posteriores incluyen cookie automáticamente

## 🌐 API

### Base URL

- **Desarrollo**: `http://localhost:8000/api`
- **Producción**: `https://api.playrke.com/api`

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

**Documentación completa**: [api-endpoints-required.md](docs/api-endpoints-required.md)

## 🎯 Roles y Permisos

El sistema maneja múltiples roles:

- **Super Admin** - Acceso completo al sistema
- **Admin** - Gestión de salas, transacciones, reportes
- **Customer** - Cliente con panel de administración limitado
- **Treasurer** - Manejo de transacciones financieras
- **Seller** - Venta de cartones
- **Player** - Jugador estándar

## 🔌 WebSockets

Eventos en tiempo real con Laravel Reverb/Pusher:

```javascript
// Frontend
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

## 🧪 Testing

### Backend

```bash
php artisan test
php artisan test --filter=AuthTest
php artisan test --coverage
```

### Frontend

```bash
cd frontend
npm run test              # Vitest (cuando esté configurado)
npm run test:e2e          # E2E tests (cuando esté configurado)
```

## 🚀 Deployment

### Backend (Laravel)

1. Configurar servidor (Apache/Nginx)
2. Configurar `.env` de producción
3. Ejecutar migraciones
4. Configurar supervisor para queues
5. SSL/HTTPS

### Frontend (Nuxt 3)

1. Build de producción:
   ```bash
   cd frontend
   npm run build
   ```
2. Deploy a servidor Node.js o plataforma (Vercel/Netlify)
3. Configurar variables de entorno de producción

## 🔒 Seguridad

- ✅ Laravel Sanctum para autenticación
- ✅ CSRF protection
- ✅ XSS protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Rate limiting en API
- ✅ Validación de inputs
- ✅ HTTPS en producción

## 📊 Stack Tecnológico

### Backend
- Laravel 10.x
- PHP 8.1+
- MySQL/PostgreSQL
- Redis
- Laravel Reverb (WebSockets)
- Laravel Sanctum (Auth)

### Frontend
- Nuxt 3
- Vue 3 (Composition API)
- TypeScript
- Tailwind CSS
- Pinia (State Management)
- Laravel Echo (WebSockets)

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature: `git checkout -b feature/nueva-funcionalidad`
3. Commit cambios: `git commit -m 'feat: agregar nueva funcionalidad'`
4. Push a la rama: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request

## 📝 Convenciones de Código

### Git Commits

Seguir [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: agregar nueva funcionalidad
fix: corregir bug
docs: actualizar documentación
style: formateo de código
refactor: refactorización
test: agregar tests
chore: tareas de mantenimiento
```

### Code Style

- **Backend**: PSR-12 (PHP)
- **Frontend**: ESLint + Prettier

## 🐛 Troubleshooting

### Error: "CORS policy"
- Verificar `config/cors.php` en Laravel
- Asegurar `supports_credentials: true`

### Error: "CSRF token mismatch"
- Verificar configuración de Sanctum
- Limpiar cookies del navegador

### Error: WebSocket no conecta
- Verificar que Reverb esté corriendo
- Verificar configuración en `.env`

Más soluciones en [installation.md](docs/installation.md)

## 📄 Licencia

Proyecto propietario - PlayRKE © 2025

## 👥 Equipo

Desarrollado por el equipo de PlayRKE

## 📞 Soporte

Para soporte técnico, contactar al equipo de desarrollo.

---

## ✅ Estado del Proyecto

- ✅ **Backend Laravel**: Funcional
- ✅ **Frontend Nuxt 3**: Migración 100% completada
- ✅ **API Integration**: Lista
- ✅ **WebSockets**: Implementado
- ✅ **Autenticación**: Funcionando
- ✅ **Documentación**: Completa

**Versión**: 1.0.0
**Última actualización**: 2025-10-05
