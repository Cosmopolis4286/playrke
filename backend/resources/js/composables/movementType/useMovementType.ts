import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { MovementType } from '@/js/types/movementType'

/**
 * Contrato público del composable useMovementType
 */
interface UseMovementTypeReturn {
    /** Datos del tipo de movimiento (para crear o editar) */
    movementTypeData: Ref<Partial<MovementType>>

    /** Lista reactiva de tipos de movimiento */
    movementTypes: Ref<MovementType[]>

    /** Inicializa la lista local de tipos de movimiento */
    setInitialMovementTypes: (initial: MovementType[]) => void

    /**
     * Envía el formulario de tipo de movimiento.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina un tipo de movimiento */
    remove: (id: string | number) => Promise<boolean>

    /** Alterna el status de un tipo de movimiento (ACTIVE ↔ INACTIVE) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de tipos de movimiento desde backend */
    fetchMovementTypes: () => Promise<boolean>
}

/**
 * Composable para manejar el catálogo de tipos de movimiento (MovementType).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de tipos de movimiento
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 *
 * @param {string} tokenId Token opcional del usuario autenticado
 * @returns {UseMovementTypeReturn}
 */
export function useMovementType(tokenId: string = ''): UseMovementTypeReturn {
    const loading = ref(false)

    const movementTypeData = ref<Partial<MovementType>>({})

    const movementTypes = ref<MovementType[]>([])

    function setInitialMovementTypes(initial: MovementType[]) {
        movementTypes.value = [...initial]
    }

    async function fetchMovementTypes(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: MovementType[]
            }>('/movement-types/fetch-list', {
                params: { token: tokenId }
            })

            if (Array.isArray(response.data?.data)) {
                movementTypes.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener los tipos de movimiento:', error)
            showToast('Error al cargar los tipos de movimiento', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!movementTypeData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (movementTypeData.value.id) {
                const { data } = await backendClient.put(
                    `/movement-types/${movementTypeData.value.id}`,
                    movementTypeData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/movement-types',
                    movementTypeData.value
                )
                showToast(data.message, 'success')
            }

            await fetchMovementTypes()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el tipo de movimiento', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        movementTypeData.value = {
            code: '',
            label: '',
            description: null,
            icon: 'fas fa-circle',
            color: '#718096',
            is_credit: true,
            requires_approval: false,
            status: 'ACTIVE',
            visible_roles: null,
            game_group: null,
            sort_order: 0
        }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: MovementType
            }>(`/movement-types/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró el tipo de movimiento')
            }

            const data = response.data.data

            movementTypeData.value = {
                id: data.id,
                code: data.code,
                label: data.label,
                description: data.description,
                icon: data.icon,
                color: data.color,
                is_credit: data.is_credit,
                requires_approval: data.requires_approval,
                is_system: data.is_system,
                status: data.status,
                visible_roles: data.visible_roles,
                game_group: data.game_group,
                sort_order: data.sort_order
            }

            return true
        } catch (error) {
            console.error('Error cargando tipo de movimiento:', error)
            showToast('Error al cargar el tipo de movimiento', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const movementType = movementTypes.value.find(t => t.id === id)
        const name = movementType?.label ?? ''
        const currentStatus = movementType?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextStatus = movementType?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El tipo de movimiento <b>${name}</b> está actualmente <b>${currentStatus}</b>. ¿Deseas marcarlo como <b>${nextStatus}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/movement-types/${id}/status`)
            showToast(data.message, 'success')
            await fetchMovementTypes()
            return true
        } catch (error) {
            console.error('Error alternando status:', error)
            showToast('Error al actualizar el estado del tipo de movimiento', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const movementType = movementTypes.value.find(t => t.id === id)
        const name = movementType?.label ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar este tipo de movimiento: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/movement-types/${id}/delete`)
            showToast('Tipo de movimiento eliminado con éxito', 'success')
            await fetchMovementTypes()
            return true
        } catch (error: any) {
            console.error('Error eliminando tipo de movimiento:', error)
            showToast(error?.response?.data?.message ?? 'Error al eliminar el tipo de movimiento', 'error')
            return false
        }
    }

    return {
        movementTypeData,
        movementTypes,
        setInitialMovementTypes,
        submit,
        create,
        edit,
        remove,
        toggleStatus,
        fetchMovementTypes
    }
}
