import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'

interface ExchangeRate {
    id?: string | number
    currency: string
    exchange_rate: number
    token?: string
}

/**
 * Contrato público del composable useExchangeRate
 */
interface UseExchangeRateReturn {
    /** Estado global de carga (submit, fetch, etc.) */
    loading: Ref<boolean>

    /** Controla la visualización del modal */
    showModalForm: Ref<boolean>

    /** Controla el titulo del modal */
    modalTitle: Ref<string>

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
    changeStatus: (id: string | number) => void

    /** Elimina una tasa */
    remove: (id: string | number) => void

    /** Sincroniza tasas desde backend */
    updateExchangeRates: () => Promise<void>
}

/**
 * Composable para manejar operaciones sobre tasas de cambio.
 *
 * Responsabilidades:
 * - Control de estado del modal
 * - Submit del formulario (POST / PUT)
 * - Operaciones CRUD
 * - Estados de carga
 *
 * ⚠️ No controla UI directamente (el modal decide cerrar)
 *
 * @param tokenId Token del usuario autenticado (opcional)
 * @returns {UseExchangeRateReturn}
 */
export function useExchangeRate(tokenId: string = ''): UseExchangeRateReturn {
    const loading = ref<boolean>(false)
    const modalTitle = ref<string>('')
    const showModalForm = ref<boolean>(false)

    // Estado reactivo para datos del formulario (nuevo o editar)
    const exchangeRateData = ref<Partial<ExchangeRate>>({})

    /**
     * Envía el formulario de tasa de cambio.
     * Hace POST si no existe id, PUT si edita.
     *
     * @returns {Promise<boolean>}
     * - true  → operación exitosa (cerrar modal)
     * - false → error (mantener modal abierto)
     */
    async function submit(): Promise<boolean> {
        loading.value = true
        try {
            if (!exchangeRateData.value) throw new Error('No hay datos para enviar')

            if (exchangeRateData.value.id) {
                // Editar - PUT /exchange-rate/{id}
                await backendClient.put(
                    `/exchange-rate/${exchangeRateData.value.id}`,
                    exchangeRateData.value
                )
            } else {
                // Crear - POST /exchange-rate
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
     * Abre el modal para crear una nueva tasa con datos vacíos
     */
    function create(): void {
        modalTitle.value = "Registar Tasa de Cambio"
        exchangeRateData.value = {
            currency: '',
            exchange_rate: 0,
        }

        if (tokenId) {
            exchangeRateData.value.token = tokenId
        }

        showModalForm.value = true
    }

    /**
     * Abre el modal para editar una tasa existente
     * Carga datos desde backend y asigna a exchangeRateData
     *
     * @param id Identificador de la tasa
     */
    async function edit(id: string | number): Promise<void> {
        loading.value = true
        try {
            const response = await backendClient.get(`/exchange-rate/${id}`)
            exchangeRateData.value = response.data
            modalTitle.value = "Editar Tasa de Cambio"
            showModalForm.value = true
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
    function changeStatus(id: string | number): void {
        console.log('Cambiar status:', id)
        // Puedes implementar aquí llamada PUT a /exchange-rate/{id}/status
    }

    /**
     * Elimina una tasa de cambio
     *
     * @param id Identificador de la tasa
     */
    function remove(id: string | number): void {
        console.log('Eliminar tasa de cambio:', id)
        // Puedes implementar llamada DELETE a /exchange-rate/{id}
    }

    /**
     * Actualiza tasas de cambio desde backend
     */
    async function updateExchangeRates(): Promise<void> {
        loading.value = true

        try {
            const response = await backendClient.get('/exchange-rate/getExchangeRates')
            const data = response.data

            alert(data.message)

            if (data.type === 'success') {
                window.location.reload()
            }
        } catch (error) {
            console.error('Error actualizando tasas:', error)
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        modalTitle,
        showModalForm,
        exchangeRateData,
        submit,
        create,
        edit,
        changeStatus,
        remove,
        updateExchangeRates,
    }
}
