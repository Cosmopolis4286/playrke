# PlayRKE - Reorganización a Monorepo

## 🎯 Resumen de Cambios

Este documento detalla la reorganización del proyecto PlayRKE de una estructura mixta a una arquitectura de **monorepo profesional** con mejores prácticas de desarrollo.

**Fecha**: 2025-10-07
**Versión**: 2.0.0 (Monorepo)

---

## 📁 Estructura Anterior vs Nueva

### Antes (Estructura Mixta)
```
playrke/
├── app/              # Laravel
├── config/           # Laravel
├── public/           # Laravel
├── resources/        # Laravel + Assets
├── frontend/         # Nuxt 3
├── package.json      # npm/node mezclado
└── composer.json     # PHP
```

### Después (Monorepo Organizado)
```
playrke/
├── backend/                    # ⚡ TODO LARAVEL AQUÍ
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── resources/
│   ├── storage/
│   ├── tests/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   ├── composer.json
│   ├── package.json           # Para Vite
│   └── vite.config.ts
│
├── frontend/                   # ⚡ TODO NUXT AQUÍ
│   ├── layers/
│   ├── pages/
│   ├── .env
│   ├── nuxt.config.ts
│   └── package.json
│
├── docs/                       # Documentación compartida
├── scripts/                    # Scripts de utilidad
│   └── generate-secrets.js    # Generador de .env
│
├── pnpm-workspace.yaml         # 🆕 Workspace pnpm
├── package.json                # 🆕 Scripts centralizados
├── docker-compose.yml          # 🆕 Orquestación
├── start-dev.bat               # 🆕 Inicio Windows
├── start-dev.sh                # 🆕 Inicio Linux/Mac
├── .gitignore                  # ✏️ Actualizado para monorepo
└── README.md                   # ✏️ Documentación completa
```

---

## 🔄 Cambios Realizados

### 1. Reorganización de Archivos

#### Backend (Laravel)
- ✅ Movidos a `backend/`: app, bootstrap, config, database, public, routes, storage, tests, vendor
- ✅ Configuración movida: .env, composer.json, artisan, phpunit.xml
- ✅ Assets de Vite: package.json, vite.config.ts, node_modules

#### Frontend (Nuxt)
- ✅ Mantenido en `frontend/` sin cambios
- ✅ Ya estaba correctamente organizado

### 2. Gestor de Paquetes: npm → pnpm

**¿Por qué pnpm?**
- ⚡ **Más rápido**: 2-3x más rápido que npm
- 💾 **Ahorra espacio**: Hasta 50% menos disco (hard links)
- 📦 **Workspaces nativos**: Mejor para monorepos
- 🔒 **Estricto**: Evita dependencias fantasma
- ✅ **Compatible**: Funciona con lock files existentes

**Instalado**: pnpm v10.18.1

### 3. Archivos de Configuración Nuevos

#### `pnpm-workspace.yaml`
```yaml
packages:
  - 'backend'
  - 'frontend'
```

#### `package.json` (raíz)
Scripts centralizados para gestionar todo el proyecto:
- `pnpm run dev` - Inicia todos los servicios
- `pnpm run setup` - Setup completo inicial
- `pnpm run generate:secrets` - Genera archivos .env
- `pnpm run migrate` - Ejecuta migraciones

#### `docker-compose.yml`
Orquestación completa de servicios:
- MySQL 8.0
- Redis 7
- Backend Laravel
- Frontend Nuxt
- phpMyAdmin

### 4. Scripts de Inicio Automatizados

#### Windows: `start-dev.bat`
- Verifica dependencias (Node, pnpm, PHP, Composer)
- Instala dependencias si faltan
- Configura .env automáticamente
- Inicia servicios en ventanas separadas

#### Linux/Mac: `start-dev.sh`
- Mismo comportamiento que Windows
- Con colores y mejor UX en terminal

### 5. Generador de Secretos

**Script**: `scripts/generate-secrets.js`

Genera automáticamente:
- ✅ `backend/.env` con todas las variables
- ✅ `frontend/.env` sincronizado con backend
- ✅ Claves de encriptación seguras (Laravel APP_KEY)
- ✅ Credenciales de WebSockets (Reverb)
- ✅ Archivos `.env.example` para referencia

**Uso**:
```bash
pnpm run generate:secrets
```

### 6. Actualización de .gitignore

Adaptado para estructura de monorepo:
- Paths actualizados: `/backend/...`, `/frontend/...`
- Soporte para pnpm: `/.pnpm-store`
- Ignorar node_modules en múltiples niveles
- Archivos .env protegidos por carpeta

### 7. README.md Completamente Reescrito

Nueva documentación profesional incluye:
- ✅ Estructura visual del proyecto
- ✅ 3 métodos de inicio (Script, Manual, Docker)
- ✅ Guía completa de pnpm workspaces
- ✅ Tabla de comandos disponibles
- ✅ Troubleshooting común
- ✅ Stack tecnológico detallado

---

## 🚀 Cómo Usar el Nuevo Sistema

### Inicio Rápido (Opción 1)

**Windows**:
```bash
start-dev.bat
```

**Linux/Mac**:
```bash
chmod +x start-dev.sh
./start-dev.sh
```

### Manual (Opción 2)

```bash
# 1. Instalar pnpm si no lo tienes
npm install -g pnpm

# 2. Setup inicial completo
pnpm run setup

# 3. Generar archivos .env
pnpm run generate:secrets

# 4. Configurar base de datos en backend/.env
# DB_DATABASE=playrke
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Migrar base de datos
pnpm run migrate

# 6. Iniciar todos los servicios
# Opción A: Automático (todos juntos)
pnpm run dev

# Opción B: Manual (en terminales separadas)
pnpm run dev:backend   # Terminal 1
pnpm run dev:frontend  # Terminal 2
pnpm run dev:reverb    # Terminal 3
```

### Docker (Opción 3)

```bash
docker-compose up -d
```

---

## 🌐 URLs de Servicios

| Servicio | URL | Descripción |
|----------|-----|-------------|
| **Backend API** | http://localhost:8000 | Laravel API REST |
| **Frontend** | http://localhost:3000 | Nuxt 3 SPA |
| **WebSockets** | ws://localhost:8080 | Laravel Reverb |
| **phpMyAdmin** | http://localhost:8081 | Administrar BD (Docker) |

---

## 📦 Gestión de Dependencias

### Comandos Principales

```bash
# Instalar todo
pnpm run install:all

# Agregar dependencia al backend
pnpm --filter backend add <paquete>

# Agregar dependencia al frontend
pnpm --filter playrke-frontend add <paquete>

# Agregar dependencia PHP
cd backend && composer require <paquete>

# Limpiar todo
pnpm run clean
```

---

## 🔧 Comandos Útiles

### Proyecto Completo

```bash
pnpm run dev              # Iniciar todo
pnpm run build            # Build de producción
pnpm run setup            # Setup inicial
pnpm run generate:secrets # Generar .env
pnpm run lint             # Linter
pnpm run test             # Tests
pnpm run clean            # Limpiar
```

### Backend Específico

```bash
cd backend

php artisan serve         # Servidor dev
php artisan migrate       # Migraciones
php artisan db:seed       # Seeders
php artisan reverb:start  # WebSockets
php artisan test          # Tests

pnpm run dev             # Vite (assets)
pnpm run build           # Build assets
```

### Frontend Específico

```bash
cd frontend

pnpm run dev             # Servidor Nuxt
pnpm run build           # Build producción
pnpm run preview         # Preview build
pnpm run lint            # ESLint
pnpm run type-check      # TypeScript
```

---

## 🔒 Seguridad y Secretos

### Archivos .env

**NO commitear** archivos con valores reales:
- ❌ `backend/.env`
- ❌ `frontend/.env`

**SÍ commitear** archivos de ejemplo:
- ✅ `backend/.env.example`
- ✅ `frontend/.env.example`

### Generación de Claves

El script `generate-secrets.js` genera:
- Laravel APP_KEY (base64, 32 bytes)
- JSON_ENCRYPTION_KEY (base64, 32 bytes)
- CTMER_ENCRYPTION_KEY (base64, 32 bytes)
- REVERB_APP_ID (6 dígitos aleatorios)
- REVERB_APP_KEY (alfanumérico, 20 caracteres)
- REVERB_APP_SECRET (alfanumérico, 20 caracteres)

Todas generadas con `crypto.randomBytes()` de Node.js (criptográficamente seguras).

---

## 🐛 Solución de Problemas

### Error: "pnpm: command not found"
```bash
npm install -g pnpm
```

### Error: Backend no inicia
```bash
cd backend
composer install
php artisan config:clear
php artisan serve
```

### Error: Frontend no inicia
```bash
cd frontend
pnpm install
pnpm run dev
```

### Error: "Class CookieController does not exist"
Este controlador fue comentado temporalmente en las rutas. No afecta la funcionalidad principal.

### Regenerar .env desde cero
```bash
# Respaldar existente
mv backend/.env backend/.env.old
mv frontend/.env frontend/.env.old

# Generar nuevos
pnpm run generate:secrets
```

---

## 📊 Beneficios de la Nueva Estructura

### Antes ❌
- Archivos mezclados (Laravel + assets)
- npm lento y pesado
- Difícil de escalar
- Sin orquestación
- Setup manual propenso a errores

### Ahora ✅
- **Separación clara** de responsabilidades
- **pnpm rápido** y eficiente
- **Scripts automatizados** de inicio
- **Docker-ready** para producción
- **Generación automática** de .env
- **Documentación completa** y profesional
- **Fácil de entender** para nuevos desarrolladores
- **Escalable** para agregar más servicios

---

## 🎓 Mejores Prácticas Aplicadas

1. ✅ **Separación de concerns**: Backend y frontend independientes
2. ✅ **Monorepo structure**: Gestión centralizada con workspaces
3. ✅ **Automatización**: Scripts para tareas comunes
4. ✅ **Seguridad**: Generación automática de secretos
5. ✅ **Docker**: Containerización lista para producción
6. ✅ **Documentación**: README detallado y actualizado
7. ✅ **Gitignore**: Protección de archivos sensibles
8. ✅ **DX (Developer Experience)**: Setup en un comando

---

## 📝 Notas de Migración

### Archivos Movidos

Todos los archivos de Laravel fueron movidos a `backend/`:
- Carpetas: app, bootstrap, config, database, public, routes, storage, tests, vendor, resources
- Archivos: .env, .env.example, artisan, composer.json, composer.lock, phpunit.xml, .editorconfig

Archivos de Vite también en `backend/`:
- package.json, package-lock.json, node_modules
- vite.config.ts, tailwind.config.js, postcss.config.js
- tsconfig.json, eslint.config.js, prettier.config.js

### Configuraciones Actualizadas

- ✅ `backend/.env`: APP_URL cambiado a http://localhost:8000
- ✅ `frontend/.env`: API_BASE actualizado
- ✅ `.gitignore`: Paths actualizados para monorepo
- ✅ Rutas comentadas: CookieController (temporal)

### Compatibilidad

- ✅ Laravel: Sin cambios en código, solo ubicación
- ✅ Nuxt: Sin cambios
- ✅ Vite: Funciona desde backend/
- ✅ Git: Historial preservado

---

## 🚢 Próximos Pasos Recomendados

1. **Probar el proyecto**
   ```bash
   pnpm run setup
   pnpm run dev
   ```

2. **Configurar base de datos**
   - Editar `backend/.env`
   - Ejecutar `pnpm run migrate`

3. **Implementar CookieController**
   - Descomentar rutas
   - Crear controlador faltante

4. **CI/CD**
   - Configurar GitHub Actions
   - Usar estructura monorepo en pipelines

5. **Producción**
   - Usar `docker-compose.prod.yml`
   - Configurar variables de entorno de producción

---

## 👥 Equipo y Soporte

**Reorganización realizada**: 2025-10-07
**Por**: Equipo de desarrollo PlayRKE
**Asistente**: Claude (Anthropic)

Para preguntas o problemas, contactar al equipo de desarrollo.

---

## ✅ Checklist de Verificación

- [x] Archivos Laravel movidos a `backend/`
- [x] Frontend intacto en `frontend/`
- [x] pnpm instalado y configurado
- [x] pnpm-workspace.yaml creado
- [x] package.json principal con scripts
- [x] docker-compose.yml creado
- [x] Scripts de inicio (Windows/Linux)
- [x] generate-secrets.js implementado
- [x] .gitignore actualizado
- [x] README.md reescrito
- [x] Backend funcionando en :8000
- [x] Frontend funcionando en :3000
- [x] Documentación completa

---

**Estado**: ✅ Reorganización completada exitosamente
**Versión**: 2.0.0 (Monorepo)
**Fecha**: 2025-10-07
