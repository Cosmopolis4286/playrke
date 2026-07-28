import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { RoomType } from '@/js/types/roomType'

/**
 * Contrato público del composable useRoomType
 */
interface UseRoomTypeReturn {
    /** Datos del tipo de sala (para crear o editar) */
    roomTypeData: Ref<Partial<RoomType>>

    /** Lista reactiva de tipos de sala */
    roomTypes: Ref<RoomType[]>

    /** Inicializa la lista local de tipos de sala */
    setInitialRoomTypes: (initial: RoomType[]) => void

    /**
     * Envía el formulario de tipo de sala.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina un tipo de sala */
    remove: (id: string | number) => Promise<boolean>

    /** Alterna el status de un tipo de sala (ACTIVE ↔ INACTIVE) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de tipos de sala desde backend */
    fetchRoomTypes: () => Promise<boolean>
}

/**
 * Composable para manejar el catálogo global de tipos de sala (RoomType).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de tipos de sala
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 */
export function useRoomType(): UseRoomTypeReturn {
    const loading = ref(false)

    const roomTypeData = ref<Partial<RoomType>>({})

    const roomTypes = ref<RoomType[]>([])

    function setInitialRoomTypes(initial: RoomType[]) {
        roomTypes.value = [...initial]
    }

    async function fetchRoomTypes(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: RoomType[]
            }>('/room-types/fetch-list')

            if (Array.isArray(response.data?.data)) {
                roomTypes.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener los tipos de sala:', error)
            showToast('Error al cargar los tipos de sala', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!roomTypeData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (roomTypeData.value.id) {
                const { data } = await backendClient.put(
                    `/room-types/${roomTypeData.value.id}`,
                    roomTypeData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/room-types',
                    roomTypeData.value
                )
                showToast(data.message, 'success')
            }

            await fetchRoomTypes()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar el tipo de sala', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        roomTypeData.value = {
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
                data: RoomType
            }>(`/room-types/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró el tipo de sala')
            }

            const data = response.data.data

            roomTypeData.value = {
                id: data.id,
                code: data.code,
                label: data.label,
                status: data.status
            }

            return true
        } catch (error) {
            console.error('Error cargando tipo de sala:', error)
            showToast('Error al cargar el tipo de sala', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const roomType = roomTypes.value.find(t => t.id === id)
        const name = roomType?.label ?? ''
        const currentStatus = roomType?.status === 'ACTIVE' ? 'activo' : 'inactivo'
        const nextStatus = roomType?.status === 'ACTIVE' ? 'inactivo' : 'activo'

        const confirm = await showToastConfirmAction({
            html: `El tipo de sala <b>${name}</b> está actualmente <b>${currentStatus}</b>. ¿Deseas marcarlo como <b>${nextStatus}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/room-types/${id}/status`)
            showToast(data.message, 'success')
            await fetchRoomTypes()
            return true
        } catch (error) {
            console.error('Error alternando status:', error)
            showToast('Error al actualizar el estado del tipo de sala', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const roomType = roomTypes.value.find(t => t.id === id)
        const name = roomType?.label ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar este tipo de sala: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/room-types/${id}/delete`)
            showToast('Tipo de sala eliminado con éxito', 'success')
            await fetchRoomTypes()
            return true
        } catch (error: any) {
            console.error('Error eliminando tipo de sala:', error)
            showToast(error?.response?.data?.message ?? 'Error al eliminar el tipo de sala', 'error')
            return false
        }
    }

    return {
        roomTypeData,
        roomTypes,
        setInitialRoomTypes,
        submit,
        create,
        edit,
        remove,
        toggleStatus,
        fetchRoomTypes
    }
}
