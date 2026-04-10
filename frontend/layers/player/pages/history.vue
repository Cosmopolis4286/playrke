<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">
          Mi Historial de Juegos
        </h1>
        <p class="text-slate-300">
          Revisa tu historial completo de partidas y estadísticas
        </p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-300 text-sm mb-1">Total Jugado</p>
              <p class="text-3xl font-bold text-white">{{ stats.totalGames }}</p>
            </div>
            <div class="bg-blue-500/20 p-3 rounded-lg">
              <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-300 text-sm mb-1">Victorias</p>
              <p class="text-3xl font-bold text-green-400">{{ stats.wins }}</p>
            </div>
            <div class="bg-green-500/20 p-3 rounded-lg">
              <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-300 text-sm mb-1">Tasa de Victoria</p>
              <p class="text-3xl font-bold text-purple-400">{{ stats.winRate }}%</p>
            </div>
            <div class="bg-purple-500/20 p-3 rounded-lg">
              <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-300 text-sm mb-1">Premios Ganados</p>
              <p class="text-3xl font-bold text-yellow-400">{{ formatCurrency(stats.totalPrizes) }}</p>
            </div>
            <div class="bg-yellow-500/20 p-3 rounded-lg">
              <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Fecha Desde</label>
            <input
              v-model="filters.dateFrom"
              type="date"
              class="w-full bg-slate-800/50 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-purple-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Fecha Hasta</label>
            <input
              v-model="filters.dateTo"
              type="date"
              class="w-full bg-slate-800/50 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-purple-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Estado</label>
            <select
              v-model="filters.status"
              class="w-full bg-slate-800/50 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-purple-500"
            >
              <option value="">Todos</option>
              <option value="winner">Ganadas</option>
              <option value="loser">Perdidas</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              @click="applyFilters"
              class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200"
            >
              Aplicar Filtros
            </button>
          </div>
        </div>
      </div>

      <!-- Game History Table -->
      <div class="bg-white/10 backdrop-blur-lg rounded-xl border border-white/20 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Fecha</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Sala</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Cartones</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Inversión</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Premio</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Estado</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
              <tr
                v-for="game in paginatedGames"
                :key="games.id"
                class="hover:bg-white/5 transition-colors"
              >
                <td class="px-6 py-4 text-sm text-slate-300">
                  {{ formatDate(games.played_at) }}
                </td>
                <td class="px-6 py-4">
                  <div>
                    <p class="text-white font-medium">{{ games.room_name }}</p>
                    <p class="text-sm text-slate-400">{{ games.room_type }}</p>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-slate-300">
                  {{ games.cards_count }}
                </td>
                <td class="px-6 py-4 text-sm text-slate-300">
                  {{ formatCurrency(games.total_invested) }}
                </td>
                <td class="px-6 py-4">
                  <span
                    v-if="games.prize_won > 0"
                    class="text-green-400 font-semibold"
                  >
                    +{{ formatCurrency(games.prize_won) }}
                  </span>
                  <span v-else class="text-slate-500">-</span>
                </td>
                <td class="px-6 py-4">
                  <span
                    v-if="games.status === 'winner'"
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30"
                  >
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Ganado
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-500/20 text-slate-400 border border-slate-500/30"
                  >
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    Perdido
                  </span>
                </td>
                <td class="px-6 py-4">
                  <button
                    @click="viewGameDetails(games.id)"
                    class="text-purple-400 hover:text-purple-300 font-medium text-sm"
                  >
                    Ver Detalles
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty State -->
          <div
            v-if="filteredGames.length === 0"
            class="text-center py-12"
          >
            <svg class="w-16 h-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-slate-400 text-lg">No hay juegos en tu historial</p>
            <p class="text-slate-500 text-sm mt-2">Comienza a jugar para ver tus estadísticas aquí</p>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="filteredGames.length > 0"
          class="bg-slate-800/50 px-6 py-4 flex items-center justify-between border-t border-slate-700/50"
        >
          <div class="text-sm text-slate-400">
            Mostrando {{ startIndex + 1 }} - {{ endIndex }} de {{ filteredGames.length }} juegos
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="currentPage === 1"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-all',
                currentPage === 1
                  ? 'bg-slate-700/50 text-slate-500 cursor-not-allowed'
                  : 'bg-purple-600 hover:bg-purple-700 text-white'
              ]"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-all',
                currentPage === totalPages
                  ? 'bg-slate-700/50 text-slate-500 cursor-not-allowed'
                  : 'bg-purple-600 hover:bg-purple-700 text-white'
              ]"
            >
              Siguiente
            </button>
          </div>
        </div>
      </div>

      <!-- Game Details Modal -->
      <div
        v-if="selectedGame"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
        @click.self="closeModal"
      >
        <div class="bg-slate-800 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-slate-700">
            <div class="flex items-center justify-between">
              <h2 class="text-2xl font-bold text-white">Detalles del Juego</h2>
              <button
                @click="closeModal"
                class="text-slate-400 hover:text-white"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-6">
            <!-- Game Info -->
            <div class="grid grid-cols-2 gap-6 mb-6">
              <div>
                <p class="text-slate-400 text-sm mb-1">Sala</p>
                <p class="text-white font-semibold">{{ selectedGame.room_name }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm mb-1">Fecha</p>
                <p class="text-white font-semibold">{{ formatDate(selectedGame.played_at) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm mb-1">Inversión Total</p>
                <p class="text-white font-semibold">{{ formatCurrency(selectedGame.total_invested) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-sm mb-1">Premio Ganado</p>
                <p
                  :class="selectedGame.prize_won > 0 ? 'text-green-400' : 'text-slate-500'"
                  class="font-semibold"
                >
                  {{ selectedGame.prize_won > 0 ? formatCurrency(selectedGame.prize_won) : 'Sin premio' }}
                </p>
              </div>
            </div>

            <!-- Cards -->
            <div>
              <h3 class="text-lg font-semibold text-white mb-4">Tus Cartones</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="card in selectedGame.cards"
                  :key="card.id"
                  :class="[
                    'p-4 rounded-lg border-2',
                    card.status === 'winner'
                      ? 'bg-green-500/10 border-green-500/50'
                      : 'bg-slate-700/50 border-slate-600'
                  ]"
                >
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-slate-400">Cartón #{{ card.card_number }}</span>
                    <span
                      v-if="card.status === 'winner'"
                      class="text-xs font-semibold text-green-400"
                    >
                      GANADOR
                    </span>
                  </div>
                  <div class="grid grid-cols-5 gap-1">
                    <div
                      v-for="(col, colIndex) in card.numbers"
                      :key="colIndex"
                      class="space-y-1"
                    >
                      <div
                        v-for="(num, rowIndex) in col"
                        :key="rowIndex"
                        :class="[
                          'aspect-square flex items-center justify-center text-xs font-semibold rounded',
                          num === null
                            ? 'bg-yellow-500/20 text-yellow-400'
                            : 'bg-slate-600/50 text-white'
                        ]"
                      >
                        {{ num === null ? 'FREE' : num }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Called Numbers -->
            <div class="mt-6">
              <h3 class="text-lg font-semibold text-white mb-4">Números Cantados ({{ selectedGame.called_numbers.length }})</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="num in selectedGame.called_numbers"
                  :key="num"
                  class="w-10 h-10 flex items-center justify-center bg-purple-600 text-white font-semibold rounded-lg"
                >
                  {{ num }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['auth'],
  layout: 'player'
})

interface GameHistory {
  id: string
  room_id: string
  room_name: string
  room_type: string
  played_at: string
  cards_count: number
  total_invested: number
  prize_won: number
  status: 'winner' | 'loser'
  cards: Array<{
    id: string
    card_number: number
    numbers: number[][]
    status: 'winner' | 'loser'
  }>
  called_numbers: number[]
}

interface Stats {
  totalGames: number
  wins: number
  winRate: number
  totalPrizes: number
}

const { getPlayerHistory } = useBingoApi()

// Estados
const games = ref<GameHistory[]>([])
const selectedGame = ref<GameHistory | null>(null)
const isLoading = ref(false)

// Filtros
const filters = ref({
  dateFrom: '',
  dateTo: '',
  status: ''
})

// Paginación
const currentPage = ref(1)
const itemsPerPage = 10

// Computed
const stats = computed<Stats>(() => {
  const totalGames = games.value.length
  const wins = games.value.filter(g => g.status === 'winner').length
  const winRate = totalGames > 0 ? Math.round((wins / totalGames) * 100) : 0
  const totalPrizes = games.value.reduce((sum, g) => sum + g.prize_won, 0)

  return {
    totalGames,
    wins,
    winRate,
    totalPrizes
  }
})

const filteredGames = computed(() => {
  let filtered = [...games.value]

  // Filtro por fecha desde
  if (filters.value.dateFrom) {
    filtered = filtered.filter(g => g.played_at >= filters.value.dateFrom)
  }

  // Filtro por fecha hasta
  if (filters.value.dateTo) {
    filtered = filtered.filter(g => g.played_at <= filters.value.dateTo)
  }

  // Filtro por estado
  if (filters.value.status) {
    filtered = filtered.filter(g => g.status === filters.value.status)
  }

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredGames.value.length / itemsPerPage)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage
})

const endIndex = computed(() => {
  return Math.min(startIndex.value + itemsPerPage, filteredGames.value.length)
})

const paginatedGames = computed(() => {
  return filteredGames.value.slice(startIndex.value, endIndex.value)
})

// Métodos
const loadHistory = async () => {
  isLoading.value = true
  try {
    const data = await getPlayerHistory()
    games.value = data
  } catch (error) {
    console.error('Error cargando historial:', error)
  } finally {
    isLoading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1 // Reset a primera página
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const viewGameDetails = (gameId: string) => {
  selectedGame.value = games.value.find(g => g.id === gameId) || null
}

const closeModal = () => {
  selectedGame.value = null
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount)
}

// Cargar al montar
onMounted(() => {
  loadHistory()
})
</script>
