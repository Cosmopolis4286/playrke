import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { AdminUserDetail } from '@/js/types/roles/admin/admin-user'

/**
 * Ficha consolidada de un usuario (datos básicos, wallet, nivel/XP, últimos
 * eventos de auditoría) para el modal de detalle — antes esta información
 * vivía repartida en modales separados sin un lugar único para verla junta.
 */
export function useAdminUserDetail() {
    const loading = ref(false)
    const detail = ref<AdminUserDetail | null>(null)

    async function fetchDetail(userId: string | number): Promise<void> {
        loading.value = true
        detail.value = null
        try {
            const { data } = await backendClient.get<{ data: AdminUserDetail }>(`/admin/user/${userId}/show`)
            detail.value = data.data
        } catch (error) {
            console.error(error)
            showToast('Error al cargar el detalle del usuario', 'error')
        } finally {
            loading.value = false
        }
    }

    function clearDetail() {
        detail.value = null
    }

    return { loading, detail, fetchDetail, clearDetail }
}
