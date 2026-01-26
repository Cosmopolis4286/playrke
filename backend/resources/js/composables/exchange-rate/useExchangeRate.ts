import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { ExchangeRate } from '@/js/types/exchange-rate'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

/**
 * Contrato público del composable useExchangeRate
 */
interface UseExchangeRateReturn {
    /** Datos de la tasa (para crear o editar) */
    exchangeRateData: Ref<Partial<ExchangeRate>>

    /** Lista reactiva de tasas de cambio */
    exchangeRates: Ref<ExchangeRate[]>

    /** Inicializa la lista local de tasas */
    setInitialExchangeRates: (initialRates: ExchangeRate[]) => void

    /**
     * Envía el formulario de tasa de cambio.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<void>

    /** Cambia el estado de una tasa */
    changeStatus: (id: string | number) => Promise<void>

    /** Elimina una tasa */
    remove: (id: string | number) => Promise<void>

    /** Sincroniza tasas desde backend */
    updateExchangeRates: () => Promise<void>

    /** Obtiene y actualiza la lista filtrada de tasas desde backend */
    fetchExchangeRates: () => Promise<void>
}

/**
 * Composable para manejar tasas de cambio (Exchange Rates).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Sincronización masiva desde backend
 * - Gestión local de la lista de tasas
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 *
 * @param {string} tokenId Token opcional del usuario autenticado
 * @returns {UseExchangeRateReturn}
 */
export function useExchangeRate(tokenId: string = ''): UseExchangeRateReturn {
    /** Estado de carga global del composable */
    const loading = ref(false)

    /** Datos reactivos del formulario (crear / editar) */
    const exchangeRateData = ref<Partial<ExchangeRate>>({})

    /** Lista reactiva local de tasas de cambio */
    const exchangeRates = ref<ExchangeRate[]>([])

    /**
     * Inicializa la lista local de tasas
     * @param initialRates Lista inicial para sincronizar estado local
     */
    function setInitialExchangeRates(initialRates: ExchangeRate[]) {
        exchangeRates.value = [...initialRates]
    }

    /**
     * Obtiene y actualiza la lista filtrada de tasas desde backend.
     */
    async function fetchExchangeRates(): Promise<void> {
        if (loading.value) return

        try {
            loading.value = true
            const response = await backendClient.get<{ success: boolean; data: ExchangeRate[] }>('/exchange-rate/list-filtered', {
                params: { token: tokenId }
            })
            const data = response.data?.data
            if (Array.isArray(data)) {
                exchangeRates.value = data
            }
        } catch (error) {
            console.error('Error al obtener las tasas de cambio:', error)
        } finally {
            loading.value = false
        }
    }

    /**
     * Envía el formulario de tasa de cambio.
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

            if (!exchangeRateData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (exchangeRateData.value.id) {
                // Editar tasa existente
                const { data } = await backendClient.put(`/exchange-rate/${exchangeRateData.value.id}`, exchangeRateData.value)
                showToast(data.message, data.success ? 'success' : 'error')
            } else {
                // Crear nueva tasa
                const { data } = await backendClient.post('/exchange-rate', exchangeRateData.value)
                showToast(data.message, data.success ? 'success' : 'error')
            }

            loading.value = false

            // Actualizar lista tras operación exitosa sin recargar página
            await fetchExchangeRates()

            return true
        } catch (error) {
            console.error('Error en submit:', error)
            return false
        }
    }

    /**
     * Inicializa el formulario en modo creación
     * y abre el modal con valores por defecto.
     */
    function create(): void {
        exchangeRateData.value = {
            currency_id: '',
            amount: 0,
            status: 'INACTIVE',
            token: tokenId || undefined
        }
    }

    /**
     * Carga una tasa existente desde backend
     * e inicializa el formulario en modo edición.
     *
     * @param {string | number} id Identificador de la tasa
     */
    async function edit(id: string | number): Promise<void> {
        if (loading.value) return

        try {
            loading.value = true
            const response = await backendClient.get<{ success: boolean; data: ExchangeRate }>(`/exchange-rate/${id}`)
            const data = response.data?.data

            if (!data) {
                throw new Error('No se encontró la tasa de cambio')
            }

            exchangeRateData.value = {
                id: data.id,
                currency_id: data.currency_id,
                amount: Number(data.amount),
                status: data.status,
                token: tokenId || undefined
            }
        } catch (error) {
            console.error('Error cargando tasa:', error)
        } finally {
            loading.value = false
        }
    }

    /**
     * Cambia el estado (activo/inactivo) de una tasa
     *
     * @param id Identificador de la tasa
     */
    async function changeStatus(id: string | number): Promise<void> {
        const confirm = await showToastConfirmAction(
            '¿Quieres cambiar el estado de esta tasa de cambio?',
            'warning',
            'Sí, cambiar estado'
        )
        if (!confirm) return

        try {
            await backendClient.put(`/exchange-rate/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchExchangeRates()
        } catch (error) {
            console.error('Error cambiando estado:', error)
            showToast('Error al cambiar el estado', 'error')
        }
    }

    /**
     * Elimina una tasa de cambio (soft delete)
     *
     * @param id Identificador de la tasa
     */
    async function remove(id: string | number): Promise<void> {
        const confirm = await showToastConfirmAction(
            '¿Estás seguro que deseas eliminar esta tasa de cambio?',
            'warning',
            'Sí, eliminar'
        )
        if (!confirm) return

        try {
            await backendClient.put(`/exchange-rate/${id}/delete`)
            showToast('Tasa de cambio eliminada con éxito', 'success')
            await fetchExchangeRates()
        } catch (error) {
            console.error('Error eliminando tasa:', error)
            showToast('Error al eliminar la tasa', 'error')
        }
    }

    /**
     * Sincroniza todas las tasas de cambio desde backend.
     * Usado para actualización masiva.
     */
    async function updateExchangeRates(): Promise<void> {
        if (loading.value) return

        try {
            loading.value = true
            await backendClient.put('/exchange-rate/update-all')

            showToast('Tasas de cambio actualizadas con éxito', 'success')
            await fetchExchangeRates()
        } catch (error) {
            console.error('Error actualizando tasas:', error)
            showToast('Error al actualizar las tasas', 'error')
        } finally {
            loading.value = false
        }
    }

    return {
        exchangeRateData,
        exchangeRates,
        setInitialExchangeRates,
        submit,
        create,
        edit,
        changeStatus,
        remove,
        updateExchangeRates,
        fetchExchangeRates
    }
}
