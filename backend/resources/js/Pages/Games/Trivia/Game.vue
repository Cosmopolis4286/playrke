<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import GameHeader from '@js/Components/Trivia/Game/GameHeader.vue'
import QuestionCard from '@js/Components/Trivia/Game/QuestionCard.vue'
import AnswerOptions from '@js/Components/Trivia/Game/AnswerOptions.vue'
import TimerBar from '@js/Components/Trivia/Game/TimerBar.vue'
import GameStatus from '@js/Components/Trivia/Game/GameStatus.vue'
import Leaderboard from '@js/Components/Trivia/Score/Leaderboard.vue'
import ConnectionStatus from '@js/Components/Trivia/Shared/ConnectionStatus.vue'
import type { Question, Player, GameState } from '@js/types/trivia'

interface Props {
  roomCode: string
  gameId: string
  currentPlayerId: string
}

const props = defineProps<Props>()

// Game state
const question = ref<Question | null>(null)
const selectedAnswer = ref<number | null>(null)
const hasAnswered = ref(false)
const timeLeft = ref(0)
const totalTime = ref(0)
const gameState = ref<GameState>('waiting')
const leaderboard = ref<Player[]>([])
const questionNumber = ref(0)
const totalQuestions = ref(0)
const connectionStatus = ref<'connected' | 'disconnected' | 'reconnecting'>('connected')

let ws: WebSocket | null = null
let timerInterval: number | null = null

const progress = computed(() => {
  if (totalQuestions.value === 0) return 0
  return (questionNumber.value / totalQuestions.value) * 100
})

function connectWebSocket() {
  const wsUrl = `${import.meta.env.VITE_WEBSOCKET_URL}/trivia/game/${props.gameId}`
  ws = new WebSocket(wsUrl)

  ws.onopen = () => {
    connectionStatus.value = 'connected'
  }

  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    handleGameEvent(data)
  }

  ws.onclose = () => {
    connectionStatus.value = 'disconnected'
    setTimeout(connectWebSocket, 3000)
  }

  ws.onerror = () => {
    connectionStatus.value = 'reconnecting'
  }
}

function handleGameEvent(data: any) {
  switch (data.type) {
    case 'question.sent':
      loadQuestion(data.question)
      break
    case 'question.closed':
      handleQuestionClosed()
      break
    case 'leaderboard.updated':
      leaderboard.value = data.leaderboard
      break
    case 'game.finished':
      handleGameFinished()
      break
    case 'answer.received':
      // Show who answered (optional)
      break
  }
}

function loadQuestion(q: Question) {
  question.value = q
  selectedAnswer.value = null
  hasAnswered.value = false
  timeLeft.value = q.timeLimit
  totalTime.value = q.timeLimit
  questionNumber.value = q.orderIndex
  totalQuestions.value = q.totalQuestions
  gameState.value = 'answering'

  // Start timer
  startTimer()
}

function startTimer() {
  if (timerInterval) clearInterval(timerInterval)
  
  timerInterval = window.setInterval(() => {
    if (timeLeft.value > 0 && !hasAnswered.value) {
      timeLeft.value--
    } else if (timeLeft.value === 0 && !hasAnswered.value) {
      // Time's up - auto-submit or mark as unanswered
      handleTimeUp()
    }
  }, 1000)
}

function handleTimeUp() {
  hasAnswered.value = true
  // Send no answer or timeout signal
  axios.post(`/api/trivia/games/${props.gameId}/timeout`, {
    playerId: props.currentPlayerId,
    questionId: question.value?.id
  })
}

function handleQuestionClosed() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  gameState.value = 'waiting'
}

function selectAnswer(index: number) {
  if (hasAnswered.value || !question.value) return

  selectedAnswer.value = index
  hasAnswered.value = true

  // Send answer to server
  axios.post(`/api/trivia/games/${props.gameId}/answer`, {
    playerId: props.currentPlayerId,
    questionId: question.value.id,
    answer: index,
    responseTime: totalTime.value - timeLeft.value
  })
    .then(response => {
      // Show correct/wrong feedback if needed
      console.log('Answer submitted:', response.data)
    })
    .catch(error => {
      console.error('Error submitting answer:', error)
      // Allow retry
      hasAnswered.value = false
      selectedAnswer.value = null
    })
}

function handleGameFinished() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  
  // Redirect to results page
  window.location.href = `/trivia/results/${props.gameId}`
}

onMounted(() => {
  connectWebSocket()
})

onUnmounted(() => {
  ws?.close()
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
  <Head title="Jugando Trivia" />

  <div class="min-h-screen bg-gray-900 text-white">
    <ConnectionStatus :status="connectionStatus" />

    <div class="max-w-6xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
      <!-- Game Header -->
      <GameHeader
        :question-number="questionNumber"
        :total-questions="totalQuestions"
        :progress="progress"
      />

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Game Area -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Timer -->
          <TimerBar
            :time-left="timeLeft"
            :total-time="totalTime"
          />

          <!-- Status -->
          <GameStatus :state="gameState" />

          <!-- Question -->
          <QuestionCard
            v-if="question"
            :question="question"
          />

          <!-- Answer Options -->
          <AnswerOptions
            v-if="question && gameState === 'answering'"
            :options="question.options"
            :selected="selectedAnswer"
            :disabled="hasAnswered"
            @select="selectAnswer"
          />
        </div>

        <!-- Sidebar: Leaderboard -->
        <div class="lg:col-span-1">
          <Leaderboard
            :players="leaderboard"
            :current-player-id="currentPlayerId"
          />
        </div>
      </div>
    </div>
  </div>
</template>
