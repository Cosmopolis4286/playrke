import { ref } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

/** Modal de detalle de sala (ventas, ganadores, desglose financiero). */
export function useRoomDetailsModal(fetchRoomDetails: (roomId: string) => Promise<BingoRoom | null | undefined>) {
  const showDetailsModal = ref(false)
  const selectedRoomDetails = ref<BingoRoom | null>(null)
  const loadingDetails = ref(false)

  const openRoomDetails = async (ids: string[]) => {
    if (!ids || ids.length !== 1) return

    const roomId = ids[0]
    selectedRoomDetails.value = null
    loadingDetails.value = true
    showDetailsModal.value = true

    const roomDetails = await fetchRoomDetails(roomId)
    selectedRoomDetails.value = roomDetails ?? null

    loadingDetails.value = false
  }

  const closeDetailsModal = () => {
    showDetailsModal.value = false
    selectedRoomDetails.value = null
  }

  return { showDetailsModal, selectedRoomDetails, loadingDetails, openRoomDetails, closeDetailsModal }
}
