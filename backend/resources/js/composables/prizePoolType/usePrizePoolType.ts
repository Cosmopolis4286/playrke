import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { PrizePoolType } from '@/js/types/prizePoolType'

/**
 * Contrato público del composable usePrizePoolType
 */
interface UsePrizePoolTypeReturn {
    /** Datos del tipo de bote (para crear o editar) */
    prizePoolTypeData: Ref<Partial<PrizePoolType>>

    /** Lista reactiva de tipos de bote */
    prizePoolTypes: Ref<PrizePoolType[]>

    /** Inicializa la lista local de tipos de bote */
    setInitialPrizePoolTypes: (initial: PrizePoolType[]) => void

    /**
     * Envía el formulario de tipo de bote.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: (gameId?: string) => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina un tipo de bote */
    remove: (id: string | number) => Promise<boolean>

    /** Alterna el status de un tipo de bote (ACTIVE ↔ INACTIVE) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de tipos de bote desde backend */
    fetchPrizePoolTypes: (gameId?: string) => Promise<boolean>
}

/**
 * Composable para manejar el catálogo de tipos de bote (PrizePoolType),
 * específico por juego (Bingo, Trivia, etc.).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de tipos de bote
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 */
export function usePrizePoolType(): UsePrizePoolTypeReturn {
    const loading = ref(false)

    const prizePoolTypeData = ref<Partial<PrizePoolType>>({})

    const prizePoolTypes = ref<PrizePoolType[]>([])

    function setInitialPrizePoolTypes(initial: PrizePoolType[]) {
        prizePoolTypes.value = [...initial]
    }

    async function fetchPrizePoolTypes(gameId?: string): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: PrizePoolType[]
            }>('/prize-pool-types/fetch-list', {
                params: gameId ? { game_id: gameId } : {}
            })

            if (Array.isArray(response.data?.data)) {
                prizePoolTypes.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener los tipos de bote:', error)
            showToast('Error al cargar los tipos de bote', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!prizePoolTypeData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (prizePoolTypeData.value.id) {
                const { data } = await backendClient.put(
                    `/prize-pool-types/${prizePoolTypeData.value.id}`,
                    prizePoolTypeData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/prize-pool-types',
                    prizePoolTypeData.value
                )
                showToast(data.message, 'success')
            }

            await fetchPrizePoolTypes()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el tipo de bote', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(gameId?: string): void {
        prizePoolTypeData.value = {
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
                data: PrizePoolType
            }>(`/prize-pool-types/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró el tipo de bote')
            }

            const data = response.data.data

            prizePoolTypeData.value = {
                id: data.id,
                game_id: data.game_id,
                code: data.code,
                label: data.label,
                status: data.status
            }

            return true
        } catch (error) {
            console.error('Error cargando tipo de bote:', error)
            showToast('Error al cargar el tipo de bote', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const prizePoolType = prizePoolTypes.value.find(t => t.id === id)
        const name = prizePoolType?.label ?? ''
        const currentStatus = prizePoolType?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextStatus = prizePoolType?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El tipo de bote <b>${name}</b> está actualmente <b>${currentStatus}</b>. ¿Deseas marcarlo como <b>${nextStatus}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/prize-pool-types/${id}/status`)
            showToast(data.message, 'success')
            await fetchPrizePoolTypes()
            return true
        } catch (error) {
            console.error('Error alternando status:', error)
            showToast('Error al actualizar el estado del tipo de bote', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const prizePoolType = prizePoolTypes.value.find(t => t.id === id)
        const name = prizePoolType?.label ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar este tipo de bote: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/prize-pool-types/${id}/delete`)
            showToast('Tipo de bote eliminado con éxito', 'success')
            await fetchPrizePoolTypes()
            return true
        } catch (error: any) {
            console.error('Error eliminando tipo de bote:', error)
            showToast(error?.response?.data?.message ?? 'Error al eliminar el tipo de bote', 'error')
            return false
        }
    }

    return {
        prizePoolTypeData,
        prizePoolTypes,
        setInitialPrizePoolTypes,
        submit,
        create,
        edit,
        remove,
        toggleStatus,
        fetchPrizePoolTypes
    }
}
