<!--
  Página principal del panel de administración.

  Dashboard con métricas, estadísticas y accesos rápidos
  para la gestión del sistema de bingo.
-->

<template>
  <div class="admin-layout">
    <!-- Header del dashboard -->
    <div class="bg-white dark:bg-slate-800 shadow border-b border-slate-200 dark:border-slate-700">
      <div class="px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
              Dashboard Administrativo
            </h1>
            <p class="text-slate-600 dark:text-slate-400">
              Bienvenido al panel de control de PlayRKE
            </p>
          </div>
          <div class="flex items-center space-x-4">
            <!-- Indicador de estado del sistema -->
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
              <span class="text-sm text-slate-600 dark:text-slate-400">Sistema Online</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-6">
      <!-- Métricas principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card-admin">
          <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Icon name="heroicons:users" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Usuarios Activos</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.activeUsers }}</p>
            </div>
          </div>
        </div>

        <div class="card-admin">
          <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <Icon name="heroicons:play" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Partidas Hoy</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.gamesToday }}</p>
            </div>
          </div>
        </div>

        <div class="card-admin">
          <div class="flex items-center">
            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
              <Icon name="heroicons:currency-dollar" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Ingresos Hoy</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ stats.revenueToday }}</p>
            </div>
          </div>
        </div>

        <div class="card-admin">
          <div class="flex items-center">
            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <Icon name="heroicons:exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Alertas</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.alerts }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Gráfico de actividad -->
        <div class="lg:col-span-2 card-admin">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
              Actividad del Sistema
            </h3>
            <select class="text-sm border border-slate-300 dark:border-slate-600 rounded-md px-3 py-1">
              <option>Últimos 7 días</option>
              <option>Último mes</option>
              <option>Últimos 3 meses</option>
            </select>
          </div>
          <div class="h-64 bg-slate-50 dark:bg-slate-700 rounded-lg flex items-center justify-center">
            <p class="text-slate-500 dark:text-slate-400">
              Gráfico de actividad - Integración pendiente
            </p>
          </div>
        </div>

        <!-- Salas activas -->
        <div class="card-admin">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
            Salas Activas
          </h3>
          <div class="space-y-3">
            <div v-for="room in activeRooms" :key="room.id" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
              <div>
                <p class="font-medium text-slate-900 dark:text-white">{{ room.name }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ room.players }} jugadores</p>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-sm text-slate-600 dark:text-slate-400">Activa</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="card-admin">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
            Acciones Rápidas
          </h3>
          <div class="space-y-3">
            <button class="btn-admin w-full justify-start">
              <Icon name="heroicons:plus" class="w-4 h-4 mr-2" />
              Crear Nueva Sala
            </button>
            <button class="btn-admin w-full justify-start">
              <Icon name="heroicons:users" class="w-4 h-4 mr-2" />
              Gestionar Usuarios
            </button>
            <button class="btn-admin w-full justify-start">
              <Icon name="heroicons:chart-bar" class="w-4 h-4 mr-2" />
              Ver Reportes
            </button>
            <button class="btn-admin w-full justify-start">
              <Icon name="heroicons:cog-6-tooth" class="w-4 h-4 mr-2" />
              Configuración
            </button>
          </div>
        </div>

        <!-- Actividad reciente -->
        <div class="lg:col-span-2 card-admin">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
            Actividad Reciente
          </h3>
          <div class="space-y-3">
            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start space-x-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">
              <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <Icon :name="activity.icon" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-white">
                  {{ activity.title }}
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                  {{ activity.description }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                  {{ activity.time }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Configuración de la página
definePageMeta({
  layout: 'admin',
  middleware: ['auth', 'admin-only']
})

// SEO
useHead({
  title: 'Dashboard - Admin PlayRKE',
  meta: [
    { name: 'description', content: 'Panel administrativo de PlayRKE - Gestión de salas, usuarios y estadísticas' }
  ]
})

// Estados reactivos con datos de ejemplo
const stats = ref({
  activeUsers: 142,
  gamesToday: 28,
  revenueToday: 1250,
  alerts: 3
})

const activeRooms = ref([
  { id: 1, name: 'Sala Principal', players: 24 },
  { id: 2, name: 'Sala VIP', players: 8 },
  { id: 3, name: 'Sala Rápida', players: 12 },
  { id: 4, name: 'Sala Nocturna', players: 15 }
])

const recentActivity = ref([
  {
    id: 1,
    icon: 'heroicons:user-plus',
    title: 'Nuevo usuario registrado',
    description: 'juan.perez@email.com se registró en el sistema',
    time: 'Hace 5 minutos'
  },
  {
    id: 2,
    icon: 'heroicons:play',
    title: 'Partida finalizada',
    description: 'Sala Principal - Ganador: María González',
    time: 'Hace 12 minutos'
  },
  {
    id: 3,
    icon: 'heroicons:currency-dollar',
    title: 'Pago procesado',
    description: 'Depósito de $50 confirmado',
    time: 'Hace 18 minutos'
  },
  {
    id: 4,
    icon: 'heroicons:exclamation-triangle',
    title: 'Alerta de sistema',
    description: 'Alto tráfico detectado en Sala VIP',
    time: 'Hace 25 minutos'
  }
])

// Auto-actualización de estadísticas (simulado)
onMounted(() => {
  const interval = setInterval(() => {
    // Simular cambios en las estadísticas
    stats.value.activeUsers += Math.floor(Math.random() * 3) - 1
    stats.value.gamesToday += Math.floor(Math.random() * 2)
  }, 30000) // Cada 30 segundos

  onUnmounted(() => {
    clearInterval(interval)
  })
})
</script>