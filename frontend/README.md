# PlayRKE Bingo - Frontend Nuxt 3

Frontend moderno y escalable para el sistema de bingo en línea PlayRKE, construido con Nuxt 3, TypeScript y Tailwind CSS.

## 🎯 Características

- ✅ **Nuxt 3** - Framework Vue de última generación con SSR
- ✅ **TypeScript Strict** - Type-safety completo
- ✅ **Arquitectura en Capas** - Separación admin/player/shared
- ✅ **Laravel Sanctum** - Autenticación stateful con cookies
- ✅ **WebSockets** - Tiempo real con Laravel Echo/Reverb
- ✅ **Pinia** - State management con persistencia
- ✅ **Tailwind CSS** - Styling utility-first con dark mode
- ✅ **Composables** - Lógica reutilizable para API y UI
- ✅ **Auto-imports** - Sin necesidad de importar componentes
- ✅ **TypeScript Strict** - Seguridad de tipos completa

## 🚀 Inicio Rápido

```bash
# Instalar dependencias
npm install

# Configurar variables de entorno
npm run setup

# Iniciar servidor de desarrollo
npm run dev
```

Abre [http://localhost:3000](http://localhost:3000)

📖 **Más detalles**: [Guía de Inicio Rápido](../docs/quick-start.md)

## 📋 Requisitos

- Node.js >= 18.0.0
- npm >= 8.0.0
- Backend Laravel corriendo en puerto 8000
- Laravel Reverb corriendo en puerto 6001

## 📁 Estructura del Proyecto

```
frontend/
├── layers/
│   ├── admin/                  # Sección administrativa
│   │   ├── components/         # Componentes de admin
│   │   ├── pages/              # Páginas de admin
│   │   │   ├── customers/      # Gestión de clientes
│   │   │   ├── treasurers/     # Gestión de tesoreros
│   │   │   ├── sellers/        # Gestión de vendedores
│   │   │   ├── rooms/          # Gestión de salas
│   │   │   ├── transactions/   # Gestión de transacciones
│   │   │   ├── reports/        # Reportes y analytics
│   │   │   └── settings/       # Configuración del sistema
│   │   └── layouts/            # Layouts de admin
│   │
│   ├── player/                 # Sección de jugador
│   │   ├── components/         # Componentes de player
│   │   │   ├── bingo/          # BingoBoard, BingoCard
│   │   │   └── game/           # GameControls, GameStats
│   │   ├── pages/              # Páginas de player
│   │   │   ├── lobby.vue       # Lobby principal
│   │   │   ├── game/[id].vue   # Sala de juego
│   │   │   ├── history.vue     # Historial de juegos
│   │   │   ├── wallet.vue      # Billetera
│   │   │   └── profile.vue     # Perfil
│   │   └── layouts/            # Layouts de player
│   │
│   └── shared/                 # Código compartido
│       ├── components/         # Componentes UI reutilizables
│       │   └── ui/             # Button, Modal, Input, etc.
│       ├── composables/        # Lógica reutilizable
│       │   ├── useAuth.ts      # Autenticación
│       │   ├── useBingoApi.ts  # API de bingo
│       │   ├── useAdminApi.ts  # API de admin
│       │   └── useWebSocket.ts # WebSockets
│       ├── stores/             # Pinia stores
│       │   ├── auth.ts         # Estado de autenticación
│       │   ├── bingo.ts        # Estado del juego
│       │   ├── wallet.ts       # Estado de billetera
│       │   └── ui.ts           # Estado de UI (notificaciones, modals)
│       ├── types/              # TypeScript types
│       ├── utils/              # Utilidades
│       │   ├── formatters.ts   # 45+ funciones (currency, date, etc.)
│       │   ├── validators.ts   # 35+ funciones (email, password, etc.)
│       │   └── helpers.ts      # 50+ funciones (debounce, clone, etc.)
│       └── middleware/         # Route guards
│           ├── auth.ts         # Protección de rutas autenticadas
│           ├── guest.ts        # Solo para no autenticados
│           └── role.ts         # Verificación de roles
│
├── pages/                      # Páginas públicas
│   ├── index.vue               # Home
│   └── auth/                   # Login, registro
│
├── public/                     # Archivos estáticos
├── layouts/                    # Layouts públicos
│   └── default.vue             # Layout público
│
├── .env                        # Variables de entorno (local)
├── .env.example                # Template de variables
├── nuxt.config.ts              # Configuración de Nuxt
├── tailwind.config.ts          # Configuración de Tailwind
├── tsconfig.json               # Configuración de TypeScript
└── package.json                # Dependencias y scripts
```

## 🛠️ Scripts Disponibles

```bash
# Desarrollo
npm run dev              # Servidor con hot-reload en puerto 3000
npm run dev -- --port 3001  # Servidor en puerto personalizado

# Configuración
npm run setup            # Configurar .env interactivo (desarrollo)
npm run setup:prod       # Configurar .env para producción

# Build
npm run build            # Build de producción
npm run preview          # Preview del build de producción
npm run generate         # Generar sitio estático (SSG)

# Calidad de código
npm run lint             # ESLint con auto-fix
npm run type-check       # Verificar tipos TypeScript

# Utilidades
npm run postinstall      # Se ejecuta automáticamente tras npm install
```

## 🔑 Autenticación

El sistema usa **Laravel Sanctum Stateful Authentication**:

1. Se obtiene token CSRF: `GET /sanctum/csrf-cookie`
2. Login/Register con token CSRF
3. Laravel establece cookie de sesión
4. Todas las peticiones incluyen `credentials: 'include'`

**Ejemplo de uso**:

```typescript
const { login, user, isAuthenticated } = useAuth()

await login({
  login: 'player@example.com',
  password: 'password',
  remember: true
})

if (isAuthenticated.value) {
  console.log('Usuario:', user.value)
}
```

## 🌐 API Integration

Todas las peticiones API están encapsuladas en composables:

```typescript
// Bingo API
const { getRooms, purchaseCard, checkWinner } = useBingoApi()

// Admin API
const { getCustomers, approveTransaction, getReports } = useAdminApi()

// WebSocket
const { connect, listenToChannel } = useWebSocket()
```

## 🎨 Componentes UI

Componentes reutilizables en `layers/shared/components/ui/`:

- **Button** - 8 variantes, estados de loading, iconos
- **Modal** - 7 tamaños, 4 variantes, animaciones
- **Input** - Validación, iconos, estados
- **Card** - Layout flexible
- **Badge** - Estados y colores
- **Alert** - Info, success, warning, error
- Y más...

**Ejemplo**:

```vue
<template>
  <Button
    variant="primary"
    size="lg"
    :loading="isLoading"
    @click="handleClick"
  >
    Comprar Cartones
  </Button>
</template>
```

## 📦 State Management

Pinia stores con persistencia:

```typescript
// Auth Store
const authStore = useAuthStore()
authStore.user           // Usuario actual
authStore.isAuthenticated // Estado de auth
authStore.hasRole('admin') // Verificar rol

// Bingo Store
const bingoStore = useBingoStore()
bingoStore.currentRoom   // Sala actual
bingoStore.myCards       // Cartones del jugador
bingoStore.calledNumbers // Números cantados

// Wallet Store
const walletStore = useWalletStore()
walletStore.wallets      // Todas las billeteras
walletStore.getBalance('USD') // Balance específico

// UI Store
const uiStore = useUiStore()
uiStore.notify('success', 'Operación exitosa')
uiStore.showModal('confirmDialog')
```

## 🔌 WebSockets en Tiempo Real

Integración completa con Laravel Echo/Reverb:

```typescript
const { listenToChannel, disconnect } = useWebSocket()

// Escuchar eventos de sala
listenToChannel(`bingo.room.${roomId}`, 'NumberCalled', (data) => {
  console.log('Número cantado:', data.number)
})

// O usar el composable especializado
const { startListening, stopListening } = useBingoWebSocket(roomId)
startListening() // Auto-integra con bingoStore
```

**Eventos soportados**:
- `NumberCalled` - Nuevo número cantado
- `GameStarted` - Juego iniciado
- `GameEnded` - Juego finalizado
- `WinnerDeclared` - Ganador declarado

## 🎯 Utilidades

Más de 130+ funciones utility en `layers/shared/utils/`:

### Formatters (45+ funciones)

```typescript
import { formatCurrency, formatDateTime, formatRelativeTime } from '#shared/utils/formatters'

formatCurrency(1234.56, 'USD')  // "$1,234.56"
formatDateTime(new Date())       // "05/10/2025 14:30"
formatRelativeTime(yesterday)    // "hace 1 día"
```

### Validators (35+ funciones)

```typescript
import { isValidEmail, isValidPassword, validateForm } from '#shared/utils/validators'

isValidEmail('test@example.com')  // true
isValidPassword('weak')           // { valid: false, errors: [...] }

const result = validateForm(formData, {
  email: [{ type: 'required' }, { type: 'email' }],
  password: [{ type: 'required' }, { type: 'minLength', value: 8 }]
})
```

### Helpers (50+ funciones)

```typescript
import { debounce, deepClone, copyToClipboard } from '#shared/utils/helpers'

const search = debounce(performSearch, 500)
const clone = deepClone(originalObject)
await copyToClipboard('text to copy')
```

## 🎨 Theming

Soporte completo para dark mode:

```vue
<template>
  <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
    Contenido con tema
  </div>
</template>
```

Cambiar tema:

```typescript
const uiStore = useUiStore()
uiStore.toggleTheme() // Cambia entre light/dark
```

## 🔒 Protección de Rutas

Middleware para protección de rutas:

```typescript
// pages/admin/dashboard.vue
definePageMeta({
  middleware: ['auth', 'role'],
  roleRequired: 'admin'
})

// pages/auth/login.vue
definePageMeta({
  middleware: 'guest' // Solo para no autenticados
})
```

## 📱 Responsive Design

Todo el sistema es completamente responsive:

- Mobile first approach
- Breakpoints: sm, md, lg, xl, 2xl
- Menús móviles con animaciones
- Grids adaptables

## 🧪 Testing (Preparado)

Estructura lista para testing:

```bash
# Instalar dependencias de testing
npm install -D vitest @vue/test-utils

# Ejecutar tests
npm run test
```

## 🚀 Despliegue

### Build de Producción

```bash
# Configurar .env para producción
npm run setup:prod

# Generar build
npm run build

# El output estará en .output/
```

### Opciones de Deployment

1. **Node.js Server**:
   ```bash
   node .output/server/index.mjs
   ```

2. **Vercel/Netlify**: Auto-detección de Nuxt

3. **Docker**:
   ```dockerfile
   FROM node:20-alpine
   WORKDIR /app
   COPY . .
   RUN npm install && npm run build
   EXPOSE 3000
   CMD ["node", ".output/server/index.mjs"]
   ```

## 📚 Documentación

- [Instalación Completa](../docs/installation.md)
- [Guía de Inicio Rápido](../docs/quick-start.md)
- [API Endpoints](../docs/api-endpoints-required.md)
- [Desarrollo Frontend](../docs/frontend-desarrollo.md)

## 🐛 Troubleshooting

### Error: Network Error
- Verifica que Laravel esté corriendo en puerto 8000
- Verifica CORS en `config/cors.php`

### Error: CSRF token mismatch
- Limpia cookies del navegador
- Verifica `SANCTUM_STATEFUL_DOMAINS` en Laravel

### Error: WebSocket no conecta
- Verifica que Reverb esté corriendo: `php artisan reverb:start`
- Verifica configuración en `.env`

Más soluciones en [installation.md](../docs/installation.md)

## 🤝 Contribuir

Este es un proyecto privado. Para contribuir:

1. Crea una rama feature: `git checkout -b feature/nueva-funcionalidad`
2. Commit cambios: `git commit -m 'feat: agregar nueva funcionalidad'`
3. Push a la rama: `git push origin feature/nueva-funcionalidad`
4. Abre un Pull Request

## 📄 Licencia

Proyecto propietario - PlayRKE © 2025

## 👥 Equipo

Desarrollado por el equipo de PlayRKE

---

## 🎯 Estado del Proyecto

- ✅ Autenticación (100%)
- ✅ Panel de Jugador (100%)
- ✅ Panel de Admin (100%)
- ✅ WebSockets (100%)
- ✅ State Management (100%)
- ✅ Componentes UI (100%)
- ✅ Utilidades (100%)
- ✅ Documentación (100%)

**Migración completada**: 138/138 vistas Blade → Nuxt 3 ✅

---

¿Necesitas ayuda? Consulta la [documentación completa](../docs/) o contacta al equipo de desarrollo.
