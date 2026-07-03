import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

import { useGameRoomCountdown }    from './useGameRoomCountdown'
import { useGameRoomEcho }         from './useGameRoomEcho'
import { useGameRoomPurchaseModal } from '@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal'
import { useGameRoomRoles }        from './useGameRoomRoles'

import type { BingoGameRoom, BingoGameRoomsPageProps } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

/**
 * Composable orquestador de la página de salas de juego.
 * Centraliza: lista de salas, filtros por rol, countdown, WebSockets,
 * modal de info, modal de compra y navegación al lobby.
 */
export function useGameRooms(props: BingoGameRoomsPageProps) {
  const page = usePage()
  const { isAdmin, canBuy, isPlayer } = useGameRoomRoles()

  // ── Lista de salas (mutable para parches WS) ──────────────────────────────
  const rooms = ref<BingoGameRoom[]>(props.rooms.map(r => ({ ...r })))

  const filteredRooms = computed<BingoGameRoom[]>(() =>
    isPlayer.value
      ? rooms.value.filter(r => r.room_type === 'PUBLIC')
      : rooms.value
  )

  // ── Countdown ─────────────────────────────────────────────────────────────
  const serverTimestampMs = props.dateTimeRooms?.timestamp
    ? Number(props.dateTimeRooms.timestamp)
    : Date.now()

  // OPT-01: al expirar el countdown de una sala, actualizar su status_code localmente
  const { init: initCountdown, getCountdown, updateRoomTime } = useGameRoomCountdown(
    serverTimestampMs,
    (roomId) => {
      const idx = rooms.value.findIndex(r => r.id === roomId)
      if (idx !== -1 && rooms.value[idx].status_code === 'ACTIVE') {
        rooms.value[idx] = { ...rooms.value[idx], status_code: 'IN_PROGRESS' }
      }
    },
  )

  // ── WebSockets ────────────────────────────────────────────────────────────
  useGameRoomEcho(rooms, {
    userId: page.props.auth?.user?.id ?? null,
    onRoomAdded: (room) => {
      if (room.config?.unix_date) updateRoomTime(room.id, room.config.unix_date)
    },
  })

  // ── Modal de info de sala ─────────────────────────────────────────────────
  const selectedRoomId    = ref<string | null>(null)
  const showRoomInfoModal = ref(false)

  const selectedRoomForInfo = computed<BingoGameRoom | null>(() =>
    selectedRoomId.value
      ? (rooms.value.find(r => r.id === selectedRoomId.value) ?? null)
      : null
  )

  function openRoomInfo(roomId: string): void {
    const room = filteredRooms.value.find(r => r.id === roomId)
    if (!room) return
    selectedRoomId.value    = roomId
    showRoomInfoModal.value = true
    modal.prefetch(room)
  }

  function closeRoomInfo(): void {
    showRoomInfoModal.value = false
    selectedRoomId.value    = null
  }

  // ── Modal de compra ───────────────────────────────────────────────────────
  const modal = useGameRoomPurchaseModal()

  function openPurchaseFromInfo(): void {
    if (!selectedRoomForInfo.value) return
    const room = selectedRoomForInfo.value
    closeRoomInfo()
    modal.open(room)
  }

  function onPurchaseSuccess(
    roomId: string,
    result: { prizePool: number; ctns: number },
  ): void {
    const idx = rooms.value.findIndex(r => r.id === roomId)
    if (idx === -1) return

    const room   = rooms.value[idx]
    const userId = String(page.props.auth?.user?.id ?? '')
    const existing = room.players_room?.find(p => String((p as any).user_id ?? '') === userId)
      ?? room.players_room?.[0]

    rooms.value[idx] = {
      ...room,
      prize_pool:   result.prizePool,
      players_room: [{ ...(existing ?? {}), ctns: (existing?.ctns ?? 0) + result.ctns }],
    }
  }

  // ── Modal de reglamento ───────────────────────────────────────────────────
  const showRulesModal = ref(false)

  // ── Navegación ────────────────────────────────────────────────────────────
  function goToLobby(roomId: string): void {
    router.visit(`/bingo/${roomId}/lobby`)
  }

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  // OPT-03: usar rooms.value en lugar de props.rooms para incluir salas llegadas por WS antes del mount
  onMounted(() => {
    initCountdown(rooms.value)
  })

  return {
    // Estado
    rooms,
    filteredRooms,
    // Permisos (isAdmin se mantiene internamente, no se exporta — DEAD-01)
    canBuy,
    // Countdown
    getCountdown,
    // Modales
    showRulesModal,
    showRoomInfoModal,
    selectedRoomForInfo,
    openRoomInfo,
    closeRoomInfo,
    openPurchaseFromInfo,
    onPurchaseSuccess,
    // Compra
    modal,
    // Navegación
    goToLobby,
  }
}
