import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Treasurer } from '@/js/types/roles/treasurer/treasurer'
import { showPasswordModal } from '@/js/helpers/user/password'

/**
 * Contrato público del composable useAdminTreasurer
 */
interface UseAdminTreasurerReturn {
    /** Datos del tesorero (para crear o editar) */
    treasurerData: Ref<Partial<Treasurer>>

    /** Lista reactiva de tesoreros */
    treasurers: Ref<Treasurer[]>

    /** Inicializa la lista local */
    setInitialTreasurers: (initialTreasurers: Treasurer[]) => void

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
    fetchTreasurers: () => Promise<boolean>

    /** Estado de carga */
    loading: Ref<boolean>
}

/**
 * Factory para un tesorero vacío
 */
const emptyTreasurer = (): Partial<Treasurer> =>
    Object.freeze({
        name: '',
        username: '',
        email: '',
        has_commission: false,
        commission: '',
        country_id: null,
        time_zone_id: null,
        phone: '',
        currency_id: null,
        other_currencies: [],
        allowed_games: []
    })

/**
 * Composable para manejar tesoreros
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * ⚠️ No controla UI directamente
 */
export function useAdminTreasurer(): UseAdminTreasurerReturn {
    const loading = ref(false)
    const treasurerData = ref<Partial<Treasurer>>(emptyTreasurer())
    const treasurers = ref<Treasurer[]>([])

    function setInitialTreasurers(initialTreasurers: Treasurer[]) {
        treasurers.value = [...initialTreasurers]
    }

    async function fetchTreasurers(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: Treasurer[] }>(
                '/admin/treasurer/fetch-list'
            )

            if (Array.isArray(response.data?.data)) {
                treasurers.value = response.data.data
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los tesoreros', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Transforma el payload del frontend al formato esperado por el backend
     */
    function buildPayload(): Record<string, unknown> {
        const { country_id, currency_id, time_zone_id, has_commission, ...rest } = treasurerData.value

        return {
            ...rest,
            country: country_id,
            currency: currency_id,
            time_zone: time_zone_id,
            commission: has_commission ? rest.commission : null
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const payload = buildPayload()

            if (treasurerData.value.id) {
                await backendClient.put(
                    `/admin/treasurer/${treasurerData.value.id}/update`,
                    payload
                )
                showToast('Tesorero actualizado con éxito', 'success')
            } else {
                const { data } = await backendClient.post(
                    '/admin/treasurer',
                    payload
                )
                showToast('Tesorero creado con éxito', 'success')
                await showPasswordModal(data.password)
            }

            await fetchTreasurers()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar el tesorero',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        treasurerData.value = { ...emptyTreasurer() }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Record<string, any> }>(
                `/admin/treasurer/${id}/show`
            )

            const treasurer = data.data
            const commission = treasurer.commission ?? ''

            treasurerData.value = {
                id: treasurer.id,
                name: treasurer.name,
                phone: treasurer.phone,
                has_commission: !!commission && commission !== '0.00' && commission !== '0',
                commission,
                username: treasurer.user?.username,
                email: treasurer.user?.email,
                time_zone_id: treasurer.user?.time_zone_id,
                country_id: treasurer.country?.id,
                currency_id: treasurer.currency,
                other_currencies: treasurer.other_currencies ?? [],
                allowed_games: treasurer.allowed_games ?? []
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el tesorero', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function reset(): void {
        treasurerData.value = { ...emptyTreasurer() }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const treasurer = treasurers.value.find(c => c.id === id)
        const name = treasurer?.name ?? 'este tesorero'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/treasurer/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchTreasurers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function resetPassword(id: string | number): Promise<boolean> {
        const treasurer = treasurers.value.find(c => c.id === id)
        const name = treasurer?.name ?? 'este tesorero'

        if (treasurer?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de tesoreros activos', 'warning')
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
                `/admin/treasurer/${id}/reset-password`
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

    async function remove(id: string | number): Promise<boolean> {
        const treasurer = treasurers.value.find(c => c.id === id)
        const name = treasurer?.name ?? 'este tesorero'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/treasurer/${id}/delete`)
            showToast('Tesorero eliminado con éxito', 'success')
            await fetchTreasurers()
            return true
        } catch {
            showToast('Error al eliminar el tesorero', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => treasurers.value.find(c => c.id === id))
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

    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false

        if (ids.length === 1) {
            return toggleStatus(ids[0])
        }

        const itemListHtml = buildItemListHtml(ids)

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estos ${ids.length} tesoreros?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/admin/treasurer/${id}/status`)
            }
            showToast(`Estado cambiado con éxito para ${ids.length} tesoreros`, 'success')
            await fetchTreasurers()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false

        if (ids.length === 1) {
            return remove(ids[0])
        }

        const itemListHtml = buildItemListHtml(ids)

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estos ${ids.length} tesoreros?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/admin/treasurer/${id}/delete`)
            }
            showToast(`${ids.length} tesoreros eliminados con éxito`, 'success')
            await fetchTreasurers()
            return true
        } catch {
            showToast('Error al eliminar los tesoreros', 'error')
            return false
        }
    }

    return {
        treasurerData,
        treasurers,
        loading,
        setInitialTreasurers,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        resetPassword,
        remove,
        removeBulk,
        fetchTreasurers
    }
}
