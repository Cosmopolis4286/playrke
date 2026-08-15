import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SystemLogTableData, SystemLogDetail } from '@/js/types/audit/system-log'
import type { ServerQuery } from '@/js/composables/common/table/useTableController'

interface SystemLogFilters {
    level?: string
    context?: string
    date_from?: string
    date_to?: string
    search?: string
}

interface UseSystemLogsReturn {
    logs: Ref<SystemLogTableData>
    detail: Ref<SystemLogDetail | null>
    loading: Ref<boolean>
    lastQuery: Ref<ServerQuery>
    setInitialLogs: (data: SystemLogTableData) => void
    fetchList: (filters?: SystemLogFilters, page?: number) => Promise<boolean>
    onQueryChange: (query: ServerQuery, extraFilters?: SystemLogFilters) => Promise<void>
    fetchDetail: (id: string) => Promise<boolean>
    toggleReviewed: (id: string) => Promise<boolean>
    clearDetail: () => void
}

const emptyTableData = (): SystemLogTableData => ({ columns: [], rows: [] })

export function useSystemLogs(): UseSystemLogsReturn {
    const loading = ref(false)
    const logs    = ref<SystemLogTableData>(emptyTableData())
    const detail  = ref<SystemLogDetail | null>(null)

    const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

    function setInitialLogs(data: SystemLogTableData): void {
        logs.value = {
            columns:      Array.isArray(data?.columns) ? [...data.columns] : [],
            rows:         Array.isArray(data?.rows)    ? [...data.rows]    : [],
            total:        data?.total,
            per_page:     data?.per_page,
            current_page: data?.current_page,
            last_page:    data?.last_page,
        }
    }

    async function fetchList(filters: SystemLogFilters = {}, page: number = 1): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true

            const params = { ...filters, page }
            const { data } = await backendClient.get<{ data: SystemLogTableData }>(
                '/logs/fetch-list',
                { params }
            )

            if (data?.data) {
                setInitialLogs(data.data)
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los logs del sistema', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function onQueryChange(query: ServerQuery, extraFilters: SystemLogFilters = {}): Promise<void> {
        lastQuery.value = query

        await fetchList(
            {
                ...extraFilters,
                ...query.filters,
                search: query.search || undefined,
            } as SystemLogFilters,
            query.page
        )
    }

    async function fetchDetail(id: string): Promise<boolean> {
        if (loading.value) return false
        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: SystemLogDetail }>(
                `/logs/${id}/show`
            )
            if (data?.data) {
                detail.value = data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el detalle del log', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function toggleReviewed(id: string): Promise<boolean> {
        try {
            const { data } = await backendClient.put<{ data: SystemLogDetail }>(
                `/logs/${id}/reviewed`
            )
            if (data?.data) {
                detail.value = data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al actualizar el estado de revisión', 'error')
            return false
        }
    }

    function clearDetail(): void {
        detail.value = null
    }

    return {
        logs,
        detail,
        loading,
        lastQuery,
        setInitialLogs,
        fetchList,
        onQueryChange,
        fetchDetail,
        toggleReviewed,
        clearDetail,
    }
}
