import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { KycTableData, KycDocumentDetail, KycStats } from '@/js/types/roles/admin/kyc'
import type { ServerQuery } from '@/js/composables/common/table/useTableController'

const emptyTableData = (): KycTableData => ({ columns: [], rows: [] })
const emptyStats = (): KycStats => ({ pending: 0, approved_today: 0, rejected_today: 0, rejection_rate: 0 })

interface KycFilters {
    status?: string
    document_type?: string
    search?: string
}

/**
 * Cola de revisión de documentos KYC — mismo patrón server-side que
 * useSystemLogs.ts (fetchList con filtros/página, detalle bajo demanda).
 */
export function useAdminKyc() {
    const loading = ref(false)
    const documents = ref<KycTableData>(emptyTableData())
    const stats = ref<KycStats>(emptyStats())
    const detail = ref<KycDocumentDetail | null>(null)
    const detailLoading = ref(false)
    const approving = ref(false)
    const rejecting = ref(false)

    const lastQuery = ref<ServerQuery>({ search: '', filters: { status: 'pending' }, sort: { key: null, direction: null }, page: 1 })

    function setInitialDocuments(data: KycTableData): void {
        documents.value = {
            columns:      Array.isArray(data?.columns) ? [...data.columns] : [],
            rows:         Array.isArray(data?.rows)    ? [...data.rows]    : [],
            total:        data?.total,
            per_page:     data?.per_page,
            current_page: data?.current_page,
            last_page:    data?.last_page,
        }
    }

    function setInitialStats(data: KycStats): void {
        stats.value = data ?? emptyStats()
    }

    async function fetchDocuments(filters: KycFilters = {}, page: number = 1): Promise<boolean> {
        try {
            loading.value = true
            const { data } = await backendClient.get<{ data: KycTableData, stats: KycStats }>('/admin/kyc/fetch-list', {
                params: {
                    page,
                    status:        filters.status || undefined,
                    document_type: filters.document_type || undefined,
                    search:        filters.search || undefined,
                },
            })
            if (data?.data) setInitialDocuments(data.data)
            if (data?.stats) setInitialStats(data.stats)
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar los documentos KYC', 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    async function onQueryChange(query: ServerQuery): Promise<void> {
        lastQuery.value = query
        await fetchDocuments({ status: query.filters.status, document_type: query.filters.document_type, search: query.search }, query.page)
    }

    async function fetchDetail(id: string): Promise<boolean> {
        try {
            detailLoading.value = true
            const { data } = await backendClient.get<{ data: KycDocumentDetail }>(`/admin/kyc/${id}/show`)
            if (data?.data) detail.value = data.data
            return true
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el documento', 'error')
            return false
        } finally {
            detailLoading.value = false
        }
    }

    function clearDetail(): void {
        detail.value = null
    }

    async function approve(id: string): Promise<boolean> {
        try {
            approving.value = true
            const { data } = await backendClient.post(`/admin/kyc/${id}/approve`)
            showToast(data.message ?? 'Documento aprobado', 'success')
            await fetchDocuments({ status: lastQuery.value.filters.status, document_type: lastQuery.value.filters.document_type, search: lastQuery.value.search }, lastQuery.value.page)
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al aprobar el documento', 'error')
            return false
        } finally {
            approving.value = false
        }
    }

    async function reject(id: string, reason: string): Promise<boolean> {
        try {
            rejecting.value = true
            const { data } = await backendClient.post(`/admin/kyc/${id}/reject`, { reason })
            showToast(data.message ?? 'Documento rechazado', 'success')
            await fetchDocuments({ status: lastQuery.value.filters.status, document_type: lastQuery.value.filters.document_type, search: lastQuery.value.search }, lastQuery.value.page)
            return true
        } catch (error: any) {
            console.error(error)
            showToast(error?.response?.data?.message ?? 'Error al rechazar el documento', 'error')
            return false
        } finally {
            rejecting.value = false
        }
    }

    return {
        loading,
        documents,
        stats,
        detail,
        detailLoading,
        approving,
        rejecting,
        lastQuery,
        setInitialDocuments,
        setInitialStats,
        fetchDocuments,
        onQueryChange,
        fetchDetail,
        clearDetail,
        approve,
        reject,
    }
}
