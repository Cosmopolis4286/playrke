<!--
  Página principal para jugadores.

  Lobby principal con salas disponibles, perfil del jugador
  y acceso rápido a las partidas de bingo.
-->

<template>
  <div class="player-layout min-h-screen relative">
    <!-- Hero Section -->
    <div class="relative z-10 py-12 px-6">
      <div class="max-w-7xl mx-auto">
        <!-- Bienvenida -->
        <div class="text-center mb-12">
          <h1 class="font-gaming text-4xl md:text-6xl font-bold text-white mb-4 text-glow">
            ¡Bienvenido a PlayRKE!
          </h1>
          <p class="text-xl text-slate-300 max-w-2xl mx-auto">
            Vive la emoción del bingo como nunca antes. Únete a miles de jugadores en tiempo real.
          </p>
        </div>

        <!-- Stats del jugador -->
        <div v-if="isAuthenticated" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
          <div class="card-player text-center">
            <div class="p-4">
              <Icon name="lucide:wallet" class="w-8 h-8 text-gaming-gold mx-auto mb-2" />
              <p class="text-2xl font-bold text-white">${{ playerStats.balance }}</p>
              <p class="text-slate-400 text-sm">Saldo</p>
            </div>
          </div>

          <div class="card-player text-center">
            <div class="p-4">
              <Icon name="lucide:trophy" class="w-8 h-8 text-gaming-gold mx-auto mb-2" />
              <p class="text-2xl font-bold text-white">{{ playerStats.level }}</p>
              <p class="text-slate-400 text-sm">Nivel</p>
            </div>
          </div>

          <div class="card-player text-center">
            <div class="p-4">
              <Icon name="lucide:zap" class="w-8 h-8 text-gaming-neon mx-auto mb-2" />
              <p class="text-2xl font-bold text-white">{{ playerStats.winStreak }}</p>
              <p class="text-slate-400 text-sm">Racha</p>
            </div>
          </div>

          <div class="card-player text-center">
            <div class="p-4">
              <Icon name="lucide:star" class="w-8 h-8 text-gaming-gold mx-auto mb-2" />
              <p class="text-2xl font-bold text-white">{{ playerStats.achievements }}</p>
              <p class="text-slate-400 text-sm">Logros</p>
            </div>
          </div>
        </div>

        <!-- Salas disponibles -->
        <div class="mb-12">
          <div class="flex items-center justify-between mb-8">
            <h2 class="font-gaming text-3xl font-bold text-white">
              Salas Disponibles
            </h2>
            <div class="flex items-center space-x-4">
              <select v-model="selectedFilter" class="bg-slate-800 border border-slate-600 text-white rounded-lg px-4 py-2">
                <option value="all">Todas las salas</option>
                <option value="beginner">Principiante</option>
                <option value="intermediate">Intermedio</option>
                <option value="expert">Experto</option>
                <option value="vip">VIP</option>
              </select>
              <button class="btn-gaming">
                <Icon name="lucide:refresh-cw" class="w-4 h-4 mr-2" />
                Actualizar
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="room in filteredRooms" :key="room.id" class="card-player group hover:scale-105 transition-all duration-300">
              <div class="p-6">
                <!-- Header de la sala -->
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg">
                      <Icon name="lucide:users" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <h3 class="font-bold text-white text-lg">{{ room.name }}</h3>
                      <p class="text-slate-400 text-sm">{{ room.type }}</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-1">
                    <div class="w-2 h-2 rounded-full" :class="room.status === 'active' ? 'bg-green-500' : 'bg-yellow-500'"></div>
                    <span class="text-xs text-slate-400">{{ room.status === 'active' ? 'Activa' : 'Esperando' }}</span>
                  </div>
                </div>

                <!-- Info de la sala -->
                <div class="space-y-3 mb-6">
                  <div class="flex justify-between items-center">
                    <span class="text-slate-400">Jugadores:</span>
                    <span class="text-white font-medium">{{ room.players }}/{{ room.maxPlayers }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-slate-400">Premio:</span>
                    <span class="text-gaming-gold font-bold">${{ room.prize }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-slate-400">Entrada:</span>
                    <span class="text-white font-medium">${{ room.entryFee }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-slate-400">Próximo juego:</span>
                    <span class="text-white font-medium">{{ room.nextGame }}</span>
                  </div>
                </div>

                <!-- Barra de progreso -->
                <div class="mb-4">
                  <div class="flex justify-between text-sm text-slate-400 mb-1">
                    <span>Ocupación</span>
                    <span>{{ Math.round((room.players / room.maxPlayers) * 100) }}%</span>
                  </div>
                  <div class="w-full bg-slate-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-300"
                         :style="{ width: (room.players / room.maxPlayers) * 100 + '%' }"></div>
                  </div>
                </div>

                <!-- Botón de acción -->
                <button
                  class="btn-gaming w-full group-hover:scale-105 transition-transform"
                  :disabled="room.players >= room.maxPlayers"
                  @click="joinRoom(room)"
                >
                  <Icon name="lucide:play" class="w-4 h-4 mr-2" />
                  {{ room.players >= room.maxPlayers ? 'Sala Llena' : 'Unirse' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección de torneos -->
        <div class="mb-12">
          <div class="flex items-center justify-between mb-8">
            <h2 class="font-gaming text-3xl font-bold text-white">
              Torneos Especiales
            </h2>
            <NuxtLink to="/player/tournaments" class="btn-gaming">
              Ver Todos
            </NuxtLink>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="tournament in activeTournaments" :key="tournament.id" class="card-player relative overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 pointer-events-none"></div>
              <div class="relative p-6">
                <div class="flex items-center space-x-3 mb-4">
                  <Icon name="lucide:trophy" class="w-8 h-8 text-gaming-gold" />
                  <div>
                    <h3 class="font-bold text-white text-xl">{{ tournament.name }}</h3>
                    <p class="text-gaming-gold">Premio: ${{ tournament.prize }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <p class="text-slate-400 text-sm">Participantes</p>
                    <p class="text-white font-bold">{{ tournament.participants }}</p>
                  </div>
                  <div>
                    <p class="text-slate-400 text-sm">Tiempo restante</p>
                    <p class="text-white font-bold">{{ tournament.timeLeft }}</p>
                  </div>
                </div>
                <button class="btn-gaming w-full bg-gradient-gold">
                  <Icon name="lucide:star" class="w-4 h-4 mr-2" />
                  Participar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de login si no está autenticado -->
    <div v-if="!isAuthenticated" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="card-player max-w-md w-full">
        <div class="p-6">
          <div class="text-center mb-6">
            <Icon name="lucide:user" class="w-16 h-16 text-gaming-gold mx-auto mb-4" />
            <h3 class="text-2xl font-bold text-white mb-2">¡Únete a la Diversión!</h3>
            <p class="text-slate-400">Inicia sesión para jugar y ganar increíbles premios</p>
          </div>
          <div class="space-y-4">
            <button class="btn-gaming w-full" @click="openLogin">
              <Icon name="lucide:log-in" class="w-4 h-4 mr-2" />
              Iniciar Sesión
            </button>
            <button class="btn-secondary w-full">
              <Icon name="lucide:user-plus" class="w-4 h-4 mr-2" />
              Registrarse
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Configuración de la página
definePageMeta({
  layout: 'player'
})

// SEO
useHead({
  title: 'PlayRKE - Bingo Online',
  meta: [
    { name: 'description', content: 'Juega bingo online en tiempo real con miles de jugadores. Salas 24/7, premios increíbles y diversión garantizada.' }
  ]
})

// Composables
const { isAuthenticated } = useAuth()

// Estados reactivos
const selectedFilter = ref('all')

const playerStats = ref({
  balance: 250,
  level: 12,
  winStreak: 5,
  achievements: 23
})

const rooms = ref([
  {
    id: 1,
    name: 'Sala Principiante',
    type: 'Clásico',
    players: 15,
    maxPlayers: 50,
    prize: 500,
    entryFee: 5,
    nextGame: '2 min',
    status: 'active',
    category: 'beginner'
  },
  {
    id: 2,
    name: 'Sala VIP Diamante',
    type: 'VIP',
    players: 8,
    maxPlayers: 20,
    prize: 2500,
    entryFee: 25,
    nextGame: '5 min',
    status: 'active',
    category: 'vip'
  },
  {
    id: 3,
    name: 'Súper Rápido',
    type: 'Velocidad',
    players: 32,
    maxPlayers: 100,
    prize: 1000,
    entryFee: 10,
    nextGame: '30 seg',
    status: 'active',
    category: 'intermediate'
  },
  {
    id: 4,
    name: 'Maestros del Bingo',
    type: 'Experto',
    players: 45,
    maxPlayers: 50,
    prize: 5000,
    entryFee: 50,
    nextGame: '8 min',
    status: 'waiting',
    category: 'expert'
  }
])

const activeTournaments = ref([
  {
    id: 1,
    name: 'Copa de Oro Semanal',
    prize: '10,000',
    participants: 234,
    timeLeft: '2d 14h'
  },
  {
    id: 2,
    name: 'Mega Jackpot',
    prize: '50,000',
    participants: 1205,
    timeLeft: '6d 8h'
  }
])

// Computed
const filteredRooms = computed(() => {
  if (selectedFilter.value === 'all') {
    return rooms.value
  }
  return rooms.value.filter(room => room.category === selectedFilter.value)
})

// Métodos
const joinRoom = (room) => {
  if (!isAuthenticated.value) {
    openLogin()
    return
  }

  // Lógica para unirse a la sala
  navigateTo(`/player/room/${room.id}`)
}

const openLogin = () => {
  // Abrir modal de login o redirigir
  navigateTo('/auth/login')
}

// Auto-actualización de salas
onMounted(() => {
  const interval = setInterval(() => {
    // Simular cambios en las salas
    rooms.value.forEach(room => {
      if (Math.random() > 0.7) {
        room.players = Math.min(room.maxPlayers, room.players + Math.floor(Math.random() * 3))
      }
    })
  }, 10000) // Cada 10 segundos

  onUnmounted(() => {
    clearInterval(interval)
  })
})
</script>