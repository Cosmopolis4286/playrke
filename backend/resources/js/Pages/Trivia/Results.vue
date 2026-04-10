<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Leaderboard from '@/Components/Trivia/Score/Leaderboard.vue'
import PlayerScore from '@/Components/Trivia/Score/PlayerScore.vue'
import LoadingOverlay from '@/Components/Trivia/Shared/LoadingOverlay.vue'
import type { Player, GameStats } from '@/types/trivia'

interface Props {
  gameId: string
  roomCode: string
}

const props = defineProps<Props>()

const isLoading = ref(true)
const players = ref<Player[]>([])
const currentPlayerStats = ref<GameStats | null>(null)
const winner = ref<Player | null>(null)

async function loadResults() {
  try {
    const response = await axios.get(`/api/trivia/games/${props.gameId}/results`)
    players.value = response.data.leaderboard
    currentPlayerStats.value = response.data.playerStats
    winner.value = response.data.winner
  } catch (error) {
    console.error('Error loading results:', error)
  } finally {
    isLoading.value = false
  }
}

function shareResults() {
  if (navigator.share) {
    navigator.share({
      title: 'Mis resultados en Trivia!',
      text: `¡Obtuve ${currentPlayerStats.value?.score} puntos en la trivia!`,
      url: window.location.href
    })
  } else {
    // Fallback: copy to clipboard
    navigator.clipboard.writeText(window.location.href)
    alert('¡Enlace copiado al portapapeles!')
  }
}

onMounted(() => {
  loadResults()
})
</script>

<template>
  <Head title="Resultados - Trivia" />

  <div class="min-h-screen bg-gradient-to-br from-purple-600 to-blue-600">
    <LoadingOverlay v-if="isLoading" message="Cargando resultados..." />

    <div v-else class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <!-- Winner Banner -->
      <div
        v-if="winner"
        class="bg-yellow-400 rounded-2xl shadow-xl p-8 text-center mb-8 transform hover:scale-105 transition-transform"
      >
        <div class="text-6xl mb-4">🏆</div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          ¡{{ winner.name }} es el ganador!
        </h1>
        <p class="text-xl text-gray-800">
          {{ winner.score }} puntos
        </p>
      </div>

      <!-- Player Stats Card -->
      <div
        v-if="currentPlayerStats"
        class="bg-white rounded-xl shadow-lg p-6 mb-8"
      >
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
          Tus Estadísticas
        </h2>
        <PlayerScore :stats="currentPlayerStats" detailed />
      </div>

      <!-- Final Leaderboard -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
          Clasificación Final
        </h2>
        <Leaderboard
          :players="players"
          show-ranks
          show-stats
        />
      </div>

      <!-- Action Buttons -->
      <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
        <button
          @click="shareResults"
          class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
          </svg>
          Compartir
        </button>

        <Link
          href="/trivia"
          class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Jugar de Nuevo
        </Link>
      </div>
    </div>
  </div>
</template>
