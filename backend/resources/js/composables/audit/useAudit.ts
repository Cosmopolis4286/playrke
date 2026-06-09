import { ref, Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { AuditTableData, AuditDetail, AuditFilterOptions } from '@/js/types/audit/audit'

interface AuditFilters {
    event?: string
    actor_type?: string
    entity?: string
    user_id?: string
    date_from?: string
    date_to?: string
}

interface UseAuditReturn {
    audits: Ref<AuditTableData>
    detail: Ref<AuditDetail | null>
    loading: Ref<boolean>
    currentPage: Ref<number>
    setInitialAudits: (data: AuditTableData) => void
    fetchList: (filters?: AuditFilters, page?: number) => Promise<boolean>
    fetchDetail: (id: string) => Promise<boolean>
    clearDetail: () => void
}

const emptyTableData = (): AuditTableData => ({ columns: [], rows: [] })

export function useAudit(): UseAuditReturn {
    const loading     = ref(false)
    const audits      = ref<AuditTableData>(emptyTableData())
    const detail      = ref<AuditDetail | null>(null)
    const currentPage = ref(1)

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
            currentPage.value = page

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
        currentPage,
        setInitialAudits,
        fetchList,
        fetchDetail,
        clearDetail,
    }
}
