<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import '@/css/games/bingo/bingo-in-game.css'
import { useGameRoomCountdown }  from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { useAntiDebug }          from '@js/composables/games/bingo/useAntiDebug'
import { useBingoSound }         from '@js/composables/games/bingo/useBingoSound'
import { useBingoGameEcho }      from '@js/composables/games/bingo/game/useBingoGameEcho'
import { useDrawScheduler }      from '@js/composables/games/bingo/game/useDrawScheduler'
import { useBingoDetection }     from '@js/composables/games/bingo/game/useBingoDetection'
import BingoCartons    from '@js/Components/Games/Bingo/views/inGame/Cartons.vue'
import GameHeader      from '@js/Components/Games/Bingo/views/inGame/GameHeader.vue'
import DrawnBoard      from '@js/Components/Games/Bingo/views/inGame/DrawnBoard.vue'
import NearWinBanner   from '@js/Components/Games/Bingo/views/inGame/NearWinBanner.vue'
import MyProgressBadge from '@js/Components/Games/Bingo/views/inGame/MyProgressBadge.vue'
import PlayersModal    from '@js/Components/Games/Bingo/views/inGame/PlayersModal.vue'
import PreDrawModal    from '@js/Components/Games/Bingo/views/inGame/PreDrawModal.vue'
import GameEndModal    from '@js/Components/Games/Bingo/views/inGame/GameEndModal.vue'
import { getBingoVariantDef }    from '@js/modules/games/bingo/utils/bingoVariants'
import type { WinnerEntry, GameEndData, PlayerProgress } from '@js/Components/Games/Bingo/views/inGame/types'
import type { GameEndedEvent }   from '@js/composables/games/bingo/game/useBingoGameEcho'
import Swal from 'sweetalert2'
import backendClient from '@/js/plugins/axios'

useAntiDebug()

const { muted, toggleMute, playNext, playWinner, preload } = useBingoSound()

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
interface Props {
  room?:          { id?: string | number; status?: string; status_code?: string; Config?: { rounds?: number; bingo_patterns?: boolean } }
  userId?:        string | null
  cartons?:       { id: string | number; [key: string]: any }[]
  customerLogo?:  string | null
  currencySym?:   string
  prizePool?:     number
  exchangeRate?:  number
  totalPlayers?:  number
  prizePoolType?: string
  round?:         number
  rounds?:        number
  bingoPattern?:  string
  draw?:          number[]
  startTime?:     number
  isLocal?:       boolean
  roomId?:        string
  roomRouteKey?:  string
  initialWinners?: WinnerEntry[]
  gameVariant?:   string
  roomFinished?:  boolean
}

const props = withDefaults(defineProps<Props>(), {
  userId:       null,
  cartons:      () => [],
  customerLogo: null,
  currencySym:  '$',
  prizePool:    0,
  exchangeRate: 1,
  totalPlayers: 0,
  prizePoolType:'PREMIO',
  round:        1,
  bingoPattern: '',
  draw:         () => [],
  startTime:    0,
  isLocal:      false,
  roomId:       '',
  roomRouteKey: '',
  gameVariant:  'BINGO_75',
  roomFinished: false,
})

// ─────────────────────────────────────────────────────────────────────────────
// Variante
// ─────────────────────────────────────────────────────────────────────────────
const variantDef = computed(() => getBingoVariantDef(props.gameVariant ?? 'BINGO_75'))
const totalChips = computed(() => variantDef.value.totalChips)

function chipLetter(n: number): string {
  for (const col of variantDef.value.columns) {
    if (n >= col.min && n <= col.max) return col.label
  }
  return '?'
}

// ─────────────────────────────────────────────────────────────────────────────
// IDs — deben estar antes del estado reactivo que los referencia
// ─────────────────────────────────────────────────────────────────────────────
const roomId   = computed(() => props.roomId || String(props.room?.id ?? ''))
const routeKey = computed(() => props.roomRouteKey || roomId.value)

// ─────────────────────────────────────────────────────────────────────────────
// Estado reactivo
// ─────────────────────────────────────────────────────────────────────────────
const revealed         = ref(false)
const livePrize        = ref(props.prizePool ?? 0)
const liveExchangeRate = ref(props.exchangeRate ?? 1)
const livePlayers      = ref(props.totalPlayers ?? 0)
const liveDrawn        = ref<number[]>([...(props.draw ?? [])])
const drawnSet         = ref(new Set<number>(props.draw ?? []))
const prizeFlash       = ref(false)
const playersFlash     = ref(false)
const playersProgress  = ref<PlayerProgress[]>([])
const showWinnerModal  = ref(false)

const modalDismissKey  = computed(() => `bingo-modal-closed-${roomId.value}`)
const userClosedModal  = ref(sessionStorage.getItem(modalDismissKey.value) === '1')
const gameEndData      = ref<GameEndData | null>(null)
const isMyWin          = ref(false)
const showPlayersModal = ref(false)
const gameStarted      = ref(false)
const devResetting     = ref(false)

const isFinished = computed(() =>
  gameEndData.value !== null ||
  String(props.room?.status ?? '').toUpperCase()      === 'FINISHED' ||
  String(props.room?.status_code ?? '').toUpperCase() === 'FINISHED'
)

const totalWinners   = computed(() => gameEndData.value?.winners?.length ?? 0)
const remainingChips = computed(() => totalChips.value - liveDrawn.value.length)

const prizeFormatted = computed(() => {
  const v = livePrize.value * liveExchangeRate.value
  return v % 1 === 0 ? v.toFixed(0) : v.toFixed(2)
})

const cardCount = computed(() => props.cartons?.length ?? 0)

const showPlayersProgress = computed(() =>
  liveDrawn.value.length > 0 && remainingChips.value <= 10 && playersProgress.value.length > 0
)

const cartonsRef = ref<InstanceType<typeof BingoCartons> | null>(null)
const myMissing  = computed<number | null>(() => cartonsRef.value?.myMissing.value ?? null)

function flash(target: { value: boolean }) {
  target.value = true
  setTimeout(() => { target.value = false }, 1000)
}

// ─────────────────────────────────────────────────────────────────────────────
// Countdown
// ─────────────────────────────────────────────────────────────────────────────
const currentUnixDate = ref(props.startTime ?? 0)

const fakeRoom = computed(() => ({
  id:     roomId.value || '0',
  config: { unix_date: currentUnixDate.value },
}))

const { init: initCountdown, getCountdown, stop: stopCountdown, syncTime, updateRoomTime } =
  useGameRoomCountdown(Date.now(), () => {
    if (!currentUnixDate.value || liveDrawn.value.length > 0 || isFinished.value) return
    gameStarted.value = true
    scheduleDrawStart()
  })

const cd      = computed(() => getCountdown(fakeRoom.value.id))
const hasTime = computed(() => cd.value && !cd.value.expired)

// ─────────────────────────────────────────────────────────────────────────────
// Draw scheduler
// ─────────────────────────────────────────────────────────────────────────────
const { drawStarting, drawStartingSecs, clearDrawTimer, scheduleDrawStart } =
  useDrawScheduler(routeKey)

// ─────────────────────────────────────────────────────────────────────────────
// Game-end handling
// ─────────────────────────────────────────────────────────────────────────────
function handleGameEnded(data: GameEndedEvent) {
  gameEndData.value = {
    hasWinner:  data.has_winner,
    totalDrawn: data.total_drawn,
    winners: (data.winners ?? []).map(w => ({
      user_id:     w.user_id,
      user_name:   w.user_name,
      card_id:     String(w.card_id ?? ''),
      card_serial: w.card_serial ?? '',
      prize:       Number(w.prize ?? 0),
      pattern:     w.pattern ?? '',
      round:       w.round ?? 1,
    })),
    prize:   data.prize ?? 0,
    endTime: data.end_time,
  }
  if (props.userId && data.winners?.some(w => String(w.user_id) === String(props.userId))) {
    isMyWin.value = true
  }
  if (!userClosedModal.value) showWinnerModal.value = true
}

function closeWinnerModal() {
  showWinnerModal.value = false
  userClosedModal.value = true
  sessionStorage.setItem(modalDismissKey.value, '1')
  if (!isFinished.value) isMyWin.value = false
}

// ─────────────────────────────────────────────────────────────────────────────
// Bingo detection
// ─────────────────────────────────────────────────────────────────────────────
const { handleBingoDetected } = useBingoDetection({
  routeKey,
  isMyWin,
  isFinished,
  onBingoConfirmed: () => {
    userClosedModal.value = false
    showWinnerModal.value = true
  },
  onBingoError: () => {
    Swal.fire({
      title: 'Error al verificar BINGO',
      text: 'No se pudo conectar con el servidor. Tu BINGO puede haberse registrado de todas formas — espera el resultado.',
      icon: 'warning',
      timer: 5000,
      showConfirmButton: false,
      background: '#1a1f3a',
      color: '#fff',
    })
  },
})

// ─────────────────────────────────────────────────────────────────────────────
// WebSocket
// ─────────────────────────────────────────────────────────────────────────────
const { subscribe, unsubscribe } = useBingoGameEcho({
  roomId,
  userId: props.userId,
  syncTime,
  updateRoomTime,
  currentUnixDate,
  clearDrawTimer,
  playNext,
  playWinner,
  onRoomUpdated: (e) => {
    if (e.prize_pool    !== undefined) { livePrize.value   = e.prize_pool;    flash(prizeFlash) }
    if (e.total_players !== undefined) { livePlayers.value = e.total_players; flash(playersFlash) }
  },
  onPlayerJoined:    (e) => { livePlayers.value = e.current_players; flash(playersFlash) },
  onNumberCalled:    (e) => {
    drawnSet.value.add(e.number)
    liveDrawn.value = [...liveDrawn.value, e.number]
  },
  onPlayersProgress: (e) => { if (Array.isArray(e.players)) playersProgress.value = e.players },
  onGameStarted:     ()  => { gameStarted.value = true },
  onWinnerDeclared:  ()  => { isMyWin.value = true; if (!userClosedModal.value) showWinnerModal.value = true },
  onGameEnded:       (e) => handleGameEnded(e),
})

// ─────────────────────────────────────────────────────────────────────────────
// [DEV] Reset
// ─────────────────────────────────────────────────────────────────────────────
async function devResetTime() {
  if (devResetting.value) return

  if (liveDrawn.value.length > 0) {
    const result = await Swal.fire({
      title: '¿Regenerar sorteo?',
      text: 'Ya existe un sorteo cargado/finalizado. ¿Deseas regenerarlo? Esto reiniciará el juego.',
      icon: 'warning',
      showCancelButton:    true,
      confirmButtonText:   'Sí, regenerar',
      cancelButtonText:    'Cancelar',
      confirmButtonColor:  '#7b4fff',
      cancelButtonColor:   '#6c757d',
      background: '#1a1f3a',
      color:      '#fff',
    })
    if (!result.isConfirmed) return
  }

  devResetting.value = true
  try {
    const { data } = await backendClient.post(`/bingo/${routeKey.value}/dev-reset-time`)
    if (data.startTime) {
      clearDrawTimer()
      liveDrawn.value       = []
      drawnSet.value        = new Set()
      gameStarted.value     = false
      gameEndData.value     = null
      isMyWin.value         = false
      showWinnerModal.value = false
      userClosedModal.value = false
      sessionStorage.removeItem(modalDismissKey.value)
      currentUnixDate.value = data.startTime
      initCountdown([{ id: roomId.value, config: { unix_date: data.startTime } } as any])
      Swal.fire({ title: '¡Sorteo iniciado!', text: 'El sorteo ha sido regenerado exitosamente.', icon: 'success', timer: 2000, showConfirmButton: false, background: '#1a1f3a', color: '#fff' })
    }
  } catch {
    Swal.fire({ title: 'Error', text: 'No se pudo regenerar el sorteo.', icon: 'error', background: '#1a1f3a', color: '#fff' })
  } finally {
    devResetting.value = false
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────────────────────────────────────
onMounted(() => {
  setTimeout(() => { revealed.value = true }, 80)
  initCountdown([fakeRoom.value as any])
  subscribe()
  preload()

  if (props.initialWinners && props.initialWinners.length > 0) {
    gameEndData.value = {
      hasWinner:  true,
      totalDrawn: liveDrawn.value.length,
      winners:    props.initialWinners,
      prize:      props.prizePool ?? 0,
      endTime:    '',
    }
    if (props.userId && props.initialWinners.some(w => String(w.user_id) === String(props.userId))) {
      isMyWin.value = true
    }
    showWinnerModal.value = true
  } else if (props.roomFinished) {
    gameEndData.value = {
      hasWinner:  false,
      totalDrawn: liveDrawn.value.length,
      winners:    [],
      prize:      0,
      endTime:    '',
    }
    showWinnerModal.value = true
  }
})

onUnmounted(() => {
  stopCountdown()
  unsubscribe()
})
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <div class="ig-inner">

      <GameHeader
        :prizePoolType="prizePoolType"
        :currencySym="currencySym"
        :prizeFormatted="prizeFormatted"
        :prizeFlash="prizeFlash"
        :hasTime="hasTime"
        :cd="cd"
        :isFinished="isFinished"
        :livePlayers="livePlayers"
        :totalWinners="totalWinners"
        :playersFlash="playersFlash"
        :cardCount="cardCount"
        :rounds="rounds"
        :round="round"
        :bingoPattern="bingoPattern"
        :muted="muted"
        :isLocal="isLocal"
        :devResetting="devResetting"
        @toggleMute="toggleMute"
        @openPlayers="showPlayersModal = true"
        @devReset="devResetTime"
      />

      <DrawnBoard
        :drawnNumbers="liveDrawn"
        :totalChips="totalChips"
        :chipLetter="chipLetter"
      />

      <MyProgressBadge v-if="!isFinished" :missing="myMissing" />

      <NearWinBanner
        :players="showPlayersProgress ? playersProgress : []"
        :remainingChips="remainingChips"
      />

      <div class="ig-cards-section">
        <BingoCartons
          ref="cartonsRef"
          :cartons="cartons ?? []"
          :customerLogo="customerLogo ?? null"
          :roomId="roomId"
          :drawnNumbers="liveDrawn"
          :totalDrawn="liveDrawn.length"
          :gameVariant="gameVariant"
          @bingo-detected="handleBingoDetected"
        />
      </div>

    </div>

    <PlayersModal
      :show="showPlayersModal"
      :livePlayers="livePlayers"
      :totalWinners="totalWinners"
      :gameEndData="gameEndData"
      :userId="userId"
      :currencySym="currencySym"
      :customerLogo="customerLogo"
      :drawnNumbers="liveDrawn"
      :roomRouteKey="roomRouteKey"
      :roomId="roomId"
      @close="showPlayersModal = false"
    />

    <PreDrawModal
      :show="drawStarting"
      :secondsLeft="drawStartingSecs"
    />

    <GameEndModal
      :show="showWinnerModal"
      :gameEndData="gameEndData"
      :isMyWin="isMyWin"
      :userId="userId"
      :currencySym="currencySym"
      :totalChips="totalChips"
      :drawnNumbers="liveDrawn"
      :chipLetter="chipLetter"
      @close="closeWinnerModal"
    />

  </div>
</template>

<style scoped>
.ig-page {
  min-height: calc(100vh - 56px - 44px);
  margin: -0.5rem -0.5rem 0;
  padding: 16px 14px 28px;
  position: relative;
  isolation: isolate;
  overflow-x: hidden;
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 0.38s ease, transform 0.38s ease;
  background:
    radial-gradient(ellipse 85% 50% at 12%  4%,  rgba(70,  0, 190, 0.28) 0%, transparent 60%),
    radial-gradient(ellipse 65% 45% at 88% 10%,  rgba( 0, 55, 210, 0.22) 0%, transparent 58%),
    radial-gradient(ellipse 55% 40% at 50% 88%,  rgba( 0,140, 105, 0.14) 0%, transparent 60%),
    radial-gradient(ellipse 50% 60% at 94% 65%,  rgba(120,  0, 200, 0.18) 0%, transparent 56%),
    radial-gradient(ellipse 40% 35% at  6% 55%,  rgba( 0, 80, 245, 0.12) 0%, transparent 50%),
    linear-gradient(160deg, #030718 0%, #050b24 45%, #020710 100%);
}
.ig-page--revealed { opacity: 1; transform: translateY(0); }

.ig-page::before {
  content: "";
  position: fixed; inset: 0; z-index: -2; pointer-events: none;
  will-change: opacity;
  background-image:
    radial-gradient(1px 1px at  6%  8%, rgba(255,255,255,0.88) 0%, transparent 100%),
    radial-gradient(1px 1px at 14% 22%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at 24%  4%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 38%, rgba(255,255,255,0.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 16%, rgba(255,255,255,0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  7%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 30%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 88%  5%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 95% 22%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at  3% 45%, rgba(255,255,255,0.62) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 60%, rgba(255,255,255,0.38) 0%, transparent 100%),
    radial-gradient(1px 1px at 20% 75%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(180,200,255,0.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(200,180,255,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,220,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,220,0.82) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 90% 45%, rgba(255,240,180,0.78) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}

.ig-page::after {
  content: "";
  position: fixed; inset: 0; z-index: -1; pointer-events: none;
  will-change: opacity;
  background-image:
    radial-gradient(2px 2px at 10% 15%, rgba(255,255,255,0.94) 0%, transparent 100%),
    radial-gradient(2px 2px at 45%  8%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255,240,180,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180,200,255,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 23% 70%, rgba(255,255,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 55% 62%, rgba(220,200,255,0.82) 0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1.00) 0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220,200,255,0.94) 0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1.00) 0%, rgba(200,220,255,0.28) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0% { opacity: 0.52 } 40% { opacity: 0.94 } 70% { opacity: 0.68 } 100% { opacity: 0.82 }
}
@keyframes twinkle-b {
  0% { opacity: 0.68 } 35% { opacity: 0.48 } 65% { opacity: 1.00 } 100% { opacity: 0.62 }
}

.ig-inner {
  position: relative; z-index: 1;
  max-width: 1120px; width: 100%; margin: 0 auto;
  display: flex; flex-direction: column; gap: 12px; min-width: 0;
}

.ig-cards-section { width: 100%; }
</style>

<!-- ig-fade es usado por DrawnBoard y NearWinBanner vía <Transition>. Debe ser global. -->
<style>
.ig-fade-enter-active,
.ig-fade-leave-active { transition: opacity 0.32s ease, transform 0.32s ease; }
.ig-fade-enter-from,
.ig-fade-leave-to     { opacity: 0; transform: translateY(-5px); }
</style>
