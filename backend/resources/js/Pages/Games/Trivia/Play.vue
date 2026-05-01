<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

interface Props {
  room: {
    id: string
    code: string
    title: string
    potAmount: number
    currentPlayers: number
    maxPlayers: number
    cupsAvailable: number
    cupsTotal: number
  }
  game: {
    id: string
    currentQuestion: number
    totalQuestions: number
  }
  question: {
    id: string
    text: string
    image?: string
    options: string[]
    timeLimit: number
    category?: string
  } | null
  player: {
    id: string
    name: string
    avatar?: string
  }
}

const props = defineProps<Props>()

// Estado del juego
const selectedAnswer = ref<number | null>(null)
const hasAnswered = ref(false)
const timeLeft = ref(0)
const totalTime = ref(0)
const gameStatus = ref<'waiting' | 'playing' | 'answered' | 'results'>('waiting')
const leaderboard = ref<Array<{ id: string; name: string; score: number; position: number }>>([])

let timerInterval: number | null = null

// Formato de moneda boliviana
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2
  }).format(amount)
}

// Letras para opciones
const optionLetters = ['A', 'B', 'C', 'D']

// Colores neón para opciones
const optionColors = [
  { border: '#a855f7', bg: 'rgba(168, 85, 247, 0.2)', text: '#c084fc' },    // Purple (A)
  { border: '#06b6d4', bg: 'rgba(6, 182, 212, 0.2)', text: '#22d3ee' },      // Cyan (B)
  { border: '#3b82f6', bg: 'rgba(59, 130, 246, 0.2)', text: '#60a5fa' },      // Blue (C)
  { border: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.2)', text: '#a78bfa' },      // Violet (D)
]

const progress = computed(() => {
  if (props.game.totalQuestions === 0) return 0
  return (props.game.currentQuestion / props.game.totalQuestions) * 100
})

const startTimer = () => {
  if (!props.question) return
  
  timeLeft.value = props.question.timeLimit
  totalTime.value = props.question.timeLimit
  gameStatus.value = 'playing'
  
  if (timerInterval) clearInterval(timerInterval)
  
  timerInterval = window.setInterval(() => {
    if (timeLeft.value > 0 && !hasAnswered.value) {
      timeLeft.value--
    } else if (timeLeft.value === 0 && !hasAnswered.value) {
      handleTimeUp()
    }
  }, 1000)
}

const handleTimeUp = () => {
  hasAnswered.value = true
  gameStatus.value = 'answered'
  // Enviar timeout al servidor
  submitAnswer(-1)
}

const selectAnswer = (index: number) => {
  if (hasAnswered.value || gameStatus.value !== 'playing') return
  
  selectedAnswer.value = index
  hasAnswered.value = true
  gameStatus.value = 'answered'
  
  submitAnswer(index)
}

const submitAnswer = (answerIndex: number) => {
  // Aquí iría la llamada al API
  console.log('Respuesta enviada:', answerIndex)
  // Simular esperando resultados
  setTimeout(() => {
    gameStatus.value = 'results'
  }, 2000)
}

onMounted(() => {
  if (props.question) {
    startTimer()
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
  <Head title="El Poder del Saber - Juego" />

  <div class="min-h-screen bg-[#0a0e27] text-white overflow-x-hidden">
    <!-- Header Principal -->
    <header class="relative py-4 px-4">
      <!-- Logo -->
      <div class="text-center mb-2">
        <div class="inline-block relative">
          <h1 class="text-3xl md:text-4xl font-black tracking-wider uppercase"
              style="font-family: 'Impact', 'Arial Black', sans-serif;
                     text-shadow: 0 0 10px #0044ff, 0 0 20px #0044ff, 0 0 30px #0044ff;
                     -webkit-text-stroke: 2px #0044ff;
                     color: white;">
            EL PODER DEL SABER
          </h1>
          <p class="text-[10px] tracking-[0.3em] text-blue-300 uppercase mt-1">
            Donde solo los mejores ganan
          </p>
        </div>
      </div>

      <!-- Stats Bar -->
      <div class="max-w-4xl mx-auto grid grid-cols-2 gap-3 mt-4">
        <!-- Pote a Repartir -->
        <div class="bg-gradient-to-r from-green-900/50 to-green-800/30 border border-green-500/50 rounded-lg p-2">
          <p class="text-[10px] text-green-400 uppercase tracking-wider">Pote a Repartir</p>
          <p class="text-lg font-bold text-green-300">{{ formatCurrency(room.potAmount) }}</p>
          <div class="flex items-center gap-1 mt-1">
            <span class="text-xs">👥</span>
            <span class="text-xs text-gray-300">{{ room.currentPlayers }} Jugadores en juego</span>
          </div>
        </div>

        <!-- Sigue en Juego -->
        <div class="bg-gradient-to-r from-cyan-900/50 to-cyan-800/30 border border-cyan-500/50 rounded-lg p-2">
          <p class="text-[10px] text-cyan-400 uppercase tracking-wider">Sigue en Juego</p>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-bold text-white">{{ player.name }}</p>
              <p class="text-xs text-cyan-300">Puntuación: 0</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-400">Ronda</p>
              <p class="text-sm font-bold text-cyan-300">{{ game.currentQuestion }}/{{ game.totalQuestions }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xs text-gray-400">Cupos Disponibles</span>
            <span class="text-xs font-bold" :class="room.cupsAvailable > 0 ? 'text-green-400' : 'text-red-400'">
              {{ room.cupsAvailable }}/{{ room.cupsTotal }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Timer Circular -->
    <div class="flex justify-center -mt-2 mb-4">
      <div class="relative w-20 h-20">
        <svg class="w-full h-full transform -rotate-90">
          <circle cx="40" cy="40" r="36" stroke="#1e3a5f" stroke-width="4" fill="none"/>
          <circle cx="40" cy="40" r="36" stroke="#00d4ff" stroke-width="4" fill="none"
                  :stroke-dasharray="226"
                  :stroke-dashoffset="226 - (226 * timeLeft / totalTime)"
                  class="transition-all duration-1000 ease-linear"/>
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <span class="text-2xl font-bold" :class="timeLeft <= 5 ? 'text-red-500' : 'text-white'">
            {{ timeLeft }}
          </span>
        </div>
        <div class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
          <span class="text-xs">⚡</span>
        </div>
      </div>
    </div>

    <!-- Área de Pregunta -->
    <main class="max-w-2xl mx-auto px-4 pb-8">
      <!-- Question Card -->
      <div v-if="question" class="relative mb-6">
        <!-- Números laterales -->
        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-2">
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-600 to-red-800 border-2 border-red-400 flex items-center justify-center shadow-lg shadow-red-500/30">
            <span class="text-xl font-black">18</span>
          </div>
        </div>
        <div class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-2">
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-600 to-red-800 border-2 border-red-400 flex items-center justify-center shadow-lg shadow-red-500/30">
            <span class="text-xl font-black">18</span>
          </div>
        </div>

        <!-- Pregunta -->
        <div class="bg-gradient-to-b from-[#1a1f3a] to-[#0f1429] border border-blue-500/50 rounded-2xl p-6 mx-8 shadow-2xl">
          <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center border-2 border-blue-400">
              <span class="text-lg">🎯</span>
            </div>
          </div>
          
          <h2 class="text-lg md:text-xl font-bold text-center text-white mt-2 leading-relaxed">
            {{ question.text }}
          </h2>
        </div>
      </div>

      <!-- Opciones de Respuesta -->
      <div v-if="question" class="space-y-3">
        <button
          v-for="(option, index) in question.options"
          :key="index"
          @click="selectAnswer(index)"
          :disabled="hasAnswered"
          class="w-full relative group transition-all duration-200"
          :style="{
            opacity: hasAnswered && selectedAnswer !== index ? 0.5 : 1
          }"
        >
          <div
            class="flex items-center gap-3 px-4 py-3 rounded-full border-2 transition-all duration-200"
            :style="{
              borderColor: selectedAnswer === index ? '#22d3ee' : optionColors[index].border,
              backgroundColor: selectedAnswer === index ? 'rgba(34, 211, 238, 0.3)' : optionColors[index].bg,
              boxShadow: selectedAnswer === index ? '0 0 20px rgba(34, 211, 238, 0.5)' : 'none'
            }"
          >
            <!-- Letra -->
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-lg border-2"
              :style="{
                borderColor: optionColors[index].border,
                color: optionColors[index].text,
                backgroundColor: 'rgba(0,0,0,0.3)'
              }"
            >
              {{ optionLetters[index] }}
            </div>
            
            <!-- Punto decorativo -->
            <div
              class="w-2 h-2 rounded-full"
              :style="{ backgroundColor: optionColors[index].text }"
            />
            
            <!-- Texto -->
            <span class="flex-1 text-left font-semibold text-white text-sm md:text-base">
              {{ option }}
            </span>
          </div>
        </button>
      </div>

      <!-- Imagen de la Pregunta -->
      <div v-if="question?.image" class="mt-6 flex justify-center">
        <div class="relative rounded-lg overflow-hidden border-2 border-blue-500/30 shadow-2xl">
          <img :src="question.image" alt="Imagen de la pregunta" class="max-w-full h-auto max-h-48 object-cover"/>
          <div class="absolute inset-0 bg-gradient-to-t from-[#0a0e27] via-transparent to-transparent opacity-50"></div>
          <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2">
            <div class="px-4 py-1 bg-blue-900/80 rounded-full border border-blue-400/50">
              <span class="text-xs font-bold text-blue-300 tracking-wider">PLAYRKE</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Imagen placeholder cuando no hay imagen -->
      <div v-else-if="question" class="mt-6 flex justify-center">
        <div class="relative rounded-lg overflow-hidden border-2 border-blue-500/30 shadow-2xl w-48 h-32 bg-gradient-to-br from-blue-900/50 to-cyan-900/30 flex items-center justify-center">
          <div class="text-center">
            <span class="text-4xl">🌊</span>
            <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2">
              <div class="px-4 py-1 bg-blue-900/80 rounded-full border border-blue-400/50">
                <span class="text-xs font-bold text-blue-300 tracking-wider">PLAYRKE</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Progreso -->
      <div class="mt-6 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a1f3a] rounded-full border border-blue-500/30">
          <span class="text-sm font-bold text-gray-400">
            {{ String(game.currentQuestion).padStart(2, '0') }}
          </span>
          <span class="text-gray-500">/</span>
          <span class="text-sm font-bold text-white">
            {{ String(game.totalQuestions).padStart(2, '0') }}
          </span>
        </div>
      </div>

      <!-- Estado de respuesta -->
      <div v-if="hasAnswered" class="mt-4 text-center">
        <p class="text-cyan-400 font-semibold animate-pulse">
          {{ selectedAnswer !== null ? '¡Respuesta enviada!' : '¡Se acabó el tiempo!' }}
        </p>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Animaciones personalizadas */
@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
  }
  50% {
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.8);
  }
}

.timer-glow {
  animation: pulse-glow 2s ease-in-out infinite;
}

/* Efecto de brillo en los bordes */
.question-glow {
  box-shadow: 
    0 0 20px rgba(59, 130, 246, 0.3),
    inset 0 0 20px rgba(59, 130, 246, 0.1);
}
</style>
