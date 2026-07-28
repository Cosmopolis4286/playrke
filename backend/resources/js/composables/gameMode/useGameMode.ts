import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { GameMode } from '@/js/types/gameMode'

/**
 * Contrato público del composable useGameMode
 */
interface UseGameModeReturn {
    /** Datos del modo de juego (para crear o editar) */
    gameModeData: Ref<Partial<GameMode>>

    /** Lista reactiva de modos de juego */
    gameModes: Ref<GameMode[]>

    /** Inicializa la lista local de modos de juego */
    setInitialGameModes: (initial: GameMode[]) => void

    /**
     * Envía el formulario de modo de juego.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: (gameId?: string) => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina un modo de juego */
    remove: (id: string | number) => Promise<boolean>

    /** Alterna el status de un modo de juego (ACTIVE ↔ INACTIVE) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de modos de juego desde backend */
    fetchGameModes: (gameId?: string) => Promise<boolean>
}

/**
 * Composable para manejar el catálogo de modos de juego (GameMode),
 * específico por juego (Bingo, Trivia, etc.).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de modos de juego
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 */
export function useGameMode(): UseGameModeReturn {
    const loading = ref(false)

    const gameModeData = ref<Partial<GameMode>>({})

    const gameModes = ref<GameMode[]>([])

    function setInitialGameModes(initial: GameMode[]) {
        gameModes.value = [...initial]
    }

    async function fetchGameModes(gameId?: string): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: GameMode[]
            }>('/game-modes/fetch-list', {
                params: gameId ? { game_id: gameId } : {}
            })

            if (Array.isArray(response.data?.data)) {
                gameModes.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener los modos de juego:', error)
            showToast('Error al cargar los modos de juego', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!gameModeData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (gameModeData.value.id) {
                const { data } = await backendClient.put(
                    `/game-modes/${gameModeData.value.id}`,
                    gameModeData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/game-modes',
                    gameModeData.value
                )
                showToast(data.message, 'success')
            }

            await fetchGameModes()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el modo de juego', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(gameId?: string): void {
        gameModeData.value = {
            game_id: gameId,
            code: '',
            label: '',
            status: 'ACTIVE'
        }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: GameMode
            }>(`/game-modes/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró el modo de juego')
            }

            const data = response.data.data

            gameModeData.value = {
                id: data.id,
                game_id: data.game_id,
                code: data.code,
                label: data.label,
                status: data.status
            }

            return true
        } catch (error) {
            console.error('Error cargando modo de juego:', error)
            showToast('Error al cargar el modo de juego', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const gameMode = gameModes.value.find(m => m.id === id)
        const name = gameMode?.label ?? ''
        const currentStatus = gameMode?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextStatus = gameMode?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El modo de juego <b>${name}</b> está actualmente <b>${currentStatus}</b>. ¿Deseas marcarlo como <b>${nextStatus}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/game-modes/${id}/status`)
            showToast(data.message, 'success')
            await fetchGameModes()
            return true
        } catch (error) {
            console.error('Error alternando status:', error)
            showToast('Error al actualizar el estado del modo de juego', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const gameMode = gameModes.value.find(m => m.id === id)
        const name = gameMode?.label ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar este modo de juego: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/game-modes/${id}/delete`)
            showToast('Modo de juego eliminado con éxito', 'success')
            await fetchGameModes()
            return true
        } catch (error: any) {
            console.error('Error eliminando modo de juego:', error)
            showToast(error?.response?.data?.message ?? 'Error al eliminar el modo de juego', 'error')
            return false
        }
    }

    return {
        gameModeData,
        gameModes,
        setInitialGameModes,
        submit,
        create,
        edit,
        remove,
        toggleStatus,
        fetchGameModes
    }
}
