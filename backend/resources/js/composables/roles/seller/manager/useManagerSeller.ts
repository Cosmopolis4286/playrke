import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { showPasswordModal } from '@/js/helpers/user/password'

import type { Seller } from '@/js/types/roles/seller/seller'

/**
 * Contrato público del composable useManagerSeller
 */
interface UseManagerSellerReturn {
    /** Datos del vendedor (para crear o editar) */
    sellerData: Ref<Partial<Seller>>

    /** Lista reactiva de vendedores */
    sellers: Ref<Seller[]>

    /** Inicializa la lista local */
    setInitialSellers: (initialSellers: Seller[]) => void

    /** Envío del formulario */
    submit: () => Promise<boolean>

    /** Modo creación */
    create: () => void

    /** Modo edición */
    edit: (id: string | number) => Promise<boolean>

    /** Reset del formulario */
    reset: () => void

    /** Cambio de estado (individual) */
    toggleStatus: (id: string | number) => Promise<boolean>

    /** Cambio de estado masivo */
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>

    /** Reset de contraseña */
    resetPassword: (id: string | number) => Promise<boolean>

    /** Eliminación (individual) */
    remove: (id: string | number) => Promise<boolean>

    /** Eliminación masiva */
    removeBulk: (ids: (string | number)[]) => Promise<boolean>

    /** Fetch desde backend */
    fetchSellers: () => Promise<boolean>
}

/**
 * Factory para un vendedor vacío
 */
const emptySeller = (): Partial<Seller> =>
    Object.freeze({
        name: '',
        username: '',
        email: '',
        commission: '',
        country_id: null,
        time_zone_id: null,
        phone: '',
        currency_id: null,
        other_currencies: [],
        allowed_games: []
    })

/**
 * Composable para manejar vendedores
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * ⚠️ No controla UI directamente
 */
export function useManagerSeller(tokenId: string = ''): UseManagerSellerReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** Estado único del formulario */
    const sellerData = ref<Partial<Seller>>(emptySeller())

    /** Lista reactiva local */
    const sellers = ref<Seller[]>([])

    /**
     * Inicializa la lista local
     */
    function setInitialSellers(initialSellers: Seller[]) {
        sellers.value = [...initialSellers]
    }

    /**
     * Obtiene la lista desde backend
     */
    async function fetchSellers(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: Seller[] }>(
                '/sellers/list-filtered',
                { params: { token: tokenId } }
            )

            if (Array.isArray(response.data?.data)) {
                sellers.value = response.data.data
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar las vendedores', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Transforma el payload del frontend al formato esperado por el backend
     */
    function buildPayload(): Record<string, unknown> {
        const { country_id, currency_id, time_zone_id, ...rest } = sellerData.value

        return {
            ...rest,
            country: country_id,
            currency: currency_id,
            time_zone: time_zone_id
        }
    }

    /**
     * Envío del formulario (create / update)
     */
    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const payload = buildPayload()

            if (sellerData.value.id) {
                const { data } = await backendClient.put(
                    `/sellers/${sellerData.value.id}`,
                    payload
                )
                showToast('Actualización exitosa', 'success')
            } else {
                const { data } = await backendClient.post(
                    '/sellers',
                    payload
                )
                showToast('Registro exitoso', 'success')
                await showPasswordModal(data.password)
            }

            await fetchSellers()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar el vendedor',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Modo creación
     */
    function create(): void {
        sellerData.value = { ...emptySeller() }
    }

    /**
     * Modo edición
     */
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Record<string, any> }>(
                `/sellers/${id}`
            )

            const seller = data.data

            sellerData.value = {
                id: seller.id,
                name: seller.name,
                phone: seller.phone,
                commission: seller.commission ?? '',
                // Datos del usuario anidado
                username: seller.user?.username,
                email: seller.user?.email,
                time_zone_id: seller.user?.time_zone_id,
                // Country viene como objeto {id, name}
                country_id: seller.country?.id,
                // Currency viene como string (id)
                currency_id: seller.currency,
                other_currencies: seller.other_currencies ?? [],
                allowed_games: seller.allowed_games ?? []
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el vendedor', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Reset del formulario
     */
    function reset(): void {
        sellerData.value = { ...emptySeller() }
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del vendedor.
     */
    async function toggleStatus(id: string | number): Promise<boolean> {
        const seller = sellers.value.find(c => c.id === id)
        const name = seller?.name ?? 'este vendedor'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/sellers/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchSellers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Resetea la contraseña de un vendedor y muestra la nueva contraseña en un modal (solo para vendedores activos)
     */
    async function resetPassword(id: string | number): Promise<boolean> {
        const seller = sellers.value.find(c => c.id === id)
        const name = seller?.name ?? 'este vendedor'

        if (seller?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de vendedores activos', 'warning')
            return false
        }

        const confirm = await showToastConfirmAction({
            html: `¿Deseas resetear la contraseña de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, resetear'
        })

        if (!confirm) return false

        try {
            const { data } = await backendClient.put(
                `/sellers/${id}/reset-password`
            )

            showToast(data.message ?? 'Contraseña reseteada', 'success')
            await showPasswordModal(data.password)

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al resetear la contraseña', 'error')
            return false
        }
    }

    /**
     * Eliminación (soft delete) de un vendedor
     */
    async function remove(id: string | number): Promise<boolean> {
        const seller = sellers.value.find(c => c.id === id)
        const name = seller?.name ?? 'este vendedor'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/sellers/${id}/delete`)
            showToast('Comercializadora eliminada con éxito', 'success')
            await fetchSellers()
            return true
        } catch {
            showToast('Error al eliminar el vendedor', 'error')
            return false
        }
    }

    /**
     * Genera HTML con lista de nombres para mostrar en el modal de confirmación
     */
    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => sellers.value.find(c => c.id === id))
            .filter(Boolean)
            .map(c => c!.name)

        return `
            <div style="max-height: 150px; overflow-y: auto; text-align: left; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 20px;">
                    ${items.map(name => `<li style="padding: 4px 0;">${name}</li>`).join('')}
                </ul>
            </div>
        `
    }

    /**
     * Alternar estado (masivo)
     */
    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false

        // Si es solo uno, usar el flujo individual
        if (ids.length === 1) {
            return toggleStatus(ids[0])
        }

        const itemListHtml = buildItemListHtml(ids)

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estas ${ids.length} vendedores?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/sellers/${id}/status`)
            }
            showToast(`Estado cambiado con éxito para ${ids.length} vendedores`, 'success')
            await fetchSellers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Eliminación masiva (soft delete)
     */
    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false

        // Si es solo uno, usar el flujo individual
        if (ids.length === 1) {
            return remove(ids[0])
        }

        const itemListHtml = buildItemListHtml(ids)

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estas ${ids.length} vendedores?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/sellers/${id}/delete`)
            }
            showToast(`${ids.length} vendedores eliminadas con éxito`, 'success')
            await fetchSellers()
            return true
        } catch {
            showToast('Error al eliminar las vendedores', 'error')
            return false
        }
    }

    return {
        sellerData,
        sellers,
        setInitialSellers,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        resetPassword,
        remove,
        removeBulk,
        fetchSellers
    }
}
