import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface AuthPageProps extends Record<string, unknown> {
  auth?: { user?: { id?: string | number } }
}

import { useGameRoomCountdown }      from './useGameRoomCountdown'
import { useGameRoomEcho }           from './useGameRoomEcho'
import { useGameRoomPurchaseModal }  from '@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal'
import { useGameRoomRoles }          from './useGameRoomRoles'
import { useGameRoomJoinByCode }     from './useGameRoomJoinByCode'
import { useGameRoomList }           from './useGameRoomList'
import { useGameRoomInfoModal }      from './useGameRoomInfoModal'
import { useGameRoomNavigation }     from './useGameRoomNavigation'
import { useGameRoomPurchaseBridge } from './useGameRoomPurchaseBridge'
import { useGameRoomFilters }        from './useGameRoomFilters'
import { useGameRoomKpis }           from './useGameRoomKpis'

import type { BingoGameRoomsPageProps } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { Pagination } from '@js/types/common/table/pagination'

/**
 * Composable orquestador de la página de salas de juego.
 * Compone (sin lógica propia más allá del wiring): lista de salas +
 * filtro de seguridad por rol (useGameRoomList), countdown
 * (useGameRoomCountdown), WebSockets (useGameRoomEcho), modal de info
 * (useGameRoomInfoModal), modal de compra (useGameRoomPurchaseModal +
 * useGameRoomPurchaseBridge), navegación al lobby (useGameRoomNavigation),
 * filtros/paginación/KPIs (useGameRoomFilters + useGameRoomKpis) y el modal
 * "Tengo un código" (useGameRoomJoinByCode).
 */
export function useGameRooms(props: BingoGameRoomsPageProps) {
  const page = usePage<AuthPageProps>()
  const { isAdmin, canBuy, isPlayer } = useGameRoomRoles()

  // ── Lista de salas (mutable para parches WS/filtros) ──────────────────────
  const { rooms, filteredRooms, replaceRooms } = useGameRoomList(props.rooms, isPlayer)

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

  // ── Filtros / paginación / KPIs ────────────────────────────────────────────
  // kpisApi se declara con `let` porque su callback (pasado a useGameRoomFilters)
  // necesita invocar setServerKpis() antes de que useGameRoomKpis() exista —
  // el callback solo se ejecuta de forma async (tras el GET de applyFilters()),
  // nunca durante el setup síncrono, así que para ese punto ya está asignado.
  let kpisApi: ReturnType<typeof useGameRoomKpis>

  const paginationState = ref<Pagination | null>(null)
  const filtersApi = useGameRoomFilters((newRooms, pagination, kpis) => {
    replaceRooms(newRooms)
    paginationState.value = pagination
    kpisApi.setServerKpis(kpis)
  })
  kpisApi = useGameRoomKpis(rooms, filtersApi.isFiltered)
  const { nextRoomUnixDate, totalActiveRooms } = kpisApi

  function resetFiltersToInitial(): void {
    filtersApi.resetFilters()
    paginationState.value = null
    replaceRooms(props.rooms)
  }

  // ── WebSockets ────────────────────────────────────────────────────────────
  useGameRoomEcho(rooms, {
    userId: page.props.auth?.user?.id ?? null,
    onRoomAdded: (room) => {
      // Con un filtro de fecha activo, el usuario está viendo intencionalmente
      // otra ventana temporal — no inyectar salas nuevas del "ahora" en esa vista.
      if (filtersApi.isFiltered.value) return
      if (room.config?.unix_date) updateRoomTime(room.id, room.config.unix_date)
    },
  })

  // ── Modal de info de sala ─────────────────────────────────────────────────
  const {
    showRoomInfoModal,
    selectedRoomForInfo,
    openRoomInfo: openRoomInfoBase,
    closeRoomInfo,
  } = useGameRoomInfoModal(rooms)

  // ── Modal de compra ───────────────────────────────────────────────────────
  const modal = useGameRoomPurchaseModal()

  function openRoomInfo(roomId: string): void {
    const room = filteredRooms.value.find(r => r.id === roomId)
    if (!room) return
    openRoomInfoBase(roomId)
    modal.prefetch(room)
  }

  function openPurchaseFromInfo(): void {
    if (!selectedRoomForInfo.value) return
    const room = selectedRoomForInfo.value
    closeRoomInfo()
    modal.open(room)
  }

  const { onPurchaseSuccess } = useGameRoomPurchaseBridge(rooms, () => page.props.auth?.user?.id ?? null)

  // ── Modal de reglamento ───────────────────────────────────────────────────
  const showRulesModal = ref(false)

  // ── Modal "Tengo un código" (unirse a sala privada) ──────────────────────
  const joinByCode = useGameRoomJoinByCode()

  // ── Navegación ────────────────────────────────────────────────────────────
  const { redirectingToLobby, goToLobby } = useGameRoomNavigation()

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
    redirectingToLobby,
    // Unirse a sala privada por código
    joinByCode,
    // Filtros / paginación / KPIs
    filters: filtersApi.filters,
    filtersLoading: filtersApi.loading,
    isFiltered: filtersApi.isFiltered,
    applyFilters: filtersApi.applyFilters,
    resetFilters: resetFiltersToInitial,
    goToFilterPage: filtersApi.goToPage,
    paginationState,
    nextRoomUnixDate,
    totalActiveRooms,
  }
}
