<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { getEchoInstance } from '@js/plugins/echo'
import { getRoute } from '@js/routes/triviaRoutes'
import type {
  WsQuestionSent, WsAnswerResult, WsGameCancelled, WsGameResumed, WsQuestionClosed,
  Question, AnswerResult, PowerUpsAvailable, PowerUpType, PowerUpResponse, QuestionReveal, MyAnswer,
} from '@js/types/trivia'

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
    isPaused: boolean
  }
  question: Question | null
  /** Respuesta propia ya registrada para la pregunta en curso (tras recargar). */
  myAnswer: MyAnswer | null
  /** Revelación en curso si la pregunta ya cerró al cargar la página. */
  reveal: QuestionReveal | null
  score: number
  /** Reloj del servidor al renderizar (ms epoch), para sincronizar el timer. */
  serverNowMs: number
  player: {
    id: string
    name: string
    avatar?: string
  }
  powerUps: PowerUpsAvailable | null
  isHost: boolean
}

const props = defineProps<Props>()

// ── Estado ────────────────────────────────────────────────────────────────────
// Ciclo de cada pregunta: playing → answered (esperando al resto) → reveal
// (respuesta correcta + distribución) → siguiente pregunta | resultados.
type GameStatus = 'waiting' | 'playing' | 'answered' | 'reveal' | 'finished' | 'cancelled' | 'paused'

const currentQuestion  = ref<Question | null>(null)
const questionIndex    = ref(props.game.currentQuestion)
const totalQuestions   = ref(props.game.totalQuestions)
const selectedAnswer   = ref<number | null>(null)
const hasAnswered      = ref(false)
const gameStatus       = ref<GameStatus>(props.game.isPaused ? 'paused' : 'waiting')
const statusBeforePause: { value: GameStatus } = { value: gameStatus.value }
const currentScore     = ref(props.score ?? 0)
const answerResult     = ref<AnswerResult | null>(null)
const reveal           = ref<QuestionReveal | null>(null)
const isSubmitting     = ref(false)
const cancelledReason  = ref<string | null>(null)
const isTogglingPause  = ref(false)

// ── Reloj sincronizado con el servidor ────────────────────────────────────────
// El vencimiento de cada pregunta lo fija el servidor (deadlineMs). El cliente
// solo estima su desfase de reloj: cada marca de tiempo del servidor recibida
// es una cota inferior del desfase real (se generó antes de llegar), así que
// se conserva la mayor observada. Un reloj local adelantado o atrasado, o un
// recargo de página, ya no regalan ni quitan segundos.
let clockOffsetMs = props.serverNowMs ? props.serverNowMs - Date.now() : 0
const nowMs = ref(Date.now() + clockOffsetMs)

function syncClock(serverTimestampMs?: number | null) {
  if (!serverTimestampMs) return
  clockOffsetMs = Math.max(clockOffsetMs, serverTimestampMs - Date.now())
}
function serverNow(): number {
  return Date.now() + clockOffsetMs
}

const deadlineMs        = ref<number | null>(null)
const totalTime         = ref(0)
const pausedRemainingMs = ref<number | null>(null)
let   lastProgressAt    = Date.now()
let   tickInterval: ReturnType<typeof setInterval> | null = null
let   resyncInFlight    = false

const timeLeft = computed(() => {
  if (pausedRemainingMs.value !== null) return Math.max(0, Math.ceil(pausedRemainingMs.value / 1000))
  if (deadlineMs.value === null || gameStatus.value === 'reveal') return 0
  return Math.max(0, Math.ceil((deadlineMs.value - nowMs.value) / 1000))
})

const revealLeft = computed(() =>
  reveal.value ? Math.max(0, Math.ceil((reveal.value.next_question_at_ms - nowMs.value) / 1000)) : 0
)

function tick() {
  nowMs.value = serverNow()

  if (gameStatus.value === 'playing' && !hasAnswered.value && deadlineMs.value !== null && nowMs.value >= deadlineMs.value) {
    handleTimeUp()
  }

  // Red de seguridad: si un evento en tiempo real se perdió (reconexión del
  // WebSocket, pestaña suspendida), el estado local se queda atrás. Pasado un
  // margen sobre lo esperado, se recargan los props desde el servidor.
  const stalledFor = Date.now() - lastProgressAt
  const expectedNextAt =
    gameStatus.value === 'reveal' && reveal.value ? reveal.value.next_question_at_ms
    : (gameStatus.value === 'playing' || gameStatus.value === 'answered') && deadlineMs.value ? deadlineMs.value
    : null

  if (expectedNextAt !== null && nowMs.value > expectedNextAt + 8000 && stalledFor > 8000) {
    resync()
  } else if (gameStatus.value === 'waiting' && stalledFor > 15000) {
    resync()
  }
}

function resync() {
  if (resyncInFlight) return
  resyncInFlight = true
  lastProgressAt = Date.now()
  router.reload({
    onSuccess: (page) => initFromProps(page.props as unknown as Props),
    onFinish: () => { resyncInFlight = false },
  })
}

function initFromProps(p: Props) {
  syncClock(p.serverNowMs)
  currentScore.value = p.score ?? currentScore.value
  if (p.game.isPaused) {
    gameStatus.value = 'paused'
    return
  }
  if (p.question) {
    loadQuestion(p.question, p.game.currentQuestion, p.game.totalQuestions, p.myAnswer, p.reveal)
  } else {
    gameStatus.value = 'waiting'
  }
}

// ── Pausa (anfitrión) ─────────────────────────────────────────────────────────
async function togglePause() {
  if (isTogglingPause.value) return
  isTogglingPause.value = true

  const action = gameStatus.value === 'paused' ? 'resume' : 'pause'

  try {
    await fetch(getRoute(action === 'pause' ? 'gamePause' : 'gameResume', { gameId: props.game.id }), {
      method:  'POST',
      headers: jsonHeaders(),
    })
  } catch (err) {
    console.warn('[Play] togglePause error:', err)
  } finally {
    isTogglingPause.value = false
  }
}

function jsonHeaders(): Record<string, string> {
  const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
  return {
    'Content-Type':     'application/json',
    'Accept':           'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN':     csrf,
  }
}

// ── Power-ups ─────────────────────────────────────────────────────────────────
const powerUpsLeft        = ref<PowerUpsAvailable>({ ...(props.powerUps ?? {}) })
const hiddenOptionIndexes = ref<number[]>([])
const doublePointsActive  = ref(false)
const isUsingPowerUp      = ref<PowerUpType | null>(null)

function hasPowerUp(type: PowerUpType): boolean {
  return (powerUpsLeft.value[type] ?? 0) > 0
}

async function usePowerUp(type: PowerUpType) {
  if (hasAnswered.value || gameStatus.value !== 'playing' || isUsingPowerUp.value || !hasPowerUp(type) || !currentQuestion.value) return
  isUsingPowerUp.value = type
  const questionId = currentQuestion.value.id

  try {
    const res = await fetch(getRoute('gamePowerUp', { gameId: props.game.id }), {
      method:  'POST',
      headers: jsonHeaders(),
      body: JSON.stringify({ type }),
    })

    const data: PowerUpResponse = await res.json().catch(() => ({ success: false }))

    if (!res.ok || !data.success) {
      console.warn('[Play] power-up rechazado:', data.message)
      return
    }

    powerUpsLeft.value = { ...powerUpsLeft.value, [type]: 0 }

    // La pregunta pudo cambiar mientras viajaba la petición.
    if (currentQuestion.value?.id !== questionId) return

    if (type === 'fifty_fifty' && data.hidden_indexes) {
      hiddenOptionIndexes.value = data.hidden_indexes
    } else if (type === 'double_points') {
      doublePointsActive.value = true
    } else if (type === 'extra_time' && data.bonus_ms && deadlineMs.value !== null) {
      deadlineMs.value += data.bonus_ms
      totalTime.value  += Math.round(data.bonus_ms / 1000)
    } else if (type === 'skip') {
      hasAnswered.value = true
      gameStatus.value  = 'answered'
    }
  } catch (err) {
    console.warn('[Play] usePowerUp error:', err)
  } finally {
    isUsingPowerUp.value = null
  }
}

// ── Tiempo real ───────────────────────────────────────────────────────────────
const echo          = getEchoInstance()
const roomChannel   = `trivia.room.${props.room.id}`
const playerChannel = `trivia.player.${props.player.id}`
let   channel: any  = null
let   playerCh: any = null

function questionFromPayload(raw: any): Question {
  return {
    ...raw,
    id:          raw.question_id ?? raw.id,
    text:        raw.question_text ?? raw.text,
    image:       raw.image_url ?? raw.image ?? null,
    timeLimit:   raw.time_limit_seconds ?? raw.timeLimit ?? 20,
    points:      raw.points ?? 100,
    options:     raw.options ?? [],
    startedAtMs: raw.started_at_ms ?? raw.startedAtMs ?? null,
    deadlineMs:  raw.deadline_ms ?? raw.deadlineMs ?? null,
  } as Question
}

function setupEcho() {
  try {
    channel = echo.channel(roomChannel)

    channel
      .listen('.trivia.question.sent', (data: WsQuestionSent) => {
        syncClock(data.server_timestamp)
        const q = questionFromPayload(data.question)
        if (currentQuestion.value?.id === q.id) return // duplicado
        loadQuestion(q, data.question_index, data.total_questions)
      })
      .listen('.trivia.question.closed', (data: WsQuestionClosed) => {
        syncClock(data.server_timestamp)
        if (data.question_id !== currentQuestion.value?.id) return
        applyReveal(data)
      })
      .listen('.trivia.game.finished', () => {
        gameStatus.value = 'finished'
        window.location.href = getRoute('playResults', { gameId: props.game.id })
      })
      .listen('.trivia.game.cancelled', (data: WsGameCancelled) => {
        gameStatus.value      = 'cancelled'
        cancelledReason.value = data.reason
      })
      .listen('.trivia.game.paused', () => {
        if (['paused', 'cancelled', 'finished'].includes(gameStatus.value)) return
        statusBeforePause.value = gameStatus.value
        pausedRemainingMs.value = deadlineMs.value !== null ? Math.max(0, deadlineMs.value - serverNow()) : null
        gameStatus.value = 'paused'
        lastProgressAt = Date.now()
      })
      .listen('.trivia.game.resumed', (data: WsGameResumed) => {
        if (gameStatus.value !== 'paused') return
        syncClock(data.server_timestamp)
        if (data.deadline_ms) {
          deadlineMs.value = data.deadline_ms
        } else if (pausedRemainingMs.value !== null) {
          deadlineMs.value = serverNow() + pausedRemainingMs.value
        }
        pausedRemainingMs.value = null
        gameStatus.value = statusBeforePause.value === 'paused' ? 'playing' : statusBeforePause.value
        lastProgressAt = Date.now()
      })

    playerCh = echo.private(playerChannel)
    playerCh.listen('.trivia.answer.result', (data: WsAnswerResult) => {
      applyResult(data.question_id, {
        success:              true,
        question_id:          data.question_id,
        is_correct:           data.is_correct,
        is_late:              false,
        points_earned:        data.points_earned,
        correct_option_index: data.correct_option_index,
        current_score:        data.current_score,
      })
    })
  } catch (err) {
    console.warn('[Play] Echo no disponible:', err)
  }
}

function teardownEcho() {
  try { echo.leaveChannel(roomChannel) } catch {}
  try { echo.leave(playerChannel) } catch {}
  channel  = null
  playerCh = null
}

// ── Ciclo de la pregunta ──────────────────────────────────────────────────────
function loadQuestion(q: Question, index: number, total: number, mine?: MyAnswer | null, closed?: QuestionReveal | null) {
  currentQuestion.value     = q
  questionIndex.value       = index
  totalQuestions.value      = total
  selectedAnswer.value      = null
  hasAnswered.value         = false
  answerResult.value        = null
  reveal.value              = null
  pausedRemainingMs.value   = null
  hiddenOptionIndexes.value = []
  doublePointsActive.value  = false
  gameStatus.value          = 'playing'
  totalTime.value           = q.timeLimit
  // Sin vencimiento del servidor (payload antiguo): contar desde ahora.
  deadlineMs.value          = q.deadlineMs ?? serverNow() + q.timeLimit * 1000
  lastProgressAt            = Date.now()

  if (mine && mine.question_id === q.id) {
    selectedAnswer.value = mine.selected_option_index >= 0 ? mine.selected_option_index : null
    hasAnswered.value    = true
    gameStatus.value     = 'answered'
    answerResult.value   = {
      success:              true,
      question_id:          mine.question_id,
      is_correct:           mine.is_correct,
      is_late:              mine.is_late,
      points_earned:        mine.points_earned,
      correct_option_index: mine.correct_option_index,
      current_score:        currentScore.value,
    }
  }

  if (closed && closed.question_id === q.id) {
    applyReveal(closed)
  }

  nowMs.value = serverNow()
}

function applyReveal(r: QuestionReveal) {
  reveal.value      = r
  hasAnswered.value = true
  gameStatus.value  = 'reveal'
  lastProgressAt    = Date.now()
}

/** Solo aplica un resultado si corresponde a la pregunta en pantalla. */
function applyResult(questionId: string | undefined, result: AnswerResult) {
  if (!questionId || questionId !== currentQuestion.value?.id) return
  answerResult.value = result
  currentScore.value = result.current_score
}

function handleTimeUp() {
  hasAnswered.value = true
  gameStatus.value  = 'answered'
  submitAnswer(-1)
}

function selectAnswer(index: number) {
  if (hasAnswered.value || gameStatus.value !== 'playing' || isSubmitting.value) return
  selectedAnswer.value = index
  hasAnswered.value    = true
  gameStatus.value     = 'answered'
  submitAnswer(index)
}

async function submitAnswer(optionIndex: number) {
  if (isSubmitting.value || !currentQuestion.value) return
  isSubmitting.value = true

  // La respuesta se liga a la pregunta en que se envió: si mientras viaja
  // llega la siguiente pregunta (el último en responder la desencadena), el
  // resultado tardío no debe pintarse sobre la pregunta nueva.
  const questionId = currentQuestion.value.id
  const startedAt  = currentQuestion.value.startedAtMs ?? (deadlineMs.value ?? serverNow()) - totalTime.value * 1000

  try {
    const res = await fetch(getRoute('gameAnswer', { gameId: props.game.id }), {
      method:  'POST',
      headers: jsonHeaders(),
      body: JSON.stringify({
        question_id:           questionId,
        selected_option_index: optionIndex,
        // Informativo: el servidor calcula el tiempo real con su propio reloj.
        response_time_ms:      Math.max(0, Math.round(serverNow() - startedAt)),
      }),
    })

    const data: AnswerResult = await res.json().catch(() => ({ success: false } as any))

    if (res.ok && data.success) {
      applyResult(data.question_id ?? questionId, data)
    }
  } catch (err) {
    console.warn('[Play] submitAnswer error:', err)
  } finally {
    isSubmitting.value = false
  }
}

// ── Presentación ──────────────────────────────────────────────────────────────
const optionLetters = ['A', 'B', 'C', 'D']
const optionColors  = [
  { border: '#a855f7', bg: 'rgba(168,85,247,0.2)',  text: '#c084fc' },
  { border: '#06b6d4', bg: 'rgba(6,182,212,0.2)',   text: '#22d3ee' },
  { border: '#3b82f6', bg: 'rgba(59,130,246,0.2)',  text: '#60a5fa' },
  { border: '#8b5cf6', bg: 'rgba(139,92,246,0.2)',  text: '#a78bfa' },
]

/** Índice correcto conocido: por la revelación o por el resultado propio. */
const correctIndex = computed<number | null>(() =>
  reveal.value?.correct_option_index ?? answerResult.value?.correct_option_index ?? null
)

const isWrongPick = computed(() =>
  selectedAnswer.value !== null && correctIndex.value !== null && selectedAnswer.value !== correctIndex.value
)

function optionBorderColor(index: number): string {
  if (correctIndex.value === null) return optionColors[index].border
  if (index === correctIndex.value) return '#22c55e'
  if (index === selectedAnswer.value && isWrongPick.value) return '#ef4444'
  return optionColors[index].border
}

function optionBgColor(index: number): string {
  if (correctIndex.value === null) {
    return selectedAnswer.value === index ? 'rgba(34,211,238,0.3)' : optionColors[index].bg
  }
  if (index === correctIndex.value) return 'rgba(34,197,94,0.25)'
  if (index === selectedAnswer.value && isWrongPick.value) return 'rgba(239,68,68,0.25)'
  return optionColors[index].bg
}

function optionOpacity(index: number): number {
  if (!hasAnswered.value) return 1
  if (index === selectedAnswer.value || index === correctIndex.value) return 1
  return 0.45
}

/** Porcentaje de jugadores que eligió cada opción (solo en la revelación). */
function optionShare(index: number): number {
  if (!reveal.value || reveal.value.answered_count === 0) return 0
  return Math.round(((reveal.value.distribution[index] ?? 0) / reveal.value.answered_count) * 100)
}

const revealHeadline = computed(() => {
  if (!reveal.value) return ''
  if (answerResult.value?.is_correct) return `¡Correcto! +${answerResult.value.points_earned} pts`
  if (selectedAnswer.value === null) return 'Se acabó el tiempo'
  return 'Incorrecto'
})

const isLastQuestion = computed(() =>
  reveal.value?.is_last_question ?? (totalQuestions.value > 0 && questionIndex.value >= totalQuestions.value)
)

// ── Lifecycle ─────────────────────────────────────────────────────────────────
function onVisibilityChange() {
  if (document.visibilityState === 'visible') tick()
}

onMounted(() => {
  setupEcho()
  initFromProps(props)
  tickInterval = setInterval(tick, 250)
  document.addEventListener('visibilitychange', onVisibilityChange)
})

onUnmounted(() => {
  if (tickInterval !== null) clearInterval(tickInterval)
  document.removeEventListener('visibilitychange', onVisibilityChange)
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

      <!-- Control de host: pausar partida -->
      <div v-if="isHost && !['paused', 'cancelled', 'finished'].includes(gameStatus)" class="flex justify-center mb-2">
        <button
          @click="togglePause"
          :disabled="isTogglingPause"
          class="text-xs px-3 py-1 rounded-full border border-amber-500/40 text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 disabled:opacity-50 transition-colors"
        >
          ⏸️ Pausar partida
        </button>
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

    <!-- Timer Circular (cuenta hacia el vencimiento que fija el servidor) -->
    <div v-if="['playing', 'answered', 'paused'].includes(gameStatus) && currentQuestion" class="flex justify-center -mt-2 mb-4">
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

      <!-- Partida pausada -->
      <div v-if="gameStatus === 'paused'" class="text-center py-16 space-y-4">
        <div class="w-16 h-16 mx-auto rounded-full bg-amber-500/20 border border-amber-500/50 flex items-center justify-center">
          <span class="text-3xl">⏸️</span>
        </div>
        <p class="text-amber-300 font-semibold text-lg">Partida en pausa</p>
        <p class="text-gray-400 text-sm">La partida se reanudará en breve. Tu progreso está a salvo.</p>
        <button
          v-if="isHost"
          @click="togglePause"
          :disabled="isTogglingPause"
          class="inline-block mt-4 px-6 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 rounded-lg text-sm font-bold transition-colors"
        >
          ▶️ Reanudar partida
        </button>
      </div>

      <!-- Partida cancelada -->
      <div v-else-if="gameStatus === 'cancelled'" class="text-center py-16 space-y-4">
        <div class="w-16 h-16 mx-auto rounded-full bg-red-500/20 border border-red-500/50 flex items-center justify-center">
          <span class="text-3xl">⚠️</span>
        </div>
        <p class="text-red-300 font-semibold text-lg">La partida ha sido cancelada</p>
        <p v-if="cancelledReason" class="text-gray-400 text-sm">{{ cancelledReason }}</p>
        <a :href="getRoute('lobby')"
           class="inline-block mt-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-bold transition-colors">
          Volver al lobby
        </a>
      </div>

      <!-- Esperando primera pregunta -->
      <div v-else-if="gameStatus === 'waiting'" class="text-center py-16 space-y-4">
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

        <!-- Barra de power-ups -->
        <div v-if="powerUps" class="flex justify-center gap-2 mb-4">
          <button
            v-for="pu in ([
              { type: 'fifty_fifty' as const,   icon: '➗', label: '50/50' },
              { type: 'double_points' as const, icon: '✖️2', label: 'x2' },
              { type: 'skip' as const,          icon: '⏭️', label: 'Saltar' },
              { type: 'extra_time' as const,    icon: '⏱️', label: '+5s' },
            ])"
            :key="pu.type"
            @click="usePowerUp(pu.type)"
            :disabled="hasAnswered || isSubmitting || !hasPowerUp(pu.type) || isUsingPowerUp !== null || (pu.type === 'double_points' && doublePointsActive)"
            class="flex flex-col items-center justify-center w-14 h-14 rounded-xl border-2 transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed"
            :class="hasPowerUp(pu.type) && !(pu.type === 'double_points' && doublePointsActive)
              ? 'border-yellow-500/60 bg-yellow-500/10 hover:bg-yellow-500/20'
              : 'border-gray-600/40 bg-gray-800/30'"
            :title="pu.label"
          >
            <span class="text-lg leading-none">{{ pu.icon }}</span>
            <span class="text-[9px] text-yellow-300 font-bold mt-0.5">{{ pu.label }}</span>
          </button>
        </div>
        <p v-if="doublePointsActive" class="text-center text-yellow-400 text-xs font-bold mb-2">⚡ Doble puntos activado para esta pregunta</p>

        <!-- Opciones -->
        <div class="space-y-3">
          <button
            v-for="(option, index) in currentQuestion.options"
            v-show="!hiddenOptionIndexes.includes(index)"
            :key="index"
            @click="selectAnswer(index)"
            :disabled="hasAnswered || isSubmitting || gameStatus !== 'playing'"
            class="w-full appearance-none bg-transparent border-0 p-0 transition-all duration-200 disabled:cursor-default"
            :style="{ opacity: optionOpacity(index) }"
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
              <!-- Revelación: porcentaje de la sala que eligió esta opción -->
              <span v-if="reveal" class="text-xs font-bold text-gray-300 tabular-nums">{{ optionShare(index) }}%</span>
              <!-- Indicador de resultado -->
              <span v-if="correctIndex !== null && index === correctIndex" class="text-green-400 font-bold text-lg">✓</span>
              <span v-else-if="index === selectedAnswer && isWrongPick" class="text-red-400 font-bold text-lg">✗</span>
            </div>
            <div v-if="reveal" class="mx-6 mt-1 h-1 rounded-full bg-white/5 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :style="{ width: optionShare(index) + '%', backgroundColor: index === correctIndex ? '#22c55e' : optionColors[index].border }"
              />
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

        <!-- Feedback: respuesta enviada / revelación -->
        <Transition name="fade" mode="out-in">
          <div v-if="gameStatus === 'reveal' && reveal" key="reveal" class="mt-4 text-center space-y-1">
            <p class="font-bold text-lg"
               :class="answerResult?.is_correct ? 'text-green-400' : selectedAnswer === null ? 'text-yellow-400' : 'text-red-400'">
              {{ revealHeadline }}
            </p>
            <p class="text-gray-300 text-sm">
              Respuesta correcta: <span class="font-bold text-green-400">{{ optionLetters[reveal.correct_option_index] }}</span>
              · {{ reveal.answered_count }}/{{ reveal.player_count }} respondieron
            </p>
            <p class="text-gray-400 text-xs">
              Puntuación: {{ currentScore }} pts ·
              {{ isLastQuestion ? 'Resultados' : 'Siguiente pregunta' }} en {{ revealLeft }}s
            </p>
          </div>
          <div v-else-if="gameStatus === 'answered'" key="answered" class="mt-4 text-center space-y-1">
            <template v-if="answerResult">
              <p v-if="answerResult.is_late" class="text-yellow-400 font-semibold">⏰ Tiempo agotado</p>
              <p v-else-if="answerResult.is_correct" class="text-green-400 font-bold text-lg">✅ ¡Correcto! +{{ answerResult.points_earned }} pts</p>
              <p v-else-if="selectedAnswer === null" class="text-yellow-400 font-semibold">⏭️ Pregunta saltada</p>
              <p v-else class="text-red-400 font-semibold">❌ Incorrecto</p>
            </template>
            <p v-else-if="selectedAnswer === null" class="text-yellow-400 font-semibold">⏰ Tiempo agotado</p>
            <p v-else class="text-cyan-400 font-semibold animate-pulse">Enviando respuesta…</p>
            <p class="text-gray-400 text-xs">Esperando a los demás jugadores…</p>
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
