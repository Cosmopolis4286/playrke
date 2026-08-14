import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { AuditTableData, AuditDetail } from '@/js/types/audit/audit'
import type { ServerQuery } from '@/js/composables/common/table/useTableController'

interface AuditFilters {
    event?: string
    actor_type?: string
    entity?: string
    user_id?: string
    auditable_id?: string
    date_from?: string
    date_to?: string
    search?: string
}

interface UseAuditReturn {
    audits: Ref<AuditTableData>
    detail: Ref<AuditDetail | null>
    loading: Ref<boolean>
    lastQuery: Ref<ServerQuery>
    setInitialAudits: (data: AuditTableData) => void
    fetchList: (filters?: AuditFilters, page?: number) => Promise<boolean>
    onQueryChange: (query: ServerQuery, extraFilters?: AuditFilters) => Promise<void>
    fetchDetail: (id: string) => Promise<boolean>
    clearDetail: () => void
}

const emptyTableData = (): AuditTableData => ({ columns: [], rows: [] })

export function useAudit(): UseAuditReturn {
    const loading   = ref(false)
    const audits    = ref<AuditTableData>(emptyTableData())
    const detail    = ref<AuditDetail | null>(null)

    /**
     * Último query resuelto por TableWithFilterBridge (server-side) — se
     * reusa para reconstruir los mismos filtros al exportar a CSV, igual
     * que AdminUser/Index.vue con su handleExport().
     */
    const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

    function setInitialAudits(data: AuditTableData): void {
        audits.value = {
            columns:      Array.isArray(data?.columns) ? [...data.columns] : [],
            rows:         Array.isArray(data?.rows)    ? [...data.rows]    : [],
            total:        data?.total,
            per_page:     data?.per_page,
            current_page: data?.current_page,
            last_page:    data?.last_page,
        }
    }

    async function fetchList(filters: AuditFilters = {}, page: number = 1): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true

            const params = { ...filters, page }
            const { data } = await backendClient.get<{ data: AuditTableData }>(
                '/audit/fetch-list',
                { params }
            )

            if (data?.data) {
                setInitialAudits(data.data)
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los registros de auditoría', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Adapta el evento query-change de TableWithFilterBridge (server-side)
     * al fetch — extraFilters permite mantener filtros ad-hoc que la tabla
     * genérica no conoce (rango de fechas, filtro de sujeto por deep-link).
     */
    async function onQueryChange(query: ServerQuery, extraFilters: AuditFilters = {}): Promise<void> {
        lastQuery.value = query

        await fetchList(
            {
                ...extraFilters,
                ...query.filters,
                search: query.search || undefined,
            } as AuditFilters,
            query.page
        )
    }

    async function fetchDetail(id: string): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: AuditDetail }>(
                `/audit/${id}/show`
            )
            if (data?.data) {
                detail.value = data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el detalle del registro', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function clearDetail(): void {
        detail.value = null
    }

    return {
        audits,
        detail,
        loading,
        lastQuery,
        setInitialAudits,
        fetchList,
        onQueryChange,
        fetchDetail,
        clearDetail,
    }
}
