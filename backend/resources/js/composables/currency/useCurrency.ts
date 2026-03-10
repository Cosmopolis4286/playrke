import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { Currency } from '@/js/types/currency'

/**
 * Contrato público del composable useCurrency
 */
interface UseCurrencyReturn {
    /** Datos de la moneda (para crear o editar) */
    currencyData: Ref<Partial<Currency>>

    /** Lista reactiva de monedas */
    currencies: Ref<Currency[]>

    /** Inicializa la lista local de monedas */
    setInitialCurrencies: (initialCurrencies: Currency[]) => void

    /**
     * Envía el formulario de moneda.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina una moneda */
    remove: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de monedas desde backend */
    fetchCurrencies: () => Promise<boolean>
}

/**
 * Composable para manejar monedas (Currencies).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de monedas
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 *
 * @param {string} tokenId Token opcional del usuario autenticado
 * @returns {UseCurrencyReturn}
 */
export function useCurrency(tokenId: string = ''): UseCurrencyReturn {
    /** Estado de carga global del composable */
    const loading = ref(false)

    /** Datos reactivos del formulario (crear / editar) */
    const currencyData = ref<Partial<Currency>>({})

    /** Lista reactiva local de monedas */
    const currencies = ref<Currency[]>([])

    /**
     * Inicializa la lista local de monedas
     * @param initialCurrencies Lista inicial para sincronizar estado local
     */
    function setInitialCurrencies(initialCurrencies: Currency[]) {
        currencies.value = [...initialCurrencies]
    }

    /**
     * Obtiene y actualiza la lista de monedas desde backend.
     */
    async function fetchCurrencies(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: Currency[]
            }>('/currency/fetch-list', {
                params: { token: tokenId }
            })

            if (Array.isArray(response.data?.data)) {
                currencies.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener las monedas:', error)
            showToast('Error al cargar las monedas', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Envía el formulario de moneda.
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

            if (!currencyData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (currencyData.value.id) {
                const { data } = await backendClient.put(
                    `/currency/${currencyData.value.id}`,
                    currencyData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/currency',
                    currencyData.value
                )
                showToast(data.message, 'success')
            }

            await fetchCurrencies()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar la moneda', 'error')
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
        currencyData.value = {
            iso_code: '',
            symbol: null,
            description: null,
            name: '',
            type: 'FIAT',
            country_id: null,
            decimal_places: 2,
            status: 'ACTIVE'
        }
    }

    /**
     * Carga una moneda existente desde backend
     * e inicializa el formulario en modo edición.
     *
     * @param {string | number} id Identificador de la moneda
     */
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: Currency
            }>(`/currency/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró la moneda')
            }

            const data = response.data.data

            currencyData.value = {
                id: data.id,
                iso_code: data.iso_code,
                symbol: data.symbol,
                description: data.description,
                name: data.name,
                type: data.type,
                country_id: data.country_id,
                decimal_places: data.decimal_places,
                status: data.status
            }

            return true
        } catch (error) {
            console.error('Error cargando moneda:', error)
            showToast('Error al cargar la moneda', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Elimina una moneda (soft delete)
     *
     * @param id Identificador de la moneda
     */
    async function remove(id: string | number): Promise<boolean> {
        const currency = currencies.value.find(c => c.id === id)
        const name = currency?.name ?? ''

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar esta moneda: <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/currency/${id}/delete`)
            showToast('Moneda eliminada con éxito', 'success')
            await fetchCurrencies()
            return true
        } catch (error) {
            console.error('Error eliminando moneda:', error)
            showToast('Error al eliminar la moneda', 'error')
            return false
        }
    }

    return {
        currencyData,
        currencies,
        setInitialCurrencies,
        submit,
        create,
        edit,
        remove,
        fetchCurrencies
    }
}
