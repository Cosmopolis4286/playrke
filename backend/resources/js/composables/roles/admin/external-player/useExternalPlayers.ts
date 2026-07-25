import { ref } from 'vue'
import type {
  ExternalPlayer,
  ExternalPlayerFilters,
  ExternalPlayerStatistics,
  PaginatedResult,
} from '@/js/types/roles/admin/external-player'
import axios from 'axios'

export function useExternalPlayers(initial?: {
  players?: PaginatedResult<ExternalPlayer>
  statistics?: ExternalPlayerStatistics
}) {
    const players = ref<ExternalPlayer[]>(initial?.players?.data ?? [])
    const pagination = ref({
        currentPage: initial?.players?.current_page ?? 1,
        lastPage: initial?.players?.last_page ?? 1,
        perPage: initial?.players?.per_page ?? 20,
        total: initial?.players?.total ?? 0,
    })
    const statistics = ref<ExternalPlayerStatistics>(
        initial?.statistics ?? { total: 0, active: 0, inactive: 0, blocked: 0 }
    )
    const loading = ref(false)
    const filters = ref<ExternalPlayerFilters>({})

    let debounceTimer: ReturnType<typeof setTimeout> | undefined

    const fetchPlayers = async (newFilters?: ExternalPlayerFilters, page = 1) => {
        if (newFilters) filters.value = newFilters

        loading.value = true
        try {
            const { data } = await axios.get('/admin/external-players/fetch-list', {
                params: { ...filters.value, page },
            })
            players.value = data.players.data
            pagination.value = {
                currentPage: data.players.current_page,
                lastPage: data.players.last_page,
                perPage: data.players.per_page,
                total: data.players.total,
            }
            statistics.value = data.statistics
        } finally {
            loading.value = false
        }
    }

    /** Debounce para el filtro de texto libre — evita disparar una request por tecla. */
    const searchDebounced = (newFilters: ExternalPlayerFilters, delayMs = 350) => {
        clearTimeout(debounceTimer)
        debounceTimer = setTimeout(() => fetchPlayers(newFilters), delayMs)
    }

    const goToPage = (page: number) => fetchPlayers(undefined, page)

    const toggleBlock = async (playerId: string) => {
        const { data } = await axios.put(`/admin/external-players/${playerId}/toggle-block`)
        const index = players.value.findIndex(p => p.id === playerId)
        if (index !== -1) players.value[index] = data.player
        return data.player as ExternalPlayer
    }

    return {
        players,
        pagination,
        statistics,
        loading,
        filters,
        fetchPlayers,
        searchDebounced,
        goToPage,
        toggleBlock,
    }
}
