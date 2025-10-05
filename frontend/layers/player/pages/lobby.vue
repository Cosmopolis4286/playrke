<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <!-- Header -->
    <header class="bg-black/30 backdrop-blur-md border-b border-white/10">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <h1 class="text-2xl font-bold text-white">PlayRKE</h1>
          <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm font-medium">
            Lobby de Bingo
          </span>
        </div>
        <div class="flex items-center space-x-6">
          <!-- Balance -->
          <div class="flex items-center space-x-2">
            <Icon name="lucide:wallet" class="h-5 w-5 text-green-400" />
            <span class="text-white font-semibold">$0.00</span>
          </div>

          <!-- Usuario -->
          <div class="flex items-center space-x-3">
            <div class="text-right">
              <p class="text-white font-medium">{{ user?.first_name }} {{ user?.last_name }}</p>
              <p class="text-gray-300 text-sm">@{{ user?.username }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
              {{ initials }}
            </div>
          </div>

          <!-- Logout -->
          <button
            @click="handleLogout"
            class="px-4 py-2 bg-red-600/80 hover:bg-red-600 text-white rounded-lg transition"
          >
            <Icon name="lucide:log-out" class="h-5 w-5" />
          </button>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Filtros -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-white/20">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Filtro de moneda -->
          <div>
            <label class="block text-sm font-medium text-gray-200 mb-2">
              Moneda
            </label>
            <select
              v-model="selectedCurrency"
              class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todas</option>
              <option value="USD">USD</option>
              <option value="VES">VES</option>
              <option value="BRL">BRL</option>
              <option value="USDT">USDT</option>
            </select>
          </div>

          <!-- Filtro de tipo -->
          <div>
            <label class="block text-sm font-medium text-gray-200 mb-2">
              Tipo de Sala
            </label>
            <select
              v-model="selectedRoomType"
              class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Todas</option>
              <option value="automatic">Automáticas</option>
              <option value="manual">Manuales</option>
              <option value="public">Públicas</option>
            </select>
          </div>

          <!-- Buscar -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-200 mb-2">
              Buscar Sala
            </label>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Nombre de la sala..."
              class="w-full px-4 py-2 bg-gray-800/50 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
          </div>
        </div>
      </div>

      <!-- Lista de salas -->
      <div v-if="isLoading" class="text-center py-12">
        <Icon name="lucide:loader-2" class="h-12 w-12 text-white animate-spin mx-auto mb-4" />
        <p class="text-white">Cargando salas de bingo...</p>
      </div>

      <div v-else-if="filteredRooms.length === 0" class="text-center py-12">
        <Icon name="lucide:inbox" class="h-16 w-16 text-gray-400 mx-auto mb-4" />
        <p class="text-gray-300 text-lg">No hay salas disponibles en este momento</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="room in filteredRooms"
          :key="room.id"
          class="bg-white/10 backdrop-blur-lg rounded-2xl overflow-hidden border border-white/20 hover:border-white/40 transition cursor-pointer"
          @click="joinRoom(room.id)"
        >
          <div class="p-6">
            <!-- Nombre y estado -->
            <div class="flex justify-between items-start mb-4">
              <h3 class="text-xl font-bold text-white">{{ room.name }}</h3>
              <span
                :class="{
                  'bg-green-500/20 text-green-300': room.status === 'active',
                  'bg-yellow-500/20 text-yellow-300': room.status === 'pending',
                  'bg-red-500/20 text-red-300': room.status === 'finished'
                }"
                class="px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ room.status }}
              </span>
            </div>

            <!-- Información -->
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-gray-300">
                <Icon name="lucide:users" class="h-4 w-4 mr-2" />
                <span class="text-sm">{{ room.current_players }} / {{ room.max_players }} jugadores</span>
              </div>

              <div class="flex items-center text-gray-300">
                <Icon name="lucide:calendar" class="h-4 w-4 mr-2" />
                <span class="text-sm">{{ formatDate(room.start_date) }}</span>
              </div>

              <div class="flex items-center text-green-300">
                <Icon name="lucide:trophy" class="h-4 w-4 mr-2" />
                <span class="text-sm font-semibold">Premio: ${{ room.prize_pool }} {{ room.currency }}</span>
              </div>
            </div>

            <!-- Botón unirse -->
            <button class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
              Unirse a la Sala
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
/**
 * Lobby de Bingo - Vista principal del jugador
 *
 * Muestra todas las salas de bingo disponibles para unirse.
 * Permite filtrar por moneda y tipo de sala.
 */

definePageMeta({
  middleware: ['auth'],
  layout: 'player'
})

const { user, logout } = useAuth()
const { getRooms } = useBingoApi()
const router = useRouter()

// Estado
const rooms = ref<any[]>([])
const isLoading = ref(true)
const selectedCurrency = ref('')
const selectedRoomType = ref('')
const searchTerm = ref('')

// Computed
const initials = computed(() => {
  if (!user.value) return ''
  const first = user.value.first_name[0] || ''
  const last = user.value.last_name[0] || ''
  return (first + last).toUpperCase()
})

const filteredRooms = computed(() => {
  let filtered = rooms.value

  if (selectedCurrency.value) {
    filtered = filtered.filter(r => r.currency === selectedCurrency.value)
  }

  if (selectedRoomType.value) {
    filtered = filtered.filter(r => r.type === selectedRoomType.value)
  }

  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(r => r.name.toLowerCase().includes(term))
  }

  return filtered
})

// Métodos
const loadRooms = async () => {
  isLoading.value = true
  try {
    const response = await getRooms({
      currency: selectedCurrency.value,
      roomType: selectedRoomType.value as any
    })
    rooms.value = response.data || []
  } catch (error) {
    console.error('Error cargando salas:', error)
  } finally {
    isLoading.value = false
  }
}

const joinRoom = (roomId: string) => {
  router.push(`/player/game/${roomId}`)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleLogout = async () => {
  await logout()
}

// Watchers
watch([selectedCurrency, selectedRoomType], () => {
  loadRooms()
})

// Lifecycle
onMounted(() => {
  loadRooms()

  // Recargar salas cada 30 segundos
  const interval = setInterval(loadRooms, 30000)
  onUnmounted(() => clearInterval(interval))
})

// SEO
useHead({
  title: 'Lobby - PlayRKE Bingo'
})
</script>
