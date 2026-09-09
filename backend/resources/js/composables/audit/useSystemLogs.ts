import { ref, Ref } from 'vue'
import axios from 'axios'
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
    assigned_to_user_id?: string
    ignored?: 'all' | 'only'
}

interface UseSystemLogsReturn {
    logs: Ref<SystemLogTableData>
    detail: Ref<SystemLogDetail | null>
    loading: Ref<boolean>
    lastQuery: Ref<ServerQuery>
    newLogsCount: Ref<number>
    setInitialLogs: (data: SystemLogTableData) => void
    initPolling: () => void
    fetchList: (filters?: SystemLogFilters, page?: number) => Promise<boolean>
    onQueryChange: (query: ServerQuery, extraFilters?: SystemLogFilters) => Promise<void>
    fetchDetail: (id: string) => Promise<boolean>
    toggleReviewed: (id: string) => Promise<boolean>
    toggleIgnored: (id: string) => Promise<boolean>
    assign: (id: string, userId: string | null) => Promise<boolean>
    clearDetail: () => void
    startPolling: (intervalMs?: number) => void
    stopPolling: () => void
}

const emptyTableData = (): SystemLogTableData => ({ columns: [], rows: [] })

export function useSystemLogs(): UseSystemLogsReturn {
    const loading = ref(false)
    const logs    = ref<SystemLogTableData>(emptyTableData())
    const detail  = ref<SystemLogDetail | null>(null)

    const lastQuery = ref<ServerQuery>({ search: '', filters: {}, sort: { key: null, direction: 'asc' }, page: 1 })

    /**
     * Cancela la request de listado en curso cuando se dispara una nueva
     * (ej. el admin cambia de filtro rápido) — sin esto, una respuesta vieja
     * podía llegar después de una más reciente y pisar los datos ya
     * mostrados con un resultado desactualizado.
     */
    let listAbortController: AbortController | null = null
    let detailAbortController: AbortController | null = null

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
        listAbortController?.abort()
        listAbortController = new AbortController()

        try {
            loading.value = true

            const params = { ...filters, page }
            const { data } = await backendClient.get<{ data: SystemLogTableData }>(
                '/logs/fetch-list',
                { params, signal: listAbortController.signal }
            )

            if (data?.data) {
                setInitialLogs(data.data)
                lastFetchedAt = new Date().toISOString()
                newLogsCount.value = 0
            }
            return true
        } catch (error) {
            if (axios.isCancel(error)) return false
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
        detailAbortController?.abort()
        detailAbortController = new AbortController()

        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: SystemLogDetail }>(
                `/logs/${id}/show`,
                { signal: detailAbortController.signal }
            )
            if (data?.data) {
                detail.value = data.data
            }
            return true
        } catch (error) {
            if (axios.isCancel(error)) return false
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

    async function toggleIgnored(id: string): Promise<boolean> {
        try {
            const { data } = await backendClient.put<{ data: SystemLogDetail }>(
                `/logs/${id}/ignored`
            )
            if (data?.data) {
                detail.value = data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al actualizar el estado de ignorado', 'error')
            return false
        }
    }

    async function assign(id: string, userId: string | null): Promise<boolean> {
        try {
            const { data } = await backendClient.put<{ data: SystemLogDetail }>(
                `/logs/${id}/assign`,
                { user_id: userId }
            )
            if (data?.data) {
                detail.value = data.data
            }
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al asignar el log', 'error')
            return false
        }
    }

    function clearDetail(): void {
        detail.value = null
    }

    /* -------------------------------------------------------------------------- */
    /* Auto-refresh: detecta logs nuevos sin pisar la vista actual del admin       */
    /* -------------------------------------------------------------------------- */

    const newLogsCount = ref(0)
    let lastFetchedAt: string | null = null
    let pollTimer: ReturnType<typeof setInterval> | null = null

    async function checkForNewLogs(): Promise<void> {
        if (!lastFetchedAt || loading.value) return
        try {
            const { data } = await backendClient.get<{ data: { count: number } }>(
                '/logs/check-new',
                { params: { since: lastFetchedAt } }
            )
            newLogsCount.value = data?.data?.count ?? 0
        } catch (error) {
            // Silencioso a propósito: un fallo de polling no debe interrumpir
            // ni notificar al admin, la próxima vuelta del intervalo reintenta.
            console.error(error)
        }
    }

    /**
     * Marca el punto de partida del polling con la hora actual del cliente —
     * necesario porque la primera carga de /logs viene del render Inertia
     * inicial (no de fetchList()), así que sin esto lastFetchedAt quedaría
     * null y checkForNewLogs() nunca dispararía hasta el primer refetch manual.
     */
    function initPolling(): void {
        lastFetchedAt = new Date().toISOString()
    }

    function startPolling(intervalMs: number = 30_000): void {
        stopPolling()
        pollTimer = setInterval(checkForNewLogs, intervalMs)
    }

    function stopPolling(): void {
        if (pollTimer !== null) {
            clearInterval(pollTimer)
            pollTimer = null
        }
    }

    return {
        logs,
        detail,
        loading,
        lastQuery,
        newLogsCount,
        setInitialLogs,
        initPolling,
        fetchList,
        onQueryChange,
        fetchDetail,
        toggleReviewed,
        toggleIgnored,
        assign,
        clearDetail,
        startPolling,
        stopPolling,
    }
}
