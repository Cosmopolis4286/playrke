<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import '@/css/games/bingo/bingo-in-game.css'
import { useGameRoomCountdown }  from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { useAntiDebug }          from '@js/composables/games/bingo/useAntiDebug'
import { useBingoSound }         from '@js/composables/games/bingo/useBingoSound'
import { useBingoGameEcho }      from '@js/composables/games/bingo/game/useBingoGameEcho'
import { useDrawScheduler }      from '@js/composables/games/bingo/game/useDrawScheduler'
import { useBingoDetection }     from '@js/composables/games/bingo/game/useBingoDetection'
import { useModalDismiss }       from '@js/composables/games/bingo/game/useModalDismiss'
import { useTimerRegistry }      from '@js/composables/games/bingo/game/useTimerRegistry'
import { useGameEndFlow }        from '@js/composables/games/bingo/game/useGameEndFlow'
import BingoCartons    from '@js/Components/games/bingo/views/inGame/Cartons.vue'
import GameHeader      from '@js/Components/games/bingo/views/inGame/GameHeader.vue'
import DrawnBoard      from '@js/Components/games/bingo/views/inGame/DrawnBoard.vue'
import NearWinBanner   from '@js/Components/games/bingo/views/inGame/NearWinBanner.vue'
import PlayersModal    from '@js/Components/games/bingo/views/inGame/PlayersModal.vue'
import PreDrawModal    from '@js/Components/games/bingo/views/inGame/PreDrawModal.vue'
import GameEndModal    from '@js/Components/games/bingo/views/inGame/GameEndModal.vue'
import GameStarfield      from '@js/Components/games/bingo/views/inGame/GameStarfield.vue'
import ExternalPlayerStatusNotices from '@js/Components/games/bingo/views/inGame/ExternalPlayerStatusNotices.vue'
import type { ExternalPlayerStatus } from '@js/Components/games/bingo/views/inGame/ExternalPlayerStatusNotices.vue'
import GameStatusBanners  from '@js/Components/games/bingo/views/inGame/GameStatusBanners.vue'
import { getBingoVariantDef }    from '@js/modules/games/bingo/utils/bingoVariants'
import type { WinnerEntry, PlayerProgress } from '@js/Components/games/bingo/views/inGame/types'
import type { BingoCard } from '@/js/types/games/bingo/card/in-game/bingo-card'
import Swal from 'sweetalert2'
import { SWAL_DARK } from '@js/utils/swal'
import { useToast } from 'vue-toastification'
import backendClient from '@/js/plugins/axios'

const toast = useToast()

useAntiDebug()


const _reload = () => window.location.reload()

const { muted, toggleMute, playNext, playWinner, playTick, preload } = useBingoSound()

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
interface Props {
  room?:          { id?: string | number; status?: string; status_code?: string; Config?: { rounds?: number; bingo_patterns?: boolean } }
  userId?:        string | null
  cartons?:       BingoCard[]
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
  hotThreshold?:  number
  /** Presente solo cuando la vista se sirve vía ExternalPlayerGameController
   * (jugador externo sin cuenta, acceso público por link) — ver
   * ExternalPlayerLinkService::resolveGameUrl(). Cambia el prefijo de API
   * usado por los composables (apiBase) y desactiva WS/HTTP que requieren
   * un userId real. */
  externalPlayer?: {
    link: string
    first_name: string | null
    last_name: string | null
    phone: string | null
    country_id: string | null
    /** Pago en revisión / rechazado / premios — ver BingoGameService::getExternalPlayerPurchaseStatus(). */
    status?: ExternalPlayerStatus
  } | null
}

const props = withDefaults(defineProps<Props>(), {
  userId: null,
  cartons: () => [],
  customerLogo: null,
  currencySym: '$',
  prizePool: 0,
  exchangeRate: 1,
  totalPlayers: 0,
  prizePoolType: 'PREMIO',
  round: 1,
  bingoPattern: '',
  draw: () => [],
  startTime: 0,
  isLocal: false,
  roomId: '',
  roomRouteKey: '',
  gameVariant: 'BINGO_75',
  roomFinished: false,
  hotThreshold: 10,
  externalPlayer: null
})

// ─────────────────────────────────────────────────────────────────────────────
// Variante
// ─────────────────────────────────────────────────────────────────────────────
const variantDef = computed(() => getBingoVariantDef(props.gameVariant ?? 'BINGO_75'))
const totalChips = computed(() => variantDef.value.totalChips)

// Map pre-construido número→letra; O(1) por lookup en vez de iterar columnas por cada chip
const chipLetterMap = computed(() => {
  const m = new Map<number, string>()
  for (const col of variantDef.value.columns)
    for (let n = col.min; n <= col.max; n++) m.set(n, col.label)
  return m
})
function chipLetter(n: number): string { return chipLetterMap.value.get(n) ?? '?' }

// ─────────────────────────────────────────────────────────────────────────────
// IDs — deben estar antes del estado reactivo que los referencia
// ─────────────────────────────────────────────────────────────────────────────
const roomId   = computed(() => props.roomId || String(props.room?.id ?? ''))
const routeKey = computed(() => props.roomRouteKey || roomId.value)

// Prefijo de API: rutas autenticadas (/bingo/{routeKey}/...) para un jugador
// con cuenta, o rutas públicas por link (/bingo/external/{link}/...) para un
// ExternalPlayer sin cuenta — mismos sub-paths en ambas (ver
// routes/modules/external-players.php).
const apiBase = computed(() =>
  props.externalPlayer
    ? `/bingo/external/${props.externalPlayer.link}`
    : `/bingo/${routeKey.value}`
)

// ─────────────────────────────────────────────────────────────────────────────
// Estado reactivo local
// ─────────────────────────────────────────────────────────────────────────────
const revealed         = ref(false)
const liveExchangeRate = ref(props.exchangeRate ?? 1)
const prizeFlash       = ref(false)
const playersFlash     = ref(false)
const ballFlash        = ref(false)
const showPlayersModal = ref(false)
const gameStarted      = ref(false)
const devResetting     = ref(false)
const drawnBoardRef    = ref<{ resetView: () => void } | null>(null)
const reconnectingTooLong = ref(false)
const drawStalling        = ref(false)
let _stallTimer: ReturnType<typeof setTimeout> | null = null

const modalDismissKey = computed(() => roomId.value ? `bingo-modal-closed-${roomId.value}` : '')
const { dismissed: userClosedModal, init: initModalDismiss, dismiss: dismissModal, reset: resetModal } =
  useModalDismiss(modalDismissKey)
const bingoConfirmed   = ref(false)

// Guard para cancelar updates de estado tras desmontaje (async fetches en vuelo)
let _unmounted = false
const { flash, scheduleRetry, clearAll: clearAllTimers } = useTimerRegistry()

// ─────────────────────────────────────────────────────────────────────────────
// Game-end flow (liveDrawn, liveCartons, livePrize, livePlayers, gameEndData, etc.)
// ─────────────────────────────────────────────────────────────────────────────
const {
  liveDrawn, liveCartons, livePrize, livePlayers, playersProgress, currentUnixDate,
  roomFinishedLocal, gameEndData, isMyWin, showWinnerModal, isFinished,
  bindEchoHelpers, handleGameEnded, refreshUserCards, resyncDrawState, closeWinnerModal: _closeWinnerModal
} = useGameEndFlow({
  apiBase,
  userId: props.userId,
  initialDraw: props.draw ?? [],
  initialCartons: props.cartons ?? [],
  initialPrizePool: props.prizePool ?? 0,
  initialTotalPlayers: props.totalPlayers ?? 0,
  initialRoomFinished: props.roomFinished ?? false,
  initialStartTime: props.startTime ?? 0,
  totalChips,
  gameStarted,
  userClosedModal,
  resetModal,
  scheduleRetry,
  isUnmounted: () => _unmounted
})

function closeWinnerModal() {
  _closeWinnerModal(dismissModal, bingoConfirmed)
}

const totalWinners   = computed(() => gameEndData.value?.winners?.length ?? 0)
const remainingChips = computed(() => totalChips.value - liveDrawn.value.length)

const prizeFormatted = computed(() => {
  const v = livePrize.value * liveExchangeRate.value
  return v % 1 === 0 ? v.toFixed(0) : v.toFixed(2)
})

const cardCount = computed(() => liveCartons.value.length)

const showPlayersProgress = computed(() =>
  liveDrawn.value.length > 0 && remainingChips.value <= 10 && playersProgress.value.length > 0
)

// Último valor no-vacío de playersProgress — evita que NearWinBanner desaparezca
// cuando el backend devuelve [] en un resync antes del próximo evento PlayersProgress.
const lastKnownProgress = ref<PlayerProgress[]>([])
watch(playersProgress, (v) => { if (v.length > 0) lastKnownProgress.value = v }, { immediate: true })

const cartonsRef = ref<InstanceType<typeof BingoCartons> | null>(null)
// Hasta 3 cartones activos más cerca de completar (serial + fichas faltantes),
// mostrados en el header para que el jugador salte directo a ellos.
// defineExpose auto-unwraps refs — cartonsRef.value.topCards is a plain array, not a ref
const topCards   = computed(() => cartonsRef.value?.topCards ?? [])

// Cartón actualmente seleccionado — actualizado via evento @card-selected (reactivo).
// No se puede leer de cartonsRef.value.selectedId porque los valores expuestos
// de componentes hijos no disparan reactividad cuando cambian.
const activeCardId = ref<string | null>(null)
const activeCard = computed(() => {
  if (!activeCardId.value) return null
  return liveCartons.value.find(c => String(c.id) === activeCardId.value) ?? null
})

// Set memoizado de fichas sorteadas — reutilizado por activeCardHitNumbers
const drawnSet = computed(() => new Set<number>(liveDrawn.value))

// Números del cartón activo que ya fueron sorteados.
// Se pasa a DrawnBoard para resaltarlos en el historial de bolas.
const activeCardHitNumbers = computed<number[]>(() => {
  if (!activeCard.value) return []
  const hits: number[] = []
  for (const col of variantDef.value.columns) {
    const colData = (activeCard.value as any).cardMatrix?.[col.label] as (number | null)[] | undefined
    if (!colData) continue
    for (const n of colData) {
      if (n !== null && drawnSet.value.has(n)) hits.push(n)
    }
  }
  return hits
})

// ─────────────────────────────────────────────────────────────────────────────
// Countdown
// ─────────────────────────────────────────────────────────────────────────────
// useGameRoomCountdown espera una lista de salas (uso original: lobby con
// múltiples salas). Aquí solo hay una sala, así que se adapta con un objeto
// "sala" mínimo que satisface esa forma.
const countdownRoomShim = computed(() => ({
  id: roomId.value || '0',
  config: { unix_date: currentUnixDate.value }
}))

const { init: initCountdown, getCountdown, stop: stopCountdown, syncTime, updateRoomTime } =
  useGameRoomCountdown(Date.now(), () => {
    if (!currentUnixDate.value || liveDrawn.value.length > 0 || isFinished.value || gameStarted.value) return
    gameStarted.value = true
    scheduleDrawStart()
  })

const cd      = computed(() => getCountdown(countdownRoomShim.value.id) ?? null)
const hasTime = computed(() => cd.value !== null && !cd.value.expired)

// Re-inicializa el countdown cuando devResetTime actualiza currentUnixDate.
// initCountdown en onMounted toma la copia del shim en ese instante; este watch
// garantiza que cambios posteriores (ej. dev reset) se propagen al countdown.
watch(currentUnixDate, () => {
  initCountdown([countdownRoomShim.value as any])
})

// ─────────────────────────────────────────────────────────────────────────────
// Draw scheduler
// ─────────────────────────────────────────────────────────────────────────────
const { drawStarting, drawStartingSecs, clearDrawTimer, scheduleDrawStart } =
  useDrawScheduler(routeKey)

// Tick sonoro en los últimos 5 segundos de la cuenta regresiva pre-sorteo
const _stopTickWatch = watch(drawStartingSecs, (s) => { if (s > 0 && s <= 5) playTick() })

// ─────────────────────────────────────────────────────────────────────────────
// Bingo detection
// ─────────────────────────────────────────────────────────────────────────────
const { handleBingoDetected, claimingBingo } = useBingoDetection({
  apiBase,
  isMyWin,
  isFinished,
  onBingoConfirmed: () => {
    bingoConfirmed.value = true
    // Si GameEnded ya llegó (carrera), abrir el modal de inmediato.
    if (gameEndData.value !== null) {
      bingoConfirmed.value = false
      resetModal()
      showWinnerModal.value = true
    }
    // Si no, el banner "BINGO confirmado" es visible hasta que llegue GameEnded.
  },
  onBingoError: () => {
    toast.warning('No se pudo conectar con el servidor. Tu BINGO puede haberse registrado de todas formas — espera el resultado.', { timeout: 5000 })
  }
})

// ─────────────────────────────────────────────────────────────────────────────
// WebSocket
// ─────────────────────────────────────────────────────────────────────────────
const { subscribe, unsubscribe, resetCalledNumbers, markNumbersAsSeen, isReconnecting } = useBingoGameEcho({
  roomId: computed(() => {
    const id = roomId.value
    if (!id) console.warn('[Bingo] roomId vacío — canal WebSocket no suscrito')
    return id
  }),
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
  onPlayerJoined: (e) => { livePlayers.value = e.current_players; flash(playersFlash) },
  onNumberCalled: (e) => {
    // Reasignar (no .push() in-place): Cartons.vue observa esta prop con
    // watch(() => props.drawnNumbers, ...) sin deep — con push()+triggerRef()
    // el array sigue siendo la misma referencia entre renders, así que ese
    // watcher (comparación por referencia) nunca se dispara de nuevo tras el
    // primer número, y la detección de BINGO/hits/racha se congela en vivo.
    // Ficha con posición mayor a la siguiente esperada: se perdió al menos
    // una sin que el socket se desconectara. Resincronizar por HTTP en vez de
    // seguir con un tablero incompleto (que además marca mal los cartones).
    const missedSome = typeof e.index === 'number' && e.index > liveDrawn.value.length + 1
    liveDrawn.value = [...liveDrawn.value, e.number]
    flash(ballFlash, 600) // alineado con la duración de hero-enter (550ms) en DrawnBoard
    if (missedSome) resyncDrawState()
  },
  onPlayersProgress: (e) => { if (Array.isArray(e.players)) playersProgress.value = e.players },
  onGameStarted: ()  => { gameStarted.value = true },
  onWinnerDeclared: ()  => {
    // El evento público ya no identifica al ganador. Reconsultar el estado
    // mediante la ruta autorizada y dejar que el backend determine el resultado.
    scheduleRetry(() => { resyncDrawState() }, 750)
  },
  onGameEnded: (e) => handleGameEnded(e, bingoConfirmed),
  onReconnected: ()  => { resyncDrawState() }
})

bindEchoHelpers({ markNumbersAsSeen, resetCalledNumbers })

// Watch de reconexión prolongada — declarado después de useBingoGameEcho para tener isReconnecting
let _reconnectLongTimer: ReturnType<typeof setTimeout> | null = null
watch(isReconnecting, (val) => {
  if (val) {
    _reconnectLongTimer = setTimeout(() => { reconnectingTooLong.value = true }, 15_000)
  } else {
    if (_reconnectLongTimer !== null) { clearTimeout(_reconnectLongTimer); _reconnectLongTimer = null }
    reconnectingTooLong.value = false
  }
})

// Actualiza el título del tab con la última ficha sorteada
watch(liveDrawn, (drawn) => {
  if (drawn.length > 0) {
    const last = drawn[drawn.length - 1]
    document.title = `${chipLetter(last)}${last} — Bingo`
  } else {
    document.title = 'Bingo'
  }
  // Reset del detector de sorteo lento en cada nueva ficha
  if (_stallTimer !== null) { clearTimeout(_stallTimer); _stallTimer = null }
  drawStalling.value = false
  if (drawn.length > 0 && !isFinished.value) {
    // Sin fichas por 90 s: avisar y resincronizar solo (antes había que tocar
    // el botón) — puede haber terminado o retomado sin que llegara el evento.
    _stallTimer = setTimeout(() => {
      drawStalling.value = true
      resyncDrawState()
    }, 90_000)
  }
}, { flush: 'post' })

// ─────────────────────────────────────────────────────────────────────────────
// [DEV] Reset
// ─────────────────────────────────────────────────────────────────────────────
// Máximo 3 unidades — mismo criterio que HeaderPrizeCountdown.vue: con días
// presentes se omiten los segundos, antes podía mostrar 4 partes (d/h/m/s).
function formatCountdown(): string | null {
  if (!hasTime.value || !cd.value) return null
  const { days, hours, minutes, seconds } = cd.value.time
  if (days > 0) return `${days}d ${hours}h ${minutes}m`
  const parts: string[] = []
  if (hours > 0) parts.push(`${hours}h`)
  parts.push(`${minutes}m`, `${seconds}s`)
  return parts.join(' ')
}

async function devResetTime() {
  if (devResetting.value) return

  const remaining = formatCountdown()
  const hasDrawInProgress = liveDrawn.value.length > 0

  const text = hasDrawInProgress
    ? 'Ya existe un sorteo cargado/finalizado. ¿Deseas regenerarlo? Esto reiniciará el juego.'
    : remaining
      ? `Actualmente faltan ${remaining} para que inicie la sala. ¿Deseas adelantar el sorteo de prueba?`
      : '¿Deseas iniciar el sorteo de prueba ahora?'

  const result = await Swal.fire({
    title: hasDrawInProgress ? '¿Regenerar sorteo?' : '¿Iniciar sorteo de prueba?',
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: hasDrawInProgress ? 'Sí, regenerar' : 'Sí, iniciar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#7b4fff',
    cancelButtonColor: '#6c757d',
    ...SWAL_DARK
  })
  if (!result.isConfirmed) return

  devResetting.value = true
  try {
    const { data } = await backendClient.post(`/bingo/${routeKey.value}/dev-reset-time`)
    if (data.startTime) {
      await refreshUserCards(true)
      clearDrawTimer()
      resetCalledNumbers()
      liveDrawn.value         = []
      playersProgress.value   = []
      gameStarted.value       = false
      gameEndData.value       = null
      isMyWin.value           = false
      showWinnerModal.value   = false
      resetModal()
      roomFinishedLocal.value = false
      currentUnixDate.value  = data.startTime
      initCountdown([{ id: roomId.value, config: { unix_date: data.startTime } } as any])
      // Resetear la vista del historial de bolas (Fix #3)
      nextTick(() => drawnBoardRef.value?.resetView())
      document.title = 'Bingo'
      toast.success('Sorteo regenerado. Comienza en ~2 minutos.', { timeout: 2000 })
    }
  } catch {
    Swal.fire({ title: 'Error', text: 'No se pudo regenerar el sorteo.', icon: 'error', ...SWAL_DARK })
  } finally {
    devResetting.value = false
  }
}

// Scroll suave + resaltado al cartón indicado — usado tanto por el stat del
// "cartón activo" (sin id, usa activeCardId) como por el listado de hasta 3
// cartones cercanos a completar en el header (con id explícito).
function focusCard(id?: string) {
  const targetId = id ?? activeCardId.value
  if (!targetId) return
  const el = cartonsRef.value?.$el?.querySelector?.(`[data-card-id="${targetId}"]`)
  if (!el) return
  ;(el as HTMLElement).scrollIntoView({ behavior: 'smooth', block: 'center' })
  cartonsRef.value?.pulseCard(targetId)
}

// ─────────────────────────────────────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────────────────────────────────────
onMounted(() => {
  initModalDismiss()

  scheduleRetry(() => { revealed.value = true }, 80)
  initCountdown([countdownRoomShim.value as any])
  subscribe()
  preload()

  if (props.initialWinners && props.initialWinners.length > 0) {
    gameEndData.value = {
      hasWinner: true,
      totalDrawn: liveDrawn.value.length,
      winners: props.initialWinners,
      prize: props.prizePool ?? 0,
      endTime: ''
    }
    if (props.userId && props.initialWinners.some(w => String(w.user_id) === String(props.userId))) {
      isMyWin.value = true
    }
    if (!userClosedModal.value) showWinnerModal.value = true
  } else if (props.roomFinished) {
    gameEndData.value = {
      hasWinner: false,
      totalDrawn: liveDrawn.value.length,
      winners: [],
      prize: 0,
      endTime: ''
    }
    if (!userClosedModal.value) showWinnerModal.value = true
  }
})

onUnmounted(() => {
  _unmounted = true
  stopCountdown()
  _stopTickWatch()
  unsubscribe()
  clearAllTimers()
  if (_reconnectLongTimer !== null) clearTimeout(_reconnectLongTimer)
  if (_stallTimer !== null) clearTimeout(_stallTimer)
  document.title = 'Bingo'
})
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <GameStarfield />
    <div class="ig-inner">
      <div v-if="externalPlayer" class="ig-external-player-banner">
        <i class="fas fa-user-check" aria-hidden="true" />
        <span>Hola{{ externalPlayer.first_name ? `, ${externalPlayer.first_name}` : '' }} — estás viendo tus cartones como invitado.</span>
        <a
          :href="`/register?phone=${encodeURIComponent(externalPlayer.phone ?? '')}&country=${encodeURIComponent(externalPlayer.country_id ?? '')}`"
          class="ig-external-player-banner__cta"
        >
          Regístrate y no pierdas tu premio
        </a>
      </div>

      <ExternalPlayerStatusNotices v-if="externalPlayer?.status" :status="externalPlayer.status" />

      <GameHeader
        :prize-pool-type="prizePoolType"
        :currency-sym="currencySym"
        :prize-formatted="prizeFormatted"
        :prize-flash="prizeFlash"
        :has-time="hasTime"
        :cd="cd"
        :is-finished="isFinished"
        :live-players="livePlayers"
        :total-winners="totalWinners"
        :players-flash="playersFlash"
        :card-count="cardCount"
        :rounds="rounds"
        :round="round"
        :bingo-pattern="bingoPattern"
        :muted="muted"
        :is-local="isLocal"
        :dev-resetting="devResetting"
        :top-cards="topCards"
        @toggle-mute="toggleMute"
        @open-players="showPlayersModal = true"
        @dev-reset="devResetTime"
        @focus-card="focusCard"
      />

      <DrawnBoard
        ref="drawnBoardRef"
        :drawn-numbers="liveDrawn"
        :total-chips="totalChips"
        :chip-letter="chipLetter"
        :new-ball-flash="ballFlash"
        :highlight-numbers="activeCardHitNumbers"
      />

      <NearWinBanner
        :players="showPlayersProgress ? lastKnownProgress : []"
        :remaining-chips="remainingChips"
      />

      <GameStatusBanners
        :draw-stalling="drawStalling"
        :is-finished="isFinished"
        :is-reconnecting="isReconnecting"
        :reconnecting-too-long="reconnectingTooLong"
        :claiming-bingo="claimingBingo"
        :bingo-confirmed="bingoConfirmed"
        @resync="resyncDrawState()"
        @reload="_reload"
      />

      <div class="ig-cards-section">
        <BingoCartons
          ref="cartonsRef"
          :cartons="liveCartons"
          :customer-logo="customerLogo ?? null"
          :room-id="roomId"
          :drawn-numbers="liveDrawn"
          :total-drawn="liveDrawn.length"
          :remaining-chips="remainingChips"
          :game-variant="gameVariant"
          :hot-threshold="hotThreshold"
          :bingo-pattern="bingoPattern"
          @bingo-detected="handleBingoDetected"
          @card-selected="id => activeCardId = id"
        />
      </div>
    </div>

    <PlayersModal
      :show="showPlayersModal"
      :live-players="livePlayers"
      :total-winners="totalWinners"
      :game-end-data="gameEndData"
      :user-id="userId"
      :currency-sym="currencySym"
      :customer-logo="customerLogo"
      :drawn-numbers="liveDrawn"
      :room-route-key="roomRouteKey"
      :room-id="roomId"
      @close="showPlayersModal = false"
    />

    <PreDrawModal
      :show="drawStarting"
      :seconds-left="drawStartingSecs"
    />

    <GameEndModal
      :show="showWinnerModal"
      :game-end-data="gameEndData"
      :is-my-win="isMyWin"
      :user-id="userId"
      :currency-sym="currencySym"
      :total-chips="totalChips"
      :drawn-numbers="liveDrawn"
      :chip-letter="chipLetter"
      @close="closeWinnerModal"
    />
  </div>
</template>

<style>
/* .mt-2 y #app son ancestros fuera de este componente (auth-layout.blade.php),
   scoped no los alcanza. Sin height:100% no propagan la altura real de
   #content-scrollable hacia .ig-page (mismo fix que GameRooms.vue/Lobby). */
#app:has(> .ig-page),
#slot-wrapper:has(> #app > .ig-page) {
  flex: 1 1 0%;
  min-height: 0;
}
</style>

<style scoped>
.ig-page {
  /* El footer tiene altura variable en móvil (min-height: 0 !important en
     footer.blade.php bajo 576px). calc(100vh - navbar - footer) asumía un
     footer fijo y se quedaba corto, generando overflow-y en el contenedor
     scrollable padre (mismo fix aplicado en GameRooms.vue y Lobby/Index.vue). */
  min-height: 100%;
  margin: -0.5rem -0.5rem 0;
  padding: 16px 14px 28px;
  position: relative;
  isolation: isolate;
  overflow-x: hidden;
  box-sizing: border-box;
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

.ig-inner {
  position: relative; z-index: 1;
  max-width: 1120px; width: 100%; margin: 0 auto;
  display: flex; flex-direction: column; gap: 12px; min-width: 0;
}

/* Entrada escalonada tipo app nativa: cada bloque directo de .ig-inner aparece
   con un pequeño delay tras el anterior en vez de todos a la vez con el fade
   plano de .ig-page — refuerza la sensación de "pantalla cargando por partes". */
.ig-page--revealed .ig-inner > * {
  animation: ig-stagger-in 0.5s cubic-bezier(0.16,1,0.3,1) both;
}
.ig-page--revealed .ig-inner > *:nth-child(1) { animation-delay: 0.02s; }
.ig-page--revealed .ig-inner > *:nth-child(2) { animation-delay: 0.08s; }
.ig-page--revealed .ig-inner > *:nth-child(3) { animation-delay: 0.14s; }
.ig-page--revealed .ig-inner > *:nth-child(4) { animation-delay: 0.20s; }
.ig-page--revealed .ig-inner > *:nth-child(n+5) { animation-delay: 0.24s; }
@keyframes ig-stagger-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
  .ig-page--revealed .ig-inner > * { animation: none; }
}

.ig-cards-section { width: 100%; }

.ig-external-player-banner {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.6rem 0.9rem;
  border-radius: 10px;
  background: rgba(102, 126, 234, 0.14);
  border: 1px solid rgba(102, 126, 234, 0.3);
  color: #c7d2fe;
  font-size: 0.85rem;
}
.ig-external-player-banner i { opacity: 0.85; }
.ig-external-player-banner__cta {
  margin-left: auto;
  padding: 0.35rem 0.8rem;
  border-radius: 20px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  font-weight: 700;
  font-size: 0.78rem;
  text-decoration: none;
  white-space: nowrap;
}
.ig-external-player-banner__cta:hover { opacity: 0.9; }
</style>

<!-- ig-fade es usado por DrawnBoard y NearWinBanner vía <Transition>. Debe ser global. -->
<style>
.ig-fade-enter-active,
.ig-fade-leave-active { transition: opacity 0.32s ease, transform 0.32s ease; }
.ig-fade-enter-from,
.ig-fade-leave-to     { opacity: 0; transform: translateY(-5px); }
</style>
