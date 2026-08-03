import { ref, computed, type Ref } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

/**
 * Dueño de la lista de salas (fuente de verdad) y del filtro de seguridad
 * PUBLIC-only para jugadores. Único lugar donde vive esta lógica de filtro —
 * useGameRoomEcho.ts escribe directo sobre `rooms` sin conocerla (por diseño).
 */
export function useGameRoomList(initialRooms: BingoGameRoom[], isPlayer: Ref<boolean>) {
  const rooms = ref<BingoGameRoom[]>(initialRooms.map(r => ({ ...r })))

  const filteredRooms = computed<BingoGameRoom[]>(() =>
    isPlayer.value
      ? rooms.value.filter(r => r.room_type === 'PUBLIC')
      : rooms.value
  )

  function replaceRooms(next: BingoGameRoom[]): void {
    rooms.value = next.map(r => ({ ...r }))
  }

  return {
    rooms,
    filteredRooms,
    replaceRooms,
  }
}
