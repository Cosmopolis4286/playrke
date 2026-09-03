import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SubsidiaryDetail } from '@/js/types/roles/subsidiary/subsidiary'

/**
 * Ficha de detalle de solo lectura de una sucursal — reusa el mismo endpoint
 * show() que ya consume el form de edición (AdminSubsidiaryCrudService::show()),
 * mismo patrón que useCommerceDetail.ts.
 */
export function useSubsidiaryDetail() {
    const loading = ref(false)
    const detail = ref<SubsidiaryDetail | null>(null)
    const error = ref<string | null>(null)
    const lastId = ref<string | number | null>(null)

    async function fetchDetail(id: string | number): Promise<void> {
        loading.value = true
        detail.value = null
        error.value = null
        lastId.value = id
        try {
            const { data } = await backendClient.get<{ data: SubsidiaryDetail }>(`/admin/subsidiary/${id}/show`)
            detail.value = data.data
        } catch (err) {
            console.error(err)
            error.value = 'No se pudo cargar la ficha de la sucursal.'
            showToast('Error al cargar el detalle de la sucursal', 'error')
        } finally {
            loading.value = false
        }
    }

    function retryFetchDetail(): void {
        if (lastId.value !== null) fetchDetail(lastId.value)
    }

    function clearDetail(): void {
        detail.value = null
        error.value = null
        lastId.value = null
    }

    return {
        loading,
        detail,
        error,
        fetchDetail,
        retryFetchDetail,
        clearDetail,
    }
}
