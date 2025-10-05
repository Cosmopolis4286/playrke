<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Salas de Bingo</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Gestiona las salas de juego y sus configuraciones
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Nueva Sala
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Total Salas</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Activas</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.active }}</p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">En Juego</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ stats.playing }}</p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Esperando</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.waiting }}</p>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600 dark:text-slate-400">Jugadores</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ stats.players }}</p>
          </div>
          <div class="p-3 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
      <!-- Filters -->
      <div class="p-4 border-b border-slate-200 dark:border-slate-700">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="filters.search"
              type="text"
              placeholder="Buscar por nombre..."
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="debouncedSearch"
            />
          </div>
          <select
            v-model="filters.status"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadRooms"
          >
            <option value="">Todos los estados</option>
            <option value="active">Activas</option>
            <option value="waiting">Esperando</option>
            <option value="playing">En Juego</option>
            <option value="finished">Finalizadas</option>
          </select>
          <select
            v-model="filters.type"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadRooms"
          >
            <option value="">Todos los tipos</option>
            <option value="normal">Normal</option>
            <option value="fast">Rápida</option>
            <option value="vip">VIP</option>
            <option value="jackpot">Jackpot</option>
          </select>
          <select
            v-model="filters.currency"
            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadRooms"
          >
            <option value="">Todas las monedas</option>
            <option value="USD">USD</option>
            <option value="VES">VES</option>
            <option value="BRL">BRL</option>
            <option value="USDT">USDT</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-900/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Sala
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Tipo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Precio Cartón
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Premio
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Jugadores
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="room in rooms"
              :key="room.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-10 h-10 flex-shrink-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ room.name }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                      Capacidad: {{ room.current_players }}/{{ room.max_players }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getRoomTypeColor(room.type)
                  ]"
                >
                  {{ getRoomTypeLabel(room.type) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-white font-semibold">
                  {{ formatCurrency(room.card_price, room.currency) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-green-600 dark:text-green-400 font-semibold">
                  {{ formatCurrency(room.prize_pool, room.currency) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div
                      class="bg-blue-600 h-2 rounded-full"
                      :style="{ width: `${(room.current_players / room.max_players) * 100}%` }"
                    ></div>
                  </div>
                  <span class="text-sm text-slate-600 dark:text-slate-400 w-12 text-right">
                    {{ room.current_players }}/{{ room.max_players }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getRoomStatusColor(room.status)
                  ]"
                >
                  {{ getRoomStatusLabel(room.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewRoom(room)"
                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                >
                  Ver
                </button>
                <button
                  @click="editRoom(room)"
                  class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3"
                >
                  Editar
                </button>
                <button
                  v-if="room.status === 'waiting'"
                  @click="startRoom(room)"
                  class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3"
                >
                  Iniciar
                </button>
                <button
                  @click="deleteRoom(room)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                >
                  Eliminar
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty state -->
        <div
          v-if="rooms.length === 0"
          class="text-center py-12"
        >
          <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <p class="text-slate-600 dark:text-slate-400 text-lg">No se encontraron salas</p>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.total > 0"
        class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between"
      >
        <div class="text-sm text-slate-600 dark:text-slate-400">
          Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }}
        </div>
        <div class="flex gap-2">
          <button
            @click="previousPage"
            :disabled="pagination.currentPage === 1"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              pagination.currentPage === 1
                ? 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-600 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700 text-white'
            ]"
          >
            Anterior
          </button>
          <button
            @click="nextPage"
            :disabled="pagination.currentPage === pagination.lastPage"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-all',
              pagination.currentPage === pagination.lastPage
                ? 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-600 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700 text-white'
            ]"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
      @click.self="closeModal"
    >
      <div class="bg-white dark:bg-slate-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
              {{ isEditing ? 'Editar Sala' : 'Nueva Sala' }}
            </h2>
            <button
              @click="closeModal"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <form @submit.prevent="saveRoom" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Nombre de la Sala
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Tipo
              </label>
              <select
                v-model="form.type"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="normal">Normal</option>
                <option value="fast">Rápida</option>
                <option value="vip">VIP</option>
                <option value="jackpot">Jackpot</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Moneda
              </label>
              <select
                v-model="form.currency"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="USD">USD</option>
                <option value="VES">VES</option>
                <option value="BRL">BRL</option>
                <option value="USDT">USDT</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Precio por Cartón
              </label>
              <input
                v-model.number="form.card_price"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Premio
              </label>
              <input
                v-model.number="form.prize_pool"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Jugadores Mínimos
              </label>
              <input
                v-model.number="form.min_players"
                type="number"
                min="1"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Jugadores Máximos
              </label>
              <input
                v-model.number="form.max_players"
                type="number"
                min="1"
                required
                class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Descripción
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              {{ isEditing ? 'Actualizar' : 'Crear' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatCurrency } from '~/layers/shared/utils/formatters'
import { debounce } from '~/layers/shared/utils/helpers'

definePageMeta({
  middleware: ['auth'],
  layout: 'admin'
})

interface Room {
  id: string
  name: string
  type: string
  currency: string
  card_price: number
  prize_pool: number
  min_players: number
  max_players: number
  current_players: number
  status: string
  description?: string
}

const { getRooms, createRoom, updateRoom, deleteRoom: deleteRoomApi, startGame } = useAdminApi()
const uiStore = useUIStore()

// Estados
const rooms = ref<Room[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const selectedRoom = ref<Room | null>(null)

const filters = ref({
  search: '',
  status: '',
  type: '',
  currency: ''
})

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
  from: 0,
  to: 0
})

const stats = ref({
  total: 0,
  active: 0,
  playing: 0,
  waiting: 0,
  players: 0
})

const form = ref({
  name: '',
  type: 'normal',
  currency: 'USD',
  card_price: 0,
  prize_pool: 0,
  min_players: 2,
  max_players: 50,
  description: ''
})

// Computed
const debouncedSearch = debounce(() => {
  loadRooms()
}, 500)

// Métodos
const loadRooms = async () => {
  try {
    const response = await getRooms({
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
      search: filters.value.search,
      status: filters.value.status,
      type: filters.value.type,
      currency: filters.value.currency
    })

    rooms.value = response.data
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      perPage: response.per_page,
      total: response.total,
      from: response.from,
      to: response.to
    }

    // Update stats
    stats.value = {
      total: response.total,
      active: response.data.filter((r: Room) => r.status === 'active' || r.status === 'waiting').length,
      playing: response.data.filter((r: Room) => r.status === 'playing').length,
      waiting: response.data.filter((r: Room) => r.status === 'waiting').length,
      players: response.data.reduce((sum: number, r: Room) => sum + r.current_players, 0)
    }
  } catch (error) {
    uiStore.notifyError('Error cargando salas')
  }
}

const openCreateModal = () => {
  isEditing.value = false
  resetForm()
  showModal.value = true
}

const editRoom = (room: Room) => {
  isEditing.value = true
  selectedRoom.value = room
  form.value = {
    name: room.name,
    type: room.type,
    currency: room.currency,
    card_price: room.card_price,
    prize_pool: room.prize_pool,
    min_players: room.min_players,
    max_players: room.max_players,
    description: room.description || ''
  }
  showModal.value = true
}

const viewRoom = (room: Room) => {
  navigateTo(`/admin/rooms/${room.id}`)
}

const deleteRoom = async (room: Room) => {
  if (!confirm(`¿Estás seguro de eliminar la sala "${room.name}"?`)) return

  try {
    await deleteRoomApi(room.id)
    uiStore.notifySuccess('Sala eliminada exitosamente')
    loadRooms()
  } catch (error) {
    uiStore.notifyError('Error eliminando sala')
  }
}

const startRoom = async (room: Room) => {
  if (!confirm(`¿Iniciar juego en sala "${room.name}"?`)) return

  try {
    await startGame(room.id)
    uiStore.notifySuccess('Juego iniciado')
    loadRooms()
  } catch (error: any) {
    uiStore.notifyError(error.message || 'Error iniciando juego')
  }
}

const saveRoom = async () => {
  try {
    if (isEditing.value && selectedRoom.value) {
      await updateRoom(selectedRoom.value.id, form.value)
      uiStore.notifySuccess('Sala actualizada exitosamente')
    } else {
      await createRoom(form.value)
      uiStore.notifySuccess('Sala creada exitosamente')
    }

    closeModal()
    loadRooms()
  } catch (error: any) {
    uiStore.notifyError(error.message || 'Error guardando sala')
  }
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    name: '',
    type: 'normal',
    currency: 'USD',
    card_price: 0,
    prize_pool: 0,
    min_players: 2,
    max_players: 50,
    description: ''
  }
  selectedRoom.value = null
}

const getRoomTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    normal: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    fast: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
    vip: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    jackpot: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  }
  return colors[type] || 'bg-slate-100 text-slate-800'
}

const getRoomTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    normal: 'Normal',
    fast: 'Rápida',
    vip: 'VIP',
    jackpot: 'Jackpot'
  }
  return labels[type] || type
}

const getRoomStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    waiting: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    playing: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    finished: 'bg-slate-100 text-slate-800 dark:bg-slate-900/20 dark:text-slate-400',
    active: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
  }
  return colors[status] || 'bg-slate-100 text-slate-800'
}

const getRoomStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    waiting: 'Esperando',
    playing: 'En Juego',
    finished: 'Finalizada',
    active: 'Activa'
  }
  return labels[status] || status
}

const nextPage = () => {
  if (pagination.value.currentPage < pagination.value.lastPage) {
    pagination.value.currentPage++
    loadRooms()
  }
}

const previousPage = () => {
  if (pagination.value.currentPage > 1) {
    pagination.value.currentPage--
    loadRooms()
  }
}

// Cargar al montar
onMounted(() => {
  loadRooms()
})
</script>
