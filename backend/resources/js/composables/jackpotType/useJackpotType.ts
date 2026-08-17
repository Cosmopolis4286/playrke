import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { JackpotType } from '@/js/types/jackpotType'

/**
 * Contrato público del composable useJackpotType
 */
interface UseJackpotTypeReturn {
    /** Datos del jackpot (para crear o editar) */
    jackpotTypeData: Ref<Partial<JackpotType>>

    /** Lista reactiva de jackpots */
    jackpotTypes: Ref<JackpotType[]>

    /** Inicializa la lista local de jackpots */
    setInitialJackpotTypes: (initial: JackpotType[]) => void

    /**
     * Envía el formulario de jackpot.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: (gameId?: string) => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina un jackpot */
    remove: (id: string | number) => Promise<boolean>

    /** Alterna el status de un jackpot (ACTIVE ↔ INACTIVE) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de jackpots desde backend */
    fetchJackpotTypes: (gameId?: string) => Promise<boolean>
}

/**
 * Composable para manejar el catálogo de tipos de jackpot (JackpotType),
 * específico por juego (Bingo, Trivia, etc.) y por moneda.
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de jackpots
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 */
export function useJackpotType(): UseJackpotTypeReturn {
    const loading = ref(false)

    const jackpotTypeData = ref<Partial<JackpotType>>({})

    const jackpotTypes = ref<JackpotType[]>([])

    function setInitialJackpotTypes(initial: JackpotType[]) {
        jackpotTypes.value = [...initial]
    }

    async function fetchJackpotTypes(gameId?: string): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: JackpotType[]
            }>('/jackpot-types/fetch-list', {
                params: gameId ? { game_id: gameId } : {}
            })

            if (Array.isArray(response.data?.data)) {
                jackpotTypes.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener los jackpots:', error)
            showToast('Error al cargar los jackpots', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!jackpotTypeData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (jackpotTypeData.value.id) {
                const { data } = await backendClient.put(
                    `/jackpot-types/${jackpotTypeData.value.id}`,
                    jackpotTypeData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/jackpot-types',
                    jackpotTypeData.value
                )
                showToast(data.message, 'success')
            }

            await fetchJackpotTypes()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el jackpot', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(gameId?: string): void {
        jackpotTypeData.value = {
            game_id: gameId,
            currency_id: undefined,
            code: '',
            label: '',
            periodicity: 'PER_ROOM',
            status: 'ACTIVE'
        }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: JackpotType
            }>(`/jackpot-types/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró el jackpot')
            }

            const data = response.data.data

            jackpotTypeData.value = {
                id: data.id,
                game_id: data.game_id,
                currency_id: data.currency_id,
                code: data.code,
                label: data.label,
                periodicity: data.periodicity,
                status: data.status
            }

            return true
        } catch (error) {
            console.error('Error cargando jackpot:', error)
            showToast('Error al cargar el jackpot', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const jackpotType = jackpotTypes.value.find(t => t.id === id)
        const name = jackpotType?.label ?? ''
        const currentStatus = jackpotType?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextStatus = jackpotType?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El jackpot <b>${name}</b> está actualmente <b>${currentStatus}</b>. ¿Deseas marcarlo como <b>${nextStatus}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/jackpot-types/${id}/status`)
            showToast(data.message, 'success')
            await fetchJackpotTypes()
            return true
        } catch (error) {
            console.error('Error alternando status:', error)
            showToast('Error al actualizar el estado del jackpot', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const jackpotType = jackpotTypes.value.find(t => t.id === id)
        const name = jackpotType?.label ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar este jackpot: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/jackpot-types/${id}/delete`)
            showToast('Jackpot eliminado con éxito', 'success')
            await fetchJackpotTypes()
            return true
        } catch (error: any) {
            console.error('Error eliminando jackpot:', error)
            showToast(error?.response?.data?.message ?? 'Error al eliminar el jackpot', 'error')
            return false
        }
    }

    return {
        jackpotTypeData,
        jackpotTypes,
        setInitialJackpotTypes,
        submit,
        create,
        edit,
        remove,
        toggleStatus,
        fetchJackpotTypes
    }
}
