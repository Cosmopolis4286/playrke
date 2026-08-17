import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { AdminUserDetail, AdminUserDetailAudit } from '@/js/types/roles/admin/admin-user'

interface AuditHistoryResponse {
    success: boolean
    rows: AdminUserDetailAudit[]
    has_more: boolean
    current_page: number
}

/**
 * Ficha consolidada de un usuario (datos básicos, wallet, nivel/XP, últimos
 * eventos de auditoría) para el modal de detalle — antes esta información
 * vivía repartida en modales separados sin un lugar único para verla junta.
 */
export function useAdminUserDetail() {
    const loading = ref(false)
    const detail = ref<AdminUserDetail | null>(null)
    const error = ref<string | null>(null)
    const lastUserId = ref<string | number | null>(null)

    const loadingMoreAudits = ref(false)
    const auditPage = ref(1)

    async function fetchDetail(userId: string | number): Promise<void> {
        loading.value = true
        detail.value = null
        error.value = null
        lastUserId.value = userId
        auditPage.value = 1
        try {
            const { data } = await backendClient.get<{ success: boolean } & AdminUserDetail>(`/admin/user/${userId}/show`)
            const { success, ...rest } = data
            detail.value = rest as AdminUserDetail
        } catch (err) {
            console.error(err)
            error.value = 'No se pudo cargar la ficha del usuario.'
            showToast('Error al cargar el detalle del usuario', 'error')
        } finally {
            loading.value = false
        }
    }

    function retryFetchDetail(): void {
        if (lastUserId.value !== null) fetchDetail(lastUserId.value)
    }

    /**
     * Trae la siguiente página de auditoría y la anexa a recent_audits — el
     * botón "Cargar más" del modal, alternativa rápida a saltar a /audit.
     */
    async function loadMoreAudits(): Promise<void> {
        if (!detail.value || lastUserId.value === null || loadingMoreAudits.value) return

        loadingMoreAudits.value = true
        try {
            const nextPage = auditPage.value + 1
            const { data } = await backendClient.get<AuditHistoryResponse>(
                `/admin/user/${lastUserId.value}/audit-history`,
                { params: { page: nextPage } }
            )
            detail.value = {
                ...detail.value,
                recent_audits: [...detail.value.recent_audits, ...data.rows],
                has_more_audits: data.has_more,
            }
            auditPage.value = nextPage
        } catch (err) {
            console.error(err)
            showToast('No se pudo cargar más actividad', 'error')
        } finally {
            loadingMoreAudits.value = false
        }
    }

    function clearDetail() {
        detail.value = null
        error.value = null
        lastUserId.value = null
        auditPage.value = 1
    }

    return {
        loading,
        detail,
        error,
        fetchDetail,
        retryFetchDetail,
        clearDetail,
        loadingMoreAudits,
        loadMoreAudits,
    }
}
