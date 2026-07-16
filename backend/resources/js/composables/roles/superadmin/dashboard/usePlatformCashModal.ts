import { ref, type Ref } from 'vue'
import axios from 'axios'
import type { PlatformWallet } from './usePlatformWallet'

export interface CashModalState {
  mode: 'deposit' | 'withdraw'
  amount: string
  confirmAmount: string
  description: string
  loading: boolean
  error: string | null
}

/**
 * Formulario de depósito/retiro manual sobre la billetera de plataforma.
 * Recibe la billetera activa (fuente de verdad en usePlatformWallet) y un
 * callback a ejecutar tras un envío exitoso (típicamente refrescar saldos).
 */
export function usePlatformCashModal(
  activePlatformWallet: Ref<PlatformWallet | null>,
  onSuccess: () => Promise<void>,
) {
  const cashModal = ref<CashModalState | null>(null)

  function openCashModal(mode: 'deposit' | 'withdraw') {
    cashModal.value = { mode, amount: '', confirmAmount: '', description: '', loading: false, error: null }
  }

  function closeCashModal() {
    cashModal.value = null
  }

  function withdrawConfirmed(m: CashModalState): boolean {
    if (m.mode !== 'withdraw') return true
    return m.confirmAmount.trim() !== '' && parseFloat(m.confirmAmount) === parseFloat(m.amount)
  }

  async function submitCash() {
    if (!cashModal.value || !activePlatformWallet.value) return
    const m = cashModal.value
    const amount = parseFloat(m.amount)
    if (isNaN(amount) || amount <= 0) {
      m.error = 'Ingresa un monto válido mayor a cero.'
      return
    }
    if (!withdrawConfirmed(m)) {
      m.error = 'El monto de confirmación no coincide.'
      return
    }
    m.loading = true
    m.error   = null
    try {
      const endpoint = m.mode === 'deposit' ? '/superadmin/platform-deposit' : '/superadmin/platform-withdraw'
      await axios.post(endpoint, {
        currency_id: activePlatformWallet.value.currency_id,
        amount,
        description: m.description || undefined,
      })
      closeCashModal()
      await onSuccess()
    } catch (e: any) {
      m.error = e?.response?.data?.message ?? 'No se pudo completar la operación.'
    } finally {
      if (cashModal.value) cashModal.value.loading = false
    }
  }

  return { cashModal, openCashModal, closeCashModal, withdrawConfirmed, submitCash }
}
