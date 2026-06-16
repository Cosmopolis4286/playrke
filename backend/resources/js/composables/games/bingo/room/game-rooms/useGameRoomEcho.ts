import { onUnmounted } from 'vue'
import echo from '@/js/plugins/echo'
import type { Ref } from 'vue'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

// ---------------------------------------------------------------------------
// Tipos del payload WebSocket
// ---------------------------------------------------------------------------

/**
 * Payload recibido del evento `RoomUpdated` via Laravel Echo.
 * Emitido por BingoRoomUpdated en el servidor tras cada generación de cartones.
 */
interface RoomUpdatedPayload {
  /** ID Snowflake de la sala (coincide con BingoGameRoom.id) */
  room_id: string
  /** Bote acumulado actualizado */
  prize_pool: number
  /** Total de jugadores en la sala */
  total_players: number
  /** Total de cartones vendidos en la sala */
  total_cards: number
  /** Precio base del cartón en USD */
  card_price_usd: number
}

/**
 * Payload recibido del evento `RoomChanged` via Laravel Echo (canal `bingo.lobby`).
 * Emitido por BingoLobbyRoomChanged cuando una sala se activa, se desactiva
 * o cambia mientras está activa.
 */
interface RoomChangedPayload {
  /** activated | updated | deactivated */
  action: 'activated' | 'updated' | 'deactivated'
  /** Datos de la sala en el shape de BingoGameRoom (valores base en USD) */
  room: BingoGameRoom
}

/**
 * Payload del evento `PlayerCardsUpdated` (canal privado `bingo.player.{userId}`).
 * Emitido por BingoPlayerRoomCardsUpdated tras una compra exitosa de cartones.
 */
interface PlayerCardsUpdatedPayload {
  /** ID Snowflake de la sala donde se compraron cartones */
  room_id: string
  /** Total acumulado de cartones del usuario en esa sala */
  ctns: number
  /** Precio del cartón en la moneda del usuario (null para commerce/seller) */
  card_price: number | null
  /** Símbolo de la moneda del usuario (null para commerce/seller) */
  currency_symbol: string | null
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

/**
 * Composable que suscribe a los canales de Reverb/Echo y actualiza
 * el estado reactivo de `rooms` en tiempo real sin recargar la página.
 *
 * Canales escuchados:
 *  - `bingo.room.{id}` (uno por sala visible) → evento `RoomUpdated`:
 *     actualiza prize_pool, total_players, total_cards y card_price_usd.
 *  - `bingo.lobby` (global, siempre activo) → evento `RoomChanged`:
 *     agrega, actualiza o quita una sala del listado cuando se
 *     activa/desactiva/cambia, incluso si la lista está vacía.
 *  - `bingo.player.{userId}` (privado, solo si hay userId) → evento `PlayerCardsUpdated`:
 *     actualiza players_room (cartones del usuario actual en la sala).
 *
 * Uso:
 * ```ts
 * const rooms = ref<BingoGameRoom[]>(props.rooms.map(r => ({ ...r })))
 * useGameRoomEcho(rooms, { userId: '123', onRoomAdded: (room) => initCountdown([room]) })
 * ```
 *
 * @param rooms - Ref reactivo de la lista de salas (se parchea in-place)
 * @param options.userId - ID del usuario autenticado (string). Si se omite, no se suscribe al canal privado.
 * @param options.onRoomAdded - Callback invocado cuando una sala nueva se agrega
 *   (p.ej. para registrar su countdown)
 */
export function useGameRoomEcho(
  rooms: Ref<BingoGameRoom[]>,
  options?: {
    userId?: string | number | null
    onRoomAdded?: (room: BingoGameRoom) => void
  },
): void {
  const subscribedChannels: string[] = []
  const subscribedPrivateChannels: string[] = []

  /**
   * Parchea una sala en el array reactivo con los datos recibidos del servidor.
   * Solo actualiza los campos que llegan en el payload para no perder otros datos locales.
   */
  function patchRoom(payload: RoomUpdatedPayload): void {
    const idx = rooms.value.findIndex(r => r.id === payload.room_id)
    if (idx === -1) return

    const room = rooms.value[idx]

    rooms.value[idx] = {
      ...room,
      prize_pool:    payload.prize_pool,
      total_players: payload.total_players,
      total_cards:   payload.total_cards,
      // Actualizar card_price_usd dentro de config si existe
      config: room.config
        ? { ...room.config, card_price_usd: payload.card_price_usd }
        : room.config,
    }
  }

  /** Suscribe el canal individual de una sala (RoomUpdated), evitando duplicados. */
  function subscribeRoomChannel(roomId: string): void {
    const channelName = `bingo.room.${roomId}`
    if (subscribedChannels.includes(channelName)) return

    echo.channel(channelName).listen('.RoomUpdated', (payload: RoomUpdatedPayload) => {
      patchRoom(payload)
    })
    subscribedChannels.push(channelName)
  }

  // Suscribir a cada sala al montarse (canal identificado por ID Snowflake,
  // igual que BingoRoomUpdated::broadcastOn())
  for (const room of rooms.value) {
    subscribeRoomChannel(room.id)
  }

  // Suscripción global al lobby: siempre activa, incluso sin salas visibles.
  const lobbyChannelName = 'bingo.lobby'
  echo.channel(lobbyChannelName).listen('.RoomChanged', (payload: RoomChangedPayload) => {
    const idx = rooms.value.findIndex(r => r.id === payload.room.id)

    if (payload.action === 'deactivated') {
      if (idx !== -1) rooms.value.splice(idx, 1)
      return
    }

    if (idx !== -1) {
      // Preservar datos por-usuario (players_room, card_price, currency_symbol)
      // que no viajan en el payload público.
      rooms.value[idx] = {
        ...rooms.value[idx],
        ...payload.room,
        players_room:    rooms.value[idx].players_room,
        card_price:      rooms.value[idx].card_price,
        currency_symbol: rooms.value[idx].currency_symbol,
      }
      return
    }

    // Sala nueva: se agrega al listado, se notifica para registrar su countdown
    // y se suscribe su canal individual para futuras actualizaciones (RoomUpdated).
    rooms.value.push(payload.room)
    options?.onRoomAdded?.(payload.room)
    subscribeRoomChannel(payload.room.id)
  })
  subscribedChannels.push(lobbyChannelName)

  // Canal privado por usuario: actualiza players_room (cartones del usuario actual)
  // en tiempo real tras cada compra exitosa, sin recargar la página.
  if (options?.userId) {
    const privateChannelName = `bingo.player.${options.userId}`

    echo.private(privateChannelName).listen('.PlayerCardsUpdated', (payload: PlayerCardsUpdatedPayload) => {
      const idx = rooms.value.findIndex(r => r.id === payload.room_id)
      if (idx === -1) return

      const room = rooms.value[idx]
      rooms.value[idx] = {
        ...room,
        players_room:    [{ ctns: payload.ctns }],
        card_price:      payload.card_price      ?? room.card_price,
        currency_symbol: payload.currency_symbol ?? room.currency_symbol,
      }
    })

    subscribedPrivateChannels.push(privateChannelName)
  }

  // Limpiar suscripciones al desmontar el componente
  onUnmounted(() => {
    for (const channelName of subscribedChannels) {
      echo.leaveChannel(channelName)
    }
    for (const channelName of subscribedPrivateChannels) {
      echo.leaveChannel(channelName)
    }
  })
}
