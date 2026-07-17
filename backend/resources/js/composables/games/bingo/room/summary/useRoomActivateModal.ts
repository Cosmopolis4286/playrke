import { ref, watch, type Ref } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

/** Modal de activación / programación de inicio (salas de arranque manual). */
export function useRoomActivateModal(deps: {
  rooms: Ref<BingoRoom[]>
  activateRoomNow: (roomId: string | number) => Promise<boolean>
  scheduleRoomStart: (roomId: string | number, date: string, time: string) => Promise<boolean>
}) {
  const { rooms, activateRoomNow, scheduleRoomStart } = deps

  const showActivateModal = ref(false)
  const selectedActivateRoom = ref<BingoRoom | null>(null)
  const isActivatingRoom = ref(false)
  const isSchedulingRoom = ref(false)

  const openActivateModal = (ids: string[]) => {
    if (ids.length !== 1) return
    const room = rooms.value.find(r => String(r.id) === String(ids[0]))
    if (!room) return

    selectedActivateRoom.value = room
    showActivateModal.value = true
  }

  const handleActivateNow = async (roomId: string | number) => {
    isActivatingRoom.value = true
    const ok = await activateRoomNow(roomId)
    isActivatingRoom.value = false
    if (ok) showActivateModal.value = false
  }

  const handleScheduleStart = async (roomId: string | number, date: string, time: string) => {
    isSchedulingRoom.value = true
    const ok = await scheduleRoomStart(roomId, date, time)
    isSchedulingRoom.value = false
    if (ok) showActivateModal.value = false
  }

  watch(showActivateModal, open => {
    if (!open) selectedActivateRoom.value = null
  })

  return {
    showActivateModal, selectedActivateRoom, isActivatingRoom, isSchedulingRoom,
    openActivateModal, handleActivateNow, handleScheduleStart,
  }
}
