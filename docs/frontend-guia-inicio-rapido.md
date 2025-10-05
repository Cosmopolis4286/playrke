# Guía de Inicio Rápido - Frontend Nuxt 3

## 🚀 Inicio Rápido (5 minutos)

### 1. Instalar Dependencias
```bash
cd frontend
npm install
```

### 2. Configurar Variables de Entorno
```bash
# Crear archivo .env
cp .env.example .env

# Editar .env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
```

### 3. Iniciar Desarrollo
```bash
npm run dev
```

Abrir: http://localhost:3000

---

## 📁 Estructura del Proyecto

```
frontend/
├── pages/                          # Páginas de la app
│   ├── auth/
│   │   ├── login.vue              # ✅ Login
│   │   ├── register.vue           # ✅ Registro
│   │   └── forgot-password.vue    # ✅ Recuperar contraseña
│   └── index.vue                   # Homepage
│
├── layers/                         # Arquitectura modular
│   ├── admin/                      # Capa de administración
│   │   ├── pages/
│   │   │   └── dashboard.vue      # ✅ Dashboard admin
│   │   ├── components/
│   │   └── nuxt.config.ts
│   │
│   ├── player/                     # Capa de jugador
│   │   ├── pages/
│   │   │   ├── lobby.vue          # ✅ Lobby de salas
│   │   │   └── game/
│   │   │       └── [id].vue       # ✅ Sala de juego
│   │   ├── components/
│   │   │   ├── BingoCard.vue      # ✅ Cartón de bingo
│   │   │   └── BingoBoard.vue     # ✅ Tablero de números
│   │   └── nuxt.config.ts
│   │
│   └── shared/                     # Recursos compartidos
│       ├── components/ui/
│       │   ├── Button.vue         # ✅ Botón reutilizable
│       │   ├── Card.vue           # ✅ Card
│       │   ├── Input.vue          # ✅ Input
│       │   ├── Modal.vue          # ✅ Modal
│       │   └── DataTable.vue      # ✅ Tabla con paginación
│       │
│       ├── composables/
│       │   ├── useAuth.ts         # ✅ Autenticación
│       │   ├── useApi.ts          # ✅ Cliente HTTP
│       │   ├── useBingoApi.ts     # ✅ API Bingo (10 métodos)
│       │   ├── useUserApi.ts      # ✅ API Usuarios (11 métodos)
│       │   ├── useWalletApi.ts    # ✅ API Billeteras (10 métodos)
│       │   └── useAdminApi.ts     # ✅ API Admin (18 métodos)
│       │
│       ├── stores/
│       │   └── auth.ts            # ✅ Store de autenticación
│       │
│       ├── types/
│       │   └── auth.ts            # ✅ Tipos TypeScript
│       │
│       └── nuxt.config.ts
│
├── middleware/                     # Middleware de rutas
│   ├── auth.ts                    # ✅ Protege rutas autenticadas
│   ├── guest.ts                   # ✅ Solo no autenticados
│   └── role.ts                    # ✅ Verificación de roles
│
├── nuxt.config.ts                 # Configuración principal
├── tailwind.config.ts             # Configuración Tailwind
├── tsconfig.json                  # Configuración TypeScript
└── package.json                   # Dependencias
```

---

## 🎮 Rutas Principales

### Autenticación (No requiere login)
- `/auth/login` - Iniciar sesión
- `/auth/register` - Crear cuenta
- `/auth/forgot-password` - Recuperar contraseña

### Admin (Requiere rol admin)
- `/admin/dashboard` - ✅ Dashboard administrativo
- `/admin/customers` - 🔴 Gestión de clientes (pendiente)
- `/admin/treasurers` - 🔴 Gestión de tesoreros (pendiente)
- `/admin/reports` - 🔴 Reportes (pendiente)

### Player (Requiere autenticación)
- `/player/lobby` - ✅ Lobby de salas de bingo
- `/player/game/[id]` - ✅ Sala de juego en vivo
- `/player/profile` - 🔴 Perfil del jugador (pendiente)
- `/player/wallet` - 🔴 Billetera (pendiente)
- `/player/history` - 🔴 Historial (pendiente)

---

## 🔧 Composables Disponibles

### useAuth()
```typescript
const {
  user,              // Usuario autenticado
  isAuthenticated,   // Boolean de autenticación
  login,             // (credentials) => Promise
  register,          // (data) => Promise
  logout,            // () => Promise
  hasRole,           // (role) => boolean
  hasPermission      // (permission) => boolean
} = useAuth()
```

**Ejemplo de uso**:
```vue
<script setup>
const { user, login, logout } = useAuth()

const handleLogin = async () => {
  await login({
    login: 'usuario@email.com',
    password: '12345678',
    remember: true
  })
}
</script>
```

---

### useBingoApi()
```typescript
const {
  getRooms,           // (params?) => Promise<BingoRoom[]>
  getRoom,            // (roomId) => Promise<BingoRoom>
  purchaseCard,       // (roomId, quantity) => Promise<BingoCard[]>
  getPlayerCards,     // (roomId) => Promise<BingoCard[]>
  getDraw,            // (roomId) => Promise<BingoDraw>
  getCalledNumbers,   // (roomId) => Promise<number[]>
  checkWinner,        // (roomId, cardId, patternId) => Promise
  getWinners,         // (roomId) => Promise
  getPlayerHistory,   // (params?) => Promise
  notifyPrizePayment  // (cardId, paymentInfo) => Promise
} = useBingoApi()
```

**Ejemplo de uso**:
```vue
<script setup>
const { getRooms, purchaseCard } = useBingoApi()

const rooms = ref([])

onMounted(async () => {
  const response = await getRooms({ currency: 'USD' })
  rooms.value = response.data
})

const buyCards = async (roomId) => {
  await purchaseCard(roomId, 3) // Comprar 3 cartones
}
</script>
```

---

### useUserApi()
```typescript
const {
  getUsers,          // (params?) => Promise
  getUser,           // (userId) => Promise
  createUser,        // (userData) => Promise
  updateUser,        // (userId, data) => Promise
  deleteUser,        // (userId) => Promise
  updatePassword,    // (passwordData) => Promise
  updateUserStatus,  // (userId, status) => Promise
  assignRoles,       // (userId, roles[]) => Promise
  getProfile,        // () => Promise
  updateProfile,     // (profileData) => Promise
  uploadAvatar       // (file) => Promise
} = useUserApi()
```

---

### useWalletApi()
```typescript
const {
  getWallets,         // () => Promise<Wallet[]>
  getWallet,          // (walletId) => Promise<Wallet>
  getBalance,         // (walletId) => Promise<number>
  getTransactions,    // (walletId, params?) => Promise
  requestDeposit,     // (depositData) => Promise
  requestWithdrawal,  // (withdrawalData) => Promise
  transferFunds,      // (fromId, toId, amount) => Promise
  getTransactionHistory,  // (params?) => Promise
  cancelTransaction,      // (transactionId) => Promise
  getPaymentMethods       // (currency?) => Promise
} = useWalletApi()
```

---

### useAdminApi()
```typescript
const {
  getDashboardStats,   // () => Promise<DashboardStats>
  getCustomers,        // (params?) => Promise
  createCustomer,      // (data) => Promise
  updateCustomer,      // (id, data) => Promise
  deleteCustomer,      // (id) => Promise
  getTreasurers,       // (params?) => Promise
  createTreasurer,     // (data) => Promise
  // ... más métodos para sellers, commerces, reports, etc
  approveTransaction,  // (transactionId) => Promise
  rejectTransaction    // (transactionId, reason) => Promise
} = useAdminApi()
```

---

## 🎨 Componentes UI

### Button
```vue
<Button
  variant="primary"
  size="md"
  :loading="isLoading"
  @click="handleClick"
>
  Clic aquí
</Button>
```

Variantes: `primary`, `secondary`, `success`, `danger`, `warning`, `ghost`
Tamaños: `sm`, `md`, `lg`

---

### Modal
```vue
<Modal
  v-model="showModal"
  title="Mi Modal"
  size="md"
  @confirm="handleConfirm"
>
  <p>Contenido del modal</p>
</Modal>
```

Tamaños: `sm`, `md`, `lg`, `xl`, `full`

---

### DataTable
```vue
<DataTable
  :data="users"
  :columns="[
    { key: 'name', label: 'Nombre', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'status', label: 'Estado' }
  ]"
  :loading="isLoading"
  searchable
  paginated
  :per-page="10"
>
  <!-- Slot personalizado para acciones -->
  <template #actions="{ row }">
    <Button size="sm" @click="edit(row)">Editar</Button>
  </template>
</DataTable>
```

---

### BingoCard
```vue
<BingoCard
  card-number="ABC123"
  :numbers="[[1,2,3,4,5], [16,17,18,19,20], ...]"
  :called-numbers="[1, 5, 10, 15]"
  :auto-mark="true"
  status="active"
  @bingo="handleBingo"
/>
```

Estados: `pending`, `active`, `winner`, `loser`

---

### BingoBoard
```vue
<BingoBoard
  :called-numbers="[1, 5, 10, 15, 20, ...]"
  :current-number="25"
/>
```

---

## 🔒 Middleware

### Proteger una página (requiere login)
```vue
<script setup>
definePageMeta({
  middleware: ['auth']
})
</script>
```

### Proteger por rol
```vue
<script setup>
definePageMeta({
  middleware: ['auth', 'role'],
  roles: ['admin', 'moderator']
})
</script>
```

### Solo usuarios no autenticados
```vue
<script setup>
definePageMeta({
  middleware: ['guest']
})
</script>
```

---

## 📝 Crear una Nueva Página

### 1. Página Simple
```vue
<!-- pages/ejemplo.vue -->
<template>
  <div class="p-6">
    <h1>Mi Página</h1>
    <p>{{ mensaje }}</p>
  </div>
</template>

<script setup lang="ts">
const mensaje = ref('Hola Mundo')

useHead({
  title: 'Mi Página - PlayRKE'
})
</script>
```

### 2. Página con Autenticación
```vue
<!-- pages/perfil.vue -->
<template>
  <div class="p-6">
    <h1>Perfil de {{ user?.first_name }}</h1>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['auth']
})

const { user } = useAuth()
</script>
```

### 3. Página con Datos de API
```vue
<!-- pages/mis-salas.vue -->
<template>
  <div class="p-6">
    <h1>Mis Salas de Bingo</h1>

    <div v-if="isLoading">Cargando...</div>

    <div v-else class="grid grid-cols-3 gap-4">
      <div v-for="room in rooms" :key="room.id">
        {{ room.name }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { getRooms } = useBingoApi()
const rooms = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await getRooms()
    rooms.value = response.data
  } finally {
    isLoading.value = false
  }
})
</script>
```

---

## 🐛 Debugging

### Ver estado de autenticación
```vue
<script setup>
const { user, isAuthenticated } = useAuth()

watch(user, (newUser) => {
  console.log('Usuario cambió:', newUser)
})

watch(isAuthenticated, (isAuth) => {
  console.log('Autenticado:', isAuth)
})
</script>
```

### Ver errores de API
```vue
<script setup>
const { getRooms } = useBingoApi()

const loadRooms = async () => {
  try {
    const response = await getRooms()
    console.log('Rooms:', response.data)
  } catch (error) {
    console.error('Error cargando salas:', error)
    // error.response - Respuesta del servidor
    // error.response.status - Código HTTP
    // error.response.data - Datos del error
  }
}
</script>
```

---

## 📦 Instalar Dependencias Adicionales

### Para WebSockets
```bash
npm install pusher-js laravel-echo
```

### Para Validación de Formularios
```bash
npm install vee-validate yup
```

### Para Notificaciones
```bash
npm install vue-toastification
```

### Para Manejo de Fechas
```bash
npm install dayjs
```

---

## 🚀 Build para Producción

```bash
# Build
npm run build

# Preview
npm run preview

# Generate (SSG)
npm run generate
```

---

## 📞 Comandos Útiles

```bash
# Desarrollo
npm run dev

# Build
npm run build

# Lint
npm run lint

# Type check
npm run type-check

# Limpiar cache
rm -rf .nuxt node_modules/.vite
```

---

## 🎯 Próximos Pasos

1. **Implementar WebSockets** para tiempo real
2. **Crear páginas admin** faltantes (customers, treasurers, reports)
3. **Crear páginas player** faltantes (profile, wallet, history)
4. **Agregar tests** unitarios y e2e
5. **Optimizar performance**

---

## 💡 Tips y Trucos

### Auto-imports
Nuxt auto-importa composables, componentes y utilidades. No necesitas importar:
- ✅ `useAuth()` - Auto-importado
- ✅ `ref()`, `computed()`, `watch()` - Auto-importados
- ✅ Componentes en `components/` - Auto-importados

### TypeScript
Todo está tipado. Usa el autocompletado de VS Code:
```typescript
const { getRooms } = useBingoApi() // TypeScript sabe qué retorna
const rooms: BingoRoom[] = [] // Tipado completo
```

### Reactivity
```typescript
const count = ref(0) // Reactivo
const double = computed(() => count.value * 2) // Computed

watch(count, (newVal) => {
  console.log('Count cambió a:', newVal)
})
```

---

**¡Listo para desarrollar! 🎉**
