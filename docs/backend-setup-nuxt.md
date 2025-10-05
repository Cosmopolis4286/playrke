# Configuración del Backend Laravel para Nuxt 3

Esta guía explica cómo configurar el backend Laravel para trabajar con el frontend Nuxt 3.

## 📋 Tabla de Contenidos

- [Archivos Creados](#archivos-creados)
- [Configuración Paso a Paso](#configuración-paso-a-paso)
- [Endpoints API Disponibles](#endpoints-api-disponibles)
- [Testing de la API](#testing-de-la-api)
- [Troubleshooting](#troubleshooting)

---

## 📦 Archivos Creados

### Controladores API

```
app/Http/Controllers/Api/
├── Auth/
│   └── AuthController.php          # Login, Register, Logout
├── UserController.php               # Profile, Avatar, Preferences
├── BingoController.php              # Rooms, Cards, Games
├── WalletController.php             # Wallets, Transactions
└── Admin/
    └── AdminController.php          # Admin CRUD operations
```

### Configuración

```
config/
├── cors.php                         # CORS configuration (NUEVO)
└── sanctum.php                      # Sanctum configuration (NUEVO)
```

### Rutas

```
routes/
└── api.php                          # API routes (ACTUALIZADO)
```

---

## ⚙️ Configuración Paso a Paso

### 1. Variables de Entorno

Actualiza tu archivo `.env` con las siguientes variables:

```bash
# Session Configuration
SESSION_DRIVER=database
SESSION_DOMAIN=localhost

# Sanctum Stateful Domains
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:3001,127.0.0.1:3000

# Frontend URL
FRONTEND_URL=http://localhost:3000

# App Configuration
APP_URL=http://localhost:8000
```

### 2. Instalar Dependencias (si no están instaladas)

```bash
composer require laravel/sanctum
```

### 3. Publicar Configuración de Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**Nota**: Los archivos de configuración ya están creados en `config/sanctum.php` y `config/cors.php`

### 4. Migrar Base de Datos

Asegúrate de que todas las migraciones estén ejecutadas:

```bash
php artisan migrate
```

### 5. Limpiar Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 6. Iniciar Servidor

```bash
php artisan serve
```

El backend estará disponible en `http://localhost:8000`

---

## 🔌 Endpoints API Disponibles

### Autenticación (Public)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/login` | Iniciar sesión |
| POST | `/api/auth/register` | Registrarse |
| POST | `/api/auth/forgot-password` | Recuperar contraseña |
| POST | `/api/auth/reset-password` | Restablecer contraseña |

### Autenticación (Protected)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/auth/user` | Usuario actual |

### Usuario

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/user/profile` | Obtener perfil |
| PUT | `/api/user/profile` | Actualizar perfil |
| PUT | `/api/user/password` | Cambiar contraseña |
| POST | `/api/user/avatar` | Subir avatar |
| DELETE | `/api/user/avatar` | Eliminar avatar |
| GET | `/api/user/preferences` | Obtener preferencias |
| PUT | `/api/user/preferences` | Actualizar preferencias |
| GET | `/api/user/stats` | Estadísticas |

### Bingo

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/bingo/rooms` | Listar salas |
| GET | `/api/bingo/rooms/{id}` | Detalle de sala |
| POST | `/api/bingo/rooms/{id}/purchase` | Comprar cartones |
| GET | `/api/bingo/rooms/{id}/cards` | Cartones del jugador |
| GET | `/api/bingo/rooms/{id}/draw` | Sorteo actual |
| GET | `/api/bingo/draw/{id}/numbers` | Números cantados |
| POST | `/api/bingo/cards/{id}/check-winner` | Verificar ganador |
| GET | `/api/bingo/rooms/{id}/winners` | Ganadores |
| GET | `/api/bingo/history` | Historial de juegos |
| POST | `/api/bingo/history/{id}/notify-payment` | Notificar pago |

### Wallet

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/wallet` | Listar billeteras |
| GET | `/api/wallet/balance` | Obtener balance |
| POST | `/api/wallet/deposit` | Solicitar depósito |
| POST | `/api/wallet/withdraw` | Solicitar retiro |
| GET | `/api/wallet/transactions` | Historial de transacciones |
| GET | `/api/wallet/transactions/{id}` | Detalle de transacción |

### Admin (Requiere rol admin)

#### Dashboard
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/dashboard` | Estadísticas del dashboard |

#### Customers
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/customers` | Listar clientes |
| GET | `/api/admin/customers/{id}` | Detalle de cliente |
| POST | `/api/admin/customers` | Crear cliente |
| PUT | `/api/admin/customers/{id}` | Actualizar cliente |
| DELETE | `/api/admin/customers/{id}` | Eliminar cliente |

#### Treasurers
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/treasurers` | Listar tesoreros |
| GET | `/api/admin/treasurers/{id}` | Detalle de tesorero |
| POST | `/api/admin/treasurers` | Crear tesorero |
| PUT | `/api/admin/treasurers/{id}` | Actualizar tesorero |
| DELETE | `/api/admin/treasurers/{id}` | Eliminar tesorero |

#### Sellers
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/sellers` | Listar vendedores |
| GET | `/api/admin/sellers/{id}` | Detalle de vendedor |
| POST | `/api/admin/sellers` | Crear vendedor |
| PUT | `/api/admin/sellers/{id}` | Actualizar vendedor |
| DELETE | `/api/admin/sellers/{id}` | Eliminar vendedor |

#### Rooms
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/rooms` | Listar salas |
| GET | `/api/admin/rooms/{id}` | Detalle de sala |
| POST | `/api/admin/rooms` | Crear sala |
| PUT | `/api/admin/rooms/{id}` | Actualizar sala |
| DELETE | `/api/admin/rooms/{id}` | Eliminar sala |
| POST | `/api/admin/rooms/{id}/start` | Iniciar juego |

#### Transactions
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/transactions` | Listar transacciones |
| GET | `/api/admin/transactions/{id}` | Detalle de transacción |
| POST | `/api/admin/transactions/{id}/approve` | Aprobar transacción |
| POST | `/api/admin/transactions/{id}/reject` | Rechazar transacción |

#### Reports & Settings
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/admin/reports` | Obtener reportes |
| GET | `/api/admin/reports/export` | Exportar reporte |
| GET | `/api/admin/settings` | Obtener configuración |
| PUT | `/api/admin/settings` | Actualizar configuración |

### Health Check

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/ping` | Verificar estado del API |

---

## 🧪 Testing de la API

### 1. Health Check

```bash
curl http://localhost:8000/api/ping
```

**Respuesta esperada**:
```json
{
  "status": "ok",
  "timestamp": "2025-10-05T12:00:00.000000Z",
  "version": "1.0",
  "service": "PlayRKE Bingo API"
}
```

### 2. CSRF Cookie (Necesario antes de login/register)

```bash
curl http://localhost:8000/sanctum/csrf-cookie \
  -H "Accept: application/json" \
  -c cookies.txt
```

### 3. Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{
    "username": "testplayer",
    "email": "test@example.com",
    "first_name": "Test",
    "last_name": "Player",
    "password": "password123",
    "password_confirmation": "password123",
    "country_id": 1,
    "terms": true
  }'
```

### 4. Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{
    "login": "testplayer",
    "password": "password123"
  }'
```

### 5. Get User Profile (Authenticated)

```bash
curl http://localhost:8000/api/user/profile \
  -H "Accept: application/json" \
  -b cookies.txt
```

### 6. Get Bingo Rooms

```bash
curl http://localhost:8000/api/bingo/rooms \
  -H "Accept: application/json" \
  -b cookies.txt
```

---

## 🔧 Troubleshooting

### Error: "CORS policy: No 'Access-Control-Allow-Origin'"

**Solución**:
1. Verifica que `config/cors.php` existe
2. Asegúrate de que `FRONTEND_URL` esté en `.env`
3. Limpia cache: `php artisan config:clear`
4. Verifica que el frontend esté en la lista `allowed_origins` de `config/cors.php`

### Error: "CSRF token mismatch"

**Solución**:
1. Asegúrate de llamar `/sanctum/csrf-cookie` antes de login/register
2. Verifica que `SESSION_DOMAIN` en `.env` sea `localhost`
3. Verifica que `SANCTUM_STATEFUL_DOMAINS` incluya `localhost:3000`
4. Limpia cookies del navegador o cliente API

### Error: "Unauthenticated"

**Solución**:
1. Verifica que la cookie de sesión se está enviando
2. En Nuxt, asegúrate de usar `credentials: 'include'` en todas las peticiones
3. Verifica que `supports_credentials` esté en `true` en `config/cors.php`

### Error: 404 en rutas API

**Solución**:
1. Verifica que Laravel esté corriendo: `php artisan serve`
2. Limpia cache de rutas: `php artisan route:clear`
3. Lista rutas disponibles: `php artisan route:list --path=api`

### Error: "Class 'User' not found"

**Solución**:
1. Verifica que el modelo `User` tenga el trait `HasRoles`
2. Asegúrate de que Spatie Permission esté instalado:
   ```bash
   composer require spatie/laravel-permission
   ```

---

## 📝 Notas Importantes

### Autenticación Stateful

Este sistema usa **Laravel Sanctum Stateful Authentication** con cookies:

1. **No se usan tokens Bearer** en headers
2. **Las cookies se envían automáticamente** con `credentials: 'include'`
3. **CSRF token es obligatorio** para POST/PUT/DELETE

### Formato de Respuestas

Todas las respuestas siguen este formato:

**Éxito**:
```json
{
  "success": true,
  "message": "Mensaje opcional",
  "data": {}
}
```

**Error**:
```json
{
  "success": false,
  "message": "Descripción del error",
  "errors": {}
}
```

### Paginación

Los endpoints que retornan listas incluyen paginación:

```json
{
  "success": true,
  "items": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

---

## ✅ Checklist de Verificación

- [ ] `.env` configurado con `SESSION_DOMAIN` y `SANCTUM_STATEFUL_DOMAINS`
- [ ] `config/cors.php` existe y tiene `supports_credentials: true`
- [ ] `config/sanctum.php` existe y tiene los dominios correctos
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Cache limpiado (`php artisan config:clear`)
- [ ] Servidor Laravel corriendo (`php artisan serve`)
- [ ] Health check funciona (`/api/ping`)
- [ ] CSRF cookie funciona (`/sanctum/csrf-cookie`)
- [ ] Login funciona con cookies
- [ ] Frontend Nuxt conectado y autenticándose

---

## 🚀 Próximos Pasos

1. **Implementar lógica de negocio** en los TODOs de los controladores
2. **Configurar Laravel Reverb** para WebSockets
3. **Crear seeders** con datos de prueba
4. **Escribir tests** para los endpoints
5. **Documentar con Swagger/OpenAPI** (opcional)

---

**Última actualización**: 2025-10-05
**Versión del backend**: 1.0.0
**Compatible con**: Nuxt 3 Frontend v1.0.0
