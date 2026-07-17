import { ref, computed } from 'vue'
import backendClient from '@js/plugins/axios'
import type { Movement } from '@js/types/wallet/admin-movement'

/**
 * Modal de detalle de un movimiento + acciones admin (aprobar/rechazar).
 * Recibe `onUpdated` para sincronizar el item correspondiente en la lista
 * del panel (useWalletMovementsList) sin acoplarse directamente a ella.
 */
export function useWalletMovementDetail(onUpdated: (updated: Movement) => void) {
  const showDetail     = ref(false)
  const detailLoading  = ref(false)
  const detail         = ref<Movement | null>(null)
  const adminNotes     = ref('')
  const actionLoading  = ref(false)
  const actionError    = ref('')
  const showRejectForm = ref(false)
  const receiptZoom    = ref(false)

  async function openDetail(id: string) {
    detail.value         = null
    showDetail.value     = true
    detailLoading.value  = true
    adminNotes.value     = ''
    actionError.value    = ''
    showRejectForm.value = false
    receiptZoom.value    = false
    try {
      const { data } = await backendClient.get(`/wallet-movements/${id}`)
      detail.value = data.data ?? null
    } catch (e) {
      console.error('[movements] Error cargando detalle:', e)
      showDetail.value = false
    } finally {
      detailLoading.value = false
    }
  }

  async function handleApprove() {
    if (!detail.value) return
    actionError.value   = ''
    actionLoading.value = true
    try {
      const { data } = await backendClient.post(`/wallet-movements/${detail.value.id}/approve`, {
        admin_notes: adminNotes.value || null,
      })
      detail.value = data.data
      onUpdated(data.data)
      adminNotes.value = ''
    } catch (e: any) {
      actionError.value = e?.response?.data?.message ?? 'Error al aprobar el movimiento.'
    } finally {
      actionLoading.value = false
    }
  }

  async function handleReject() {
    if (!detail.value) return
    actionError.value   = ''
    actionLoading.value = true
    try {
      const { data } = await backendClient.post(`/wallet-movements/${detail.value.id}/reject`, {
        admin_notes: adminNotes.value || null,
      })
      detail.value = data.data
      onUpdated(data.data)
      adminNotes.value     = ''
      showRejectForm.value = false
    } catch (e: any) {
      actionError.value = e?.response?.data?.message ?? 'Error al rechazar el movimiento.'
    } finally {
      actionLoading.value = false
    }
  }

  const isReceiptImage = computed(() => {
    if (!detail.value?.receipt_path) return false
    return /\.(jpg|jpeg|png|webp|gif)$/i.test(detail.value.receipt_path)
  })

  const isPending    = computed(() => detail.value?.status === 'PENDING')
  const isActionable = computed(() => isPending.value && (detail.value?.type === 'DEPOSIT' || detail.value?.type === 'WITHDRAWAL'))

  return {
    showDetail, detailLoading, detail,
    adminNotes, actionLoading, actionError, showRejectForm, receiptZoom,
    openDetail, handleApprove, handleReject,
    isReceiptImage, isPending, isActionable,
  }
}
