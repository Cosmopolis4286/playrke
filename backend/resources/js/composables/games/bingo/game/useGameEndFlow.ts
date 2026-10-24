import { ref, shallowRef, computed, type ComputedRef, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { WinnerEntry, GameEndData, PlayerProgress } from '@js/Components/games/bingo/views/inGame/types'
import type { GameEndedEvent } from '@js/composables/games/bingo/game/useBingoGameEcho'
import type { BingoCard } from '@/js/types/games/bingo/card/in-game/bingo-card'

interface GameEndFlowDeps {
  userId: string | null
  initialDraw: number[]
  initialCartons: BingoCard[]
  initialPrizePool: number
  initialTotalPlayers: number
  initialRoomFinished: boolean
  initialStartTime: number
  totalChips: ComputedRef<number>
  gameStarted: Ref<boolean>
  userClosedModal: Ref<boolean> | ComputedRef<boolean>
  resetModal: () => void
  scheduleRetry: (fn: () => void, ms: number) => void
  isUnmounted: () => boolean
  /** Prefijo de API — `/bingo/${routeKey}` para un jugador con cuenta, o
   * `/bingo/external/${link}` para un ExternalPlayer sin cuenta (ver
   * Game/Index.vue). Ambos exponen los mismos sub-paths (user-cards,
   * draw-state). */
  apiBase: ComputedRef<string>
}

/**
 * Máquina de estados de "sorteo en vivo + fin de partida": liveDrawn, liveCartons,
 * livePlayers, livePrize, playersProgress, currentUnixDate, roomFinishedLocal,
 * gameEndData, isMyWin — junto con resyncDrawState/handleGameEnded/closeWinnerModal.
 *
 * Se mantiene como una sola unidad (no dividida en composables más finos) porque
 * estas piezas forman una máquina de estados de carrera (WS vs HTTP, GameEnded vs
 * BingoConfirmed) fuertemente interconectada — separarla arriesga romper el orden
 * de aplicación de eventos concurrentes.
 *
 * markNumbersAsSeen/resetCalledNumbers se inyectan después de construir esta
 * composable (vienen de useBingoGameEcho, que a su vez necesita resyncDrawState
 * como callback onReconnected) — de ahí los setters en vez de parámetros directos.
 */
export function useGameEndFlow(deps: GameEndFlowDeps) {
  const {
    userId, initialDraw, initialCartons, initialPrizePool, initialTotalPlayers,
    initialRoomFinished, initialStartTime, totalChips, gameStarted, userClosedModal,
    resetModal, scheduleRetry, isUnmounted, apiBase,
  } = deps

  // shallowRef: cada actualización reasigna .value con un array nuevo (nunca se
  // muta in-place) — Cartons.vue observa esta prop con watch() sin deep, que
  // compara por referencia; mutar in-place haría que ese watcher dejara de
  // dispararse tras la primera ficha (ver onNumberCalled en Game/Index.vue).
  const liveDrawn = shallowRef<number[]>([...initialDraw])
  // Espejo mutable de props.cartons — se actualiza en resync cuando el usuario
  // compra cartones mientras el WebSocket estuvo caído.
  const liveCartons = shallowRef<BingoCard[]>([...initialCartons])
  const livePrize = ref(initialPrizePool)
  const livePlayers = ref(initialTotalPlayers)
  const playersProgress = ref<PlayerProgress[]>([])
  const currentUnixDate = ref(initialStartTime)
  // Espejo mutable del estado finished — permite que devResetTime lo limpie sin
  // depender de props.room.status_code que es inmutable (prop Inertia).
  const roomFinishedLocal = ref(initialRoomFinished)

  const gameEndData = ref<GameEndData | null>(null)
  const isMyWin = ref(false)
  const showWinnerModal = ref(false)

  const isFinished = computed(() => gameEndData.value !== null || roomFinishedLocal.value)

  let _markNumbersAsSeen: (nums: number[]) => void = () => {}
  let _resetCalledNumbers: () => void = () => {}
  function bindEchoHelpers(helpers: { markNumbersAsSeen: (nums: number[]) => void; resetCalledNumbers: () => void }) {
    _markNumbersAsSeen = helpers.markNumbersAsSeen
    _resetCalledNumbers = helpers.resetCalledNumbers
  }

  function handleGameEnded(data: GameEndedEvent, bingoConfirmed: Ref<boolean>) {
    // The public WebSocket event intentionally contains no winner identities,
    // card identifiers, or individual prizes. Fetch the full result through the
    // room-scoped endpoint (or the external-player bearer-link endpoint).
    if (data.results_available) {
      if (bingoConfirmed.value) {
        bingoConfirmed.value = false
        resetModal()
      }
      void resyncDrawState(0, true)
      return
    }

    const forceOpen = bingoConfirmed.value
    bingoConfirmed.value = false
    roomFinishedLocal.value = true
    gameEndData.value = {
      hasWinner: data.has_winner,
      totalDrawn: data.total_drawn,
      winners: [],
      prize: 0,
      endTime: data.end_time,
    }
    if (forceOpen) { resetModal() }
    if (!userClosedModal.value || forceOpen) showWinnerModal.value = true
  }

  // Refresca los cartones del usuario y actualiza liveCartons si hay nuevos.
  // Política: sobreescribe si el backend devuelve más cartones (nunca resta).
  async function refreshUserCards(overwrite = false) {
    try {
      const r = await backendClient.get(`${apiBase.value}/user-cards`)
      if (isUnmounted()) return
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
  async function resyncDrawState(attempt = 0, waitForFinish = false) {
    if (!apiBase.value) return
    try {
      const [stateRes, cardsRes] = await Promise.all([
        backendClient.get(`${apiBase.value}/draw-state`),
        backendClient.get(`${apiBase.value}/user-cards`).catch(() => null),
      ])
      if (isUnmounted()) return
      const data = stateRes.data

      // El array del servidor es autoritativo, con un guard de floor:
      //  - Server más largo que local → fichas perdidas por WS, aceptar.
      //  - Server igual longitud pero distinto contenido → reset/resume, aceptar.
      //  - Server vacío Y local vacío → nada que hacer.
      //  - Server vacío Y local tiene fichas → cache miss transitorio (TTL expiró
      //    entre chips); NO limpiar el estado local para evitar borrar la partida
      //    en curso. Solo limpiar si el juego aún no empezó (gameStarted=false).
      //
      // IMPORTANTE: liveDrawn.value se lee DESPUÉS del await — no antes — para
      // capturar cualquier chip que un WS BingoNumberCalled haya añadido durante
      // el viaje de red. Leer antes causaría que el response HTTP (snapshot más
      // antiguo) sobrescriba chips ya recibidos por WS, borrándolos del tablero.
      if (Array.isArray(data.draw)) {
        if (data.draw.length > 0) {
          // Solo sobreescribir si el servidor tiene MÁS fichas que el local actual
          // (no si solo tiene distintas — eso sería revertir chips WS recientes).
          if (data.draw.length > liveDrawn.value.length) {
            liveDrawn.value = data.draw
            _markNumbersAsSeen(data.draw)
          } else if (data.draw.length === liveDrawn.value.length) {
            // Misma longitud: solo aplicar si el contenido difiere (reset/resume).
            const localJson = JSON.stringify(liveDrawn.value)
            const serverJson = JSON.stringify(data.draw)
            if (serverJson !== localJson) {
              liveDrawn.value = data.draw
              _markNumbersAsSeen(data.draw)
            }
          }
          // data.draw.length < liveDrawn.value.length → WS ya avanzó más que el
          // snapshot del servidor; ignorar para no revertir fichas recientes.
        } else if (!gameStarted.value) {
          // Sorteo no iniciado o reset explícito (dev): limpiar estado local.
          if (liveDrawn.value.length > 0) {
            liveDrawn.value = []
            _resetCalledNumbers()
          }
        }
        // data.draw=[] con gameStarted=true → cache miss transitorio; ignorar.
      }
      if (data.totalPlayers !== undefined) livePlayers.value = data.totalPlayers
      if (data.prizePool !== undefined) livePrize.value = data.prizePool
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

      if (!data.roomFinished && waitForFinish && attempt < 5) {
        const delay = attempt < 2 ? 1000 : 2000
        scheduleRetry(() => resyncDrawState(attempt + 1, true), delay)
        return
      }

      if (data.roomFinished) {
        roomFinishedLocal.value = true
        if (gameEndData.value === null) {
          const winners: WinnerEntry[] = data.initialWinners ?? []
          gameEndData.value = {
            hasWinner: winners.length > 0,
            totalDrawn: liveDrawn.value.length,
            winners,
            prize: data.prizePool ?? 0,
            endTime: '',
          }
          isMyWin.value = !!(userId && winners.some(w => String(w.user_id) === String(userId)))
          if (!userClosedModal.value) showWinnerModal.value = true
        }
        return
      }

      // roomFinished=false pero se cantaron todas las fichas: posible carrera entre
      // GameEnded y la actualización de status en BD. Reintentar con backoff hasta 5 veces
      // antes de quedarse en "en curso" y dejar que el stall banner actúe.
      if (liveDrawn.value.length >= totalChips.value && attempt < 5) {
        const delay = attempt < 2 ? 2000 : 4000
        scheduleRetry(() => resyncDrawState(attempt + 1), delay)
      }
    } catch (e) {
      if (attempt < 5 && waitForFinish) {
        scheduleRetry(() => resyncDrawState(attempt + 1, true), attempt < 2 ? 1000 : 2000)
      } else if (attempt < 1) {
        scheduleRetry(() => resyncDrawState(attempt + 1, waitForFinish), 2000)
      } else {
        console.error('[Bingo] Error al resincronizar estado del sorteo tras reconexión:', e)
      }
    }
  }

  function closeWinnerModal(dismissModal: () => void, bingoConfirmed: Ref<boolean>) {
    showWinnerModal.value = false
    bingoConfirmed.value = false
    dismissModal()
    // isMyWin se conserva — el estado de victoria lo gestiona handleGameEnded/onWinnerDeclared,
    // no el cierre del modal, para que el re-open por GameEnded posterior funcione.
  }

  return {
    liveDrawn, liveCartons, livePrize, livePlayers, playersProgress, currentUnixDate,
    roomFinishedLocal, gameEndData, isMyWin, showWinnerModal, isFinished,
    bindEchoHelpers,
    handleGameEnded, refreshUserCards, resyncDrawState, closeWinnerModal,
  }
}
