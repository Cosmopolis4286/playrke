import { ref, type Ref } from 'vue'
import axios from 'axios'
import { showToast } from '@js/utils/toast'
import { showToastConfirmAction } from '@js/utils/toastNotify'
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

    const confirmed = await showToastConfirmAction({
      title: m.mode === 'deposit' ? 'Confirmar depósito' : 'Confirmar retiro',
      text: `Vas a ${m.mode === 'deposit' ? 'depositar' : 'retirar'} ${m.amount} de la caja de la plataforma. Esta acción no se puede deshacer.`,
      icon: 'warning',
      confirmButtonText: m.mode === 'deposit' ? 'Sí, depositar' : 'Sí, retirar',
    })
    if (!confirmed) return

    m.loading = true
    m.error   = null
    try {
      const endpoint = m.mode === 'deposit' ? '/superadmin/platform-deposit' : '/superadmin/platform-withdraw'
      await axios.post(endpoint, {
        currency_id: activePlatformWallet.value.currency_id,
        amount,
        description: m.description || undefined,
      })
      showToast(m.mode === 'deposit' ? 'Depósito registrado correctamente.' : 'Retiro registrado correctamente.', 'success')
      closeCashModal()
      await onSuccess()
    } catch (e: any) {
      const message = e?.response?.data?.message ?? 'No se pudo completar la operación.'
      m.error = message
      showToast(message, 'error')
    } finally {
      if (cashModal.value) cashModal.value.loading = false
    }
  }

  return { cashModal, openCashModal, closeCashModal, withdrawConfirmed, submitCash }
}
