import { ref, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import type {
  Wallet,
  WalletOperation,
  WalletOperationPayload,
  WalletOperationResponse,
} from '@js/types/wallet'

export function useWallet(initialWallets: Wallet[]) {
  const wallets      = ref<Wallet[]>(initialWallets)
  const activeWallet = ref<Wallet | null>(wallets.value[0] ?? null)

  // Modal state
  const modalOpen    = ref(false)
  const operation    = ref<WalletOperation>('deposit')
  const amount       = ref<string>('')
  const loading      = ref(false)
  const errorMsg     = ref<string | null>(null)
  const successMsg   = ref<string | null>(null)

  const canWithdraw = computed(() =>
    activeWallet.value !== null &&
    activeWallet.value.balance > 0
  )

  const formattedBalance = computed(() => {
    if (! activeWallet.value) return '0.00'
    const { balance, currency } = activeWallet.value
    return new Intl.NumberFormat('es-VE', {
      minimumFractionDigits: currency.decimal_places,
      maximumFractionDigits: currency.decimal_places,
    }).format(balance)
  })

  function openModal(op: WalletOperation) {
    operation.value = op
    amount.value    = ''
    errorMsg.value  = null
    successMsg.value = null
    modalOpen.value = true
  }

  function closeModal() {
    modalOpen.value = false
  }

  function selectWallet(wallet: Wallet) {
    activeWallet.value = wallet
    errorMsg.value     = null
    successMsg.value   = null
  }

  async function submit() {
    errorMsg.value   = null
    successMsg.value = null

    const parsedAmount = parseFloat(amount.value)

    if (isNaN(parsedAmount) || parsedAmount <= 0) {
      errorMsg.value = 'Ingresa un monto válido mayor a 0.'
      return
    }

    if (! activeWallet.value) {
      errorMsg.value = 'Selecciona una billetera.'
      return
    }

    if (operation.value === 'withdraw' && parsedAmount > activeWallet.value.balance) {
      errorMsg.value = 'Saldo insuficiente para realizar el retiro.'
      return
    }

    loading.value = true

    try {
      await getCsrfCookie()

      const payload: WalletOperationPayload = {
        wallet_id: activeWallet.value.id,
        amount: parsedAmount,
      }

      const { data } = await backendClient.post<WalletOperationResponse>(
        `wallet/${operation.value}`,
        payload,
      )

      if (data.success) {
        // Actualizar balance local
        if (data.balance !== undefined && activeWallet.value) {
          const idx = wallets.value.findIndex(w => w.id === activeWallet.value!.id)
          if (idx !== -1) {
            wallets.value[idx] = { ...wallets.value[idx], balance: data.balance! }
            activeWallet.value = wallets.value[idx]
          }
        }
        successMsg.value = data.message
        amount.value = ''
      } else {
        errorMsg.value = data.message
      }
    } catch (err: any) {
      const errors = err?.response?.data?.errors
      if (errors) {
        errorMsg.value = Object.values(errors).flat().join(' ')
      } else {
        errorMsg.value = err?.response?.data?.message ?? 'Error inesperado. Intenta nuevamente.'
      }
    } finally {
      loading.value = false
    }
  }

  return {
    wallets,
    activeWallet,
    modalOpen,
    operation,
    amount,
    loading,
    errorMsg,
    successMsg,
    canWithdraw,
    formattedBalance,
    openModal,
    closeModal,
    selectWallet,
    submit,
  }
}
