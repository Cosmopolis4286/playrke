# Guía de Instalación - PlayRKE Bingo Frontend

Esta guía te ayudará a instalar y configurar el frontend Nuxt 3 del sistema PlayRKE Bingo.

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado:

- **Node.js**: v18.0.0 o superior (recomendado: v20.x LTS)
- **npm**: v9.0.0 o superior (incluido con Node.js)
- **Git**: Para clonar el repositorio
- **Backend Laravel**: El backend debe estar corriendo en `http://localhost:8000`

### Verificar versiones instaladas

```bash
node --version   # Debe mostrar v18.0.0 o superior
npm --version    # Debe mostrar v9.0.0 o superior
```

---

## 🚀 Instalación Paso a Paso

### 1. Navegar al directorio del frontend

```bash
cd c:\laragon\www\playrke\frontend
```

### 2. Instalar dependencias

```bash
npm install
```

Este comando instalará todas las dependencias especificadas en `package.json`, incluyendo:
- Nuxt 3
- Vue 3
- TypeScript
- Tailwind CSS
- Pinia (state management)
- Laravel Echo & Pusher (WebSockets)
- Otros paquetes necesarios

**Tiempo estimado**: 2-5 minutos dependiendo de tu conexión a internet.

### 3. Configurar variables de entorno

Copia el archivo de ejemplo de variables de entorno:

```bash
cp .env.example .env
```

En Windows (PowerShell):
```powershell
copy .env.example .env
```

Luego edita el archivo `.env` con tus configuraciones:

```bash
# API Configuration
NUXT_PUBLIC_API_BASE=http://localhost:8000/api

# WebSocket Configuration (Laravel Reverb)
NUXT_PUBLIC_PUSHER_KEY=local
NUXT_PUBLIC_PUSHER_HOST=localhost
NUXT_PUBLIC_PUSHER_PORT=6001
NUXT_PUBLIC_PUSHER_CLUSTER=mt1
NUXT_PUBLIC_PUSHER_TLS=false

# App Configuration
NUXT_PUBLIC_APP_NAME=PlayRKE Bingo
NUXT_PUBLIC_APP_URL=http://localhost:3000
NODE_ENV=development
```

**Importante**:
- `NUXT_PUBLIC_API_BASE` debe apuntar a tu backend Laravel
- `NUXT_PUBLIC_PUSHER_*` debe coincidir con la configuración de Laravel Reverb en el backend

### 4. Verificar configuración del backend

Asegúrate de que el backend Laravel esté configurado correctamente:

#### a) CORS configurado en `config/cors.php`:

```php
'supports_credentials' => true,
'allowed_origins' => ['http://localhost:3000'],
```

#### b) Sanctum configurado en `config/sanctum.php`:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000')),
```

#### c) Laravel Reverb corriendo:

```bash
php artisan reverb:start
```

### 5. Iniciar el servidor de desarrollo

```bash
npm run dev
```

El servidor se iniciará en `http://localhost:3000`

Verás un mensaje similar a:
```
Nuxt 3.x.x with Nitro 2.x.x
  > Local:    http://localhost:3000
  > Network:  http://192.168.x.x:3000

ℹ Vite client warmed up in Xms
✔ Nuxt DevTools is enabled
```

---

## 🎯 Verificar que Todo Funciona

### 1. Acceder a la aplicación

Abre tu navegador en `http://localhost:3000`

Deberías ver la página de inicio del sistema PlayRKE Bingo.

### 2. Probar el login

1. Navega a `http://localhost:3000/auth/login`
2. Intenta hacer login con credenciales de prueba
3. Si el login es exitoso, serás redirigido según tu rol:
   - Admin → `/admin/dashboard`
   - Player → `/player/lobby`

### 3. Verificar WebSockets (opcional)

1. Abre la consola del navegador (F12)
2. Navega a una sala de bingo
3. Deberías ver mensajes de conexión WebSocket:
   ```
   Echo connected
   Subscribed to channel: bingo.room.xxx
   ```

### 4. Verificar API

1. Abre la consola del navegador (F12 → Network)
2. Realiza acciones en la aplicación (login, navegar, etc.)
3. Verifica que las peticiones a `http://localhost:8000/api` sean exitosas (status 200)

---

## 🛠️ Comandos Disponibles

```bash
# Desarrollo
npm run dev              # Inicia servidor de desarrollo con hot-reload

# Producción
npm run build            # Genera build de producción
npm run preview          # Preview del build de producción

# Análisis
npm run generate         # Genera sitio estático (SSG)
npm run postinstall      # Se ejecuta automáticamente después de npm install

# Limpieza
rm -rf .nuxt node_modules  # Limpiar cache y dependencias
npm install                 # Reinstalar
```

---

## 🔧 Solución de Problemas Comunes

### Error: "Cannot GET /"

**Problema**: El servidor no inicia correctamente.

**Solución**:
```bash
rm -rf .nuxt
npm run dev
```

### Error: "Network Error" en peticiones API

**Problema**: El backend no está corriendo o CORS no está configurado.

**Solución**:
1. Verifica que Laravel esté corriendo: `php artisan serve`
2. Verifica CORS en `config/cors.php`
3. Limpia cache de Laravel: `php artisan config:clear`

### Error: "CSRF token mismatch"

**Problema**: El token CSRF no se está enviando correctamente.

**Solución**:
1. Verifica que `credentials: 'include'` esté en todas las peticiones
2. Verifica configuración de Sanctum en `.env` del backend:
   ```
   SESSION_DOMAIN=localhost
   SANCTUM_STATEFUL_DOMAINS=localhost:3000
   ```
3. Limpia cookies del navegador (F12 → Application → Cookies)

### Error: WebSocket no se conecta

**Problema**: Laravel Reverb no está corriendo o mal configurado.

**Solución**:
1. Inicia Reverb: `php artisan reverb:start`
2. Verifica las variables en `.env` del frontend:
   ```
   NUXT_PUBLIC_PUSHER_KEY=local
   NUXT_PUBLIC_PUSHER_PORT=6001
   ```
3. Verifica que coincidan con `config/broadcasting.php` del backend

### Error: "Module not found"

**Problema**: Dependencias no instaladas correctamente.

**Solución**:
```bash
rm -rf node_modules package-lock.json
npm install
```

### Puerto 3000 ya está en uso

**Problema**: Otro proceso está usando el puerto 3000.

**Solución**:
```bash
# Opción 1: Cambiar puerto
PORT=3001 npm run dev

# Opción 2: Matar proceso en Windows
netstat -ano | findstr :3000
taskkill /PID <PID> /F

# Opción 3: Matar proceso en Linux/Mac
lsof -ti:3000 | xargs kill -9
```

---

## 📁 Estructura del Proyecto

```
frontend/
├── layers/
│   ├── admin/          # Sección administrativa
│   │   ├── components/
│   │   ├── pages/
│   │   └── layouts/
│   ├── player/         # Sección de jugador
│   │   ├── components/
│   │   ├── pages/
│   │   └── layouts/
│   └── shared/         # Código compartido
│       ├── components/
│       ├── composables/
│       ├── stores/
│       ├── types/
│       └── utils/
├── pages/              # Páginas públicas
├── public/             # Archivos estáticos
├── .env                # Variables de entorno (no commitear)
├── .env.example        # Template de variables
├── nuxt.config.ts      # Configuración de Nuxt
├── tailwind.config.ts  # Configuración de Tailwind
└── package.json        # Dependencias
```

---

## 🔐 Autenticación

El sistema usa **Laravel Sanctum Stateful Authentication** con cookies:

1. Se obtiene un token CSRF: `GET /sanctum/csrf-cookie`
2. Se envía login/register con el token
3. Laravel establece una cookie de sesión
4. Todas las peticiones posteriores incluyen la cookie automáticamente

**Headers requeridos**:
```javascript
{
  'Accept': 'application/json',
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': token // Solo en POST/PUT/DELETE
}
```

**Opciones de fetch**:
```javascript
{
  credentials: 'include' // Importante: envía cookies
}
```

---

## 🌐 Despliegue a Producción

### Preparar el build

```bash
npm run build
```

Esto genera los archivos optimizados en `.output/`

### Variables de entorno de producción

Actualiza `.env` con valores de producción:

```bash
NUXT_PUBLIC_API_BASE=https://api.tudominio.com/api
NUXT_PUBLIC_APP_URL=https://tudominio.com
NODE_ENV=production

# WebSocket (producción)
NUXT_PUBLIC_PUSHER_KEY=tu-pusher-key
NUXT_PUBLIC_PUSHER_HOST=tu-dominio.com
NUXT_PUBLIC_PUSHER_PORT=443
NUXT_PUBLIC_PUSHER_TLS=true
```

### Opciones de deployment

1. **Node.js Server** (recomendado):
   ```bash
   node .output/server/index.mjs
   ```

2. **Vercel**: Conecta tu repositorio y Vercel detectará Nuxt automáticamente

3. **Netlify**: Similar a Vercel, detección automática

4. **Docker**:
   ```dockerfile
   FROM node:20-alpine
   WORKDIR /app
   COPY . .
   RUN npm install
   RUN npm run build
   EXPOSE 3000
   CMD ["node", ".output/server/index.mjs"]
   ```

---

## 📚 Recursos Adicionales

- [Documentación de Nuxt 3](https://nuxt.com)
- [Documentación de API Endpoints](./api-endpoints-required.md)
- [Guía de Inicio Rápido](./quick-start.md)
- [Documentación de Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Documentación de Laravel Reverb](https://reverb.laravel.com)

---

## 🆘 Soporte

Si encuentras problemas no listados aquí:

1. Verifica los logs del navegador (F12 → Console)
2. Verifica los logs de Nuxt en la terminal
3. Verifica los logs de Laravel en `storage/logs/laravel.log`
4. Revisa la documentación de API en `docs/api-endpoints-required.md`

---

## ✅ Checklist Post-Instalación

- [ ] Node.js v18+ instalado
- [ ] Dependencias instaladas (`npm install`)
- [ ] Archivo `.env` configurado
- [ ] Backend Laravel corriendo en puerto 8000
- [ ] Laravel Reverb corriendo en puerto 6001
- [ ] CORS configurado en backend
- [ ] Sanctum configurado en backend
- [ ] Frontend corriendo en puerto 3000
- [ ] Login funciona correctamente
- [ ] API requests exitosas (Network tab)
- [ ] WebSockets conectados (opcional)

¡Felicidades! Tu frontend de PlayRKE Bingo está listo para desarrollo. 🎉
