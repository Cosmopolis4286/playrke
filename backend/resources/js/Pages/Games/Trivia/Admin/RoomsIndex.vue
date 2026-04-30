<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import debounce from 'lodash/debounce'
import { getRoute } from '@js/routes/triviaRoutes'

const props = defineProps({
  rooms: Object,
  stats: Object,
  filters: Object,
})

// Estados locales
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

// ── Selección de filas ───────────────────────────────────────────────────────
const selectedRows = ref(new Set())

const toggleSelectAll = () => {
  if (selectedRows.value.size === props.rooms.data.length) {
    selectedRows.value.clear()
  } else {
    selectedRows.value = new Set(props.rooms.data.map(r => r.id))
  }
}

const toggleSelectRow = (id) => {
  if (selectedRows.value.has(id)) {
    selectedRows.value.delete(id)
  } else {
    selectedRows.value.add(id)
  }
}

const clearSelection = () => {
  selectedRows.value.clear()
}

const selectedCount = computed(() => selectedRows.value.size)

const selectedRooms = computed(() =>
  props.rooms.data.filter(r => selectedRows.value.has(r.id))
)

// ── Acciones en bulk ──────────────────────────────────────────────────────────
const bulkView = () => {
  if (selectedRows.value.size === 1) {
    const room = selectedRooms.value[0]
    router.get(getRoute('roomsShow', { id: room.id }))
  }
}

const bulkEdit = () => {
  if (selectedRows.value.size === 1) {
    const room = selectedRooms.value[0]
    if (room.status === 'waiting') {
      router.get(getRoute('roomsEdit', { id: room.id }))
    }
  }
}

const bulkClose = () => {
  const closable = selectedRooms.value.filter(r => r.status !== 'closed')
  if (closable.length === 0) return

  const names = closable.map(r => `"${r.title}"`).join(', ')
  if (confirm(`¿Estás seguro de cerrar ${closable.length} sala(s)?\n\n${names}\n\nEsta acción no se puede deshacer.`)) {
    // Cerrar una por una
    closable.forEach(room => {
      router.delete(getRoute('roomsDestroy', { id: room.id }))
    })
    clearSelection()
  }
}

// Stats calculados
const statsCards = computed(() => [
  { label: 'Total Salas', value: props.stats?.total || 0, color: 'blue', icon: '🏠' },
  { label: 'En Espera', value: props.stats?.waiting || 0, color: 'yellow', icon: '⏳' },
  { label: 'En Juego', value: props.stats?.playing || 0, color: 'green', icon: '🎮' },
  { label: 'Cerradas', value: props.stats?.closed || 0, color: 'gray', icon: '🔒' },
])

// Buscar con debounce
const searchRooms = debounce(() => {
  router.get(getRoute('roomsIndex'), {
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
    router.delete(getRoute('roomsDestroy', { id: room.id }))
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
          :href="getRoute('roomsCreate')"
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
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-4">
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

      <!-- Acciones Bulk (debajo del filtrado) -->
      <Transition name="slide-down">
        <div v-if="selectedCount > 0" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-3 mb-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                {{ selectedCount }} sala(s) seleccionada(s)
              </span>
              <button
                @click="clearSelection"
                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 underline"
              >
                Limpiar
              </button>
            </div>
            <div class="flex items-center gap-2">
              <button
                v-if="selectedCount === 1"
                @click="bulkView"
                class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
              >
                Ver
              </button>
              <button
                v-if="selectedCount === 1 && selectedRooms[0]?.status === 'waiting'"
                @click="bulkEdit"
                class="px-3 py-1.5 text-sm font-medium text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300"
              >
                Editar
              </button>
              <button
                @click="bulkClose"
                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors"
              >
                Cerrar {{ selectedCount > 1 ? `(${selectedCount})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Rooms Table -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-3 py-3 text-left">
                <input
                  type="checkbox"
                  :checked="selectedCount === rooms.data.length && rooms.data.length > 0"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </th>
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
                Categoría
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Configuración
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Creada
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Fecha de Inicio
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="room in rooms.data"
              :key="room.id"
              :class="[
                'transition-colors cursor-pointer',
                selectedRows.has(room.id)
                  ? 'bg-indigo-50 dark:bg-indigo-900/30'
                  : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
              ]"
              @click="toggleSelectRow(room.id)"
            >
              <td class="px-3 py-4 whitespace-nowrap" @click.stop>
                <input
                  type="checkbox"
                  :checked="selectedRows.has(room.id)"
                  @change="toggleSelectRow(room.id)"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                    {{ room.title?.charAt(0).toUpperCase() }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ room.title }}
                    </div>
                    <div class="text-xs text-gray-400 truncate max-w-xs mt-0.5">
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
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="room.category" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                  {{ room.category }}
                </span>
                <span v-else class="text-xs text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ room.question_count }} preguntas • {{ room.time_per_question }}s
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(room.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span v-if="room.scheduled_at" :class="[
                  new Date(room.scheduled_at) > new Date()
                    ? 'text-blue-600 dark:text-blue-400'
                    : 'text-gray-500 dark:text-gray-400'
                ]">
                  {{ formatDate(room.scheduled_at) }}
                </span>
                <span v-else class="text-xs text-gray-400">Manual</span>
              </td>
            </tr>
            <tr v-if="rooms.data.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-2">🎮</div>
                <p>No se encontraron salas</p>
                <Link
                  :href="getRoute('roomsCreate')"
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
              <template v-for="link in rooms.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    'px-3 py-1 rounded-md text-sm',
                    link.active
                      ? 'bg-indigo-600 text-white'
                      : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                  ]"
                  v-html="link.label"
                />
                <span
                  v-else
                  :class="[
                    'px-3 py-1 rounded-md text-sm',
                    link.active
                      ? 'bg-indigo-600 text-white'
                      : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 opacity-50 cursor-not-allowed'
                  ]"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-down-enter-active {
  transition: all 0.25s ease-out;
}
.slide-down-leave-active {
  transition: all 0.15s ease-in;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
