import { ref } from 'vue'
import axios from 'axios'
import { showToast, showToastConfirmAction } from '@/js/helpers/form/ui/crudModalUI'

export interface PendingReceivable {
  debtor_user_id: string
  debtor:         string
  currency_id:    string
  currency:       string
  symbol:         string
  sales:          number
  total:          number
  oldest:         string
}

export interface ReconciliationAlert {
  id:         string
  type:       string
  message:    string
  amount:     number
  currency:   string | null
  symbol:     string | null
  room_id:    string | null
  lot_id:     string | null
  created_at: string
}

/**
 * Panel de Conciliación del SuperAdmin:
 *  - Cuentas por cobrar: RKE de ventas fuera del sistema (el dinero lo tiene el
 *    comercio). "Marcar cobrado" lo acredita a la billetera de plataforma.
 *  - Alertas: movimientos de dinero que el sistema no pudo completar solo.
 */
export function useReconciliation(onSettled?: () => void) {
  const receivables = ref<PendingReceivable[]>([])
  const alerts      = ref<ReconciliationAlert[]>([])
  const alertsOpen  = ref(0)
  const loading     = ref(false)
  const error       = ref<string | null>(null)
  const busyKey     = ref<string | null>(null)

  async function load() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get('/superadmin/reconciliation')
      receivables.value = data.receivables ?? []
      alerts.value      = data.alerts ?? []
      alertsOpen.value  = data.alerts_open ?? 0
    } catch {
      error.value = 'No se pudo cargar la conciliación.'
    } finally {
      loading.value = false
    }
  }

  async function settle(r: PendingReceivable) {
    const ok = await showToastConfirmAction(
      `¿Confirmás que ${r.debtor} ya pagó ${r.symbol} ${r.total.toFixed(2)} de RKE (${r.sales} venta(s))? Se acreditará a la billetera de plataforma.`,
      'warning',
      'Marcar cobrado'
    )
    if (!ok) return
    busyKey.value = `${r.debtor_user_id}:${r.currency_id}`
    try {
      const { data } = await axios.post('/superadmin/reconciliation/receivables/settle', {
        debtor_user_id: r.debtor_user_id,
        currency_id:    r.currency_id,
      })
      showToast(data.message ?? 'Cobro registrado.', 'success')
      await load()
      onSettled?.()
    } catch (e: any) {
      showToast(e?.response?.data?.message ?? 'No se pudo registrar el cobro.', 'error')
    } finally {
      busyKey.value = null
    }
  }

  async function resolve(alert: ReconciliationAlert) {
    const ok = await showToastConfirmAction(
      '¿Marcar esta alerta como resuelta? Hacelo solo después de conciliar el monto manualmente.',
      'warning',
      'Marcar resuelta'
    )
    if (!ok) return
    busyKey.value = alert.id
    try {
      await axios.post(`/superadmin/reconciliation/alerts/${alert.id}/resolve`)
      showToast('Alerta resuelta.', 'success')
      await load()
    } catch (e: any) {
      showToast(e?.response?.data?.message ?? 'No se pudo resolver la alerta.', 'error')
    } finally {
      busyKey.value = null
    }
  }

  return { receivables, alerts, alertsOpen, loading, error, busyKey, load, settle, resolve }
}
