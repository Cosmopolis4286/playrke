import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { CommerceDetail } from '@/js/types/roles/commerce/commerce'

/**
 * Ficha de detalle de solo lectura de una comercializadora — reusa el mismo
 * endpoint show() que ya consume el form de edición (AdminCommerceCrudService::show()),
 * mismo patrón general que useAdminUserDetail.ts pero sin las secciones
 * mutables (no hay acciones de resolución/suspensión aquí).
 */
export function useCommerceDetail() {
    const loading = ref(false)
    const detail = ref<CommerceDetail | null>(null)
    const error = ref<string | null>(null)
    const lastId = ref<string | number | null>(null)

    async function fetchDetail(id: string | number): Promise<void> {
        loading.value = true
        detail.value = null
        error.value = null
        lastId.value = id
        try {
            const { data } = await backendClient.get<{ data: CommerceDetail }>(`/admin/commerce/${id}/show`)
            detail.value = data.data
        } catch (err) {
            console.error(err)
            error.value = 'No se pudo cargar la ficha de la comercializadora.'
            showToast('Error al cargar el detalle de la comercializadora', 'error')
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
