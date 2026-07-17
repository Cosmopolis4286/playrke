import { ref, computed } from 'vue'
import backendClient from '@js/plugins/axios'
import type { TableFilterInterface } from '@js/types/common/table/table-filter'
import type { GameHistoryRow, GameHistoryPagination } from '@/js/types/games/bingo/history/my-games'

const COLUMNS = [
  { key: 'game_date',       label: 'Fecha',      sortable: false },
  { key: 'room_code',       label: 'Sala',        sortable: false },
  { key: 'game_variant',    label: 'Modalidad',   sortable: false },
  { key: 'cards_purchased', label: 'Cartones',    sortable: false },
  { key: 'result',          label: 'Resultado',   sortable: false },
  { key: 'room_status',     label: 'Estado',      sortable: false },
  { key: 'actions',         label: '',            sortable: false },
]

const TABLE_FILTERS: TableFilterInterface[] = [
  {
    key: 'result',
    label: 'Resultado',
    placeholder: 'Todos',
    options: [
      { value: 'won',  label: 'Gané' },
      { value: 'lost', label: 'No gané' },
    ],
  },
]

/**
 * Lista paginada/filtrable del historial de partidas del jugador, más los
 * stats resumen (partidas totales, victorias y cartones de la página actual).
 */
export function useMyGamesHistory(initialHistory: GameHistoryRow[], initialPagination: GameHistoryPagination) {
  const history    = ref<GameHistoryRow[]>(initialHistory)
  const meta        = ref(initialPagination)
  const loading     = ref(false)
  const lastSearch  = ref('')
  const lastFilter  = ref('')

  const columns = COLUMNS
  const tableFilters = TABLE_FILTERS

  async function fetchHistory(page = 1) {
    loading.value = true
    try {
      const { data } = await backendClient.get('/bingo/my-games', {
        params: {
          page,
          per_page: 15,
          search: lastSearch.value || undefined,
          result: lastFilter.value || undefined,
        },
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      history.value = data.props?.history ?? data.history ?? []
      meta.value    = data.props?.pagination ?? data.pagination ?? meta.value
    } catch {
      // silencioso — tabla queda vacía
    } finally {
      loading.value = false
    }
  }

  function onFilter(payload: { search: string; filters: Record<string, string> }) {
    lastSearch.value = payload.search
    lastFilter.value = payload.filters.result ?? ''
    fetchHistory(1)
  }

  const totalGames = computed(() => meta.value.total)
  const wonOnPage  = computed(() => history.value.filter(r => r.won).length)
  const totalCards = computed(() => history.value.reduce((a, r) => a + r.cards_purchased, 0))

  return {
    history, meta, loading,
    columns, tableFilters,
    fetchHistory, onFilter,
    totalGames, wonOnPage, totalCards,
  }
}
