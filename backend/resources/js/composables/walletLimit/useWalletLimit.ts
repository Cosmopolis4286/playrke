import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'

import type { WalletLimit } from '@/js/types/walletLimit'

/**
 * Contrato público del composable useWalletLimit
 */
interface UseWalletLimitReturn {
    /** Datos del límite (para crear o editar) */
    walletLimitData: Ref<Partial<WalletLimit>>

    /** Lista reactiva de límites */
    walletLimits: Ref<WalletLimit[]>

    /** Inicializa la lista local de límites */
    setInitialWalletLimits: (initial: WalletLimit[]) => void

    /**
     * Envía el formulario de límite.
     * Debe retornar:
     * - true  → el modal se cierra
     * - false → el modal permanece abierto
     */
    submit: () => Promise<boolean>

    /** Abre el modal en modo creación */
    create: () => void

    /** Abre el modal en modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Elimina un límite */
    remove: (id: string | number) => Promise<boolean>

    /** Alterna el status de un límite (activo/inactivo) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Obtiene y actualiza la lista de límites desde backend */
    fetchWalletLimits: () => Promise<boolean>
}

/**
 * Composable para manejar el catálogo de límites de billetera (WalletLimit).
 *
 * Responsabilidades:
 * - Control del modal (crear / editar)
 * - Manejo del estado del formulario
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista de límites
 *
 * ⚠️ No controla UI directamente (el componente decide cerrar el modal)
 */
export function useWalletLimit(): UseWalletLimitReturn {
    const loading = ref(false)

    const walletLimitData = ref<Partial<WalletLimit>>({})

    const walletLimits = ref<WalletLimit[]>([])

    function setInitialWalletLimits(initial: WalletLimit[]) {
        walletLimits.value = [...initial]
    }

    async function fetchWalletLimits(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: WalletLimit[]
            }>('/wallet-limits')

            if (Array.isArray(response.data?.data)) {
                walletLimits.value = response.data.data
            }

            return true
        } catch (error) {
            console.error('Error al obtener los límites de billetera:', error)
            showToast('Error al cargar los límites de billetera', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            if (!walletLimitData.value) {
                throw new Error('No hay datos para enviar')
            }

            if (walletLimitData.value.id) {
                const { data } = await backendClient.put(
                    `/wallet-limits/${walletLimitData.value.id}`,
                    walletLimitData.value
                )
                showToast(data.message, 'success')
            } else {
                const { data } = await backendClient.post(
                    '/wallet-limits',
                    walletLimitData.value
                )
                showToast(data.message, 'success')
            }

            await fetchWalletLimits()
            return true
        } catch (error: any) {
            console.error('Error en submit:', error)
            showToast(error?.response?.data?.message ?? 'Error al guardar los límites de billetera', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        walletLimitData.value = {
            currency_id: null,
            kyc_level: 0,
            min_deposit: 1,
            max_deposit: 100,
            min_per_transaction: 1,
            max_per_transaction: 50,
            max_daily: 50,
            max_weekly: 100,
            max_monthly: 200,
            is_active: true
        }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const response = await backendClient.get<{
                success: boolean
                data: WalletLimit
            }>(`/wallet-limits/${id}`)

            if (!response.data?.data) {
                throw new Error('No se encontró la configuración de límites')
            }

            const data = response.data.data

            walletLimitData.value = {
                id: data.id,
                currency_id: data.currency_id,
                kyc_level: data.kyc_level,
                min_deposit: data.min_deposit,
                max_deposit: data.max_deposit,
                min_per_transaction: data.min_per_transaction,
                max_per_transaction: data.max_per_transaction,
                max_daily: data.max_daily,
                max_weekly: data.max_weekly,
                max_monthly: data.max_monthly,
                is_active: data.is_active
            }

            return true
        } catch (error) {
            console.error('Error cargando límites de billetera:', error)
            showToast('Error al cargar la configuración de límites', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const walletLimit = walletLimits.value.find(l => l.id === id)
        const currentStatus = walletLimit?.is_active ? 'activa' : 'inactiva'
        const nextStatus = walletLimit?.is_active ? 'inactiva' : 'activa'

        const confirm = await showToastConfirmAction({
            html: `Esta configuración de límites está actualmente <b>${currentStatus}</b>. ¿Deseas marcarla como <b>${nextStatus}</b>?`,
            icon: 'question',
            confirmButtonText: 'Sí, alternar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/wallet-limits/${id}/status`)
            showToast(data.message, 'success')
            await fetchWalletLimits()
            return true
        } catch (error) {
            console.error('Error alternando status:', error)
            showToast('Error al actualizar el estado de los límites', 'error')
            return false
        }
    }

    async function remove(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: '¿Estás seguro que deseas eliminar esta configuración de límites?',
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(`/wallet-limits/${id}/delete`)
            showToast(data.message ?? 'Límites eliminados con éxito', 'success')
            await fetchWalletLimits()
            return true
        } catch (error: any) {
            console.error('Error eliminando límites de billetera:', error)
            showToast(error?.response?.data?.message ?? 'Error al eliminar los límites de billetera', 'error')
            return false
        }
    }

    return {
        walletLimitData,
        walletLimits,
        setInitialWalletLimits,
        submit,
        create,
        edit,
        remove,
        toggleStatus,
        fetchWalletLimits
    }
}
