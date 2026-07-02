import { onUnmounted, ref } from 'vue'
import echo from '@/js/plugins/echo'

export type WsStatus = 'connecting' | 'connected' | 'disconnected' | 'error'

export interface LobbyLiveState {
  prizePool: number
  totalPlayers: number
  totalCards: number
  userCards: number
  statusCode: string
  cardPrice: number
  currencySymbol: string
}

interface RoomUpdatedPayload {
  room_id: string
  prize_pool: number
  total_players: number
  total_cards: number
  card_price_usd: number
  /** Presente solo cuando cambia el estado de la sala */
  status_code?: string
}

interface PlayerCardsUpdatedPayload {
  room_id: string
  ctns: number
  card_price: number | null
  currency_symbol: string | null
}

/**
 * Gestiona todas las suscripciones WebSocket del lobby de una sala.
 *
 * Canales:
 *  - `bingo.room.{roomId}` (público) → `.RoomUpdated`: prize_pool, total_players, total_cards
 *  - `bingo.player.{userId}` (privado) → `.PlayerCardsUpdated`: cartones del usuario actual
 *
 * @param roomId   - ID Snowflake de la sala
 * @param userId   - ID del usuario autenticado (null → no suscribe canal privado)
 * @param live     - Estado reactivo mutable del lobby (se parchea in-place)
 * @param onPrizeChanged   - Callback cuando cambia el prize_pool (para flash)
 * @param onPlayersChanged - Callback cuando cambia total_players (para flash)
 * @param onCardsUpdated   - Callback cuando se actualiza el precio/moneda del usuario
 */
export function useLobbyEcho(
  roomId: string,
  userId: (() => string | number | null | undefined) | string | number | null | undefined,
  live: LobbyLiveState,
  callbacks?: {
    onPrizeChanged?: () => void
    onPlayersChanged?: () => void
    onCardsChanged?: (totalCards: number) => void
    onCardsUpdated?: (cardPrice: number | null, currencySymbol: string | null) => void
  },
): { wsStatus: ReturnType<typeof ref<WsStatus>> } {
  const wsStatus = ref<WsStatus>('connecting')

  // ── Conexión Pusher/Reverb ──
  const pusher = (echo as any).connector.pusher

  const onConnected    = () => { wsStatus.value = 'connected' }
  const onDisconnected = () => { wsStatus.value = 'disconnected' }
  const onError        = () => { wsStatus.value = 'error' }
  const onConnecting   = () => { wsStatus.value = 'connecting' }

  if (pusher.connection.state === 'connected') wsStatus.value = 'connected'
  pusher.connection.bind('connected',    onConnected)
  pusher.connection.bind('disconnected', onDisconnected)
  pusher.connection.bind('error',        onError)
  pusher.connection.bind('connecting',   onConnecting)

  // ── Canal público de sala ──
  // Flag: si acabamos de recibir una actualización propia por canal privado,
  // suprimimos el toast "otro compró" para el siguiente evento público.
  let ownPurchasePending = false

  const roomChannelName = `bingo.room.${roomId}`
  echo.channel(roomChannelName).listen('.RoomUpdated', (payload: RoomUpdatedPayload) => {
    const prizeChanged   = live.prizePool    !== payload.prize_pool
    const playersChanged = live.totalPlayers !== payload.total_players
    const cardsChanged   = live.totalCards   !== payload.total_cards

    live.prizePool    = payload.prize_pool    ?? live.prizePool
    live.totalPlayers = payload.total_players ?? live.totalPlayers
    live.totalCards   = payload.total_cards   ?? live.totalCards
    if (payload.status_code) live.statusCode = payload.status_code

    if (prizeChanged)   callbacks?.onPrizeChanged?.()
    if (playersChanged) callbacks?.onPlayersChanged?.()
    if (cardsChanged) {
      if (!ownPurchasePending) callbacks?.onCardsChanged?.(live.totalCards)
      ownPurchasePending = false
    }
  })

  // ── Canal privado del jugador ──
  const resolvedUserId = typeof userId === 'function' ? userId() : userId
  const privateChannelName = resolvedUserId ? `bingo.player.${resolvedUserId}` : null
  if (privateChannelName) {
    echo.private(privateChannelName).listen('.PlayerCardsUpdated', (payload: PlayerCardsUpdatedPayload) => {
      if (payload.room_id !== roomId) return
      live.userCards = payload.ctns
      ownPurchasePending = true
      callbacks?.onCardsUpdated?.(payload.card_price, payload.currency_symbol)
    })
  }

  onUnmounted(() => {
    echo.leaveChannel(roomChannelName)
    if (privateChannelName) echo.leaveChannel(privateChannelName)

    pusher.connection.unbind('connected',    onConnected)
    pusher.connection.unbind('disconnected', onDisconnected)
    pusher.connection.unbind('error',        onError)
    pusher.connection.unbind('connecting',   onConnecting)
  })

  return { wsStatus }
}
