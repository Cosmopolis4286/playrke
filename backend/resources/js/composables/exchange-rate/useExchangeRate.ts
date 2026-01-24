import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { ExchangeRate } from '@/js/types/exchange-rate'

/**
 * Contrato público del composable useExchangeRate
 */
interface UseExchangeRateReturn {
    /** Datos de la tasa (para crear o editar) */
    exchangeRateData: Ref<Partial<ExchangeRate>>

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
}

/**
 * Composable para manejar tasas de cambio (Exchange Rates).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Sincronización masiva desde backend
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
                await backendClient.put(
                    `/exchange-rate/${exchangeRateData.value.id}`,
                    exchangeRateData.value
                )
            } else {
                // Crear nueva tasa
                await backendClient.post('/exchange-rate', exchangeRateData.value)
            }

            return true
        } catch (error) {
            console.error('Error en submit:', error)
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
            token: tokenId || undefined,
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
            const response = await backendClient.get<{ success: boolean; data: ExchangeRate }>(
                `/exchange-rate/${id}`
            )

            // Acceso seguro a los datos dentro de la respuesta
            const data = response.data?.data

            if (!data) {
                throw new Error('No se encontró la tasa de cambio')
            }

            // Normalización de datos para el formulario
            exchangeRateData.value = {
                id: data.id,
                currency_id: data.currency_id,
                amount: Number(data.amount),
                status: data.status,
                token: tokenId || undefined,
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
        try {
            // Implementar llamada PUT para cambiar estado
            await backendClient.put(`/exchange-rate/${id}/status`)
        } catch (error) {
            console.error('Error cambiando estado:', error)
        }
    }

    /**
     * Elimina una tasa de cambio (soft delete)
     *
     * @param id Identificador de la tasa
     */
    async function remove(id: string | number): Promise<void> {
        try {
            await backendClient.put(`/exchange-rate/${id}/delete`)
        } catch (error) {
            console.error('Error eliminando tasa:', error)
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
            window.location.reload()
        } catch (error) {
            console.error('Error actualizando tasas:', error)
        } finally {
            loading.value = false
        }
    }

    return {
        exchangeRateData,
        submit,
        create,
        edit,
        changeStatus,
        remove,
        updateExchangeRates,
    }
}
