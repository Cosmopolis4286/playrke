<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { getEchoInstance } from '@js/plugins/echo'
import { getRoute } from '@js/routes/triviaRoutes'
import type { WsQuestionSent, WsAnswerResult, WsGameFinished, Question, AnswerResult } from '@js/types/trivia'

interface Props {
  room: {
    id: string
    code: string
    title: string
    potAmount: number
    currentPlayers: number
    maxPlayers: number
  }
  game: {
    id: string
    currentQuestion: number
    totalQuestions: number
  }
  question: Question | null
  player: {
    id: string
    name: string
    avatar?: string
  }
}

const props = defineProps<Props>()

// ── Estado del juego ──────────────────────────────────────────────────────────
const currentQuestion  = ref<Question | null>(props.question)
const questionIndex    = ref(props.game.currentQuestion)
const totalQuestions   = ref(props.game.totalQuestions)
const selectedAnswer   = ref<number | null>(null)
const hasAnswered      = ref(false)
const timeLeft         = ref(0)
const totalTime        = ref(0)
const gameStatus       = ref<'waiting' | 'playing' | 'answered' | 'results' | 'finished'>('waiting')
const currentScore     = ref(0)
const answerResult     = ref<AnswerResult | null>(null)
const isSubmitting     = ref(false)
let   timerInterval:   ReturnType<typeof setInterval> | null = null
let   questionStart:   number = 0

// ── Echo ──────────────────────────────────────────────────────────────────────
const echo        = getEchoInstance()
const roomChannel = `trivia.room.${props.room.id}`
const playerChannel = `trivia.player.${props.player.id}`
let   channel: any = null
let   playerCh: any = null

function setupEcho() {
  try {
    channel = echo.channel(roomChannel)

    channel
      .listen('.trivia.question.sent', (data: WsQuestionSent) => {
        loadQuestion(data.question, data.question_index, data.total_questions)
      })
      .listen('.trivia.game.finished', (data: WsGameFinished) => {
        stopTimer()
        gameStatus.value = 'finished'
        window.location.href = getRoute('playResults', { gameId: props.game.id })
      })

    playerCh = echo.channel(playerChannel)
    playerCh.listen('.trivia.answer.result', (data: WsAnswerResult) => {
      answerResult.value  = {
        success:              true,
        is_correct:           data.is_correct,
        is_late:              false,
        points_earned:        data.points_earned,
        correct_option_index: data.correct_option_index,
        current_score:        data.current_score,
      }
      currentScore.value = data.current_score
    })
  } catch (err) {
    console.warn('[Play] Echo no disponible:', err)
  }
}

function teardownEcho() {
  try { echo.leaveChannel(roomChannel) } catch {}
  try { echo.leaveChannel(playerChannel) } catch {}
  channel = null
  playerCh = null
}

// ── Timer ─────────────────────────────────────────────────────────────────────
function startTimer(limit: number) {
  stopTimer()
  timeLeft.value  = limit
  totalTime.value = limit
  questionStart   = Date.now()

  timerInterval = setInterval(() => {
    if (hasAnswered.value) { stopTimer(); return }
    if (timeLeft.value <= 0) {
      stopTimer()
      if (!hasAnswered.value) handleTimeUp()
      return
    }
    timeLeft.value--
  }, 1000)
}

function stopTimer() {
  if (timerInterval !== null) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

function handleTimeUp() {
  hasAnswered.value  = true
  gameStatus.value   = 'answered'
  submitAnswer(-1)
}

// ── Preguntas ─────────────────────────────────────────────────────────────────
function loadQuestion(q: Question, index: number, total: number) {
  stopTimer()
  currentQuestion.value = q
  questionIndex.value   = index
  totalQuestions.value  = total
  selectedAnswer.value  = null
  hasAnswered.value     = false
  answerResult.value    = null
  gameStatus.value      = 'playing'
  startTimer(q.timeLimit)
}

function selectAnswer(index: number) {
  if (hasAnswered.value || gameStatus.value !== 'playing' || isSubmitting.value) return
  selectedAnswer.value = index
  hasAnswered.value    = true
  gameStatus.value     = 'answered'
  submitAnswer(index)
}

// ── Submit al backend ─────────────────────────────────────────────────────────
async function submitAnswer(optionIndex: number) {
  if (isSubmitting.value || !currentQuestion.value) return
  isSubmitting.value = true

  const responseTimeMs = Math.max(0, Date.now() - questionStart)
  const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''

  try {
    const res = await fetch(getRoute('gameAnswer', { gameId: props.game.id }), {
      method:  'POST',
      headers: {
        'Content-Type':     'application/json',
        'Accept':           'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN':     csrf,
      },
      body: JSON.stringify({
        question_id:           currentQuestion.value.id,
        selected_option_index: optionIndex,
        response_time_ms:      responseTimeMs,
      }),
    })

    const data: AnswerResult = await res.json().catch(() => ({ success: false } as any))

    if (res.ok && data.success) {
      answerResult.value = data
      currentScore.value = data.current_score
    }
  } catch (err) {
    console.warn('[Play] submitAnswer error:', err)
  } finally {
    isSubmitting.value = false
  }
}

// ── Computed ──────────────────────────────────────────────────────────────────
const optionLetters = ['A', 'B', 'C', 'D']
const optionColors  = [
  { border: '#a855f7', bg: 'rgba(168,85,247,0.2)',  text: '#c084fc' },
  { border: '#06b6d4', bg: 'rgba(6,182,212,0.2)',   text: '#22d3ee' },
  { border: '#3b82f6', bg: 'rgba(59,130,246,0.2)',  text: '#60a5fa' },
  { border: '#8b5cf6', bg: 'rgba(139,92,246,0.2)',  text: '#a78bfa' },
]

const progress = computed(() =>
  totalQuestions.value === 0 ? 0 : (questionIndex.value / totalQuestions.value) * 100
)

function optionBorderColor(index: number): string {
  if (!hasAnswered.value || !answerResult.value) return optionColors[index].border
  if (index === answerResult.value.correct_option_index) return '#22c55e'  // verde
  if (index === selectedAnswer.value && !answerResult.value.is_correct)    return '#ef4444'  // rojo
  return optionColors[index].border
}

function optionBgColor(index: number): string {
  if (!hasAnswered.value || !answerResult.value) {
    return selectedAnswer.value === index ? 'rgba(34,211,238,0.3)' : optionColors[index].bg
  }
  if (index === answerResult.value.correct_option_index) return 'rgba(34,197,94,0.25)'
  if (index === selectedAnswer.value && !answerResult.value.is_correct) return 'rgba(239,68,68,0.25)'
  return optionColors[index].bg
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
  setupEcho()
  if (props.question) {
    loadQuestion(props.question, props.game.currentQuestion, props.game.totalQuestions)
  } else {
    gameStatus.value = 'waiting'
  }
})

onUnmounted(() => {
  stopTimer()
  teardownEcho()
})

const formatCurrency = (amount: number) =>
  new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(amount)
</script>

<template>
  <Head title="El Poder del Saber - Juego" />

  <div class="min-h-screen bg-[#0a0e27] text-white overflow-x-hidden">
    <!-- Header -->
    <header class="relative py-4 px-4">
      <div class="text-center mb-2">
        <h1 class="text-3xl md:text-4xl font-black tracking-wider uppercase"
            style="font-family:'Impact','Arial Black',sans-serif;text-shadow:0 0 10px #0044ff,0 0 20px #0044ff,0 0 30px #0044ff;-webkit-text-stroke:2px #0044ff;color:white;">
          EL PODER DEL SABER
        </h1>
        <p class="text-[10px] tracking-[0.3em] text-blue-300 uppercase mt-1">Donde solo los mejores ganan</p>
      </div>

      <!-- Stats Bar -->
      <div class="max-w-4xl mx-auto grid grid-cols-2 gap-3 mt-4">
        <div class="bg-gradient-to-r from-green-900/50 to-green-800/30 border border-green-500/50 rounded-lg p-2">
          <p class="text-[10px] text-green-400 uppercase tracking-wider">Pote a Repartir</p>
          <p class="text-lg font-bold text-green-300">{{ formatCurrency(room.potAmount) }}</p>
          <span class="text-xs text-gray-300">👥 {{ room.currentPlayers }} jugadores</span>
        </div>
        <div class="bg-gradient-to-r from-cyan-900/50 to-cyan-800/30 border border-cyan-500/50 rounded-lg p-2">
          <p class="text-[10px] text-cyan-400 uppercase tracking-wider">Tu puntuación</p>
          <p class="text-xl font-bold text-white">{{ currentScore }}</p>
          <p class="text-xs text-cyan-300">Ronda {{ questionIndex }}/{{ totalQuestions }}</p>
        </div>
      </div>
    </header>

    <!-- Timer Circular -->
    <div class="flex justify-center -mt-2 mb-4">
      <div class="relative w-20 h-20">
        <svg class="w-full h-full transform -rotate-90">
          <circle cx="40" cy="40" r="36" stroke="#1e3a5f" stroke-width="4" fill="none"/>
          <circle cx="40" cy="40" r="36"
                  :stroke="timeLeft <= 5 ? '#ef4444' : '#00d4ff'"
                  stroke-width="4" fill="none"
                  :stroke-dasharray="226"
                  :stroke-dashoffset="totalTime > 0 ? 226 - (226 * timeLeft / totalTime) : 226"
                  class="transition-all duration-1000 ease-linear"/>
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <span class="text-2xl font-bold" :class="timeLeft <= 5 ? 'text-red-500 animate-pulse' : 'text-white'">
            {{ timeLeft }}
          </span>
        </div>
      </div>
    </div>

    <!-- Área de pregunta -->
    <main class="max-w-2xl mx-auto px-4 pb-8">

      <!-- Esperando primera pregunta -->
      <div v-if="gameStatus === 'waiting'" class="text-center py-16 space-y-4">
        <div class="w-16 h-16 mx-auto rounded-full bg-blue-500/20 border border-blue-500/50 flex items-center justify-center">
          <svg class="w-8 h-8 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
        </div>
        <p class="text-blue-300 font-semibold">Esperando la primera pregunta...</p>
      </div>

      <!-- Pregunta activa -->
      <template v-else-if="currentQuestion">
        <!-- Tarjeta de pregunta -->
        <div class="relative mb-6">
          <div class="bg-gradient-to-b from-[#1a1f3a] to-[#0f1429] border border-blue-500/50 rounded-2xl p-6 shadow-2xl">
            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
              <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center border-2 border-blue-400">
                <span class="text-lg">🎯</span>
              </div>
            </div>
            <h2 class="text-lg md:text-xl font-bold text-center text-white mt-2 leading-relaxed">
              {{ currentQuestion.text }}
            </h2>
          </div>
        </div>

        <!-- Opciones -->
        <div class="space-y-3">
          <button
            v-for="(option, index) in currentQuestion.options"
            :key="index"
            @click="selectAnswer(index)"
            :disabled="hasAnswered || isSubmitting"
            class="w-full transition-all duration-200"
            :style="{ opacity: hasAnswered && selectedAnswer !== index && answerResult?.correct_option_index !== index ? 0.45 : 1 }"
          >
            <div
              class="flex items-center gap-3 px-4 py-3 rounded-full border-2 transition-all duration-300"
              :style="{
                borderColor:     optionBorderColor(index),
                backgroundColor: optionBgColor(index),
                boxShadow:       selectedAnswer === index ? `0 0 20px ${optionColors[index].border}55` : 'none',
              }"
            >
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-lg border-2"
                :style="{ borderColor: optionColors[index].border, color: optionColors[index].text, backgroundColor: 'rgba(0,0,0,0.3)' }"
              >
                {{ optionLetters[index] }}
              </div>
              <span class="flex-1 text-left font-semibold text-white text-sm md:text-base">{{ option }}</span>
              <!-- Indicador de resultado -->
              <span v-if="answerResult && index === answerResult.correct_option_index" class="text-green-400 font-bold text-lg">✓</span>
              <span v-else-if="answerResult && index === selectedAnswer && !answerResult.is_correct" class="text-red-400 font-bold text-lg">✗</span>
            </div>
          </button>
        </div>

        <!-- Imagen -->
        <div v-if="currentQuestion.image" class="mt-6 flex justify-center">
          <img :src="currentQuestion.image" alt="Imagen de la pregunta"
               class="max-w-full h-auto max-h-48 object-cover rounded-lg border-2 border-blue-500/30 shadow-2xl"/>
        </div>

        <!-- Progreso -->
        <div class="mt-6 text-center">
          <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a1f3a] rounded-full border border-blue-500/30">
            <span class="text-sm font-bold text-gray-400">{{ String(questionIndex).padStart(2, '0') }}</span>
            <span class="text-gray-500">/</span>
            <span class="text-sm font-bold text-white">{{ String(totalQuestions).padStart(2, '0') }}</span>
          </div>
        </div>

        <!-- Feedback de respuesta -->
        <Transition name="fade">
          <div v-if="answerResult" class="mt-4 text-center space-y-1">
            <p v-if="answerResult.is_correct" class="text-green-400 font-bold text-lg animate-bounce">
              ✅ ¡Correcto! +{{ answerResult.points_earned }} pts
            </p>
            <p v-else-if="answerResult.is_late" class="text-yellow-400 font-semibold">
              ⏰ Tiempo agotado
            </p>
            <p v-else class="text-red-400 font-semibold">
              ❌ Incorrecto
            </p>
            <p class="text-gray-400 text-xs">Puntuación total: {{ answerResult.current_score }}</p>
          </div>
          <div v-else-if="hasAnswered && !answerResult" class="mt-4 text-center">
            <p class="text-cyan-400 font-semibold animate-pulse">Procesando respuesta...</p>
          </div>
        </Transition>
      </template>

    </main>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
