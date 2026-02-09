import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { ExchangeRate } from '@/js/types/exchange-rate'

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
    edit: (id: string | number) => Promise<boolean>

    /** Cambia el estado de una tasa */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Elimina una tasa */
    remove: (id: string | number) => Promise<boolean>

    /** Sincroniza tasas desde backend */
    updateExchangeRates: () => Promise<boolean>

    /** Obtiene y actualiza la lista filtrada de tasas desde backend */
    fetchExchangeRates: () => Promise<boolean>
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
    async function fetchExchangeRates(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: ExchangeRate[]
            }>('/exchange-rate/list-filtered', {
                params: { token: tokenId }
            })

            if (Array.isArray(response.data?.data)) {
                exchangeRates.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener las tasas de cambio:', error)
            showToast('Error al cargar las tasas de cambio', 'error')
            return false
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
                const { data } = await backendClient.put(
                    `/exchange-rate/${exchangeRateData.value.id}`,
                    exchangeRateData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/exchange-rate',
                    exchangeRateData.value
                )
                showToast(data.message, 'success')
            }

            await fetchExchangeRates()
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
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: ExchangeRate
            }>(`/exchange-rate/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró la tasa de cambio')
            }

            const data = response.data.data

            exchangeRateData.value = {
                id: data.id,
                currency_id: data.currency_id,
                amount: Number(data.amount),
                status: data.status,
                token: tokenId || undefined
            }

            return true
        } catch (error) {
            console.error('Error cargando tasa:', error)
            showToast('Error al cargar la tasa de cambio', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Altenar el estado (activo/inactivo) de una tasa
     *
     * @param id Identificador de la tasa
     */
    async function toggleStatus(id: string | number): Promise<boolean> {
        const exchangeRate = exchangeRates.value.find(c => c.id === id)
        const name = exchangeRate?.currency_name ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de esta tasa de cambio: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/exchange-rate/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchExchangeRates()
            return true
        } catch (error) {
            console.error('Error cambiando estado:', error)
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Elimina una tasa de cambio (soft delete)
     *
     * @param id Identificador de la tasa
     */
    async function remove(id: string | number): Promise<boolean> {
        const exchangeRate = exchangeRates.value.find(c => c.id === id)
        const name = exchangeRate?.currency_name ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar esta tasa de cambio: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/exchange-rate/${id}/delete`)
            showToast('Tasa de cambio eliminada con éxito', 'success')
            await fetchExchangeRates()
            return true
        } catch (error) {
            console.error('Error eliminando tasa:', error)
            showToast('Error al eliminar la tasa', 'error')
            return false
        }
    }

    /**
     * Sincroniza todas las tasas de cambio desde backend.
     *
     * Muestra un SweetAlert con las tasas actuales obtenidas,
     * y solicita confirmación antes de proceder con la actualización masiva.
     *
     * @returns {Promise<boolean>}
     */
    async function updateExchangeRates(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: Record<string, number>
            }>('/exchange-rate/raw-rates')

            if (!response.data?.data) {
                throw new Error('No se recibieron tasas para mostrar')
            }

            const rates = response.data.data

            const ratesHtml = Object.entries(rates)
                .map(([currency, value]) => `<li><strong>${currency}:</strong> ${value}</li>`)
                .join('')

            const confirmed = await showToastConfirmAction({
                html: `<ul style="text-align: left;">${ratesHtml}</ul><strong>¿Deseas actualizar con estas tasas?</strong>`,
                icon: 'info',
                confirmButtonText: 'Sí, actualizar',
                title: 'Tasas de cambio obtenidas'
            })

            if (!confirmed) return false

            await backendClient.put('/exchange-rate/update-all', { rates })
            showToast('Tasas de cambio actualizadas con éxito', 'success')
            await fetchExchangeRates()

            return true
        } catch (error) {
            console.error('Error actualizando tasas:', error)
            showToast('Error al actualizar las tasas', 'error')
            return false
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
        toggleStatus,
        remove,
        updateExchangeRates,
        fetchExchangeRates
    }
}
