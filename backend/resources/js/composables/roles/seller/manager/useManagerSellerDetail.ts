import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SellerDetail } from '@/js/types/roles/seller/seller'

/**
 * Ficha de detalle de solo lectura de un vendedor — reusa el mismo endpoint
 * show() que ya consume el form de edición (ManagerSellerCrudService::show()),
 * mismo patrón que useCommerceDetail.ts.
 */
export function useManagerSellerDetail() {
    const loading = ref(false)
    const detail = ref<SellerDetail | null>(null)
    const error = ref<string | null>(null)
    const lastId = ref<string | number | null>(null)

    async function fetchDetail(id: string | number): Promise<void> {
        loading.value = true
        detail.value = null
        error.value = null
        lastId.value = id
        try {
            const { data } = await backendClient.get<{ data: SellerDetail }>(`/sellers/${id}/show`)
            detail.value = data.data
        } catch (err) {
            console.error(err)
            error.value = 'No se pudo cargar la ficha del vendedor.'
            showToast('Error al cargar el detalle del vendedor', 'error')
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
