import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Commerce } from '@/js/types/roles/commerce/commerce'
import { showPasswordModal } from '@/js/helpers/user/password'

/**
 * Contrato público del composable useAdminCustomer
 */
interface UseAdminCustomerReturn {
    /** Datos del comercio (para crear o editar) */
    commerceData: Ref<Partial<Commerce>>

    /** Lista reactiva de comercializadoras */
    commerces: Ref<Commerce[]>

    /** Inicializa la lista local */
    setInitialCommerces: (initialCommerces: Commerce[]) => void

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
    fetchCommerces: () => Promise<boolean>
}

/**
 * Factory para un comercio vacío
 */
const emptyCommerce = (): Partial<Commerce> =>
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
 * Composable para manejar comercializadoras
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * ⚠️ No controla UI directamente
 */
export function useAdminCustomer(tokenId: string = ''): UseAdminCustomerReturn {
    /** Estado de carga global */
    const loading = ref(false)

    /** Estado único del formulario */
    const commerceData = ref<Partial<Commerce>>(emptyCommerce())

    /** Lista reactiva local */
    const commerces = ref<Commerce[]>([])

    /**
     * Inicializa la lista local
     */
    function setInitialCommerces(initialCommerces: Commerce[]) {
        commerces.value = [...initialCommerces]
    }

    /**
     * Obtiene la lista desde backend
     */
    async function fetchCommerces(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: Commerce[] }>(
                '/admin/commerce/list-filtered',
                { params: { token: tokenId } }
            )

            if (Array.isArray(response.data?.data)) {
                commerces.value = response.data.data
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar las comercializadoras', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Transforma el payload del frontend al formato esperado por el backend
     */
    function buildPayload(): Record<string, unknown> {
        const { country_id, currency_id, time_zone_id, ...rest } = commerceData.value

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

            if (commerceData.value.id) {
                const { data } = await backendClient.put(
                    `/admin/commerce/${commerceData.value.id}`,
                    payload
                )
                showToast('Actualización exitosa', 'success')
            } else {
                const { data } = await backendClient.post(
                    '/admin/commerce',
                    payload
                )
                showToast('Registro exitoso', 'success')
                await showPasswordModal(data.password)
            }

            await fetchCommerces()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar la comercializadora',
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
        commerceData.value = { ...emptyCommerce() }
    }

    /**
     * Modo edición
     */
    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Record<string, any> }>(
                `/admin/commerce/${id}`
            )

            const commerce = data.data

            commerceData.value = {
                id: commerce.id,
                name: commerce.name,
                phone: commerce.phone,
                commission: commerce.commission ?? '',
                // Datos del usuario anidado
                username: commerce.user?.username,
                email: commerce.user?.email,
                time_zone_id: commerce.user?.time_zone_id,
                // Country viene como objeto {id, name}
                country_id: commerce.country?.id,
                // Currency viene como string (id)
                currency_id: commerce.currency,
                other_currencies: commerce.other_currencies ?? [],
                allowed_games: commerce.allowed_games ?? []
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar la comercializadora', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Reset del formulario
     */
    function reset(): void {
        commerceData.value = { ...emptyCommerce() }
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     */
    async function toggleStatus(id: string | number): Promise<boolean> {
        const commerce = commerces.value.find(c => c.id === id)
        const name = commerce?.name ?? 'esta comercializadora'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/commerce/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchCommerces()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    /**
     * Resetea la contraseña de una comercializadora
     */
    async function resetPassword(id: string | number): Promise<boolean> {
        const commerce = commerces.value.find(c => c.id === id)
        const name = commerce?.name ?? 'esta comercializadora'

        if (commerce?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de comercializadoras activas', 'warning')
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
                `/admin/commerce/${id}/reset-password`
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
     * Eliminación (soft delete)
     */
    async function remove(id: string | number): Promise<boolean> {
        const commerce = commerces.value.find(c => c.id === id)
        const name = commerce?.name ?? 'esta comercializadora'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/commerce/${id}/delete`)
            showToast('Comercializadora eliminada con éxito', 'success')
            await fetchCommerces()
            return true
        } catch {
            showToast('Error al eliminar la comercializadora', 'error')
            return false
        }
    }

    /**
     * Genera HTML con lista de nombres para mostrar en el modal de confirmación
     */
    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => commerces.value.find(c => c.id === id))
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
            html: `¿Quieres cambiar el estado de estas ${ids.length} comercializadoras?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/admin/commerce/${id}/status`)
            }
            showToast(`Estado cambiado con éxito para ${ids.length} comercializadoras`, 'success')
            await fetchCommerces()
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
            html: `¿Estás seguro que deseas eliminar estas ${ids.length} comercializadoras?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/admin/commerce/${id}/delete`)
            }
            showToast(`${ids.length} comercializadoras eliminadas con éxito`, 'success')
            await fetchCommerces()
            return true
        } catch {
            showToast('Error al eliminar las comercializadoras', 'error')
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
        reset,
        toggleStatus,
        toggleStatusBulk,
        resetPassword,
        remove,
        removeBulk,
        fetchCommerces
    }
}
