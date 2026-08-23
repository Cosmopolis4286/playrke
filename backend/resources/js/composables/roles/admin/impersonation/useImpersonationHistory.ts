import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { ImpersonationHistoryTableData } from '@/js/types/roles/admin/impersonation'
import type { ServerQuery } from '@/js/composables/common/table/useTableController'

const emptyTableData = (): ImpersonationHistoryTableData => ({ columns: [], rows: [] })

/**
 * Historial consolidado de impersonación — solo lectura (log de auditoría,
 * no un CRUD), mismo patrón server-side que useAdminKyc.ts.
 */
export function useImpersonationHistory() {
    const loading = ref(false)
    const history = ref<ImpersonationHistoryTableData>(emptyTableData())

    function setInitialHistory(data: ImpersonationHistoryTableData): void {
        history.value = {
            columns:      Array.isArray(data?.columns) ? [...data.columns] : [],
            rows:         Array.isArray(data?.rows)    ? [...data.rows]    : [],
            total:        data?.total,
            per_page:     data?.per_page,
            current_page: data?.current_page,
            last_page:    data?.last_page,
        }
    }

    async function fetchHistory(page: number = 1): Promise<void> {
        try {
            loading.value = true
            const { data } = await backendClient.get<{ success: boolean } & ImpersonationHistoryTableData>(
                '/admin/impersonation/history/data',
                { params: { page } }
            )
            setInitialHistory(data)
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el historial de impersonación', 'error')
        } finally {
            loading.value = false
        }
    }

    async function onQueryChange(query: ServerQuery): Promise<void> {
        await fetchHistory(query.page)
    }

    return {
        loading,
        history,
        setInitialHistory,
        fetchHistory,
        onQueryChange,
    }
}
