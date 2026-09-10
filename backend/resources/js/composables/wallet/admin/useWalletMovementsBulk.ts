import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toast'
import { showToastConfirmAction } from '@js/utils/toastNotify'
import { buildBulkItemListHtml, reportBulkResult, type BulkResult } from '@js/composables/common/table/useBulkTableActions'
import type { Movement } from '@js/types/wallet/admin-movement'

/**
 * Aprobación/rechazo en lote de movimientos PENDING seleccionados en la
 * tabla — mismo patrón que useAdminUser.ts::toggleStatusBulk (confirmación
 * con lista de afectados, llamada al endpoint bulk, reporte de resultado
 * parcial vía useBulkTableActions). Un movimiento que ya no es procesable
 * (no pendiente, tipo no aprobable, bloqueado por otro admin) no aborta el
 * lote — el backend lo reporta en `skipped` con motivo humano.
 */
export function useWalletMovementsBulk(onProcessed: () => void) {
  const bulkLoading = ref(false)

  async function bulkApprove(movements: Movement[]): Promise<boolean> {
    return runBulk(movements, '/wallet-movements/bulk/approve', 'aprobar', 'aprobados')
  }

  async function bulkReject(movements: Movement[]): Promise<boolean> {
    return runBulk(movements, '/wallet-movements/bulk/reject', 'rechazar', 'rechazados')
  }

  async function runBulk(movements: Movement[], url: string, verb: string, successVerb: string): Promise<boolean> {
    if (movements.length === 0) return false

    const names = movements.map(m => `${m.type} · ${m.user?.email ?? m.id}`)
    const confirmed = await showToastConfirmAction({
      title: `¿${verb.charAt(0).toUpperCase()}${verb.slice(1)} estos ${movements.length} movimientos?`,
      html: buildBulkItemListHtml(names),
      icon: 'warning',
      confirmButtonText: `Sí, ${verb}`,
    })
    if (!confirmed) return false

    bulkLoading.value = true
    try {
      const { data } = await backendClient.post<BulkResult>(url, { ids: movements.map(m => m.id) })
      await reportBulkResult(data, 'movimientos', successVerb)
      onProcessed()
      return true
    } catch (e: any) {
      const message = e?.response?.data?.message ?? `Error al ${verb} en lote.`
      showToast(message, 'error')
      return false
    } finally {
      bulkLoading.value = false
    }
  }

  return { bulkLoading, bulkApprove, bulkReject }
}
