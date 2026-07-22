import { ref } from 'vue'
import type { ExternalPlayer, ExternalPlayerStatistics } from '@/js/types/roles/admin/external-player'
import axios from 'axios'

export function useExternalPlayers(initial?: { players?: ExternalPlayer[]; statistics?: ExternalPlayerStatistics }) {
    const players = ref<ExternalPlayer[]>(initial?.players ?? [])
    const statistics = ref<ExternalPlayerStatistics>(
        initial?.statistics ?? { total: 0, active: 0, inactive: 0, blocked: 0 }
    )
    const loading = ref(false)

    const fetchPlayers = async (filters?: { search?: string; status?: string; game_id?: string }) => {
        loading.value = true
        try {
            const { data } = await axios.get('/admin/external-players/fetch-list', { params: filters })
            players.value = data.players
            statistics.value = data.statistics
        } finally {
            loading.value = false
        }
    }

    const toggleBlock = async (playerId: string) => {
        const { data } = await axios.put(`/admin/external-players/${playerId}/toggle-block`)
        const index = players.value.findIndex(p => p.id === playerId)
        if (index !== -1) players.value[index] = data.player
        return data.player as ExternalPlayer
    }

    return {
        players,
        statistics,
        loading,
        fetchPlayers,
        toggleBlock,
    }
}
