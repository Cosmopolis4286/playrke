import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Commerce } from '@/js/types/roles/commerce/commerce'

/**
 * Contrato público del composable useAdminCommerce
 */
interface UseAdminCommerceReturn {
    /** Datos del comercio (para crear o editar) */
    commerceData: Ref<Partial<Commerce>>

    /** Lista reactiva de comercializadoras */
    commerces: Ref<Commerce[]>

    /** Inicializa la lista local de comercializadoras */
    setInitialCommerces: (initialRates: Commerce[]) => void

    /**
     * Envía el formulario de comercio.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Cambia el estado de una tasa */
    changeStatus: (id: string | number) => Promise<boolean>

    /** Elimina una tasa */
    remove: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista filtrada de comercializadoras desde backend */
    fetchCommerces: () => Promise<boolean>
}

/**
 * Composable para manejar comercializadoras (Exchange Rates).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Sincronización masiva desde backend
 * - Gestión local de la lista de comercializadoras
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 *
 * @param {string} tokenId Token opcional del usuario autenticado
 * @returns {UseAdminCommerceReturn}
 */
export function useAdminCommerce(tokenId: string = ''): UseAdminCommerceReturn {
    /** Estado de carga global del composable */
    const loading = ref(false)

    /** Datos reactivos del formulario (crear / editar) */
    const commerceData = ref<Partial<Commerce>>({})

    /** Lista reactiva local de comercializadoras */
    const commerces = ref<Commerce[]>([])

    /**
     * Inicializa la lista local de comercializadoras
     * @param initialRates Lista inicial para sincronizar estado local
     */
    function setInitialCommerces(initialRates: Commerce[]) {
        commerces.value = [...initialRates]
    }

    /**
     * Obtiene y actualiza la lista filtrada de comercializadoras desde backend.
     */
    async function fetchCommerces(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: Commerce[]
            }>('/admin/commerce/list-filtered', {
                params: { token: tokenId }
            })

            if (Array.isArray(response.data?.data)) {
                commerces.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener las comercializadoras:', error)
            showToast('Error al cargar las comercializadoras', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Envía el formulario de comercio.
     * - POST si no existe id
     * - PUT si existe id
     *
     * @returns {Promise<boolean>}
     * - true  → operación exitosa (cerrar modal)
     * - false → error (mantener modal abierto)
     */
    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!commerceData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (commerceData.value.id) {
                const { data } = await backendClient.put(
                    `/admin/commerce/${commerceData.value.id}`,
                    commerceData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/exchange-rate',
                    commerceData.value
                )
                showToast(data.message, 'success')
            }

            await fetchCommerces()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar la tasa', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Inicializa el formulario en modo creación
     * y abre el modal con valores por defecto.
     */
    function create(): void {
        // commerceData.value = {
        //     currency_id: '',
        //     amount: 0,
        //     status: 'INACTIVE',
        //     token: tokenId || undefined
        // }
    }

    /**
     * Carga una tasa existente desde backend
     * e inicializa el formulario en modo edición.
     *
     * @param {string | number} id Identificador de la tasa
     */
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: Commerce
            }>(`/admin/commerce/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró la comercio')
            }

            const data = response.data.data

            // commerceData.value = {
            //     id: data.id,
            //     currency_id: data.currency_id,
            //     amount: Number(data.amount),
            //     status: data.status,
            //     token: tokenId || undefined
            // }

            return true
        } catch (error) {
            console.error('Error cargando tasa:', error)
            showToast('Error al cargar la comercio', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Cambia el estado (activo/inactivo) de una tasa
     *
     * @param id Identificador de la tasa
     */
    async function changeStatus(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            text: '¿Quieres cambiar el estado de esta comercio?',
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/commerce/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchCommerces()
            return true
        } catch (error) {
            console.error('Error cambiando estado:', error)
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Elimina una comercio (soft delete)
     *
     * @param id Identificador de la tasa
     */
    async function remove(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            text: '¿Estás seguro que deseas eliminar esta comercio?',
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/commerce/${id}/delete`)
            showToast('Tasa de cambio eliminada con éxito', 'success')
            await fetchCommerces()
            return true
        } catch (error) {
            console.error('Error eliminando tasa:', error)
            showToast('Error al eliminar la tasa', 'error')
            return false
        }
    }

    return {
        commerceData,
        commerces,
        setInitialCommerces,
        submit,
        create,
        edit,
        changeStatus,
        remove,
        fetchCommerces
    }
}
