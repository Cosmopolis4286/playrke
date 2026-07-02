<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import '@/css/games/bingo/bingo-in-game.css'
import echo from '@js/plugins/echo'
import { useGameRoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { useAntiDebug }         from '@js/composables/games/bingo/useAntiDebug'
import { useBingoSound }        from '@js/composables/games/bingo/useBingoSound'
import BingoCartons  from '@js/Components/Games/Bingo/views/inGame/Cartons.vue'
import GameHeader    from '@js/Components/Games/Bingo/views/inGame/GameHeader.vue'
import DrawnBoard    from '@js/Components/Games/Bingo/views/inGame/DrawnBoard.vue'
import NearWinBanner from '@js/Components/Games/Bingo/views/inGame/NearWinBanner.vue'
import PlayersModal  from '@js/Components/Games/Bingo/views/inGame/PlayersModal.vue'
import PreDrawModal  from '@js/Components/Games/Bingo/views/inGame/PreDrawModal.vue'
import GameEndModal  from '@js/Components/Games/Bingo/views/inGame/GameEndModal.vue'
import type { WinnerInfo, WinnerEntry, GameEndData, PlayerProgress } from '@js/Components/Games/Bingo/views/inGame/types'
import type { BingoRoom, BingoCard } from '@js/Services/bingoService'
import Swal from 'sweetalert2'

useAntiDebug()

const { muted, toggleMute, playNext, playWinner, preload } = useBingoSound()

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
interface Player { ctns: number }

interface Props {
  title?: string
  room: BingoRoom & { Config: { rounds?: number; bingo_patterns?: boolean } }
  userId?: string | null
  cartons?: BingoCard[]
  customerLogo?: string
  currencySym?: string
  prizePool?: number
  exchangeRate?: number
  totalPlayers?: number
  prizePoolType?: string
  round?: number
  rounds?: number
  bingoPattern?: string
  vanDraw?: number[]
  player?: Player
  encrypt?: boolean
  chipsRest?: number[]
  draw?: number[]
  timeServ?: number
  allChips?: number[]
  infoGame?: any
  creatorType?: string
  creatorId?: string
  currencyId?: string
  cardPrice?: number
  startTime?: number
  closingRoute?: string
  isLocal?: boolean
  roomId?: string
  roomRouteKey?: string
  initialWinners?: WinnerEntry[]
  gameVariant?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Juego de Bingo',
  cartons: () => [],
  customerLogo: null,
  currencySym: '$',
  prizePool: 0,
  exchangeRate: 1,
  totalPlayers: 0,
  prizePoolType: 'PREMIO',
  round: 1,
  bingoPattern: '',
  vanDraw: () => [],
  player: () => ({ ctns: 0 }),
  encrypt: false,
  chipsRest: () => [],
  draw: () => [],
  timeServ: 0,
  allChips: () => [],
  infoGame: null,
  creatorType: '',
  creatorId: '',
  currencyId: '',
  cardPrice: 0,
  startTime: 0,
  closingRoute: '',
  isLocal: false,
  roomId: '',
  roomRouteKey: '',
  gameVariant: 'BINGO_75',
})

// ─────────────────────────────────────────────────────────────────────────────
// Variante de juego
// ─────────────────────────────────────────────────────────────────────────────
const VARIANT_CHIPS: Record<string, number> = {
  BINGO_75: 75, BINGO_90: 90, BINGO_80: 80, BINGO_30: 30,
}
const VARIANT_COLS: Record<string, { min: number; max: number; label: string }[]> = {
  BINGO_75: [
    { min: 1,  max: 15, label: 'B' },
    { min: 16, max: 30, label: 'I' },
    { min: 31, max: 45, label: 'N' },
    { min: 46, max: 60, label: 'G' },
    { min: 61, max: 75, label: 'O' },
  ],
  BINGO_90: [
    { min:  1, max:  9, label: '1' }, { min: 10, max: 19, label: '2' },
    { min: 20, max: 29, label: '3' }, { min: 30, max: 39, label: '4' },
    { min: 40, max: 49, label: '5' }, { min: 50, max: 59, label: '6' },
    { min: 60, max: 69, label: '7' }, { min: 70, max: 79, label: '8' },
    { min: 80, max: 90, label: '9' },
  ],
  BINGO_80: [
    { min:  1, max: 20, label: '1' }, { min: 21, max: 40, label: '2' },
    { min: 41, max: 60, label: '3' }, { min: 61, max: 80, label: '4' },
  ],
  BINGO_30: [
    { min:  1, max: 10, label: '1' }, { min: 11, max: 20, label: '2' }, { min: 21, max: 30, label: '3' },
  ],
}

const totalChips  = computed(() => VARIANT_CHIPS[props.gameVariant ?? 'BINGO_75'] ?? 75)
const variantCols = computed(() => VARIANT_COLS[props.gameVariant ?? 'BINGO_75'] ?? VARIANT_COLS.BINGO_75)

function chipLetter(n: number): string {
  for (const col of variantCols.value) {
    if (n >= col.min && n <= col.max) return col.label
  }
  return '?'
}

// ─────────────────────────────────────────────────────────────────────────────
// Reactive state
// ─────────────────────────────────────────────────────────────────────────────
const livePrize        = ref(props.prizePool ?? 0)
const liveExchangeRate = ref(props.exchangeRate ?? 1)
const livePlayers      = ref(props.totalPlayers ?? 0)
const liveDrawn        = ref<number[]>([...(props.draw ?? [])])
const prizeFlash       = ref(false)
const playersFlash     = ref(false)
const playersProgress  = ref<PlayerProgress[]>([])

const showWinnerModal  = ref(false)
const gameEndData      = ref<GameEndData | null>(null)
const winnerInfo       = ref<WinnerInfo | null>(null)
const isMyWin          = ref(false)
const checkingBingo    = ref(false)
const showPlayersModal = ref(false)

const revealed = ref(false)

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────
const roomId = computed(() => props.roomId || props.room?.id?.toString() || '')

const isFinished = computed(() =>
  gameEndData.value !== null ||
  String(props.room?.status ?? '').toUpperCase() === 'FINISHED' ||
  String((props.room as any)?.status_code ?? '').toUpperCase() === 'FINISHED'
)

const totalWinners = computed(() => gameEndData.value?.winners?.length ?? 0)

const prizeFormatted = computed(() => {
  const v = livePrize.value * liveExchangeRate.value
  return v % 1 === 0 ? v.toFixed(0) : v.toFixed(2)
})

const cardCount        = computed(() => props.cartons?.length ?? 0)
const remainingChips   = computed(() => totalChips.value - liveDrawn.value.length)
const showPlayersProgress = computed(() =>
  liveDrawn.value.length > 0 && remainingChips.value <= 10 && playersProgress.value.length > 0
)

// ─────────────────────────────────────────────────────────────────────────────
// Countdown
// ─────────────────────────────────────────────────────────────────────────────
const gameStarted      = ref(false)
const drawStarting     = ref(false)
const drawStartingSecs = ref(10)
let   drawStartTimer: ReturnType<typeof setInterval> | null = null

const fakeRoom = computed(() => ({
  id: props.room?.id?.toString() ?? '0',
  config: { unix_date: props.startTime ?? 0 },
}))

const { init: initCountdown, getCountdown, stop: stopCountdown, syncTime, updateRoomTime } = useGameRoomCountdown(
  Date.now(),
  () => {
    gameStarted.value = true
    scheduleDrawStart()
  },
)

const cd      = computed(() => getCountdown(fakeRoom.value.id))
const hasTime = computed(() => cd.value && !cd.value.expired)

// ─────────────────────────────────────────────────────────────────────────────
// Draw scheduling
// ─────────────────────────────────────────────────────────────────────────────
function clearDrawTimer() {
  if (drawStartTimer !== null) { clearInterval(drawStartTimer); drawStartTimer = null }
  drawStarting.value     = false
  drawStartingSecs.value = 10
}

async function triggerStartDraw() {
  try {
    const routeKey = props.roomRouteKey || roomId.value
    await fetch(`/bingo/${routeKey}/dev-start-draw`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
  } catch (e) {
    console.error('[BingoDraw] Error al iniciar sorteo:', e)
  }
}

function scheduleDrawStart() {
  clearDrawTimer()
  drawStarting.value     = true
  drawStartingSecs.value = 10
  drawStartTimer = setInterval(() => {
    drawStartingSecs.value -= 1
    if (drawStartingSecs.value <= 0) { clearDrawTimer(); triggerStartDraw() }
  }, 1_000)
}

// ─────────────────────────────────────────────────────────────────────────────
// Bingo detection & winner handling
// ─────────────────────────────────────────────────────────────────────────────
async function handleBingoDetected(_card: BingoCard) {
  if (checkingBingo.value) return
  checkingBingo.value = true
  try {
    const routeKey = props.roomRouteKey || roomId.value
    const res  = await fetch(`/bingo/${routeKey}/check-bingo`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    const data = await res.json()
    if (data.success && data.hasBingo && data.winner) {
      winnerInfo.value     = data.winner
      isMyWin.value        = true
      showWinnerModal.value = true
    }
  } catch (e) {
    console.error('[Bingo] Error verificando bingo:', e)
  } finally {
    checkingBingo.value = false
  }
}

function handleWinnerDeclared(winner: WinnerInfo) {
  winnerInfo.value = winner
  isMyWin.value    = String(winner.id) === String(props.userId)
}

function handleGameEnded(data: { has_winner: boolean; total_drawn: number; winners: any[]; prize: number; end_time: string }) {
  gameEndData.value = {
    hasWinner:  data.has_winner,
    totalDrawn: data.total_drawn,
    winners: (data.winners ?? []).map((w: any) => ({
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
  if (props.userId && data.winners?.some((w: any) => String(w.user_id) === String(props.userId))) {
    isMyWin.value = true
  }
  showWinnerModal.value = true
}

// ─────────────────────────────────────────────────────────────────────────────
// WebSocket
// ─────────────────────────────────────────────────────────────────────────────
type Ref<T> = ReturnType<typeof ref<T>>

const ROOM_EVENTS = [
  '.RoomUpdated', '.PlayerJoined', '.BingoNumberCalled', '.PlayersProgress',
  '.GameStarted', '.WinnerDeclared', '.GameEnded', '.RoomTimeSync',
] as const

let roomChannelName: string | null = null

function flash(target: Ref<boolean>) {
  target.value = true
  setTimeout(() => { target.value = false }, 1000)
}

function subscribeToRoom() {
  if (!roomId.value) return
  roomChannelName = `bingo.room.${roomId.value}`
  const channel   = echo.channel(roomChannelName)

  channel.listen('.RoomUpdated', (e: { prize_pool: number; total_players: number }) => {
    if (e.prize_pool !== undefined)   { livePrize.value   = e.prize_pool;   flash(prizeFlash) }
    if (e.total_players !== undefined){ livePlayers.value = e.total_players; flash(playersFlash) }
  })

  channel.listen('.PlayerJoined', (e: { current_players: number }) => {
    livePlayers.value = e.current_players; flash(playersFlash)
  })

  channel.listen('.BingoNumberCalled', (e: { number: number }) => {
    if (e.number && !liveDrawn.value.includes(e.number)) {
      liveDrawn.value = [...liveDrawn.value, e.number]
      playNext()
    }
  })

  channel.listen('.PlayersProgress', (e: { players: PlayerProgress[] }) => {
    if (Array.isArray(e.players)) playersProgress.value = e.players
  })

  channel.listen('.GameStarted', () => {
    gameStarted.value = true; clearDrawTimer()
  })

  channel.listen('.WinnerDeclared', (e: { winner: WinnerInfo }) => {
    if (e.winner) handleWinnerDeclared(e.winner)
  })

  channel.listen('.GameEnded', (e: any) => {
    handleGameEnded(e); playWinner()
  })

  channel.listen('.RoomTimeSync', (e: { server_timestamp: number; unix_date: number }) => {
    if (e.server_timestamp && e.unix_date) {
      syncTime(e.server_timestamp * 1000)
      if (e.unix_date !== props.startTime) updateRoomTime(roomId.value, e.unix_date)
    }
  })
}

function unsubscribeFromRoom() {
  if (!roomChannelName) return
  const channel = echo.channel(roomChannelName)
  for (const event of ROOM_EVENTS) channel.stopListening(event)
  echo.leaveChannel(roomChannelName)
  roomChannelName = null
}

// ─────────────────────────────────────────────────────────────────────────────
// [DEV] Reset
// ─────────────────────────────────────────────────────────────────────────────
const devResetting = ref(false)

async function devResetTime() {
  if (devResetting.value) return

  if (liveDrawn.value.length > 0) {
    const result = await Swal.fire({
      title: '¿Regenerar sorteo?',
      text: 'Ya existe un sorteo cargado/finalizado. ¿Deseas regenerarlo? Esto reiniciará el juego.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, regenerar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#7b4fff',
      cancelButtonColor: '#6c757d',
      background: '#1a1f3a',
      color: '#fff',
    })
    if (!result.isConfirmed) return
  }

  devResetting.value = true
  try {
    const routeKey = props.roomRouteKey || roomId.value
    const res  = await fetch(`/bingo/${routeKey}/dev-reset-time`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    const data = await res.json()
    if (data.startTime) {
      clearDrawTimer()
      liveDrawn.value    = []
      gameStarted.value  = false
      gameEndData.value  = null
      showWinnerModal.value = false
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
  subscribeToRoom()
  preload()

  if (props.initialWinners && props.initialWinners.length > 0 && gameEndData.value === null) {
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
  }
})

onUnmounted(() => {
  stopCountdown()
  clearDrawTimer()
  unsubscribeFromRoom()
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
        :liveDrawnLength="liveDrawn.length"
        :totalChips="totalChips"
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

      <NearWinBanner
        v-if="showPlayersProgress"
        :players="playersProgress"
        :remainingChips="remainingChips"
      />

      <div class="ig-cards-section">
        <BingoCartons
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
      @close="showWinnerModal = false"
    />

  </div>
</template>


<style scoped>
/* ══════════════════════════════════════
   PAGE
══════════════════════════════════════ */
.ig-page {
  min-height: calc(100vh - 56px - 44px);
  margin: -0.5rem -0.5rem 0;
  padding: 20px 16px;
  position: relative;
  isolation: isolate;
  overflow-x: hidden;
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  background:
    radial-gradient(ellipse 90% 55% at 15%  5%,  rgba(80,  0, 200, 0.30) 0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%,  rgba( 0, 60, 220, 0.24) 0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%,  rgba( 0,150, 110, 0.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%,  rgba(130,  0, 210, 0.20) 0%, transparent 58%),
    radial-gradient(ellipse 45% 38% at  8% 58%,  rgba( 0, 90, 255, 0.14) 0%, transparent 52%),
    radial-gradient(ellipse 35% 30% at 40% 40%,  rgba( 0,180, 255, 0.08) 0%, transparent 50%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}
.ig-page--revealed { opacity: 1; transform: translateY(0) }

.ig-page::before {
  content: "";
  position: fixed; inset: 0;
  z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  6%  8%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(1px 1px at 14% 22%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 24%  4%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 38%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 16%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  7%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 30%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 88%  5%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 95% 22%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at  3% 45%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 60%, rgba(255,255,255,0.40) 0%, transparent 100%),
    radial-gradient(1px 1px at 20% 75%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(180,200,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(200,180,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,220,255,0.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,220,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 90% 45%, rgba(255,240,180,0.80) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}
.ig-page::after {
  content: "";
  position: fixed; inset: 0;
  z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at 10% 15%, rgba(255,255,255,0.95) 0%, transparent 100%),
    radial-gradient(2px 2px at 45%  8%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180,200,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 23% 70%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 55% 62%, rgba(220,200,255,0.85) 0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1.00) 0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220,200,255,0.96) 0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1.00) 0%, rgba(200,220,255,0.3) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0% { opacity: 0.55 } 40% { opacity: 0.95 } 70% { opacity: 0.70 } 100% { opacity: 0.85 }
}
@keyframes twinkle-b {
  0% { opacity: 0.70 } 35% { opacity: 0.50 } 65% { opacity: 1.00 } 100% { opacity: 0.65 }
}

.ig-inner {
  position: relative; z-index: 1;
  max-width: 1100px; width: 100%; margin: 0 auto;
  display: flex; flex-direction: column; gap: 14px; min-width: 0;
}

.ig-cards-section { width: 100% }

.ig-fade-enter-active,
.ig-fade-leave-active { transition: opacity 0.35s ease, transform 0.35s ease }
.ig-fade-enter-from,
.ig-fade-leave-to { opacity: 0; transform: translateY(-6px) }
</style>


<!-- Estilos globales (componentes hijos, Teleport) -->
<style>
@keyframes dev-spin { to { transform: rotate(360deg) } }

/* ── Panel ── */
.ig-panel {
  display: flex; flex-direction: column;
  background: linear-gradient(160deg, rgba(11,18,48,0.97) 0%, rgba(6,10,30,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.16); border-radius: 18px; overflow: hidden;
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 48px rgba(0,0,0,0.55), 0 1px 0 rgba(255,255,255,0.04) inset;
  position: relative; min-width: 0;
}
.ig-panel__topline {
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, rgba(80,140,255,0.6) 20%, rgba(42,245,152,0.8) 50%, rgba(123,79,255,0.6) 80%, transparent 100%);
}
.ig-panel__hline { height: 1px; background: rgba(80,120,255,0.1); }
.ig-panel__row { display: flex; align-items: center; padding: 10px 20px; gap: 0; }
.ig-panel__row--top  { min-height: 64px; }
.ig-panel__row--stats { padding: 0 20px; gap: 0; flex-wrap: wrap; justify-content: space-between; background: rgba(255,255,255,0.018); }

/* ── Prize ── */
.ig-prize { display: flex; flex-direction: column; gap: 0; flex-shrink: 0; transition: transform 0.3s ease; }
.ig-prize--flash { animation: prize-pop 0.65s ease }
@keyframes prize-pop { 0% { transform: scale(1) } 40% { transform: scale(1.07) } 100% { transform: scale(1) } }
.ig-prize__label { display: flex; align-items: center; gap: 4px; font-size: 8.5px; font-weight: 700; color: #4a68a8; text-transform: uppercase; letter-spacing: 1.2px; }
.ig-prize__icon { color: #f5c518; font-size: 8.5px; }
.ig-prize__amount { display: flex; align-items: baseline; gap: 4px; }
.ig-prize__sym { font-size: 16px; font-weight: 700; color: #ffc840; }
.ig-prize__num { font-family: 'Anton', sans-serif; font-size: 42px; line-height: 1; background: linear-gradient(130deg, #ffe566 0%, #ffb800 55%, #ff8800 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; filter: drop-shadow(0 2px 12px rgba(255,180,0,0.4)); letter-spacing: -1px; }

/* ── Divider ── */
.ig-panel__divider { width: 1px; height: 38px; background: linear-gradient(180deg, transparent, rgba(80,120,255,0.2), transparent); margin: 0 16px; flex-shrink: 0; }

/* ── Countdown ── */
.ig-countdown { display: flex; flex-direction: column; gap: 3px; flex-shrink: 0; }
.ig-countdown__label { font-size: 9.5px; font-weight: 700; color: #4a68a8; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 4px; }
.ig-timer { display: flex; align-items: center; gap: 2px; }
.timer__unit { display: flex; flex-direction: column; align-items: center; background: rgba(80,120,255,0.08); border: 1px solid rgba(80,120,255,0.14); border-radius: 6px; padding: 2px 6px; min-width: 30px; }
.timer__num { font-size: 16px; font-weight: 800; color: #e0eaff; line-height: 1.1; font-family: 'Courier New', monospace; }
.timer__lbl { font-size: 7px; color: #5a7acc; text-transform: uppercase; letter-spacing: 0.5px; }
.timer__sep { color: #3a5080; font-size: 18px; font-weight: 300; margin: 0 2px; padding-bottom: 10px; }

/* ── Live badge ── */
.ig-live-badge { display: flex; align-items: center; gap: 8px; padding: 6px 16px; background: rgba(42,245,152,0.08); border: 1px solid rgba(42,245,152,0.25); border-radius: 30px; font-size: 12px; font-weight: 900; color: #2af598; letter-spacing: 2px; flex-shrink: 0; box-shadow: 0 0 20px rgba(42,245,152,0.08); }
.ig-live-badge__dot { width: 8px; height: 8px; background: #2af598; border-radius: 50%; animation: dot-pulse 1.4s ease-in-out infinite; box-shadow: 0 0 6px rgba(42,245,152,0.8); }
@keyframes dot-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(42,245,152,0.7); } 50% { box-shadow: 0 0 0 7px rgba(42,245,152,0); } }
.ig-live-badge--finished { background: rgba(100,120,160,0.10); border-color: rgba(100,120,160,0.25); color: #7a90b8; box-shadow: none; letter-spacing: 1.5px; }

/* ── Actions ── */
.ig-panel__actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.ig-action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.18s ease; border: 1px solid; text-decoration: none; }
.ig-action-btn--rooms { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.25); color: #7c9dff; }
.ig-action-btn--rooms:hover { background: rgba(100,140,255,0.2); color: #b0c8ff; box-shadow: 0 4px 14px rgba(100,140,255,0.2); }
.ig-action-btn--mute { width: 34px; height: 34px; padding: 0; justify-content: center; background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #6a88c0; font-size: 13px; border-radius: 50%; }
.ig-action-btn--mute:hover { background: rgba(255,255,255,0.1); color: #a0c0ff; }
.ig-action-btn--muted { color: #2a3055; border-color: rgba(255,255,255,0.06); }

/* ── Stats ── */
.ig-stat { display: flex; align-items: center; gap: 7px; padding: 5px 14px; flex: 1; min-width: 0; position: relative; height: 38px; }
.ig-stat__icons { position: relative; flex-shrink: 0; width: 22px; height: 22px; }
.ig-stat__icons > i { position: absolute; inset: 0; width: 22px !important; height: 22px !important; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #7c9dff; background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.16); border-radius: 50%; transition: background 0.2s ease, color 0.2s ease; }
.ig-stat__trophy-overlay { font-size: 7px !important; bottom: -6px !important; left: 10px !important; inset: auto !important; width: 14px !important; height: 14px !important; background: rgba(255,200,0,0.18) !important; border-color: rgba(255,200,0,0.35) !important; color: #ffd700 !important; }
.ig-stat > i { width: 22px; height: 22px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #7c9dff; background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.16); border-radius: 50%; transition: background 0.2s ease, color 0.2s ease; }
.ig-stat__body { display: flex; flex-direction: column; gap: 0; min-width: 0; }
.ig-stat__val { font-size: 13px; font-weight: 800; color: #c8d8ff; line-height: 1.1; font-family: 'Courier New', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ig-stat__val small { font-size: 9px; color: #3a5080; font-weight: 500; }
.ig-stat__val--sm { font-size: 11px; font-family: inherit; }
.ig-stat__lbl { font-size: 7.5px; font-weight: 700; color: #3a5080; text-transform: uppercase; letter-spacing: 0.6px; }
.ig-stat--flash > i, .ig-stat--flash .ig-stat__icons > i:first-child { background: rgba(42,245,152,0.16); color: #2af598; }
.ig-stat--flash .ig-stat__val { animation: stat-pop 0.5s ease; }
@keyframes stat-pop { 0% { color: #2af598; transform: scale(1); } 40% { color: #2af598; transform: scale(1.15); } 100% { color: #c8d8ff; transform: scale(1); } }
.ig-stat__winners-badge { font-size: 11px; font-weight: 700; color: #ffd700; font-family: inherit; margin-left: 6px; }
.ig-stat__winners-badge i { font-size: 9px; }
.ig-stat__bar { position: absolute; bottom: 0; left: 44px; right: 14px; height: 2px; background: rgba(80,120,255,0.1); border-radius: 2px; overflow: hidden; }
.ig-stat__bar-fill { height: 100%; background: linear-gradient(90deg, #4a7fff, #2af598); border-radius: 2px; transition: width 0.6s cubic-bezier(0.4,0,0.2,1); }
.ig-stat__sep { width: 1px; align-self: stretch; margin: 6px 0; background: linear-gradient(180deg, transparent, rgba(80,120,255,0.14), transparent); flex-shrink: 0; }
.ig-stat--clickable { cursor: pointer; border-radius: 10px; transition: background 0.2s ease; }
.ig-stat--clickable:hover { background: rgba(100,140,255,0.08); }

/* ── DEV ribbon ── */
.ig-dev-ribbon { display: flex; align-items: center; gap: 10px; padding: 6px 18px 8px; border-top: 1px dashed rgba(255,171,0,0.18); background: rgba(255,171,0,0.03); }
.ig-dev-ribbon__label { font-size: 8px; font-weight: 800; letter-spacing: 1.2px; color: rgba(255,171,0,0.45); text-transform: uppercase; display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
.ig-dev-ribbon__label i { font-size: 7px; }
.ig-dev-ribbon__btn { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 8px; background: rgba(255,171,0,0.10); border: 1px solid rgba(255,171,0,0.28); color: #ffd060; font-size: 11px; font-weight: 700; cursor: pointer; transition: background 0.2s, border-color 0.2s, color 0.2s; }
.ig-dev-ribbon__btn:hover:not(:disabled) { background: rgba(255,171,0,0.20); border-color: rgba(255,171,0,0.50); color: #ffe090; }
.ig-dev-ribbon__btn:disabled { opacity: 0.5; cursor: not-allowed; }
.ig-dev-ribbon__spinner { width: 10px; height: 10px; border-radius: 50%; border: 1.5px solid rgba(255,171,0,0.3); border-top-color: #ffd060; animation: dev-spin 0.7s linear infinite; }

/* ── Drawn board ── */
.ig-drawn-board { background: linear-gradient(160deg, rgba(10,16,44,0.97) 0%, rgba(6,10,28,0.97) 100%); border: 1px solid rgba(80,120,255,0.14); border-radius: 18px; overflow: hidden; box-shadow: 0 6px 36px rgba(0,0,0,0.5); display: flex; flex-direction: column; }
.ig-db-top { display: flex; align-items: center; gap: 0; padding: 14px 18px; }
.ig-db-last { position: relative; flex-shrink: 0; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; margin-right: 16px; }
.ig-db-last__ring { position: absolute; inset: -5px; border-radius: 50%; border: 2px solid transparent; background: linear-gradient(135deg, #7b4fff, #2af598) border-box; -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0); -webkit-mask-composite: destination-out; mask-composite: exclude; animation: ring-spin 4s linear infinite; }
@keyframes ring-spin { to { transform: rotate(360deg); } }
.ig-db-info { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 0; }
.ig-db-info__label { font-size: 9px; font-weight: 700; color: #344870; text-transform: uppercase; letter-spacing: 1.2px; display: flex; align-items: center; gap: 4px; }
.ig-db-info__label i { color: #5a3aaa; font-size: 9px; }
.ig-db-info__num { font-family: 'Anton', sans-serif; font-size: 36px; line-height: 1; background: linear-gradient(130deg, #d8e8ff 0%, #8090ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: -1px; }
.ig-db-info__progress { display: flex; align-items: center; gap: 8px; height: 4px; position: relative; background: rgba(80,120,255,0.1); border-radius: 4px; overflow: visible; }
.ig-db-info__bar { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #6040ff, #2af598); transition: width 0.7s cubic-bezier(0.4,0,0.2,1); box-shadow: 0 0 8px rgba(42,245,152,0.4); }
.ig-db-info__pct { position: absolute; right: -42px; top: 50%; transform: translateY(-50%); font-size: 10px; font-weight: 800; color: #6080c0; white-space: nowrap; }
.ig-db-info__pct em { font-style: normal; color: #2a3a5a; font-weight: 500; }
.ig-db-recent { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; flex-shrink: 0; margin-left: 14px; margin-right: 4px; }
.ig-db-recent__label { font-size: 8px; font-weight: 700; color: #2a3a5a; text-transform: uppercase; letter-spacing: 1px; }
.ig-db-recent__row { display: flex; gap: 5px; align-items: center; flex-wrap: nowrap; }
.ig-db-sep { height: 1px; background: rgba(80,120,255,0.08); margin: 0; }
.ig-db-chips { display: flex; flex-wrap: wrap; gap: 5px; padding: 10px 16px 14px; }

/* ── Near win ── */
.ig-near-win { background: linear-gradient(135deg, rgba(10,14,36,0.96) 0%, rgba(6,10,26,0.96) 100%); border: 1px solid rgba(123,79,255,0.22); border-radius: 14px; padding: 10px 14px; display: flex; flex-direction: column; gap: 8px; }
.ig-near-win__header { display: flex; align-items: center; gap: 7px; }
.ig-near-win__fire { font-size: 12px; color: #ff8c00; animation: fire-flicker 1.2s ease-in-out infinite alternate; }
@keyframes fire-flicker { from { color: #ff6600; text-shadow: 0 0 6px rgba(255,100,0,0.6); } to { color: #ffcc00; text-shadow: 0 0 10px rgba(255,200,0,0.8); } }
.ig-near-win__chips-badge { font-family: 'Anton', sans-serif; font-size: 18px; line-height: 1; color: #fff; min-width: 20px; text-align: center; }
.ig-near-win__title { font-size: 10px; font-weight: 700; color: #4a5a88; text-transform: uppercase; letter-spacing: 0.8px; }
.ig-near-win__row { display: flex; flex-wrap: wrap; gap: 6px; }
.ig-near-win__player { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.03); border: 1px solid rgba(80,100,200,0.15); border-radius: 999px; padding: 4px 10px 4px 8px; transition: border-color 0.2s, box-shadow 0.2s; }
.ig-near-win__player--warn { border-color: rgba(255,171,0,0.3); }
.ig-near-win__player--hot { border-color: rgba(42,245,152,0.45); box-shadow: 0 0 10px rgba(42,245,152,0.18); background: rgba(42,245,152,0.04); }
.ig-near-win__rank { font-size: 9px; font-weight: 800; color: #3a4a70; letter-spacing: 0.3px; flex-shrink: 0; }
.ig-near-win__name { font-size: 12px; font-weight: 700; color: #c0d0f0; letter-spacing: 0.2px; }
.ig-near-win__player--hot .ig-near-win__name { color: #e0fff0; }
.ig-near-win__miss-badge { font-size: 10px; font-weight: 800; background: rgba(80,100,200,0.15); border: 1px solid rgba(80,100,200,0.2); color: #5a7acc; padding: 1px 7px; border-radius: 999px; flex-shrink: 0; min-width: 20px; text-align: center; font-family: 'Courier New', monospace; }
.ig-near-win__miss-badge--hot { background: rgba(42,245,152,0.18); border-color: rgba(42,245,152,0.45); color: #2af598; animation: miss-pulse 0.8s ease-in-out infinite alternate; }
@keyframes miss-pulse { from { box-shadow: 0 0 0 0 rgba(42,245,152,0.3); } to { box-shadow: 0 0 0 4px rgba(42,245,152,0); } }

/* ── Chips ── */
.ig-chip { border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1; user-select: none; position: relative; flex-shrink: 0; }
.ig-chip--lg { width: 52px; height: 52px; box-shadow: 0 4px 20px rgba(0,0,0,0.6), inset 0 2px 0 rgba(255,255,255,0.3), inset 0 -2px 4px rgba(0,0,0,0.2); animation: chip-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both; z-index: 1; }
.ig-chip--md { width: 34px; height: 34px; box-shadow: 0 2px 6px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.25), inset 0 -1px 3px rgba(0,0,0,0.15); animation: chip-pop 0.3s cubic-bezier(0.34,1.56,0.64,1) both; transition: transform 0.15s ease, box-shadow 0.15s ease; cursor: default; }
.ig-chip--md:hover { transform: scale(1.2); box-shadow: 0 4px 14px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.3); }
.ig-chip--sm { width: 24px; height: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.22); opacity: 0.75; transition: opacity 0.18s, transform 0.15s; }
.ig-chip--sm:hover { opacity: 1; transform: scale(1.18); }
.ig-chip--B { background: radial-gradient(circle at 38% 32%, #5080ff, #0d35b0); }
.ig-chip--I { background: radial-gradient(circle at 38% 32%, #ff5050, #900000); }
.ig-chip--N { background: radial-gradient(circle at 38% 32%, #f0d040, #806e00); }
.ig-chip--G { background: radial-gradient(circle at 38% 32%, #30d870, #057030); }
.ig-chip--O { background: radial-gradient(circle at 38% 32%, #c050ff, #5808a8); }
.ig-chip--latest { box-shadow: 0 0 0 2px rgba(255,255,255,0.7), 0 0 18px rgba(255,255,255,0.3), 0 2px 6px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.3) !important; animation: chip-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both !important; }
.ig-chip__letter { font-family: 'Anton', sans-serif; line-height: 1; color: rgba(255,255,255,0.5); }
.ig-chip--lg .ig-chip__letter { font-size: 8.5px; margin-bottom: 1px; }
.ig-chip--md .ig-chip__letter { font-size: 7.5px; }
.ig-chip--sm .ig-chip__letter { display: none; }
.ig-chip__num { font-family: 'Anton', sans-serif; line-height: 1; color: #fff; text-shadow: 0 1px 4px rgba(0,0,0,0.55); }
.ig-chip--lg .ig-chip__num { font-size: 19px; }
.ig-chip--md .ig-chip__num { font-size: 12px; }
.ig-chip--sm .ig-chip__num { font-size: 10.5px; }
@keyframes chip-enter { 0% { transform: scale(0) rotate(-25deg); opacity: 0; } 65% { transform: scale(1.15) rotate(4deg); opacity: 1; } 100% { transform: scale(1) rotate(0deg); } }
@keyframes chip-pop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }

/* ── Transition fade ── */
.ig-fade-enter-active, .ig-fade-leave-active { transition: opacity 0.35s ease, transform 0.35s ease }
.ig-fade-enter-from, .ig-fade-leave-to { opacity: 0; transform: translateY(-6px) }

/* ══════ MODALS (overlay / gm-modal compartido) ══════ */
.gm-overlay { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(4,8,26,0.88); backdrop-filter: blur(10px); padding: 12px; }
.gm-modal { position: relative; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; background: linear-gradient(160deg, #0d1633 0%, #080f26 100%); border: 1.5px solid rgba(123,79,255,0.35); border-radius: 24px; padding: 28px 24px 24px; animation: gm-enter 0.45s cubic-bezier(0.34,1.56,0.64,1) both; scrollbar-width: thin; scrollbar-color: rgba(123,79,255,0.3) transparent; }
.gm-modal--win { border-color: rgba(42,245,152,0.45); box-shadow: 0 0 60px rgba(42,245,152,0.15), 0 0 100px rgba(123,79,255,0.1); }
.gm-modal--no-winner { border-color: rgba(100,140,255,0.25); }
@keyframes gm-enter { from { transform: scale(0.82) translateY(24px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
.gm-fade-enter-active, .gm-fade-leave-active { transition: opacity 0.28s ease; }
.gm-fade-enter-from, .gm-fade-leave-to { opacity: 0; }
.gm-fade-enter-active .gm-modal, .gm-fade-leave-active .gm-modal { transition: transform 0.32s cubic-bezier(0.34,1.56,0.64,1), opacity 0.28s ease; }
.gm-fade-enter-from .gm-modal, .gm-fade-leave-to .gm-modal { transform: scale(0.88); opacity: 0; }
.gm-close { position: absolute; top: 14px; right: 14px; width: 32px; height: 32px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: #7c9dff; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s, color 0.2s; }
.gm-close:hover { background: rgba(255,255,255,0.12); color: #fff; }

/* GameEnd modal */
.gm-header { text-align: center; margin-bottom: 20px; }
.gm-trophy { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; margin-bottom: 14px; }
.gm-trophy__ring { position: absolute; inset: 0; border-radius: 50%; border: 2px solid transparent; background: linear-gradient(135deg,#ffd700,#7b4fff,#2af598) border-box; -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0); -webkit-mask-composite: destination-out; mask-composite: exclude; animation: ring-spin 3s linear infinite; }
.gm-trophy__icon { font-size: 48px; animation: trophy-bounce 1.2s ease-in-out infinite; }
.gm-trophy--end .gm-trophy__icon { animation: none; }
@keyframes trophy-bounce { 0%,100% { transform: scale(1) rotate(-4deg); } 50% { transform: scale(1.1) rotate(4deg); } }
.gm-title { font-family: 'Anton', sans-serif; font-size: 26px; letter-spacing: 1px; margin: 0 0 6px; background: linear-gradient(135deg,#ffe566,#ffab00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.gm-modal--win .gm-title { background: linear-gradient(135deg,#2af598,#00c853); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.gm-modal--no-winner .gm-title { background: linear-gradient(135deg,#7c9dff,#5578bb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.gm-subtitle { font-size: 13px; color: #5578bb; margin: 0 0 12px; }
.gm-progress { position: relative; height: 6px; border-radius: 6px; background: rgba(100,140,255,0.1); overflow: hidden; margin: 0 auto; max-width: 260px; }
.gm-progress__bar { height: 100%; border-radius: 6px; background: linear-gradient(90deg,#7b4fff,#2af598); transition: width 0.8s ease; }
.gm-progress__label { position: absolute; right: 0; top: -18px; font-size: 10px; color: #5578bb; }
.gm-winners { margin: 16px 0; }
.gm-winners__title { font-size: 11px; font-weight: 700; color: #5578bb; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.gm-winners__title i { color: #ffd700; }
.gm-winner-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(100,140,255,0.1); margin-bottom: 8px; transition: border-color 0.2s; }
.gm-winner-card--me { background: rgba(42,245,152,0.06); border-color: rgba(42,245,152,0.3); }
.gm-winner-card__rank { font-family: 'Anton', sans-serif; font-size: 18px; color: #ffd700; min-width: 28px; }
.gm-winner-card--me .gm-winner-card__rank { color: #2af598; }
.gm-winner-card__info { flex: 1; }
.gm-winner-card__name { font-size: 14px; font-weight: 700; color: #e0eaff; display: flex; align-items: center; gap: 6px; }
.gm-winner-card__you { font-size: 9px; font-weight: 800; letter-spacing: 1px; background: linear-gradient(135deg,#2af598,#00c853); color: #000; padding: 1px 6px; border-radius: 8px; }
.gm-winner-card__serial { font-size: 11px; color: #4a6aaa; margin-top: 2px; }
.gm-winner-card__prize { font-family: 'Anton', sans-serif; font-size: 20px; color: #ffd060; display: flex; align-items: baseline; gap: 2px; }
.gm-winner-card__sym { font-size: 12px; color: #b09040; }
.gm-no-winner { text-align: center; padding: 20px; font-size: 13px; color: #5578bb; background: rgba(100,140,255,0.04); border-radius: 12px; border: 1px solid rgba(100,140,255,0.08); display: flex; align-items: center; justify-content: center; gap: 8px; margin: 8px 0 16px; }
.gm-no-winner i { color: #4a6aaa; }
.gm-chips-summary { margin: 16px 0; }
.gm-chips-summary__label { font-size: 10px; font-weight: 700; color: #3d5280; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
.gm-chips-summary__grid { display: flex; flex-wrap: wrap; gap: 4px; max-height: 120px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(123,79,255,0.2) transparent; }
.gm-actions { display: flex; gap: 10px; margin-top: 20px; }
.gm-btn { flex: 1; padding: 13px 20px; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; transition: transform 0.18s, box-shadow 0.18s; }
.gm-btn--primary { background: linear-gradient(135deg,#7b4fff,#009efd); color: #fff; box-shadow: 0 4px 16px rgba(123,79,255,0.3); }
.gm-modal--win .gm-btn--primary { background: linear-gradient(135deg,#2af598,#00c853); color: #003316; box-shadow: 0 4px 16px rgba(42,245,152,0.3); }
.gm-btn--primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(123,79,255,0.45); }
.gm-confetti { position: fixed; top: -12px; width: 10px; height: 14px; border-radius: 2px; z-index: 10000; animation: cf-fall linear both; pointer-events: none; }
@keyframes cf-fall { 0% { transform: translateY(0) rotate(0deg) scaleX(1); opacity: 1; } 80% { opacity: 1; } 100% { transform: translateY(110vh) rotate(720deg) scaleX(-1); opacity: 0; } }

/* Players modal */
.pm-modal { max-width: 460px; display: flex !important; flex-direction: column; max-height: 82vh; padding: 0 !important; overflow: hidden !important; }
.pm-modal__head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 14px; border-bottom: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
.pm-modal__title-row { display: flex; align-items: center; gap: 12px; }
.pm-modal__icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(100,140,255,0.12); border: 1px solid rgba(100,140,255,0.22); display: flex; align-items: center; justify-content: center; color: #7c9dff; font-size: 15px; flex-shrink: 0; }
.pm-modal__title { font-family: 'Anton', sans-serif; font-size: 20px; color: #e0eaff; letter-spacing: 0.5px; line-height: 1; }
.pm-modal__sub { display: flex; align-items: center; gap: 5px; margin-top: 3px; font-size: 11px; color: #4a6aaa; }
.pm-modal__sub-sep { color: #2a3a5a; }
.pm-modal__close { width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.04); color: #5a7aaa; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s, color 0.2s; }
.pm-modal__close:hover { background: rgba(255,80,80,0.15); color: #ff8080; }
.pm-modal__body { flex: 1; overflow-y: auto; padding: 12px 16px; scrollbar-width: thin; scrollbar-color: rgba(100,140,255,0.2) transparent; }
.pm-section-label { font-size: 10px; font-weight: 800; color: #3a4a70; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.pm-section-label i { color: #ffd700; font-size: 9px; }
.pm-row { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 10px 12px; margin-bottom: 6px; transition: border-color 0.2s; }
.pm-row:last-child { margin-bottom: 0; }
.pm-row--me { background: rgba(42,245,152,0.05); border-color: rgba(42,245,152,0.22); }
.pm-row__rank { font-family: 'Anton', sans-serif; font-size: 16px; color: #3a4a70; flex-shrink: 0; min-width: 28px; }
.pm-row--me .pm-row__rank { color: #2af598; }
.pm-row__info { flex: 1; min-width: 0; }
.pm-row__name { font-size: 14px; font-weight: 700; color: #c0d0f0; display: flex; align-items: center; gap: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pm-row__meta { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; margin-top: 3px; }
.pm-you { font-size: 8px; font-weight: 800; letter-spacing: 1px; background: rgba(42,245,152,0.18); border: 1px solid rgba(42,245,152,0.35); color: #2af598; border-radius: 4px; padding: 1px 5px; }
.pm-serial { font-size: 10px; color: #3a4a70; font-family: monospace; }
.pm-badge { font-size: 9px; font-weight: 700; letter-spacing: 0.4px; padding: 1px 6px; border-radius: 6px; }
.pm-badge--round { background: rgba(100,140,255,0.12); border: 1px solid rgba(100,140,255,0.22); color: #7c9dff; }
.pm-badge--pattern { background: rgba(123,79,255,0.12); border: 1px solid rgba(123,79,255,0.22); color: #c4aaff; text-transform: uppercase; }
.pm-row__prize { font-family: 'Anton', sans-serif; font-size: 18px; color: #ffd700; flex-shrink: 0; white-space: nowrap; }
.pm-row__sym { font-size: 11px; color: #a08030; margin-right: 1px; }
.pm-card-btn { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(100,140,255,0.20); background: rgba(100,140,255,0.07); color: #5a7aaa; font-size: 11px; cursor: pointer; transition: background 0.2s, color 0.2s, border-color 0.2s; }
.pm-card-btn:hover { background: rgba(100,140,255,0.18); color: #b0c8ff; border-color: rgba(100,140,255,0.4); }
.pm-empty { display: flex; align-items: center; gap: 8px; justify-content: center; padding: 28px 16px; font-size: 13px; color: #3a4a70; }
.pm-empty i { color: #4a5a80; }
.pm-modal__foot { padding: 12px 16px 16px; border-top: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
.pm-close-btn { width: 100%; padding: 11px; background: linear-gradient(135deg, rgba(100,140,255,0.18), rgba(123,79,255,0.18)); border: 1px solid rgba(100,140,255,0.25); border-radius: 10px; color: #7c9dff; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; transition: background 0.2s, border-color 0.2s, color 0.2s; }
.pm-close-btn:hover { background: linear-gradient(135deg, rgba(100,140,255,0.28), rgba(123,79,255,0.28)); border-color: rgba(100,140,255,0.45); color: #b0c8ff; }

/* Card viewer */
.cv-modal { max-width: 340px; display: flex !important; flex-direction: column; max-height: 92vh; padding: 0 !important; overflow: hidden !important; }
.cv-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px 10px; border-bottom: 1px solid rgba(255,255,255,0.06); flex-shrink: 0; }
.cv-head__icon { width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0; background: rgba(255,215,0,0.10); border: 1px solid rgba(255,215,0,0.20); display: flex; align-items: center; justify-content: center; color: #ffd700; font-size: 13px; }
.cv-head__info { flex: 1; min-width: 0; }
.cv-head__title { font-family: 'Anton', sans-serif; font-size: 15px; color: #e0eaff; line-height: 1; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.cv-head__serial { font-family: monospace; font-size: 9px; font-weight: 400; color: #3a4a70; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 4px; padding: 1px 5px; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; display: inline-block; }
.cv-head__owner { font-size: 10px; color: #4a6aaa; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cv-loading { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 36px; font-size: 13px; color: #5578bb; flex: 1; }
.cv-spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(100,140,255,0.2); border-top-color: #7c9dff; animation: dev-spin 0.7s linear infinite; flex-shrink: 0; }
.cv-card { flex: 1; padding: 10px 12px 0; overflow: hidden; }
.cv-card__header { display: grid; grid-template-columns: repeat(5, 1fr); gap: 2px; margin-bottom: 2px; }
.cv-card__lbl { display: flex; align-items: center; justify-content: center; height: 26px; border-radius: 5px; font-family: 'Anton', sans-serif; font-size: 13px; letter-spacing: 1px; color: #fff; }
.cv-card__lbl--B { background: linear-gradient(135deg, #1d4ed8, #3b82f6); }
.cv-card__lbl--I { background: linear-gradient(135deg, #b91c1c, #ef4444); }
.cv-card__lbl--N { background: linear-gradient(135deg, #15803d, #22c55e); }
.cv-card__lbl--G { background: linear-gradient(135deg, #a16207, #eab308); }
.cv-card__lbl--O { background: linear-gradient(135deg, #7e22ce, #a855f7); }
.cv-card__grid { display: flex; flex-direction: column; gap: 2px; }
.cv-card__row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 2px; }
.cv-cell { position: relative; aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center; border-radius: 5px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); transition: background 0.2s, border-color 0.2s; overflow: hidden; min-width: 0; }
.cv-cell__num { font-size: 13px; font-weight: 800; color: #6070a0; font-variant-numeric: tabular-nums; line-height: 1; }
.cv-cell__mark { position: absolute; bottom: 1px; right: 2px; font-size: 6px; color: rgba(42,245,152,0.75); }
.cv-cell__logo { width: 70%; height: 70%; object-fit: contain; border-radius: 3px; filter: brightness(0.9) saturate(0.7); }
.cv-cell__free-icon { font-size: 13px; color: rgba(123,79,255,0.6); }
.cv-cell--drawn { background: rgba(42,245,152,0.11); border-color: rgba(42,245,152,0.28); }
.cv-cell--drawn .cv-cell__num { color: #2af598; text-shadow: 0 0 8px rgba(42,245,152,0.35); }
.cv-cell--free { background: rgba(123,79,255,0.10); border-color: rgba(123,79,255,0.22); }
.cv-legend { display: flex; align-items: center; gap: 10px; justify-content: center; padding: 8px 0 2px; flex-shrink: 0; }
.cv-legend__item { display: flex; align-items: center; gap: 3px; font-size: 8px; color: #3a4a70; }
.cv-legend__dot { width: 7px; height: 7px; border-radius: 2px; flex-shrink: 0; }
.cv-legend__item--drawn .cv-legend__dot  { background: rgba(42,245,152,0.5);  border: 1px solid rgba(42,245,152,0.4); }
.cv-legend__item--free .cv-legend__dot   { background: rgba(123,79,255,0.4);  border: 1px solid rgba(123,79,255,0.4); }
.cv-legend__item--normal .cv-legend__dot { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); }

/* Pre-draw modal */
.pdm-overlay { position: fixed; inset: 0; z-index: 10001; display: flex; align-items: center; justify-content: center; background: rgba(3,6,20,0.92); backdrop-filter: blur(18px); }
.pdm-box { position: relative; display: flex; flex-direction: column; align-items: center; gap: 18px; padding: 40px 44px 36px; background: linear-gradient(160deg, rgba(14,20,52,0.98) 0%, rgba(8,12,32,0.98) 100%); border: 1.5px solid rgba(123,79,255,0.4); border-radius: 28px; box-shadow: 0 0 0 1px rgba(42,245,152,0.06) inset, 0 30px 80px rgba(0,0,0,0.7), 0 0 60px rgba(123,79,255,0.15); animation: pdm-box-enter 0.5s cubic-bezier(0.34,1.56,0.64,1) both; text-align: center; min-width: 280px; max-width: 380px; }
@keyframes pdm-box-enter { from { transform: scale(0.78) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
.pdm-particles { position: absolute; inset: 0; border-radius: 28px; pointer-events: none; overflow: hidden; }
.pdm-particle { position: absolute; width: 4px; height: 4px; border-radius: 50%; top: 50%; left: 50%; background: rgba(42,245,152,0.7); animation: pdm-orbit calc(2s + var(--i) * 0.2s) linear infinite; }
@keyframes pdm-orbit { from { transform: rotate(0deg) translateX(90px) rotate(0deg); opacity: 0.8; } 50% { opacity: 0.3; } to { transform: rotate(360deg) translateX(90px) rotate(-360deg); opacity: 0.8; } }
.pdm-ball { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at 38% 32%, rgba(123,79,255,0.6), rgba(0,60,180,0.8)); border: 2px solid rgba(123,79,255,0.5); box-shadow: 0 0 40px rgba(123,79,255,0.4), 0 0 80px rgba(42,245,152,0.1); animation: pdm-ball-float 3s ease-in-out infinite; }
@keyframes pdm-ball-float { 0%,100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-8px) scale(1.04); } }
.pdm-ball__letter { font-size: 38px; }
.pdm-title { font-family: 'Anton', sans-serif; font-size: 26px; letter-spacing: 1.5px; background: linear-gradient(135deg, #fff 0%, #c4aaff 60%, #7b4fff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1.1; margin: 0; }
.pdm-subtitle { font-size: 13px; font-weight: 600; color: #6a8acc; letter-spacing: 0.4px; margin-top: -8px; }
.pdm-countdown { position: relative; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; }
.pdm-countdown__ring { position: absolute; inset: 0; transform: rotate(-90deg); width: 80px; height: 80px; }
.pdm-countdown__track { fill: none; stroke: rgba(100,140,255,0.1); stroke-width: 5; }
.pdm-countdown__fill { fill: none; stroke: #2af598; stroke-width: 5; stroke-linecap: round; stroke-dasharray: 175.9; transition: stroke-dashoffset 0.95s linear; filter: drop-shadow(0 0 6px rgba(42,245,152,0.5)); }
.pdm-countdown__num { font-family: 'Anton', sans-serif; font-size: 30px; color: #fff; line-height: 1; position: relative; z-index: 1; text-shadow: 0 0 20px rgba(42,245,152,0.6); animation: pdm-num-tick 1s ease-in-out infinite; }
@keyframes pdm-num-tick { 0%,100% { transform: scale(1); } 10% { transform: scale(1.2); } 30% { transform: scale(0.95); } }
.pdm-hint { font-size: 11px; color: #3a4a70; letter-spacing: 0.3px; }
.pdm-enter-active { transition: opacity 0.3s ease; }
.pdm-leave-active { transition: opacity 0.5s ease; }
.pdm-enter-from, .pdm-leave-to { opacity: 0; }
.pdm-leave-active .pdm-box { animation: pdm-box-leave 0.5s cubic-bezier(0.4,0,0.2,1) both; }
@keyframes pdm-box-leave { from { transform: scale(1); opacity: 1; } to { transform: scale(1.1) translateY(-10px); opacity: 0; filter: blur(4px); } }

/* ── Responsive ── */
@media (max-width: 768px) {
  .ig-panel__row--top { padding: 10px 14px; }
  .ig-stat { padding: 5px 10px; height: 36px; }
  .ig-stat__sep { display: none; }
  .ig-prize__num { font-size: 36px; }
}
@media (max-width: 540px) {
  .ig-panel__row--top { padding: 10px 12px; min-height: auto; flex-wrap: nowrap; gap: 0; }
  .ig-panel__divider { display: none; }
  .ig-prize { flex-direction: row; align-items: center; gap: 6px; }
  .ig-prize__label { display: none; }
  .ig-prize__sym { font-size: 12px; }
  .ig-prize__num { font-size: 24px; }
  .ig-countdown { gap: 1px; }
  .ig-countdown__label { font-size: 8px; }
  .timer__unit { min-width: 24px; padding: 2px 4px; }
  .timer__num { font-size: 13px; }
  .timer__lbl { font-size: 6px; }
  .timer__sep { font-size: 14px; margin: 0 1px; padding-bottom: 8px; }
  .ig-live-badge { padding: 4px 10px; font-size: 10px; letter-spacing: 1px; }
  .ig-panel__row--stats { padding: 0 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
  .ig-stat__sep { display: none; }
  .ig-stat { padding: 5px 8px; height: auto; flex: none; border-bottom: 1px solid rgba(80,120,255,0.07); }
  .ig-stat:nth-child(odd) { border-right: 1px solid rgba(80,120,255,0.07); }
  .ig-stat > i, .ig-stat__icons { width: 20px; height: 20px; }
  .ig-stat__val { font-size: 12px; }
  .ig-stat__lbl { font-size: 7px; }
  .ig-stat__bar { left: 34px; right: 8px; }
  .ig-prize__num { font-size: 28px; }
  .ig-db-top { padding: 10px 12px; gap: 0; flex-wrap: wrap; }
  .ig-db-last { width: 44px; height: 44px; margin-right: 10px; }
  .ig-chip--lg { width: 44px; height: 44px; }
  .ig-chip--lg .ig-chip__num { font-size: 15px; }
  .ig-db-info__num { font-size: 28px; }
  .ig-db-recent { margin-left: 0; margin-right: 0; margin-top: 8px; width: 100%; }
  .ig-db-recent__row { flex-wrap: wrap; gap: 4px; }
  .ig-chip--sm { width: 22px; height: 22px; }
  .ig-chip--sm .ig-chip__num { font-size: 9px; }
  .ig-chip--md { width: 28px; height: 28px; }
  .ig-chip--md .ig-chip__num { font-size: 10px; }
  .ig-chip--md .ig-chip__letter { font-size: 6px; }
  .ig-db-chips { padding: 8px 12px 10px; gap: 4px; }
  .gm-modal { padding: 22px 16px 18px; }
  .gm-title { font-size: 22px; }
  .gm-trophy__icon { font-size: 40px; }
  .gm-winner-card__prize { font-size: 17px; }
}
@media (max-width: 360px) {
  .ig-prize__num { font-size: 20px; }
  .ig-panel__row--top { padding: 8px 10px; }
  .timer__unit { min-width: 20px; padding: 1px 3px; }
  .timer__num { font-size: 11px; }
  .ig-stat { padding: 5px 6px; }
  .ig-stat__val { font-size: 12px; }
}
</style>
