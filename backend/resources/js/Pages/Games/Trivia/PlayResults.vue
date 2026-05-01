<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

interface Props {
  game: {
    id: string
    roomTitle: string
    totalQuestions: number
    correctAnswers: number
    finalScore: number
    rank: number
    totalPlayers: number
    wonPrize?: number
  }
  player: {
    id: string
    name: string
    avatar?: string
  }
  topPlayers: Array<{
    position: number
    name: string
    score: number
    correctAnswers: number
    isCurrentPlayer: boolean
  }>
}

const props = defineProps<Props>()

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2
  }).format(amount)
}

const accuracy = computed(() => {
  if (props.game.totalQuestions === 0) return 0
  return Math.round((props.game.correctAnswers / props.game.totalQuestions) * 100)
})

const rankMedal = computed(() => {
  if (props.game.rank === 1) return '🥇'
  if (props.game.rank === 2) return '🥈'
  if (props.game.rank === 3) return '🥉'
  return '🏅'
})

const rankColor = computed(() => {
  if (props.game.rank === 1) return 'from-yellow-500 to-yellow-700'
  if (props.game.rank === 2) return 'from-gray-400 to-gray-600'
  if (props.game.rank === 3) return 'from-amber-600 to-amber-800'
  return 'from-blue-500 to-blue-700'
})
</script>

<template>
  <Head title="Resultados - El Poder del Saber" />

  <div class="min-h-screen bg-[#0a0e27] text-white">
    <!-- Header -->
    <header class="py-6 px-4">
      <div class="text-center">
        <h1 class="text-3xl md:text-4xl font-black tracking-wider uppercase"
            style="font-family: 'Impact', 'Arial Black', sans-serif;
                   text-shadow: 0 0 10px #0044ff, 0 0 20px #0044ff, 0 0 30px #0044ff;
                   -webkit-text-stroke: 2px #0044ff;
                   color: white;">
          EL PODER DEL SABER
        </h1>
        <p class="text-[10px] tracking-[0.3em] text-blue-300 uppercase mt-1">
          Resultados Finales
        </p>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 pb-8">
      <!-- Tarjeta de Resultado Principal -->
      <div class="relative mb-8">
        <!-- Medalla flotante -->
        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 z-10">
          <div class="w-20 h-20 rounded-full bg-gradient-to-br flex items-center justify-center text-4xl shadow-2xl"
               :class="rankColor"
               style="box-shadow: 0 0 30px rgba(255, 215, 0, 0.5);">
            {{ rankMedal }}
          </div>
        </div>

        <!-- Contenido principal -->
        <div class="bg-gradient-to-b from-[#1a1f3a] to-[#0f1429] border-2 border-blue-500/50 rounded-2xl p-6 pt-12 mt-4">
          <h2 class="text-xl font-bold text-center mb-4">¡Felicitaciones {{ player.name }}!</h2>
          
          <!-- Posición -->
          <div class="text-center mb-6">
            <p class="text-sm text-gray-400">Tu posición</p>
            <p class="text-4xl font-black" :class="game.rank <= 3 ? 'text-yellow-400' : 'text-white'">
              #{{ game.rank }}
            </p>
            <p class="text-xs text-gray-500">de {{ game.totalPlayers }} jugadores</p>
          </div>

          <!-- Estadísticas -->
          <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-[#0a0e27]/50 rounded-xl p-3 text-center border border-blue-500/20">
              <p class="text-2xl font-bold text-green-400">{{ game.correctAnswers }}</p>
              <p class="text-[10px] text-gray-400 uppercase">Correctas</p>
            </div>
            <div class="bg-[#0a0e27]/50 rounded-xl p-3 text-center border border-blue-500/20">
              <p class="text-2xl font-bold text-cyan-400">{{ game.finalScore }}</p>
              <p class="text-[10px] text-gray-400 uppercase">Puntos</p>
            </div>
            <div class="bg-[#0a0e27]/50 rounded-xl p-3 text-center border border-blue-500/20">
              <p class="text-2xl font-bold" :class="accuracy >= 70 ? 'text-green-400' : accuracy >= 50 ? 'text-yellow-400' : 'text-red-400'">
                {{ accuracy }}%
              </p>
              <p class="text-[10px] text-gray-400 uppercase">Precisión</p>
            </div>
          </div>

          <!-- Premio -->
          <div v-if="game.wonPrize && game.wonPrize > 0" class="bg-gradient-to-r from-green-900/50 to-green-800/30 border border-green-500/50 rounded-xl p-4 text-center">
            <p class="text-sm text-green-400 uppercase tracking-wider mb-1">🎉 Premio Ganado</p>
            <p class="text-3xl font-black text-green-300">{{ formatCurrency(game.wonPrize) }}</p>
          </div>

          <!-- Sin premio -->
          <div v-else class="bg-[#0a0e27]/50 border border-gray-500/30 rounded-xl p-4 text-center">
            <p class="text-sm text-gray-400">No ganaste premio esta vez</p>
            <p class="text-xs text-gray-500 mt-1">¡Sigue practicando para la próxima!</p>
          </div>
        </div>
      </div>

      <!-- Clasificación -->
      <div class="bg-gradient-to-b from-[#1a1f3a] to-[#0f1429] border border-blue-500/30 rounded-2xl p-4 mb-6">
        <h3 class="text-lg font-bold text-center mb-4 flex items-center justify-center gap-2">
          <span>🏆</span> Clasificación
        </h3>

        <div class="space-y-2">
          <div
            v-for="player in topPlayers"
            :key="player.position"
            class="flex items-center gap-3 p-3 rounded-xl transition-colors"
            :class="[
              player.isCurrentPlayer ? 'bg-blue-600/30 border border-blue-400/50' : 'bg-[#0a0e27]/50 border border-transparent',
              player.position <= 3 ? 'bg-gradient-to-r' : ''
            ]"
            :style="player.position === 1 ? 'background: linear-gradient(90deg, rgba(234, 179, 8, 0.2), transparent)' :
                     player.position === 2 ? 'background: linear-gradient(90deg, rgba(156, 163, 175, 0.2), transparent)' :
                     player.position === 3 ? 'background: linear-gradient(90deg, rgba(180, 83, 9, 0.2), transparent)' : ''"
          >
            <!-- Posición -->
            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                 :class="[
                   player.position === 1 ? 'bg-yellow-500 text-white' :
                   player.position === 2 ? 'bg-gray-400 text-gray-900' :
                   player.position === 3 ? 'bg-amber-600 text-white' :
                   'bg-[#1a1f3a] text-gray-400'
                 ]">
              {{ player.position <= 3 ? ['🥇', '🥈', '🥉'][player.position - 1] : player.position }}
            </div>

            <!-- Nombre -->
            <div class="flex-1">
              <p class="font-semibold text-sm" :class="player.isCurrentPlayer ? 'text-cyan-300' : 'text-white'">
                {{ player.name }}
                <span v-if="player.isCurrentPlayer" class="text-xs text-cyan-400 ml-1">(Tú)</span>
              </p>
            </div>

            <!-- Score -->
            <div class="text-right">
              <p class="font-bold text-sm">{{ player.score }}</p>
              <p class="text-[10px] text-gray-500">{{ player.correctAnswers }} aciertos</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="space-y-3">
        <Link
          :href="getRoute('lobby')"
          class="block w-full py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 rounded-xl font-bold text-lg text-center transition-all duration-200 shadow-lg shadow-blue-500/30"
        >
          🎮 Jugar Otra Partida
        </Link>

        <Link
          :href="getRoute('leaderboard')"
          class="block w-full py-3 bg-[#1a1f3a] hover:bg-[#252b4a] border border-blue-500/30 rounded-xl font-semibold text-center transition-all duration-200"
        >
          📊 Ver Ranking Global
        </Link>

        <Link
          :href="getRoute('stats')"
          class="block w-full py-3 bg-transparent hover:bg-[#1a1f3a]/50 border border-gray-500/30 rounded-xl font-semibold text-gray-400 hover:text-white text-center transition-all duration-200"
        >
          📈 Mis Estadísticas
        </Link>
      </div>
    </main>
  </div>
</template>

<style scoped>
.glow-text {
  text-shadow: 0 0 10px currentColor, 0 0 20px currentColor;
}
</style>
