import { ref, computed } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { BingoGameRoom, GameRoomFilters, GameRoomKpisPayload } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { Pagination } from '@js/types/common/table/pagination'

/**
 * Estado de filtros (fecha/variante) del listado de salas de juego y su
 * refetch vía GET /bingo/game-rooms/filter-data — mismo patrón que
 * Room/Summary.vue's fetchSummaryPage() (admin), pero para el listado de
 * jugador.
 */
export function useGameRoomFilters(
  onResult: (rooms: BingoGameRoom[], pagination: Pagination, kpis: GameRoomKpisPayload) => void
) {
  const filters = ref<GameRoomFilters>({ dateFrom: null, dateTo: null, variant: null })
  const loading = ref(false)
  const page = ref(1)

  const isFiltered = computed(() =>
    filters.value.dateFrom !== null || filters.value.dateTo !== null || filters.value.variant !== null
  )

  // Token de request: descarta respuestas que llegan fuera de orden (ej. el
  // usuario cambia de pill de variante dos veces rápido y la primera request,
  // más lenta, resuelve después de la segunda) — sin esto, la UI podría
  // quedar mostrando el resultado del filtro anterior en vez del actual.
  let requestToken = 0

  async function fetchPage(): Promise<void> {
    const token = ++requestToken
    loading.value = true
    try {
      const { data } = await backendClient.get<{ rooms: BingoGameRoom[]; pagination: Pagination; kpis: GameRoomKpisPayload }>(
        '/bingo/game-rooms/filter-data',
        {
          params: {
            page: page.value,
            per_page: 24,
            date_from: filters.value.dateFrom ?? undefined,
            date_to: filters.value.dateTo ?? undefined,
            variant: filters.value.variant ?? undefined,
          },
        }
      )
      if (token !== requestToken) return
      onResult(data.rooms, data.pagination, data.kpis)
    } finally {
      if (token === requestToken) loading.value = false
    }
  }

  /** Aplicar un cambio de filtro (fecha/variante) — siempre vuelve a la página 1. */
  async function applyFilters(): Promise<void> {
    page.value = 1
    await fetchPage()
  }

  function resetFilters(): void {
    filters.value = { dateFrom: null, dateTo: null, variant: null }
    page.value = 1
  }

  function goToPage(next: number): void {
    page.value = next
    fetchPage()
  }

  return {
    filters,
    loading,
    isFiltered,
    page,
    applyFilters,
    resetFilters,
    goToPage,
  }
}
