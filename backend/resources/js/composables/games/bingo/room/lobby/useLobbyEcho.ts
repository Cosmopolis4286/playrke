import { onUnmounted, ref, watch } from 'vue'
import echo from '@/js/plugins/echo'
import type { LobbyLiveState } from '@js/Components/Games/Bingo/Room/Lobby/types'

export type WsStatus = 'connecting' | 'connected' | 'disconnected' | 'error'
export type { LobbyLiveState }

interface RoomUpdatedPayload {
  room_id: string
  prize_pool: number
  total_players: number
  total_cards: number
  card_price_usd: number
  status_code?: string
  full_house_prize?: number
  serial_prize?: number
}

interface PlayerCardsUpdatedPayload {
  room_id: string
  card_count: number
  card_price: number | null
  currency_symbol: string | null
}

export function useLobbyEcho(
  roomId: string,
  userId: (() => string | number | null | undefined) | string | number | null | undefined,
  live: LobbyLiveState,
  callbacks?: {
    onPrizeChanged?: () => void
    onPlayersChanged?: (prev: number, next: number) => void
    onCardsChanged?: (totalCards: number) => void
    onCardsUpdated?: (cardPrice: number | null, currencySymbol: string | null) => void
    onGameStarted?: () => void
    onSubPrizesChanged?: (fullHousePrize: number | undefined, serialPrize: number | undefined) => void
  },
): { wsStatus: ReturnType<typeof ref<WsStatus>> } {
  const wsStatus = ref<WsStatus>('connecting')

  // ── Conexión Pusher/Reverb ──
  const pusher = (echo as any).connector.pusher
  let mounted = true

  // Al reconectar, re-suscribir el canal público por si se perdieron eventos.
  // Guard: si el componente ya fue desmontado no re-abrimos el canal.
  const onConnected = () => {
    wsStatus.value = 'connected'
    if (mounted) echo.channel(roomChannelName)
  }
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
  let ownPurchasePending      = false
  let ownPurchaseResetTimer: ReturnType<typeof setTimeout> | null = null

  function setOwnPurchasePending() {
    ownPurchasePending = true
    if (ownPurchaseResetTimer) clearTimeout(ownPurchaseResetTimer)
    ownPurchaseResetTimer = setTimeout(() => {
      ownPurchasePending    = false
      ownPurchaseResetTimer = null
    }, 10_000)
  }

  interface GameStartedPayload { room_id: string | number; draw_id: number; start_time: string }

  const roomChannelName = `bingo.room.${roomId}`
  echo.channel(roomChannelName)
    .listen('.GameStarted', (payload: GameStartedPayload) => {
      if (String(payload.room_id) !== String(roomId)) return
      live.statusCode = 'IN_PROGRESS'
      callbacks?.onGameStarted?.()
    })
    .listen('.RoomUpdated', (payload: RoomUpdatedPayload) => {
    const prizeChanged   = live.prizePool    !== payload.prize_pool
    const prevPlayers    = live.totalPlayers
    const playersChanged = live.totalPlayers !== payload.total_players
    const cardsChanged   = live.totalCards   !== payload.total_cards

    live.prizePool    = payload.prize_pool    ?? live.prizePool
    live.totalPlayers = payload.total_players ?? live.totalPlayers
    live.totalCards   = payload.total_cards   ?? live.totalCards
    if (payload.status_code) live.statusCode = payload.status_code

    if (prizeChanged)   callbacks?.onPrizeChanged?.()
    if (playersChanged) callbacks?.onPlayersChanged?.(prevPlayers, live.totalPlayers)
    if (cardsChanged) {
      if (!ownPurchasePending) callbacks?.onCardsChanged?.(live.totalCards)
      ownPurchasePending = false
    }
    if (payload.full_house_prize !== undefined || payload.serial_prize !== undefined) {
      callbacks?.onSubPrizesChanged?.(payload.full_house_prize, payload.serial_prize)
    }
  })

  // ── Canal privado del jugador — reactivo al userId ──
  // userId puede llegar null en el primer render (auth aún cargando).
  // watch lo suscribe en cuanto el valor esté disponible.
  const getUserId = typeof userId === 'function' ? userId : () => userId
  let currentPrivateChannel: string | null = null

  function subscribePrivate(id: string | number | null | undefined) {
    const channelName = id ? `bingo.player.${id}` : null

    if (channelName === currentPrivateChannel) return

    if (currentPrivateChannel) {
      echo.leaveChannel(currentPrivateChannel)
      currentPrivateChannel = null
    }

    if (!channelName) return

    currentPrivateChannel = channelName
    echo.private(channelName).listen('.PlayerCardsUpdated', (payload: PlayerCardsUpdatedPayload) => {
      if (payload.room_id !== roomId) return
      live.userCards = payload.card_count
      setOwnPurchasePending()
      callbacks?.onCardsUpdated?.(payload.card_price, payload.currency_symbol)
    })
  }

  watch(getUserId, subscribePrivate, { immediate: true })

  onUnmounted(() => {
    mounted = false
    if (ownPurchaseResetTimer) clearTimeout(ownPurchaseResetTimer)
    echo.leaveChannel(roomChannelName)
    if (currentPrivateChannel) echo.leaveChannel(currentPrivateChannel)

    pusher.connection.unbind('connected',    onConnected)
    pusher.connection.unbind('disconnected', onDisconnected)
    pusher.connection.unbind('error',        onError)
    pusher.connection.unbind('connecting',   onConnecting)
  })

  return { wsStatus }
}
