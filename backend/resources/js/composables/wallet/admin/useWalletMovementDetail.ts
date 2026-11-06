import { ref, computed, type Ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toast'
import { showToastConfirmAction } from '@js/utils/toastNotify'
import type { Movement } from '@js/types/wallet/admin-movement'
import type { MovementType } from '@js/types/movementType'

/**
 * Modal de detalle de un movimiento + acciones admin (aprobar/rechazar).
 * Recibe `onUpdated` para sincronizar el item correspondiente en la lista
 * del panel (useWalletMovementsList) sin acoplarse directamente a ella.
 *
 * `movementTypes` es el catálogo dinámico — requires_approval decide si el
 * tipo pasa por el flujo approve/reject, igual que ya decide el backend
 * (WalletMovementController::approve/reject). Antes esto estaba hardcodeado
 * a type === 'DEPOSIT' || 'WITHDRAWAL', así que un tipo custom marcado
 * requires_approval=true desde el CRUD de tipos de movimiento nunca mostraba
 * los botones de aprobar/rechazar, aunque el backend sí lo procesaba.
 */
export function useWalletMovementDetail(
  onUpdated: (updated: Movement) => void,
  movementTypes: Ref<MovementType[]> | MovementType[],
  pendingQueue?: Ref<Movement[]>,
) {
  const showDetail     = ref(false)
  const detailLoading  = ref(false)
  const detail         = ref<Movement | null>(null)
  const adminNotes     = ref('')
  const playerMessage  = ref('')
  const actionLoading  = ref(false)
  const actionError    = ref('')
  const showRejectForm = ref(false)
  const receiptZoom    = ref(false)

  async function openDetail(id: string) {
    detail.value         = null
    showDetail.value     = true
    detailLoading.value  = true
    adminNotes.value     = ''
    playerMessage.value  = ''
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

  /**
   * Siguiente PENDING en la cola visible (excluyendo el que se acaba de
   * resolver) — permite encadenar aprobar/rechazar sin volver a la tabla y
   * hacer click de nuevo. `pendingQueue` es la lista ya filtrada/ordenada que
   * ve el admin en pantalla, así que respeta el filtro/búsqueda activos.
   */
  const nextPendingId = computed(() => {
    if (!pendingQueue?.value || !detail.value) return null
    const next = pendingQueue.value.find(m => m.status === 'PENDING' && m.id !== detail.value!.id)
    return next?.id ?? null
  })

  function openNextPending() {
    if (nextPendingId.value) openDetail(nextPendingId.value)
    else showDetail.value = false
  }

  const typeByCode = computed<Record<string, MovementType>>(() => {
    const types = Array.isArray(movementTypes) ? movementTypes : movementTypes.value
    return Object.fromEntries(types.map(t => [t.code, t]))
  })

  /** Etiqueta del tipo en minúscula para frases ("este depósito", "esta compra") — antes todo lo que no era DEPOSIT decía "retiro". */
  const typeLabel = computed(() => {
    if (!detail.value) return 'movimiento'
    return (typeByCode.value[detail.value.type]?.label ?? detail.value.type).toLowerCase()
  })

  /** Mensaje del backend, priorizando el primer error de validación (422) sobre el genérico. */
  function errorMessage(e: any, fallback: string): string {
    const errors = e?.response?.data?.errors as Record<string, string[]> | undefined
    const firstValidation = errors ? Object.values(errors)[0]?.[0] : undefined
    return firstValidation ?? e?.response?.data?.message ?? fallback
  }

  async function handleApprove() {
    if (!detail.value || actionLoading.value) return

    const confirmed = await showToastConfirmAction({
      title: 'Aprobar movimiento',
      text: `Vas a aprobar: ${typeLabel.value}. Esta acción mueve saldo real y no se puede deshacer.`,
      icon: 'warning',
      confirmButtonText: 'Sí, aprobar'
    })
    if (!confirmed) return

    actionError.value   = ''
    actionLoading.value = true
    try {
      const { data } = await backendClient.post(`/wallet-movements/${detail.value.id}/approve`, {
        admin_notes: adminNotes.value.trim() || null
      })
      onUpdated(data.data)
      adminNotes.value = ''
      playerMessage.value = ''
      showToast('Movimiento aprobado correctamente.', 'success')
      openNextPending()
    } catch (e: any) {
      const message = errorMessage(e, 'Error al aprobar el movimiento.')
      actionError.value = message
      showToast(message, 'error')
    } finally {
      actionLoading.value = false
    }
  }

  /** Longitud mínima del motivo — misma regla que PLAYER_MESSAGE_RULES en WalletMovementController. */
  const REJECT_REASON_MIN = 5

  const canSubmitReject = computed(() => playerMessage.value.trim().length >= REJECT_REASON_MIN)

  /**
   * El paso "Confirmar rechazo" del formulario inline ya ES la confirmación
   * (antes se encadenaba además un SweetAlert: 3 clicks para rechazar). Lo que
   * sí se exige ahora es el motivo — el jugador lo recibe en su notificación.
   */
  async function handleReject() {
    if (!detail.value || actionLoading.value) return

    if (!canSubmitReject.value) {
      actionError.value = `Indica el motivo para el jugador (mínimo ${REJECT_REASON_MIN} caracteres).`
      return
    }

    actionError.value   = ''
    actionLoading.value = true
    try {
      const { data } = await backendClient.post(`/wallet-movements/${detail.value.id}/reject`, {
        admin_notes: adminNotes.value.trim() || null,
        player_message: playerMessage.value.trim()
      })
      onUpdated(data.data)
      adminNotes.value     = ''
      playerMessage.value  = ''
      showRejectForm.value = false
      showToast('Movimiento rechazado correctamente.', 'success')
      openNextPending()
    } catch (e: any) {
      const message = errorMessage(e, 'Error al rechazar el movimiento.')
      actionError.value = message
      showToast(message, 'error')
    } finally {
      actionLoading.value = false
    }
  }

  const isReceiptImage = computed(() => {
    if (!detail.value?.receipt_path) return false
    return /\.(jpg|jpeg|png|webp|gif)$/i.test(detail.value.receipt_path)
  })

  const requiresApprovalByCode = computed<Record<string, boolean>>(() => {
    const types = Array.isArray(movementTypes) ? movementTypes : movementTypes.value
    const map: Record<string, boolean> = {}
    for (const type of types) map[type.code] = type.requires_approval
    return map
  })

  const isPending    = computed(() => detail.value?.status === 'PENDING')
  const isActionable = computed(() =>
    isPending.value && !!detail.value && !!requiresApprovalByCode.value[detail.value.type]
  )

  return {
    showDetail, detailLoading, detail,
    adminNotes, playerMessage, actionLoading, actionError, showRejectForm, receiptZoom,
    openDetail, handleApprove, handleReject,
    isReceiptImage, isPending, isActionable,
    nextPendingId, typeLabel, canSubmitReject
  }
}
