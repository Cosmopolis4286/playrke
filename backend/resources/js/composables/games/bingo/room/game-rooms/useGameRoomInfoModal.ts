import { ref, computed, type Ref } from 'vue'
import type { BingoGameRoom } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

/**
 * Estado del modal de información de sala (abrir/cerrar, sala seleccionada).
 * No conoce el modal de compra — el orquestador decide si además hace
 * prefetch al abrir, manteniendo este composable desacoplado de la compra.
 */
export function useGameRoomInfoModal(rooms: Ref<BingoGameRoom[]>) {
  const selectedRoomId = ref<string | null>(null)
  const showRoomInfoModal = ref(false)

  const selectedRoomForInfo = computed<BingoGameRoom | null>(() =>
    selectedRoomId.value
      ? (rooms.value.find(r => r.id === selectedRoomId.value) ?? null)
      : null
  )

  function openRoomInfo(roomId: string): void {
    selectedRoomId.value = roomId
    showRoomInfoModal.value = true
  }

  function closeRoomInfo(): void {
    showRoomInfoModal.value = false
    selectedRoomId.value = null
  }

  return {
    selectedRoomId,
    showRoomInfoModal,
    selectedRoomForInfo,
    openRoomInfo,
    closeRoomInfo,
  }
}
