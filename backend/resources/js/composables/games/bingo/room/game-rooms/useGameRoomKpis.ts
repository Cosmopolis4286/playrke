import { ref, computed, type Ref } from 'vue'
import type { BingoGameRoom, GameRoomKpisPayload } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

/**
 * KPIs del header ("próxima sala", "salas activas"): calculados en cliente
 * desde el listado ya cargado cuando no hay filtro activo (evita 2 queries
 * extra en cada carga de página), leídos del backend cuando sí lo hay (el
 * cliente ya no tiene el conjunto completo una vez que hay paginación).
 */
export function useGameRoomKpis(rooms: Ref<BingoGameRoom[]>, isFiltered: Ref<boolean>) {
  const serverKpis = ref<GameRoomKpisPayload | null>(null)

  const clientNextRoom = computed(() => {
    const active = rooms.value
      .filter(r => r.status_code === 'ACTIVE' && r.config?.unix_date != null)
      .sort((a, b) => (a.config!.unix_date) - (b.config!.unix_date))
    return active[0] ?? null
  })

  const nextRoomUnixDate = computed<number | null>(() =>
    isFiltered.value
      ? serverKpis.value?.next_room_unix_date ?? null
      : clientNextRoom.value?.config?.unix_date ?? null
  )

  const nextRoomId = computed<string | null>(() =>
    isFiltered.value
      ? serverKpis.value?.next_room_id ?? null
      : clientNextRoom.value?.id ?? null
  )

  const totalActiveRooms = computed<number>(() =>
    isFiltered.value
      ? serverKpis.value?.total_active_rooms ?? 0
      : rooms.value.filter(r => r.status_code === 'ACTIVE').length
  )

  function setServerKpis(kpis: GameRoomKpisPayload): void {
    serverKpis.value = kpis
  }

  return {
    nextRoomUnixDate,
    nextRoomId,
    totalActiveRooms,
    setServerKpis,
  }
}
