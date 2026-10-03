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

    /** Cambia el estado de una tasa (1 confirmación) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Cambia el estado de varias tasas con UNA sola confirmación agregada. Devuelve la cantidad actualizada con éxito. */
    toggleStatusMany: (ids: (string | number)[]) => Promise<number>

    /** Elimina una tasa (1 confirmación) */
    remove: (id: string | number) => Promise<boolean>

    /** Elimina varias tasas con UNA sola confirmación agregada. Devuelve la cantidad eliminada con éxito. */
    removeMany: (ids: (string | number)[]) => Promise<number>

    /** Sincroniza tasas desde backend */
    updateExchangeRates: () => Promise<boolean>

    /** Obtiene y actualiza la lista filtrada de tasas desde backend */
    fetchExchangeRates: () => Promise<boolean>

    /** Maneja el cambio del switch de actualización automática */
    toggleAutoUpdate: (enabled: boolean) => Promise<boolean>

    /** Consulta la tasa oficial USD/VES publicada por el BCV */
    fetchBcvRate: () => Promise<number | null>

    /** Maneja el cambio del switch de actualización automática vía BCV (solo VES) */
    toggleBcvAutoUpdate: (enabled: boolean) => Promise<boolean>

    /** Consulta el estado de auto-update de una moneda individual (control independiente del switch global) */
    fetchCurrencyAutoUpdateStatus: (isoCode: string) => Promise<boolean>

    /** Maneja el cambio del switch de actualización automática de una moneda individual (todas excepto VES) */
    toggleCurrencyAutoUpdate: (isoCode: string, enabled: boolean) => Promise<boolean>
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
            }>('/exchange-rate/fetch-list', {
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

            const payload: Record<string, unknown> = { ...exchangeRateData.value }

            if (exchangeRateData.value.id) {
                const { data } = await backendClient.put(
                    `/exchange-rate/${exchangeRateData.value.id}`,
                    payload
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/exchange-rate',
                    payload
                )
                showToast(data.message, 'success')
            }

            await fetchExchangeRates()
            return true
        } catch (error: any) {
            // ExchangeRateRequest::validateAmountJump() rechaza (422) un
            // cambio >50% respecto al valor vigente salvo que el request
            // traiga confirm_large_jump=true — se detecta por el mensaje
            // específico del error de 'amount' (no hay un código de error
            // dedicado en el shape estándar de errores 422 del backend) y
            // se ofrece confirmar y reenviar en vez de solo mostrar el error.
            const amountError = error?.response?.data?.errors?.amount?.[0] as string | undefined

            if (amountError?.includes('difiere') && !loading.value) {
                loading.value = false

                const confirm = await showToastConfirmAction({
                    html: `${amountError}<br><br>¿Confirmas que el valor es correcto?`,
                    icon: 'warning',
                    confirmButtonText: 'Sí, es correcto',
                })

                if (confirm) {
                    exchangeRateData.value = { ...exchangeRateData.value, confirm_large_jump: true } as Partial<ExchangeRate>
                    return submit()
                }

                return false
            }

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
                status: 'status' in data ? data.status : 'ACTIVE',
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
     * PUT /exchange-rate/{id}/status sin confirmación ni refetch — bloque
     * interno reutilizado por toggleStatus() (1 tasa, con confirmación
     * propia) y toggleStatusMany() (lote, una sola confirmación agregada).
     */
    async function toggleStatusRequest(id: string | number): Promise<boolean> {
        try {
            await backendClient.put(`/exchange-rate/${id}/status`)
            return true
        } catch (error) {
            console.error('Error cambiando estado:', error)
            return false
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

        const ok = await toggleStatusRequest(id)
        showToast(ok ? 'Estado cambiado con éxito' : 'Error al cambiar el estado', ok ? 'success' : 'error')
        if (ok) await fetchExchangeRates()
        return ok
    }

    /**
     * Alterna el estado de varias tasas con UNA sola confirmación (no una
     * por fila) — usado por la acción en lote de la tabla. Antes reusaba
     * toggleStatus() en un loop, que abría un diálogo de confirmación por
     * cada tasa seleccionada.
     *
     * @param ids Identificadores de las tasas seleccionadas
     * @returns Cantidad de tasas actualizadas con éxito
     */
    async function toggleStatusMany(ids: (string | number)[]): Promise<number> {
        if (!ids.length) return 0

        const confirm = await showToastConfirmAction({
            html: ids.length === 1
                ? '¿Quieres cambiar el estado de esta tasa de cambio?'
                : `¿Quieres cambiar el estado de <b>${ids.length}</b> tasas de cambio?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return 0

        const results = await Promise.all(ids.map(toggleStatusRequest))
        const successCount = results.filter(Boolean).length

        if (successCount > 0) {
            showToast(
                successCount === ids.length
                    ? 'Estado cambiado con éxito'
                    : `Estado cambiado para ${successCount} de ${ids.length} tasas`,
                'success'
            )
            await fetchExchangeRates()
        } else {
            showToast('Error al cambiar el estado', 'error')
        }

        return successCount
    }

    /**
     * DELETE /exchange-rate/{id}/delete sin confirmación ni refetch — bloque
     * interno reutilizado por remove() (1 tasa, con confirmación propia) y
     * removeMany() (lote, una sola confirmación agregada).
     */
    async function removeRequest(id: string | number): Promise<boolean> {
        try {
            await backendClient.put(`/exchange-rate/${id}/delete`)
            return true
        } catch (error) {
            console.error('Error eliminando tasa:', error)
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

        const ok = await removeRequest(id)
        showToast(ok ? 'Tasa de cambio eliminada con éxito' : 'Error al eliminar la tasa', ok ? 'success' : 'error')
        if (ok) await fetchExchangeRates()
        return ok
    }

    /**
     * Elimina varias tasas con UNA sola confirmación (no una por fila) —
     * usado por la acción en lote de la tabla.
     *
     * @param ids Identificadores de las tasas seleccionadas
     * @returns Cantidad de tasas eliminadas con éxito
     */
    async function removeMany(ids: (string | number)[]): Promise<number> {
        if (!ids.length) return 0

        const confirm = await showToastConfirmAction({
            html: ids.length === 1
                ? '¿Estás seguro que deseas eliminar esta tasa de cambio?'
                : `¿Estás seguro que deseas eliminar <b>${ids.length}</b> tasas de cambio?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return 0

        const results = await Promise.all(ids.map(removeRequest))
        const successCount = results.filter(Boolean).length

        if (successCount > 0) {
            showToast(
                successCount === ids.length
                    ? 'Tasa(s) de cambio eliminada(s) con éxito'
                    : `Se eliminaron ${successCount} de ${ids.length} tasas`,
                'success'
            )
            await fetchExchangeRates()
        } else {
            showToast('Error al eliminar la tasa', 'error')
        }

        return successCount
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

            if (!response.data?.data || Object.keys(response.data.data).length === 0) {
                throw new Error('No hay tasas disponibles. Verifique que tenga monedas activas configuradas o que el proveedor de tasas esté disponible.')
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

    /**
     * Maneja el cambio del switch de actualización automática
     * Configura o detiene el cronjob según el estado
     *
     * @param enabled Estado del switch (true para activar, false para desactivar)
     * @returns {Promise<boolean>}
     */
    async function toggleAutoUpdate(enabled: boolean): Promise<boolean> {
        try {
            const endpoint = enabled ? '/exchange-rate/cronjob/enable' : '/exchange-rate/cronjob/disable'
            
            const response = await backendClient.post(endpoint)
            
            showToast(response.data.message, 'success')
            return true
        } catch (error: any) {
            console.error('Error al cambiar estado del cronjob:', error)
            showToast(error?.response?.data?.message || 'Error al cambiar estado del cronjob', 'error')
            return false
        }
    }

    /**
     * Consulta la tasa oficial USD/VES publicada por el BCV.
     *
     * @returns {Promise<number | null>} Tasa obtenida o null si falló la consulta.
     */
    async function fetchBcvRate(): Promise<number | null> {
        try {
            const response = await backendClient.get<{
                success: boolean
                data: { rate: number }
            }>('/exchange-rate/bcv-rate')

            return response.data?.data?.rate ?? null
        } catch (error: any) {
            console.error('Error al consultar la tasa BCV:', error)
            showToast(error?.response?.data?.message ?? 'Error al consultar la tasa del BCV', 'error')
            return null
        }
    }

    /**
     * Maneja el cambio del switch de actualización automática vía BCV (solo VES)
     *
     * @param enabled Estado del switch (true para activar, false para desactivar)
     * @returns {Promise<boolean>}
     */
    async function toggleBcvAutoUpdate(enabled: boolean): Promise<boolean> {
        try {
            const endpoint = enabled ? '/exchange-rate/bcv-cronjob/enable' : '/exchange-rate/bcv-cronjob/disable'

            const response = await backendClient.post(endpoint)

            showToast(response.data.message, 'success')
            return true
        } catch (error: any) {
            console.error('Error al cambiar estado del cronjob BCV:', error)
            showToast(error?.response?.data?.message || 'Error al cambiar estado del cronjob BCV', 'error')
            return false
        }
    }

    /**
     * Consulta el estado de auto-update de una moneda individual.
     *
     * @param isoCode Código ISO de la moneda (ej. 'COP', 'BRL')
     */
    async function fetchCurrencyAutoUpdateStatus(isoCode: string): Promise<boolean> {
        try {
            const response = await backendClient.get<{ enabled: boolean }>(
                `/exchange-rate/currency-cronjob/${isoCode}/status`
            )
            return response.data?.enabled ?? false
        } catch (error) {
            console.error('Error al consultar el estado de auto-update de la moneda:', error)
            return false
        }
    }

    /**
     * Maneja el cambio del switch de actualización automática de una moneda
     * individual (todas excepto VES, que usa toggleBcvAutoUpdate con la
     * fuente oficial del BCV) — control independiente del switch global.
     *
     * @param isoCode Código ISO de la moneda (ej. 'COP', 'BRL')
     * @param enabled Estado del switch (true para activar, false para desactivar)
     */
    async function toggleCurrencyAutoUpdate(isoCode: string, enabled: boolean): Promise<boolean> {
        try {
            const endpoint = enabled
                ? `/exchange-rate/currency-cronjob/${isoCode}/enable`
                : `/exchange-rate/currency-cronjob/${isoCode}/disable`

            const response = await backendClient.post(endpoint)

            showToast(response.data.message, 'success')
            return true
        } catch (error: any) {
            console.error('Error al cambiar estado del auto-update de la moneda:', error)
            showToast(error?.response?.data?.message || 'Error al cambiar el estado de la actualización automática', 'error')
            return false
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
        toggleStatusMany,
        remove,
        removeMany,
        updateExchangeRates,
        fetchExchangeRates,
        toggleAutoUpdate,
        fetchBcvRate,
        toggleBcvAutoUpdate,
        fetchCurrencyAutoUpdateStatus,
        toggleCurrencyAutoUpdate,
    }
}
