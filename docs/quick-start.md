# Guía de Inicio Rápido - PlayRKE Bingo

Esta guía te permitirá tener el frontend funcionando en menos de 5 minutos.

## ⚡ Instalación Rápida

### 1. Requisitos Previos

- Node.js v18+ instalado
- Backend Laravel corriendo en `http://localhost:8000`

### 2. Instalación

```bash
# Navegar al directorio
cd c:\laragon\www\playrke\frontend

# Instalar dependencias
npm install

# Configurar variables de entorno (opción automática)
npm run setup

# O configurar manualmente
cp .env.example .env
```

### 3. Iniciar Servidor

```bash
npm run dev
```

Abre `http://localhost:3000` en tu navegador.

---

## 🎯 Configuración Mínima

Si usaste `npm run setup`, ya tienes todo configurado. Si no, edita `.env`:

```bash
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
NUXT_PUBLIC_PUSHER_KEY=local
NUXT_PUBLIC_PUSHER_HOST=localhost
NUXT_PUBLIC_PUSHER_PORT=6001
NUXT_PUBLIC_APP_URL=http://localhost:3000
```

---

## 🔑 Probar el Sistema

### Login de Prueba

1. Ve a `http://localhost:3000/auth/login`
2. Ingresa credenciales de prueba (configúralas en el backend):
   - **Admin**: admin@playrke.com / password
   - **Player**: player@playrke.com / password
3. Serás redirigido según tu rol

### Rutas Principales

| Rol | Ruta |
|-----|------|
| Admin | `/admin/dashboard` |
| Player | `/player/lobby` |
| Público | `/` |

---

## 🛠️ Comandos Útiles

```bash
# Desarrollo
npm run dev              # Servidor con hot-reload

# Configuración
npm run setup            # Configurar .env interactivo
npm run setup:prod       # Configurar .env para producción

# Build
npm run build            # Build de producción
npm run preview          # Preview del build

# Calidad de código
npm run lint             # Linter
npm run type-check       # Verificar tipos TypeScript
```

---

## 🔧 Verificación Rápida

### ✅ Checklist

- [ ] Node.js v18+ instalado (`node --version`)
- [ ] Dependencias instaladas (`npm install`)
- [ ] Archivo `.env` configurado
- [ ] Backend corriendo en puerto 8000
- [ ] Frontend corriendo en puerto 3000
- [ ] Login funciona correctamente

### 🐛 Problemas Comunes

**Error: "Network Error"**
- Verifica que Laravel esté corriendo: `php artisan serve`

**Error: "CSRF token mismatch"**
- Verifica CORS en Laravel: `config/cors.php` debe tener `supports_credentials: true`
- Limpia cookies del navegador

**Error: Puerto 3000 en uso**
- Usa otro puerto: `PORT=3001 npm run dev`

---

## 📚 Siguiente Nivel

Una vez funcionando, consulta:

- [**Documentación Completa**](./installation.md) - Instalación detallada
- [**API Endpoints**](./api-endpoints-required.md) - Lista completa de endpoints
- [**Arquitectura**](./frontend-desarrollo.md) - Entender la estructura del proyecto

---

## 🎨 Estructura Simplificada

```
frontend/
├── layers/
│   ├── admin/          # Panel administrativo
│   ├── player/         # Panel de jugador
│   └── shared/         # Código compartido (API, stores, utils)
├── pages/              # Páginas públicas
├── .env                # Tu configuración
└── nuxt.config.ts      # Configuración de Nuxt
```

---

## 🚀 Workflow de Desarrollo

1. **Inicia el backend**: `php artisan serve` (puerto 8000)
2. **Inicia Reverb**: `php artisan reverb:start` (puerto 6001)
3. **Inicia el frontend**: `npm run dev` (puerto 3000)
4. **Desarrolla**: Los cambios se reflejan automáticamente (hot-reload)

---

## 💡 Tips

- **Auto-imports**: No necesitas importar composables ni componentes
- **TypeScript**: El proyecto usa strict mode para mayor seguridad
- **Tailwind**: Todas las clases de Tailwind están disponibles
- **Dark Mode**: Soportado con `dark:` prefix
- **DevTools**: Nuxt DevTools activado automáticamente (F12)

---

## 🆘 Ayuda Rápida

```bash
# Limpiar cache
rm -rf .nuxt node_modules
npm install

# Ver logs detallados
npm run dev -- --verbose

# Verificar configuración
cat .env
```

---

¡Listo! Ya tienes el frontend de PlayRKE Bingo corriendo. 🎉

**Siguiente paso**: Explora el código en `frontend/layers/shared/composables/` para ver cómo funciona la integración con la API.
