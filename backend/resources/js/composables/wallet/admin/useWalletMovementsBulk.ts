import { ref } from 'vue'
import Swal from 'sweetalert2'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toast'
import { showToastConfirmAction } from '@js/utils/toastNotify'
import { buildBulkItemListHtml, reportBulkResult, type BulkResult } from '@js/composables/common/table/useBulkTableActions'
import type { Movement } from '@js/types/wallet/admin-movement'

/** Misma regla que REJECT_NOTES_RULES en WalletMovementController. */
const REJECT_REASON_MIN = 5

/**
 * Aprobación/rechazo en lote de movimientos PENDING seleccionados en la
 * tabla — mismo patrón que useAdminUser.ts::toggleStatusBulk (confirmación
 * con lista de afectados, llamada al endpoint bulk, reporte de resultado
 * parcial vía useBulkTableActions). Un movimiento que ya no es procesable
 * (no pendiente, tipo no aprobable, bloqueado por otro admin) no aborta el
 * lote — el backend lo reporta en `skipped` con motivo humano.
 *
 * Solo se envían los seleccionados que siguen PENDING en pantalla: el resto
 * se descarta antes de confirmar (y se avisa), en vez de mandarlos para que
 * el backend los devuelva uno por uno como "omitidos".
 */
export function useWalletMovementsBulk(onProcessed: () => void) {
  const bulkLoading = ref(false)

  async function bulkApprove(movements: Movement[]): Promise<boolean> {
    const pending = onlyPending(movements)
    if (!pending) return false

    const confirmed = await showToastConfirmAction({
      title: `¿Aprobar ${pending.length} movimiento(s)?`,
      html: buildBulkItemListHtml(itemNames(pending)) + nonPendingNotice(movements, pending),
      icon: 'warning',
      confirmButtonText: 'Sí, aprobar'
    })
    if (!confirmed) return false

    return send('/wallet-movements/bulk/approve', pending, null, null, 'aprobar', 'aprobados')
  }

  async function bulkReject(movements: Movement[]): Promise<boolean> {
    const pending = onlyPending(movements)
    if (!pending) return false

    // Motivo obligatorio — cada jugador lo recibe en su notificación (ver
    // WalletMovementController::bulkReject()). Mismo patrón Swal+textarea que
    // el rechazo de KYC (Pages/Roles/Admin/Kyc/Index.vue).
    const result = await Swal.fire({
      title: `¿Rechazar ${pending.length} movimiento(s)?`,
      html: buildBulkItemListHtml(itemNames(pending)) + nonPendingNotice(movements, pending),
      icon: 'warning',
      input: 'textarea',
      inputLabel: 'Motivo del rechazo (lo recibirá cada jugador)',
      inputPlaceholder: 'Ej.: Pago no recibido en la cuenta destino',
      inputAttributes: { maxlength: '512' },
      inputValidator: (value) => (!value || value.trim().length < REJECT_REASON_MIN
        ? `El motivo es obligatorio (mínimo ${REJECT_REASON_MIN} caracteres)`
        : undefined),
      showCancelButton: true,
      confirmButtonText: 'Sí, rechazar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626',
      reverseButtons: true
    })
    if (!result.isConfirmed || !result.value) return false

    return send('/wallet-movements/bulk/reject', pending, null, String(result.value).trim(), 'rechazar', 'rechazados')
  }

  function onlyPending(movements: Movement[]): Movement[] | null {
    if (movements.length === 0) return null
    const pending = movements.filter(m => m.status === 'PENDING')
    if (pending.length === 0) {
      showToast('Ninguno de los movimientos seleccionados está pendiente.', 'info')
      return null
    }
    return pending
  }

  /** buildBulkItemListHtml interpola sin escapar — se escapa aquí lo que viene del usuario. */
  function itemNames(movements: Movement[]): string[] {
    const escape = (s: string) => s.replace(/[&<>\"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '\"': '&quot;', [String.fromCharCode(39)]: '&#39;' }[c]!))
    return movements.map(m => escape(`${m.type} · ${m.user?.email ?? m.id}`))
  }

  function nonPendingNotice(all: Movement[], pending: Movement[]): string {
    const ignored = all.length - pending.length
    return ignored > 0
      ? `<p style="margin-top:.5rem;font-size:.8rem;opacity:.75">Se omitirán ${ignored} seleccionado(s) que ya no están pendientes.</p>`
      : ''
  }

  async function send(url: string, movements: Movement[], adminNotes: string | null, playerMessage: string | null, verb: string, successVerb: string): Promise<boolean> {
    bulkLoading.value = true
    try {
      const { data } = await backendClient.post<BulkResult>(url, {
        ids: movements.map(m => m.id),
        admin_notes: adminNotes,
        player_message: playerMessage
      })
      await reportBulkResult(data, 'movimientos', successVerb)
      onProcessed()
      return true
    } catch (e: any) {
      const errors = e?.response?.data?.errors as Record<string, string[]> | undefined
      const message = (errors ? Object.values(errors)[0]?.[0] : undefined)
        ?? e?.response?.data?.message
        ?? `Error al ${verb} en lote.`
      showToast(message, 'error')
      return false
    } finally {
      bulkLoading.value = false
    }
  }

  return { bulkLoading, bulkApprove, bulkReject }
}
