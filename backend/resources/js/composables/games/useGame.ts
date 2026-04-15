import { ref, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Game } from '@/js/types/game'

interface UseGameReturn {
    gameData: Ref<Partial<Game>>
    games: Ref<Game[]>
    setInitialGames: (initial: Game[]) => void
    submit: () => Promise<boolean>
    create: () => void
    edit: (id: string | number) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    toggleStatus: (id: string | number) => Promise<boolean>
    fetchGames: () => Promise<boolean>
}

export function useGame(): UseGameReturn {
    const loading   = ref(false)
    const gameData  = ref<Partial<Game>>({})
    const games     = ref<Game[]>([])

    function setInitialGames(initial: Game[]) {
        games.value = [...initial]
    }

    async function fetchGames(): Promise<boolean> {
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: Game[] }>('/games/fetch-list')
            if (Array.isArray(data?.data)) games.value = data.data
            return true
        } catch {
            showToast('Error al cargar los juegos', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            if (gameData.value.id) {
                const { data } = await backendClient.put(`/games/${gameData.value.id}`, gameData.value)
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post('/games', gameData.value)
                showToast(data.message, 'success')
            }
            await fetchGames()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al guardar el juego', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        gameData.value = { name: '', status: 'ACTIVE' }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean; data: Game }>(`/games/${id}`)
            if (!data?.data) throw new Error('No encontrado')
            gameData.value = { id: data.data.id, name: data.data.name, status: data.data.status }
            return true
        } catch {
            showToast('Error al cargar el juego', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const game = games.value.find(g => g.id === id)
        const name = game?.name ?? ''
        const currentLabel = game?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextLabel    = game?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El juego <b>${name}</b> está actualmente <b>${currentLabel}</b>. ¿Deseas marcarlo como <b>${nextLabel}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar',
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/games/${id}/status`)
            showToast(data.message, 'success')
            await fetchGames()
            return true
        } catch {
            showToast('Error al actualizar el estado', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const game = games.value.find(g => g.id === id)
        const name = game?.name ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Deseas eliminar el juego <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar',
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/games/${id}/delete`)
            showToast('Juego eliminado exitosamente', 'success')
            await fetchGames()
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al eliminar el juego', 'error')
            return false
        }
    }

    return { gameData, games, setInitialGames, submit, create, edit, remove, toggleStatus, fetchGames }
}
