<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-900 via-pink-900 to-red-900">
    <!-- Header de la sala -->
    <header class="bg-black/30 backdrop-blur-md border-b border-white/10">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <div class="flex items-center space-x-4">
            <NuxtLink
              to="/player/lobby"
              class="text-white hover:text-gray-300 transition"
            >
              <Icon name="lucide:arrow-left" class="h-6 w-6" />
            </NuxtLink>
            <div>
              <h1 class="text-2xl font-bold text-white">{{ room?.name || 'Cargando...' }}</h1>
              <p class="text-gray-300 text-sm">Sala #{{ roomId }}</p>
            </div>
          </div>

          <div class="flex items-center space-x-6">
            <!-- Premio -->
            <div class="text-right">
              <p class="text-gray-300 text-sm">Premio Acumulado</p>
              <p class="text-2xl font-bold text-green-400">
                ${{ room?.prize_pool || 0 }} {{ room?.currency }}
              </p>
            </div>

            <!-- Jugadores -->
            <div class="text-right">
              <p class="text-gray-300 text-sm">Jugadores</p>
              <p class="text-xl font-bold text-white">
                {{ room?.current_players || 0 }}/{{ room?.max_players || 0 }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div v-if="isLoading" class="text-center py-12">
        <Icon name="lucide:loader-2" class="h-12 w-12 text-white animate-spin mx-auto mb-4" />
        <p class="text-white">Cargando sala de juego...</p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna izquierda: Tablero -->
        <div class="lg:col-span-1">
          <BingoBoard
            :called-numbers="calledNumbers"
            :current-number="currentNumber"
          />

          <!-- Info del juego -->
          <div class="mt-6 bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/20">
            <h4 class="text-white font-semibold mb-3">Estado del Juego</h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between text-gray-300">
                <span>Estado:</span>
                <span :class="statusClasses">{{ statusText }}</span>
              </div>
              <div class="flex justify-between text-gray-300">
                <span>Números cantados:</span>
                <span class="text-white font-semibold">{{ calledNumbers.length }}/75</span>
              </div>
              <div class="flex justify-between text-gray-300">
                <span>Mis cartones:</span>
                <span class="text-white font-semibold">{{ myCards.length }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna derecha: Cartones -->
        <div class="lg:col-span-2">
          <!-- Mis cartones -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-2xl font-bold text-white">Mis Cartones</h2>
              <button
                v-if="canPurchaseMore"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                @click="showPurchaseModal = true"
              >
                <Icon name="lucide:plus" class="inline h-5 w-5 mr-2" />
                Comprar Cartones
              </button>
            </div>

            <div v-if="myCards.length === 0" class="text-center py-12 bg-white/10 backdrop-blur-lg rounded-xl border border-white/20">
              <Icon name="lucide:inbox" class="h-16 w-16 text-gray-400 mx-auto mb-4" />
              <p class="text-gray-300 text-lg mb-4">No tienes cartones en esta sala</p>
              <button
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition"
                @click="showPurchaseModal = true"
              >
                Comprar Cartones
              </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <BingoCard
                v-for="card in myCards"
                :key="card.id"
                :card-number="card.id"
                :numbers="card.numbers"
                :called-numbers="calledNumbers"
                :auto-mark="true"
                :status="card.status"
                @bingo="handleBingo(card)"
              />
            </div>
          </div>

          <!-- Ganadores (si hay) -->
          <div v-if="winners.length > 0" class="mt-6 bg-green-500/20 backdrop-blur-lg rounded-xl p-6 border border-green-500/50">
            <h3 class="text-2xl font-bold text-green-400 mb-4 flex items-center">
              <Icon name="lucide:trophy" class="h-6 w-6 mr-2" />
              Ganadores
            </h3>
            <div class="space-y-3">
              <div
                v-for="winner in winners"
                :key="winner.id"
                class="flex justify-between items-center bg-white/10 rounded-lg p-3"
              >
                <div>
                  <p class="text-white font-semibold">{{ winner.player_name }}</p>
                  <p class="text-gray-300 text-sm">Cartón #{{ winner.card_id }}</p>
                </div>
                <div class="text-right">
                  <p class="text-green-400 font-bold text-lg">
                    ${{ winner.prize }} {{ room?.currency }}
                  </p>
                  <p class="text-gray-300 text-sm">{{ winner.pattern }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal de compra de cartones -->
    <Modal
      v-model="showPurchaseModal"
      title="Comprar Cartones"
      size="md"
      @confirm="purchaseCards"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Cantidad de cartones
          </label>
          <input
            v-model.number="purchaseQuantity"
            type="number"
            min="1"
            max="10"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
        </div>
        <div class="p-4 bg-blue-50 rounded-lg">
          <div class="flex justify-between text-sm">
            <span>Precio por cartón:</span>
            <span class="font-semibold">${{ cardPrice }} {{ room?.currency }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold mt-2">
            <span>Total:</span>
            <span>${{ purchaseQuantity * cardPrice }} {{ room?.currency }}</span>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de juego en vivo
 *
 * Sala de bingo activa con:
 * - Tablero de números en tiempo real
 * - Cartones del jugador
 * - Sistema de compra de cartones
 * - Notificación de ganadores
 * - WebSocket para actualizaciones en vivo
 */

definePageMeta({
  middleware: ['auth'],
  layout: 'player'
})

const route = useRoute()
const { getRooms, getPlayerCards, purchaseCard, checkWinner } = useBingoApi()

// Estado
const roomId = route.params.id as string
const room = ref<any>(null)
const myCards = ref<any[]>([])
const calledNumbers = ref<number[]>([])
const currentNumber = ref<number | null>(null)
const winners = ref<any[]>([])
const isLoading = ref(true)
const showPurchaseModal = ref(false)
const purchaseQuantity = ref(1)
const cardPrice = ref(10)

// Computed
const statusText = computed(() => {
  if (!room.value) return 'Cargando...'
  const statuses: Record<string, string> = {
    pending: 'Esperando inicio',
    active: 'En curso',
    finished: 'Finalizado',
    cancelled: 'Cancelado'
  }
  return statuses[room.value.status] || room.value.status
})

const statusClasses = computed(() => {
  if (!room.value) return 'text-gray-400'
  const classes: Record<string, string> = {
    pending: 'text-yellow-400 font-semibold',
    active: 'text-green-400 font-semibold animate-pulse',
    finished: 'text-gray-400',
    cancelled: 'text-red-400'
  }
  return classes[room.value.status] || 'text-gray-400'
})

const canPurchaseMore = computed(() => {
  return room.value?.status === 'pending' || room.value?.status === 'active'
})

// Métodos
const loadRoom = async () => {
  try {
    const response = await getRooms({ roomType: 'manual' })
    room.value = response.data.find((r: any) => r.id === roomId) || null
  } catch (error) {
    console.error('Error cargando sala:', error)
  }
}

const loadMyCards = async () => {
  try {
    const response = await getPlayerCards(roomId)
    myCards.value = response.data || []
  } catch (error) {
    console.error('Error cargando cartones:', error)
  }
}

const loadGameData = async () => {
  isLoading.value = true
  try {
    await Promise.all([
      loadRoom(),
      loadMyCards()
    ])
  } finally {
    isLoading.value = false
  }
}

const purchaseCards = async () => {
  try {
    const response = await purchaseCard(roomId, purchaseQuantity.value)
    
    // Update room prize pool and currency with the response data
    if (response.data && room.value) {
      room.value.prize_pool = response.data.prize_pool
      room.value.currency = response.data.currency
    }
    
    await loadMyCards()
    showPurchaseModal.value = false
    purchaseQuantity.value = 1
  } catch (error) {
    console.error('Error comprando cartones:', error)
    alert('Error al comprar cartones. Por favor intenta de nuevo.')
  }
}

const handleBingo = async (card: any) => {
  try {
    const response = await checkWinner(roomId, card.id, 'default-pattern')

    if (response.data.isWinner) {
      alert(`¡Felicidades! Ganaste $${response.data.prize}`)
      await loadGameData()
    } else {
      alert('No tienes un patrón ganador aún. Sigue jugando!')
    }
  } catch (error) {
    console.error('Error verificando bingo:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadGameData()

  // TODO: Conectar WebSocket para actualizaciones en tiempo real
  // setupWebSocket()

  // Simular números llamados (TEMPORAL)
  // En producción esto vendría del WebSocket
  const interval = setInterval(() => {
    if (calledNumbers.value.length < 75 && room.value?.status === 'active') {
      const nextNum = Math.floor(Math.random() * 75) + 1
      if (!calledNumbers.value.includes(nextNum)) {
        calledNumbers.value.push(nextNum)
        currentNumber.value = nextNum
      }
    }
  }, 3000)

  onUnmounted(() => clearInterval(interval))
})

// SEO
useHead({
  title: `Sala ${roomId} - PlayRKE Bingo`
})
</script>
