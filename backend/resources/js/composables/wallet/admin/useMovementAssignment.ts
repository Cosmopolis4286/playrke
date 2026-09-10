import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toast'
import type { Movement } from '@js/types/wallet/admin-movement'

/**
 * "Tomar caso"/"Liberar" desde el modal de detalle — evita que dos admins
 * trabajen el mismo movimiento pendiente a la vez (lock real en
 * WalletService::guardNotLockedByOther(), no solo informativo). Un intento
 * de tomar/procesar un caso ya bloqueado por otro admin vuelve como 409 con
 * mensaje humano.
 */
export function useMovementAssignment(onUpdated: (updated: Movement) => void) {
  const assignLoading = ref(false)

  async function assignToMe(movementId: string) {
    assignLoading.value = true
    try {
      const { data } = await backendClient.put(`/wallet-movements/${movementId}/assign`)
      onUpdated(data.data)
    } catch (e: any) {
      showToast(e?.response?.data?.message ?? 'Error al tomar el caso.', 'error')
    } finally {
      assignLoading.value = false
    }
  }

  async function unassign(movementId: string) {
    assignLoading.value = true
    try {
      const { data } = await backendClient.put(`/wallet-movements/${movementId}/unassign`)
      onUpdated(data.data)
    } catch (e: any) {
      showToast(e?.response?.data?.message ?? 'Error al liberar el caso.', 'error')
    } finally {
      assignLoading.value = false
    }
  }

  return { assignLoading, assignToMe, unassign }
}
