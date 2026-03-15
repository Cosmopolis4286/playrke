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
  /** ID real (Snowflake) de la sala */
  room_id: string
  /** Bote acumulado actualizado */
  prize_pool: number
  /** Total de jugadores en la sala */
  total_players: number
  /** Precio base del cartón en USD */
  card_price_usd: number
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

/**
 * Composable que suscribe a los canales públicos de Reverb/Echo para cada sala
 * y actualiza el estado reactivo de `rooms` en tiempo real sin recargar la página.
 *
 * Eventos escuchados por sala (canal `bingo.room.{id}`):
 *  - `RoomUpdated`  → actualiza prize_pool, total_players y card_price_usd
 *
 * Uso:
 * ```ts
 * const rooms = ref<BingoGameRoom[]>(props.rooms.map(r => ({ ...r })))
 * useGameRoomEcho(rooms)
 * ```
 *
 * @param rooms - Ref reactivo de la lista de salas (se parchea in-place)
 */
export function useGameRoomEcho(rooms: Ref<BingoGameRoom[]>): void {
  const subscribedChannels: string[] = []

  /**
   * Parchea una sala en el array reactivo con los datos recibidos del servidor.
   * Solo actualiza los campos que llegan en el payload para no perder otros datos locales.
   */
  function patchRoom(payload: RoomUpdatedPayload): void {
    console.log(`🔄 Actualizando sala ${payload.room_id} con:`, payload)
    
    const idx = rooms.value.findIndex(r => r.id === payload.room_id)
    if (idx === -1) {
      console.warn(`⚠️ No se encontró la sala ${payload.room_id} en la lista local`)
      return
    }

    const room = rooms.value[idx]
    console.log(`📊 Sala antes de actualizar:`, room)

    rooms.value[idx] = {
      ...room,
      prize_pool:    payload.prize_pool,
      total_players: payload.total_players,
      // Actualizar card_price_usd dentro de config si existe
      config: room.config
        ? { ...room.config, card_price_usd: payload.card_price_usd }
        : room.config,
    }
    
    console.log(`✅ Sala después de actualizar:`, rooms.value[idx])
  }

  // Suscribir a cada sala al montarse
  for (const room of rooms.value) {
    const channelName = `bingo.room.${room.uuid_code}`

    console.log(`🔌 Suscribiendo al canal: ${channelName}`)

    const channel = echo.channel(channelName)
    
    // Escuchar todos los eventos del canal para debug
    channel.listen('*', (event: any, data: any) => {
      console.log(`📨 CUALQUIER EVENTO recibido en ${channelName}:`, { event, data })
    })
    
    // Escuchar específicamente el evento RoomUpdated
    channel.listen('.RoomUpdated', (payload: RoomUpdatedPayload) => {
      console.log(`📨 Evento RoomUpdated recibido en canal ${channelName}:`, payload)
      patchRoom(payload)
    })
    
    // También intentar con el nombre sin prefijo
    channel.listen('RoomUpdated', (payload: RoomUpdatedPayload) => {
      console.log(`📨 Evento RoomUpdated (sin prefijo) recibido en canal ${channelName}:`, payload)
      patchRoom(payload)
    })

    // Debug: verificar estado del canal
    console.log(`📡 Estado del canal ${channelName}:`, channel)

    subscribedChannels.push(channelName)
  }

  // Limpiar suscripciones al desmontar el componente
  onUnmounted(() => {
    for (const channelName of subscribedChannels) {
      echo.leaveChannel(channelName)
    }
  })
}
