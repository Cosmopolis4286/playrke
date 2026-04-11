<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import debounce from 'lodash/debounce'

const props = defineProps({
  rooms: Object,
  stats: Object,
  filters: Object,
})

// Estados locales
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

// Stats calculados
const statsCards = computed(() => [
  { label: 'Total Salas', value: props.stats?.total || 0, color: 'blue', icon: '🏠' },
  { label: 'En Espera', value: props.stats?.waiting || 0, color: 'yellow', icon: '⏳' },
  { label: 'En Juego', value: props.stats?.playing || 0, color: 'green', icon: '🎮' },
  { label: 'Cerradas', value: props.stats?.closed || 0, color: 'gray', icon: '🔒' },
])

// Buscar con debounce
const searchRooms = debounce(() => {
  router.get(route('trivia-rooms-admin.index'), {
    search: search.value,
    status: status.value,
  }, {
    preserveState: true,
    replace: true,
  })
}, 300)

// Cambiar filtro de estado
const filterByStatus = (newStatus) => {
  status.value = newStatus
  searchRooms()
}

// Formatear fecha
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('es-ES', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Color del badge de estado
const getStatusColor = (status) => {
  const colors = {
    waiting: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    playing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    closed: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

// Label del estado
const getStatusLabel = (status) => {
  const labels = {
    waiting: 'En Espera',
    playing: 'En Juego',
    closed: 'Cerrada',
    cancelled: 'Cancelada',
  }
  return labels[status] || status
}

// Confirmar eliminar
const confirmDelete = (room) => {
  if (confirm(`¿Estás seguro de cerrar la sala "${room.title}"?\n\nEsta acción no se puede deshacer.`)) {
    router.delete(route('trivia-rooms-admin.destroy', room.id))
  }
}
</script>

<template>
  <Head title="Gestionar Salas - Trivia" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            🎮 Gestionar Salas de Trivia
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Administra las salas de juego y monitorea jugadores en tiempo real
          </p>
        </div>
        <Link
          :href="route('trivia-rooms-admin.create')"
          class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >
          <span class="mr-2">+</span> Nueva Sala
        </Link>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div
          v-for="stat in statsCards"
          :key="stat.label"
          class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-4"
        >
          <div class="flex items-center">
            <span class="text-2xl mr-3">{{ stat.icon }}</span>
            <div>
              <p class="text-xs text-gray-600 dark:text-gray-400 uppercase">{{ stat.label }}</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="search"
              type="text"
              placeholder="Buscar por título..."
              class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              @input="searchRooms"
            />
          </div>
          <div class="flex gap-2">
            <button
              v-for="s in ['', 'waiting', 'playing', 'closed']"
              :key="s"
              @click="filterByStatus(s)"
              :class="[
                'px-3 py-1 rounded-md text-sm font-medium transition-colors',
                status === s
                  ? 'bg-indigo-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300'
              ]"
            >
              {{ s === '' ? 'Todas' : getStatusLabel(s) }}
            </button>
          </div>
        </div>
      </div>

      <!-- Rooms Table -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Sala
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Código
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Jugadores
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Configuración
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Creada
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="room in rooms.data"
              :key="room.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                    {{ room.title?.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ room.title }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                      {{ room.description || 'Sin descripción' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono text-gray-800 dark:text-gray-200">
                  {{ room.code }}
                </code>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(room.status)]">
                  {{ getStatusLabel(room.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="text-sm text-gray-900 dark:text-white">
                    {{ room.current_players || 0 }} / {{ room.max_players }}
                  </span>
                  <div class="ml-2 w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-green-500 rounded-full"
                      :style="{ width: ((room.current_players || 0) / room.max_players * 100) + '%' }"
                    />
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ room.question_count }} preguntas • {{ room.time_per_question }}s
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(room.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <Link
                  :href="route('trivia-rooms-admin.show', room.id)"
                  class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3"
                >
                  Ver
                </Link>
                <Link
                  v-if="room.status === 'waiting'"
                  :href="route('trivia-rooms-admin.edit', room.id)"
                  class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 mr-3"
                >
                  Editar
                </Link>
                <button
                  v-if="room.status !== 'closed'"
                  @click="confirmDelete(room)"
                  class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                >
                  Cerrar
                </button>
              </td>
            </tr>
            <tr v-if="rooms.data.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-2">🎮</div>
                <p>No se encontraron salas</p>
                <Link
                  :href="route('trivia-rooms-admin.create')"
                  class="text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block"
                >
                  Crear primera sala
                </Link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
              Mostrando {{ rooms.from }} - {{ rooms.to }} de {{ rooms.total }} resultados
            </div>
            <div class="flex gap-2">
              <Link
                v-for="link in rooms.links"
                :key="link.label"
                :href="link.url"
                :class="[
                  'px-3 py-1 rounded-md text-sm',
                  link.active
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                  !link.url && 'opacity-50 cursor-not-allowed'
                ]"
                v-html="link.label"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
