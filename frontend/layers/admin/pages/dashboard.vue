<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard Administrativo</h1>
        <div class="flex items-center space-x-4">
          <span class="text-gray-700">{{ user?.first_name }} {{ user?.last_name }}</span>
          <button
            @click="handleLogout"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
          >
            Cerrar Sesión
          </button>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Estadísticas generales -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Usuarios Totales -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Icon name="lucide:users" class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Usuarios Totales
                  </dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.totalUsers || 0 }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Salas Activas -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Icon name="lucide:play-circle" class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Salas Activas
                  </dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.activeRooms || 0 }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Transacciones Hoy -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Icon name="lucide:dollar-sign" class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Transacciones Hoy
                  </dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      ${{ stats?.todayTransactions || 0 }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Jugadores Online -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Icon name="lucide:activity" class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Jugadores Online
                  </dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.onlinePlayers || 0 }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navegación rápida -->
      <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Accesos Rápidos</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <NuxtLink
            to="/admin/customers"
            class="flex flex-col items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
          >
            <Icon name="lucide:user-check" class="h-8 w-8 text-blue-600 mb-2" />
            <span class="text-sm font-medium">Clientes</span>
          </NuxtLink>

          <NuxtLink
            to="/admin/treasurers"
            class="flex flex-col items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition"
          >
            <Icon name="lucide:wallet" class="h-8 w-8 text-green-600 mb-2" />
            <span class="text-sm font-medium">Tesoreros</span>
          </NuxtLink>

          <NuxtLink
            to="/admin/reports"
            class="flex flex-col items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition"
          >
            <Icon name="lucide:bar-chart" class="h-8 w-8 text-purple-600 mb-2" />
            <span class="text-sm font-medium">Reportes</span>
          </NuxtLink>

          <NuxtLink
            to="/admin/settings"
            class="flex flex-col items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-gray-500 hover:bg-gray-50 transition"
          >
            <Icon name="lucide:settings" class="h-8 w-8 text-gray-600 mb-2" />
            <span class="text-sm font-medium">Configuración</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Actividad reciente (placeholder) -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Actividad Reciente</h2>
        <p class="text-gray-500">Cargando actividad reciente...</p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
/**
 * Dashboard Administrativo
 *
 * Vista principal del panel de administración.
 * Muestra estadísticas generales y accesos rápidos.
 */

definePageMeta({
  middleware: ['auth', 'role'],
  roles: ['admin', 'super_admin'],
  layout: 'admin'
})

const { user, logout } = useAuth()
const router = useRouter()

// Estado de estadísticas
const stats = ref({
  totalUsers: 0,
  activeRooms: 0,
  todayTransactions: 0,
  onlinePlayers: 0
})

// Cargar estadísticas
const loadStats = async () => {
  try {
    // TODO: Implementar llamada a API para obtener estadísticas
    // const data = await api.get('/admin/stats')
    // stats.value = data
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

// Manejar logout
const handleLogout = async () => {
  await logout()
}

// Cargar datos al montar
onMounted(() => {
  loadStats()
})

// SEO
useHead({
  title: 'Dashboard - Admin PlayRKE'
})
</script>
