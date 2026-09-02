import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast, showToastConfirmAction } from '@/js/utils/toastNotify'
import { showPasswordModal } from '@/js/helpers/user/password'
import { buildBulkItemListHtml, reportBulkResult, type BulkResult } from '@/js/composables/common/table/useBulkTableActions'
import type { Commerce } from '@/js/types/roles/commerce/commerce'
import type { Pagination } from '@/js/types/common/table/pagination'
import type { ServerQuery } from '@/js/composables/common/table/useTableController'

/**
 * Contrato público del composable useAdminCommerce
 */
interface UseAdminCommerceReturn {
    commerceData: Ref<Partial<Commerce>>
    commerces: Ref<Commerce[]>
    pagination: Ref<Pagination | null>
    loading: Ref<boolean>
    lastQuery: Ref<ServerQuery>
    setInitialCommerces: (initial: Commerce[], initialPagination?: Pagination | null) => void
    submit: () => Promise<boolean>
    create: () => void
    edit: (id: string | number) => Promise<boolean>
    reset: () => void
    toggleStatus: (id: string | number) => Promise<boolean>
    toggleStatusBulk: (ids: (string | number)[]) => Promise<boolean>
    resetPassword: (id: string | number) => Promise<boolean>
    remove: (id: string | number) => Promise<boolean>
    removeBulk: (ids: (string | number)[]) => Promise<boolean>
    setAsDefault: (id: string | number) => Promise<boolean>
    fetchCommerces: () => Promise<boolean>
    onQueryChange: (query: ServerQuery) => Promise<void>
}

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
 * Composable para manejar comercializadoras — server-side real (búsqueda/
 * filtros/paginación contra el catálogo completo, no solo la página
 * cargada), mismo patrón que useAdminPlayer.ts.
 */
export function useAdminCommerce(): UseAdminCommerceReturn {
    const loading = ref(false)
    const commerceData = ref<Partial<Commerce>>(emptyCommerce())
    const commerces = ref<Commerce[]>([])
    const pagination = ref<Pagination | null>(null)

    const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: null }, page: 1 })

    function setInitialCommerces(initial: Commerce[], initialPagination: Pagination | null = null) {
        commerces.value = [...initial]
        pagination.value = initialPagination
    }

    function reset(): void {
        commerceData.value = { ...emptyCommerce() }
    }

    const create = (): void => { reset() }

    async function fetchCommerces(): Promise<boolean> {
        try {
            loading.value = true
            const response = await backendClient.get<{ data: Commerce[]; meta: Pagination }>('/admin/commerce/fetch-list', {
                params: {
                    page: lastQuery.value.page,
                    search: lastQuery.value.search || undefined,
                    country_name: lastQuery.value.filters.country_name || undefined,
                    status: lastQuery.value.filters.status || undefined,
                },
            })
            if (Array.isArray(response.data?.data)) {
                commerces.value = response.data.data
            }
            if (response.data?.meta) {
                pagination.value = response.data.meta
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

    /** Adapta el evento query-change de TableWithFilterBridge (server-side) al fetch. */
    async function onQueryChange(query: ServerQuery): Promise<void> {
        lastQuery.value = query
        await fetchCommerces()
    }

    function buildPayload(): Record<string, unknown> {
        const { country_id, currency_id, time_zone_id, ...rest } = commerceData.value

        return {
            ...rest,
            country: country_id,
            currency: currency_id,
            time_zone: time_zone_id
        }
    }

    async function submit(): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true
            const payload = buildPayload()

            if (commerceData.value.id) {
                await backendClient.put(`/admin/commerce/${commerceData.value.id}/update`, payload)
                showToast('Actualización exitosa', 'success')
            } else {
                const { data } = await backendClient.post('/admin/commerce', payload)
                showToast('Registro exitoso', 'success')
                await showPasswordModal(data.password)
            }

            await fetchCommerces()
            reset()
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al guardar la comercializadora', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function edit(id: string | number): Promise<boolean> {
        if (loading.value) return false

        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: Record<string, any> }>(`/admin/commerce/${id}/show`)
            const commerce = data.data

            commerceData.value = {
                id: commerce.id,
                name: commerce.name,
                phone: commerce.phone,
                commission: commerce.commission ?? '',
                username: commerce.user?.username,
                email: commerce.user?.email,
                time_zone_id: commerce.user?.time_zone_id,
                country_id: commerce.country?.id,
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
            const { data } = await backendClient.put(`/admin/commerce/${id}/reset-password`)
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

    async function setAsDefault(id: string | number): Promise<boolean> {
        const confirm = await showToastConfirmAction({
            html: '¿Marcar esta comercializadora como principal de la plataforma?',
            icon: 'question',
            confirmButtonText: 'Sí, marcar como principal'
        })
        if (!confirm) return false

        try {
            await backendClient.put(`/admin/commerce/${id}/set-as-default`)
            showToast('Comercializadora marcada como principal', 'success')
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al marcar como principal', 'error')
            return false
        }
    }

    function buildItemListHtml(ids: (string | number)[]): string {
        const names = ids
            .map(id => commerces.value.find(c => c.id === id))
            .filter(Boolean)
            .map(c => c!.name)

        return buildBulkItemListHtml(names)
    }

    /**
     * Una sola request transaccional al backend (antes hacía un loop
     * secuencial de N requests, sin atomicidad ni reporte de omitidos) —
     * mismo patrón que useAdminPlayer.ts.
     */
    async function toggleStatusBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return toggleStatus(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Quieres cambiar el estado de estas ${ids.length} comercializadoras?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, cambiar estado'
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put<BulkResult>('/admin/commerce/bulk/status', { ids })
            await reportBulkResult(data, 'comercializadora(s)', 'actualizadas')
            await fetchCommerces()
            return true
        } catch {
            showToast('Error al cambiar el estado', 'error')
            return false
        }
    }

    async function removeBulk(ids: (string | number)[]): Promise<boolean> {
        if (ids.length === 0) return false
        if (ids.length === 1) return remove(ids[0])

        const confirm = await showToastConfirmAction({
            html: `¿Estás seguro que deseas eliminar estas ${ids.length} comercializadoras?${buildItemListHtml(ids)}`,
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar'
        })
        if (!confirm) return false

        try {
            const { data } = await backendClient.put<BulkResult>('/admin/commerce/bulk/delete', { ids })
            await reportBulkResult(data, 'comercializadora(s)', 'eliminadas')
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
        pagination,
        loading,
        lastQuery,
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
        setAsDefault,
        fetchCommerces,
        onQueryChange,
    }
}
