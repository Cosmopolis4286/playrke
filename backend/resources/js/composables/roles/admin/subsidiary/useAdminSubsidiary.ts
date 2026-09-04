import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import type { Subsidiary } from '@/js/types/roles/subsidiary/subsidiary'
import { showPasswordModal } from '@/js/helpers/user/password'

/**
 * Contrato público del composable useAdminSubsidiary
 */
interface UseAdminSubsidiaryReturn {
    /** Datos de la sucursal (para crear o editar) */
    subsidiaryData: Ref<Partial<Subsidiary>>

    /** Lista reactiva de sucursales */
    subsidiaries: Ref<Subsidiary[]>

    /** Inicializa la lista local */
    setInitialSubsidiaries: (initialSubsidiaries: Subsidiary[]) => void

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
    fetchSubsidiaries: () => Promise<boolean>

    /** Estado de carga */
    loading: Ref<boolean>
}

/**
 * Factory para una sucursal vacía
 */
const emptySubsidiary = (): Partial<Subsidiary> =>
    Object.freeze({
        name: '',
        username: '',
        email: '',
        commerce_id: null,
        has_commission: false,
        commission: '',
        country_id: null,
        phone: '',
        currency_id: null,
    })

/**
 * Composable para manejar sucursales
 *
 * Responsabilidades:
 * - Control del estado del formulario (create / edit / reset)
 * - Envío de datos (POST / PUT)
 * - Gestión local de la lista
 *
 * ⚠️ No controla UI directamente
 */
export function useAdminSubsidiary(): UseAdminSubsidiaryReturn {
    const loading = ref(false)
    const subsidiaryData = ref<Partial<Subsidiary>>(emptySubsidiary())
    const subsidiaries = ref<Subsidiary[]>([])

    function setInitialSubsidiaries(initialSubsidiaries: Subsidiary[]) {
        subsidiaries.value = [...initialSubsidiaries]
    }

    async function fetchSubsidiaries(): Promise<boolean> {
        try {
            loading.value = true

            const response = await backendClient.get<{ data: Subsidiary[] }>(
                '/admin/subsidiary/fetch-list'
            )

            if (Array.isArray(response.data?.data)) {
                subsidiaries.value = response.data.data
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar las sucursales', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Transforma el payload del frontend al formato esperado por el backend
     */
    function buildPayload(): Record<string, unknown> {
        const { commerce_id, country_id, currency_id, has_commission, ...rest } = subsidiaryData.value

        return {
            ...rest,
            commerce: commerce_id,
            country: country_id,
            currency: currency_id,
            commission: has_commission ? rest.commission : null
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const payload = buildPayload()

            if (subsidiaryData.value.id) {
                await backendClient.put(
                    `/admin/subsidiary/${subsidiaryData.value.id}/update`,
                    payload
                )
                showToast('Sucursal actualizada con éxito', 'success')
            } else {
                const { data } = await backendClient.post(
                    '/admin/subsidiary',
                    payload
                )
                showToast('Sucursal creada con éxito', 'success')
                await showPasswordModal(data.password)
            }

            await fetchSubsidiaries()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(
                error?.response?.data?.message ?? 'Error al guardar la sucursal',
                'error'
            )
            return false
        } finally {
            loading.value = false
        }
    }

    function create(): void {
        subsidiaryData.value = { ...emptySubsidiary() }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true

            const { data } = await backendClient.get<{ data: Record<string, any> }>(
                `/admin/subsidiary/${id}/show`
            )

            const subsidiary = data.data
            const commission = subsidiary.commission ?? ''

            subsidiaryData.value = {
                id: subsidiary.id,
                name: subsidiary.name,
                phone: subsidiary.phone,
                has_commission: !!commission && commission !== '0.00' && commission !== '0',
                commission,
                username: subsidiary.user?.username,
                email: subsidiary.user?.email,
                commerce_id: subsidiary.commerce?.id ?? null,
                country_id: subsidiary.country?.id,
                currency_id: subsidiary.currency,
            }

            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar la sucursal', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function reset(): void {
        subsidiaryData.value = { ...emptySubsidiary() }
    }

    async function toggleStatus(id: string | number): Promise<boolean> {
        const subsidiary = subsidiaries.value.find(c => c.id === id)
        const name = subsidiary?.name ?? 'esta sucursal'

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/subsidiary/${id}/status`)
            showToast('Estado cambiado con éxito', 'success')
            await fetchSubsidiaries()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function resetPassword(id: string | number): Promise<boolean> {
        const subsidiary = subsidiaries.value.find(c => c.id === id)
        const name = subsidiary?.name ?? 'esta sucursal'

        if (subsidiary?.status !== 'ACTIVE') {
            showToast('Solo se puede resetear la contraseña de sucursales activas', 'warning')
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
                `/admin/subsidiary/${id}/reset-password`
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
        const subsidiary = subsidiaries.value.find(c => c.id === id)
        const name = subsidiary?.name ?? 'esta sucursal'

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar <b>${name}</b>?`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            await backendClient.put(`/admin/subsidiary/${id}/delete`)
            showToast('Sucursal eliminada con éxito', 'success')
            await fetchSubsidiaries()
            return true
        } catch {
            showToast('Error al eliminar la sucursal', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const items = ids
            .map(id => subsidiaries.value.find(c => c.id === id))
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
            html: `¿Quieres cambiar el estado de estas ${ids.length} sucursales?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/admin/subsidiary/${id}/status`)
            }
            showToast(`Estado cambiado con éxito para ${ids.length} sucursales`, 'success')
            await fetchSubsidiaries()
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
            html: `¿Estás seguro que deseas eliminar estas ${ids.length} sucursales?${itemListHtml}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })

        if (!confirm) return false

        try {
            for (const id of ids) {
                await backendClient.put(`/admin/subsidiary/${id}/delete`)
            }
            showToast(`${ids.length} sucursales eliminadas con éxito`, 'success')
            await fetchSubsidiaries()
            return true
        } catch {
            showToast('Error al eliminar las sucursales', 'error')
            return false
        }
    }

    return {
        subsidiaryData,
        subsidiaries,
        loading,
        setInitialSubsidiaries,
        submit,
        create,
        edit,
        reset,
        toggleStatus,
        toggleStatusBulk,
        resetPassword,
        remove,
        removeBulk,
        fetchSubsidiaries
    }
}
