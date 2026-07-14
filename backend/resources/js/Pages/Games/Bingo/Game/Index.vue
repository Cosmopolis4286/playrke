<script setup lang="ts">
import { ref, computed, shallowRef, watch, onMounted, onUnmounted, type Ref, nextTick } from 'vue'
import '@/css/games/bingo/bingo-in-game.css'
import { useGameRoomCountdown }  from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { useAntiDebug }          from '@js/composables/games/bingo/useAntiDebug'
import { useBingoSound }         from '@js/composables/games/bingo/useBingoSound'
import { useBingoGameEcho }      from '@js/composables/games/bingo/game/useBingoGameEcho'
import { useDrawScheduler }      from '@js/composables/games/bingo/game/useDrawScheduler'
import { useBingoDetection }     from '@js/composables/games/bingo/game/useBingoDetection'
import { useModalDismiss }       from '@js/composables/games/bingo/game/useModalDismiss'
import BingoCartons    from '@js/Components/Games/Bingo/views/inGame/Cartons.vue'
import GameHeader      from '@js/Components/Games/Bingo/views/inGame/GameHeader.vue'
import DrawnBoard      from '@js/Components/Games/Bingo/views/inGame/DrawnBoard.vue'
import NearWinBanner   from '@js/Components/Games/Bingo/views/inGame/NearWinBanner.vue'
import PlayersModal    from '@js/Components/Games/Bingo/views/inGame/PlayersModal.vue'
import PreDrawModal    from '@js/Components/Games/Bingo/views/inGame/PreDrawModal.vue'
import GameEndModal    from '@js/Components/Games/Bingo/views/inGame/GameEndModal.vue'
import { getBingoVariantDef }    from '@js/modules/games/bingo/utils/bingoVariants'
import type { WinnerEntry, GameEndData, PlayerProgress } from '@js/Components/Games/Bingo/views/inGame/types'
import type { GameEndedEvent }   from '@js/composables/games/bingo/game/useBingoGameEcho'
import Swal from 'sweetalert2'
import { SWAL_DARK } from '@js/utils/swal'
import backendClient from '@/js/plugins/axios'

useAntiDebug()

// eslint-disable-next-line no-undef
const _reload = () => window.location.reload()

const { muted, toggleMute, playNext, playWinner, playTick, preload } = useBingoSound()

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
  hotThreshold?:  number
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
  roomFinished:  false,
  hotThreshold:  10,
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

// ─────────────────────────────────────────────────────────────────────────────
// Estado reactivo
// ─────────────────────────────────────────────────────────────────────────────
const revealed         = ref(false)
const livePrize        = ref(props.prizePool ?? 0)
const liveExchangeRate = ref(props.exchangeRate ?? 1)
const livePlayers      = ref(props.totalPlayers ?? 0)
// shallowRef: cada actualización reasigna .value con un array nuevo (nunca se
// muta in-place) — Cartons.vue observa esta prop con watch() sin deep, que
// compara por referencia; mutar in-place haría que ese watcher dejara de
// dispararse tras la primera ficha (ver onNumberCalled más abajo).
const liveDrawn        = shallowRef<number[]>([...(props.draw ?? [])])
// Espejo mutable de props.cartons — se actualiza en resync cuando el usuario
// compra cartones mientras el WebSocket estuvo caído.
const liveCartons      = shallowRef<{ id: string | number; [key: string]: any }[]>([...(props.cartons ?? [])])
const prizeFlash       = ref(false)
const playersFlash     = ref(false)
const ballFlash        = ref(false)
const playersProgress  = ref<PlayerProgress[]>([])
const showWinnerModal  = ref(false)
// Espejo mutable del estado finished — permite que devResetTime lo limpie sin
// depender de props.room.status_code que es inmutable (prop Inertia).
const roomFinishedLocal = ref(props.roomFinished ?? false)

const modalDismissKey = computed(() => roomId.value ? `bingo-modal-closed-${roomId.value}` : '')
const { dismissed: userClosedModal, init: initModalDismiss, dismiss: dismissModal, reset: resetModal } =
  useModalDismiss(modalDismissKey)
const gameEndData      = ref<GameEndData | null>(null)
const isMyWin          = ref(false)
const bingoConfirmed   = ref(false)
const showPlayersModal = ref(false)
const gameStarted      = ref(false)
const devResetting     = ref(false)
const drawnBoardRef    = ref<{ resetView: () => void } | null>(null)
const reconnectingTooLong = ref(false)
const drawStalling        = ref(false)
let _stallTimer: ReturnType<typeof setTimeout> | null = null

const isFinished = computed(() =>
  gameEndData.value !== null || roomFinishedLocal.value
)

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

// Guard para cancelar updates de estado tras desmontaje (async fetches en vuelo)
let _unmounted = false

// Todos los timers pendientes (flashes de UI + retries de red) — limpiados en onUnmounted
const _pendingTimers    = new Set<ReturnType<typeof setTimeout>>()
const _flashPerTarget   = new Map<Ref<boolean>, ReturnType<typeof setTimeout>>()
function flash(target: Ref<boolean>, ms = 1000) {
  const prev = _flashPerTarget.get(target)
  if (prev !== undefined) { clearTimeout(prev); _pendingTimers.delete(prev) }
  target.value = true
  const t = setTimeout(() => {
    target.value = false
    _pendingTimers.delete(t)
    _flashPerTarget.delete(target)
  }, ms)
  _pendingTimers.add(t)
  _flashPerTarget.set(target, t)
}
function _scheduleRetry(fn: () => void, ms: number) {
  const t = setTimeout(() => { _pendingTimers.delete(t); fn() }, ms)
  _pendingTimers.add(t)
}

// ─────────────────────────────────────────────────────────────────────────────
// Countdown
// ─────────────────────────────────────────────────────────────────────────────
const currentUnixDate = ref(props.startTime ?? 0)

// useGameRoomCountdown espera una lista de salas (uso original: lobby con
// múltiples salas). Aquí solo hay una sala, así que se adapta con un objeto
// "sala" mínimo que satisface esa forma.
const countdownRoomShim = computed(() => ({
  id:     roomId.value || '0',
  config: { unix_date: currentUnixDate.value },
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
// Game-end handling
// ─────────────────────────────────────────────────────────────────────────────
function handleGameEnded(data: GameEndedEvent) {
  // Capturar antes de limpiar: si el jugador ya confirmó su BINGO pero GameEnded
  // llega antes del callback de confirmación, forzamos la apertura del modal.
  const forceOpen = bingoConfirmed.value
  bingoConfirmed.value = false

  roomFinishedLocal.value = true
  if (_stallTimer !== null) { clearTimeout(_stallTimer); _stallTimer = null }
  drawStalling.value = false
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
  isMyWin.value = !!(props.userId && data.winners?.some(w => String(w.user_id) === String(props.userId)))
  if (forceOpen) { resetModal() }
  if (!userClosedModal.value || forceOpen) showWinnerModal.value = true
}

// Refresca los cartones del usuario y actualiza liveCartons si hay nuevos.
// Política: sobreescribe si el backend devuelve más cartones (nunca resta).
async function refreshUserCards(overwrite = false) {
  try {
    const r = await backendClient.get(`/bingo/${routeKey.value}/user-cards`)
    if (_unmounted) return
    const cards = r.data?.data
    if (!Array.isArray(cards)) return
    if (overwrite || cards.length > liveCartons.value.length) {
      liveCartons.value = cards
    }
  } catch { /* silencioso — no crítico */ }
}

// Resincroniza el estado del sorteo tras recuperar la conexión de socket —
// Reverb/Pusher no reenvía eventos emitidos mientras el cliente estuvo
// desconectado, así que se refresca contra el backend (ver useBingoGameEcho.onReconnected).
async function resyncDrawState(attempt = 0) {
  if (!routeKey.value) return
  try {
    const [stateRes, cardsRes] = await Promise.all([
      backendClient.get(`/bingo/${routeKey.value}/draw-state`),
      backendClient.get(`/bingo/${routeKey.value}/user-cards`).catch(() => null),
    ])
    if (_unmounted) return
    const data = stateRes.data

    // El array del servidor es autoritativo, con un guard de floor:
    //  - Server más largo que local → fichas perdidas por WS, aceptar.
    //  - Server igual longitud pero distinto contenido → reset/resume, aceptar.
    //  - Server vacío Y local vacío → nada que hacer.
    //  - Server vacío Y local tiene fichas → cache miss transitorio (TTL expiró
    //    entre chips); NO limpiar el estado local para evitar borrar la partida
    //    en curso. Solo limpiar si el juego aún no empezó (gameStarted=false).
    if (Array.isArray(data.draw)) {
      if (data.draw.length > 0) {
        const localJson = JSON.stringify(liveDrawn.value)
        const serverJson = JSON.stringify(data.draw)
        if (serverJson !== localJson) {
          liveDrawn.value = data.draw
          markNumbersAsSeen(data.draw)
        }
      } else if (!gameStarted.value) {
        // Sorteo no iniciado o reset explícito (dev): limpiar estado local.
        if (liveDrawn.value.length > 0) {
          liveDrawn.value = []
          resetCalledNumbers()
        }
      }
      // data.draw=[] con gameStarted=true → cache miss transitorio; ignorar.
    }
    if (data.totalPlayers !== undefined) livePlayers.value = data.totalPlayers
    if (data.prizePool    !== undefined) livePrize.value   = data.prizePool
    if (Array.isArray(data.playersProgress)) {
      playersProgress.value = data.playersProgress
    }
    // Actualizar startTime si el sorteo aún no empezó y el backend tiene una fecha distinta.
    // El watch(currentUnixDate) dispara initCountdown automáticamente — no llamar aquí.
    if (data.startTime && liveDrawn.value.length === 0 && data.startTime !== currentUnixDate.value) {
      currentUnixDate.value = data.startTime
    }
    if (cardsRes && Array.isArray(cardsRes.data?.data) && cardsRes.data.data.length > liveCartons.value.length) {
      liveCartons.value = cardsRes.data.data
    }

    if (data.roomFinished) {
      roomFinishedLocal.value = true
      if (gameEndData.value === null) {
        const winners: WinnerEntry[] = data.initialWinners ?? []
        gameEndData.value = {
          hasWinner:  winners.length > 0,
          totalDrawn: liveDrawn.value.length,
          winners,
          prize:      data.prizePool ?? 0,
          endTime:    '',
        }
        isMyWin.value = !!(props.userId && winners.some(w => String(w.user_id) === String(props.userId)))
        if (!userClosedModal.value) showWinnerModal.value = true
      }
      return
    }

    // roomFinished=false pero se cantaron todas las fichas: posible carrera entre
    // GameEnded y la actualización de status en BD. Reintentar con backoff hasta 5 veces
    // antes de quedarse en "en curso" y dejar que el stall banner actúe.
    if (liveDrawn.value.length >= totalChips.value && attempt < 5) {
      const delay = attempt < 2 ? 2000 : 4000
      _scheduleRetry(() => resyncDrawState(attempt + 1), delay)
    }
  } catch (e) {
    if (attempt < 1) {
      _scheduleRetry(() => resyncDrawState(attempt + 1), 2000)
    } else {
      console.error('[Bingo] Error al resincronizar estado del sorteo tras reconexión:', e)
    }
  }
}

function closeWinnerModal() {
  showWinnerModal.value = false
  bingoConfirmed.value = false
  dismissModal()
  // isMyWin se conserva — el estado de victoria lo gestiona handleGameEnded/onWinnerDeclared,
  // no el cierre del modal, para que el re-open por GameEnded posterior funcione.
}

// ─────────────────────────────────────────────────────────────────────────────
// Bingo detection
// ─────────────────────────────────────────────────────────────────────────────
const { handleBingoDetected, claimingBingo } = useBingoDetection({
  routeKey,
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
    Swal.fire({
      title: 'Error al verificar BINGO',
      text: 'No se pudo conectar con el servidor. Tu BINGO puede haberse registrado de todas formas — espera el resultado.',
      icon: 'warning',
      timer: 5000,
      showConfirmButton: false,
      ...SWAL_DARK,
    })
  },
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
  onPlayerJoined:    (e) => { livePlayers.value = e.current_players; flash(playersFlash) },
  onNumberCalled:    (e) => {
    // Reasignar (no .push() in-place): Cartons.vue observa esta prop con
    // watch(() => props.drawnNumbers, ...) sin deep — con push()+triggerRef()
    // el array sigue siendo la misma referencia entre renders, así que ese
    // watcher (comparación por referencia) nunca se dispara de nuevo tras el
    // primer número, y la detección de BINGO/hits/racha se congela en vivo.
    liveDrawn.value = [...liveDrawn.value, e.number]
    flash(ballFlash, 600) // alineado con la duración de hero-enter (550ms) en DrawnBoard
  },
  onPlayersProgress: (e) => { if (Array.isArray(e.players)) playersProgress.value = e.players },
  onGameStarted:     ()  => { gameStarted.value = true },
  onWinnerDeclared:  ()  => {
    // useBingoGameEcho ya filtró por userId antes de llamar este callback
    isMyWin.value = true
    // WinnerDeclared puede llegar antes que GameEnded (que trae el premio y los ganadores).
    // Esperar 1.5 s para que GameEnded llegue primero y el modal se abra con datos completos.
    // Si GameEnded ya llegó (gameEndData !== null) o el usuario ya cerró el modal, no hacer nada.
    if (!userClosedModal.value && gameEndData.value === null && !isFinished.value) {
      _scheduleRetry(() => {
        if (!userClosedModal.value && gameEndData.value === null) showWinnerModal.value = true
      }, 1500)
    }
  },
  onGameEnded:       (e) => handleGameEnded(e),
  onReconnected:     ()  => { resyncDrawState() },
})

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
    _stallTimer = setTimeout(() => { drawStalling.value = true }, 90_000)
  }
}, { flush: 'post' })

// ─────────────────────────────────────────────────────────────────────────────
// [DEV] Reset
// ─────────────────────────────────────────────────────────────────────────────
function formatCountdown(): string | null {
  if (!hasTime.value || !cd.value) return null
  const { days, hours, minutes, seconds } = cd.value.time
  const parts: string[] = []
  if (days > 0)    parts.push(`${days}d`)
  if (hours > 0)   parts.push(`${hours}h`)
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
    showCancelButton:    true,
    confirmButtonText:   hasDrawInProgress ? 'Sí, regenerar' : 'Sí, iniciar',
    cancelButtonText:    'Cancelar',
    confirmButtonColor:  '#7b4fff',
    cancelButtonColor:   '#6c757d',
    ...SWAL_DARK,
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
      Swal.fire({ title: '¡Listo!', text: 'Sorteo regenerado. Comienza en ~2 minutos.', icon: 'success', timer: 2000, showConfirmButton: false, ...SWAL_DARK })
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

  _scheduleRetry(() => { revealed.value = true }, 80)
  initCountdown([countdownRoomShim.value as any])
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
    if (!userClosedModal.value) showWinnerModal.value = true
  } else if (props.roomFinished) {
    gameEndData.value = {
      hasWinner:  false,
      totalDrawn: liveDrawn.value.length,
      winners:    [],
      prize:      0,
      endTime:    '',
    }
    if (!userClosedModal.value) showWinnerModal.value = true
  }
})

onUnmounted(() => {
  _unmounted = true
  stopCountdown()
  _stopTickWatch()
  unsubscribe()
  _pendingTimers.forEach(clearTimeout)
  _flashPerTarget.forEach((_, target) => { target.value = false })
  _flashPerTarget.clear()
  if (_reconnectLongTimer !== null) clearTimeout(_reconnectLongTimer)
  if (_stallTimer !== null) clearTimeout(_stallTimer)
  document.title = 'Bingo'
})
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <div class="ig-stars" aria-hidden="true">
      <div class="ig-stars__a" />
      <div class="ig-stars__b" />
    </div>
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
        :topCards="topCards"
        @toggleMute="toggleMute"
        @openPlayers="showPlayersModal = true"
        @devReset="devResetTime"
        @focusCard="focusCard"
      />

      <DrawnBoard
        ref="drawnBoardRef"
        :drawnNumbers="liveDrawn"
        :totalChips="totalChips"
        :chipLetter="chipLetter"
        :newBallFlash="ballFlash"
        :highlightNumbers="activeCardHitNumbers"
      />

      <NearWinBanner
        :players="showPlayersProgress ? lastKnownProgress : []"
        :remainingChips="remainingChips"
      />

      <!-- Banner sorteo lento — si pasan >90s sin nueva ficha durante partida activa -->
      <Transition name="ig-reconnect">
        <div v-if="drawStalling && !isFinished && !isReconnecting" class="ig-stall-banner">
          <i class="fas fa-hourglass-half ig-stall-banner__icon" />
          El sorteo está tardando más de lo normal
          <button class="ig-reconnect-reload ig-stall-reload" @click="resyncDrawState()">
            Resincronizar
          </button>
        </div>
      </Transition>

      <!-- Banner de reconexión WS -->
      <Transition name="ig-reconnect">
        <div v-if="isReconnecting" class="ig-reconnect-banner" :class="{ 'ig-reconnect-banner--timeout': reconnectingTooLong }">
          <span class="ig-reconnect-spinner" />
          <span v-if="reconnectingTooLong">Sin conexión —</span>
          <span v-else>Reconectando…</span>
          <button v-if="reconnectingTooLong" class="ig-reconnect-reload" @click="_reload">
            Recargar
          </button>
        </div>
      </Transition>

      <!-- Overlay claim BINGO en vuelo — sticky para no empujar el layout -->
      <Teleport to="body">
        <Transition name="ig-claim">
          <div v-if="claimingBingo" class="ig-claim-overlay">
            <span class="ig-claim-spinner" />
            Verificando BINGO…
          </div>
          <div v-else-if="bingoConfirmed" class="ig-claim-overlay ig-claim-overlay--confirmed">
            <i class="fas fa-check-circle" />
            ¡BINGO confirmado! Esperando resultado…
          </div>
        </Transition>
      </Teleport>

      <div class="ig-cards-section">
        <BingoCartons
          ref="cartonsRef"
          :cartons="liveCartons"
          :customerLogo="customerLogo ?? null"
          :roomId="roomId"
          :drawnNumbers="liveDrawn"
          :totalDrawn="liveDrawn.length"
          :remainingChips="remainingChips"
          :gameVariant="gameVariant"
          :hotThreshold="hotThreshold"
          :bingoPattern="bingoPattern"
          @bingo-detected="handleBingoDetected"
          @card-selected="id => activeCardId = id"
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

/* Estrellas: elementos reales en vez de ::before/::after — evita 2 capas GPU
   composited permanentes durante toda la partida (fix #14). */
.ig-stars { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
.ig-stars__a, .ig-stars__b {
  position: absolute; inset: 0; will-change: opacity;
}
.ig-stars__a {
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
.ig-stars__b {
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
@media (prefers-reduced-motion: reduce) {
  .ig-stars__a, .ig-stars__b { animation: none; opacity: 0.6; }
}

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

/* Banner reconexión */
.ig-reconnect-banner {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; border-radius: 10px;
  background: rgba(255,160,30,0.1); border: 1px solid rgba(255,160,30,0.3);
  color: #ffb84a; font-size: 11px; font-weight: 700; letter-spacing: 0.3px;
  transition: background 0.3s ease, border-color 0.3s ease;
}
.ig-reconnect-banner--timeout {
  background: rgba(255,60,60,0.1); border-color: rgba(255,60,60,0.35);
  color: #ff8080;
}
.ig-reconnect-reload {
  margin-left: auto; padding: 3px 10px; border-radius: 6px;
  background: rgba(255,60,60,0.15); border: 1px solid rgba(255,60,60,0.4);
  color: #ff8080; font-size: 10px; font-weight: 700; cursor: pointer;
  transition: background 0.15s ease;
}
.ig-reconnect-reload:hover { background: rgba(255,60,60,0.28); }
.ig-reconnect-spinner {
  width: 12px; height: 12px; border-radius: 50%;
  border: 2px solid rgba(255,160,30,0.28); border-top-color: #ffb84a;
  animation: ig-rspin 0.8s linear infinite; flex-shrink: 0;
}
.ig-reconnect-banner--timeout .ig-reconnect-spinner {
  animation: none;
  border-color: rgba(255,60,60,0.3);
  border-top-color: #ff8080;
}

/* Banner sorteo lento */
.ig-stall-banner {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; border-radius: 10px;
  background: rgba(255,160,30,0.07); border: 1px solid rgba(255,160,30,0.22);
  color: #c89040; font-size: 11px; font-weight: 700; letter-spacing: 0.3px;
}
.ig-stall-banner__icon { animation: ig-hourglass 2s ease-in-out infinite; }
@keyframes ig-hourglass { 0%,100% { opacity: 0.6; } 50% { opacity: 1; } }
.ig-stall-reload {
  border-color: rgba(255,160,30,0.4) !important;
  background: rgba(255,160,30,0.1) !important;
  color: #ffb84a !important;
}
.ig-stall-reload:hover { background: rgba(255,160,30,0.2) !important; }

@keyframes ig-rspin { to { transform: rotate(360deg) } }
.ig-reconnect-enter-active,
.ig-reconnect-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.ig-reconnect-enter-from,
.ig-reconnect-leave-to     { opacity: 0; transform: translateY(-4px); }

/* Overlay claim BINGO — fixed centrado, no empuja el layout (Fix #6) */
.ig-claim-overlay {
  position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
  z-index: 1400;
  display: flex; align-items: center; gap: 10px;
  padding: 10px 22px; border-radius: 30px;
  background: rgba(14,8,38,0.92); border: 1px solid rgba(123,79,255,0.5);
  color: #b090ff; font-size: 12px; font-weight: 700; letter-spacing: 0.3px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 24px rgba(123,79,255,0.2);
  backdrop-filter: blur(12px); white-space: nowrap;
}
.ig-claim-overlay--confirmed {
  border-color: rgba(42,245,152,0.5);
  color: #2af598;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 24px rgba(42,245,152,0.18);
}
.ig-claim-overlay--confirmed i { font-size: 14px; }
.ig-claim-spinner {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(123,79,255,0.3); border-top-color: #b090ff;
  animation: ig-rspin 0.7s linear infinite; flex-shrink: 0;
}
.ig-claim-enter-active,
.ig-claim-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.ig-claim-enter-from  { opacity: 0; transform: translateX(-50%) translateY(8px); }
.ig-claim-leave-to    { opacity: 0; transform: translateX(-50%) translateY(8px); }
</style>

<!-- ig-fade es usado por DrawnBoard y NearWinBanner vía <Transition>. Debe ser global. -->
<style>
.ig-fade-enter-active,
.ig-fade-leave-active { transition: opacity 0.32s ease, transform 0.32s ease; }
.ig-fade-enter-from,
.ig-fade-leave-to     { opacity: 0; transform: translateY(-5px); }
</style>
