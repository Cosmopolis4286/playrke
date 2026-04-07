import { ref, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import type {
  Wallet,
  WalletMovement,
  WalletMovementsMeta,
  WalletMovementsResponse,
  WalletOperation,
  WalletOperationResponse,
  UserDirectory,
  DirectoriesResponse,
} from '@js/types/wallet'

// Pasos del flujo
// deposit: 1=monto  → 2=comprobante → 3=confirmado
// withdraw: 1=monto → 2=directorio  → 3=confirmado
export type WalletStep = 1 | 2 | 3

export function useWallet(initialWallets: Wallet[]) {
  const wallets      = ref<Wallet[]>(initialWallets)
  const activeWallet = ref<Wallet | null>(wallets.value[0] ?? null)

  // ── Modal / flujo ──────────────────────────────────────────────────────
  const modalOpen  = ref(false)
  const operation  = ref<WalletOperation>('deposit')
  const step       = ref<WalletStep>(1)

  // Paso 1 – monto
  const amount              = ref<string>('')
  const selectedQuickAmount = ref<number | null>(null)

  // Paso 2 depósito – comprobante
  const receipt    = ref<File | null>(null)
  const reference  = ref<string>('')
  const notes      = ref<string>('')

  // Paso 2 retiro – directorio
  const directories        = ref<UserDirectory[]>([])
  const directoriesLoading = ref(false)
  const selectedDirectory  = ref<UserDirectory | null>(null)

  // Estado general
  const loading    = ref(false)
  const errorMsg   = ref<string | null>(null)
  const successMsg = ref<string | null>(null)

  // Historial
  const movements        = ref<WalletMovement[]>([])
  const movementsMeta    = ref<WalletMovementsMeta | null>(null)
  const movementsLoading = ref(false)

  // ── Computed ───────────────────────────────────────────────────────────
  const canWithdraw = computed(() =>
    activeWallet.value !== null && activeWallet.value.balance > 0
  )

  const formattedBalance = computed(() => {
    if (!activeWallet.value) return '0.00'
    const { balance, currency } = activeWallet.value
    return new Intl.NumberFormat('es-VE', {
      minimumFractionDigits: currency.decimal_places,
      maximumFractionDigits: currency.decimal_places,
    }).format(balance)
  })

  const parsedAmount = computed(() => parseFloat(amount.value) || 0)

  const canGoToStep2 = computed(() => {
    if (parsedAmount.value <= 0) return false
    if (operation.value === 'withdraw' && activeWallet.value && parsedAmount.value > activeWallet.value.balance) return false
    return true
  })

  const canSubmitDeposit = computed(() => receipt.value !== null && parsedAmount.value > 0)

  const canSubmitWithdraw = computed(() => selectedDirectory.value !== null && parsedAmount.value > 0)

  // ── Abrir / cerrar modal ───────────────────────────────────────────────
  function openModal(op: WalletOperation) {
    operation.value         = op
    step.value              = 1
    amount.value            = ''
    selectedQuickAmount.value = null
    receipt.value           = null
    reference.value         = ''
    notes.value             = ''
    selectedDirectory.value = null
    directories.value       = []
    errorMsg.value          = null
    successMsg.value        = null
    modalOpen.value         = true
  }

  function closeModal() {
    modalOpen.value = false
  }

  // ── Navegación de pasos ────────────────────────────────────────────────
  async function goToStep2() {
    errorMsg.value = null

    if (parsedAmount.value <= 0) {
      errorMsg.value = 'Ingresa un monto válido mayor a 0.'
      return
    }

    if (operation.value === 'withdraw') {
      if (!activeWallet.value) return
      if (parsedAmount.value > activeWallet.value.balance) {
        errorMsg.value = 'Saldo insuficiente para realizar el retiro.'
        return
      }
      // Cargar directorios si aún no están cargados
      await loadDirectories()
    }

    step.value = 2
  }

  function goToStep1() {
    step.value     = 1
    errorMsg.value = null
  }

  // ── Cargar directorios del usuario ────────────────────────────────────
  async function loadDirectories() {
    if (!activeWallet.value) return
    directoriesLoading.value = true
    try {
      const { data } = await backendClient.get<DirectoriesResponse>(
        `wallet/${activeWallet.value.id}/directories`
      )
      if (data.success) {
        directories.value = data.data
      }
    } catch {
      directories.value = []
    } finally {
      directoriesLoading.value = false
    }
  }

  // ── Submit depósito ────────────────────────────────────────────────────
  async function submitDeposit() {
    if (!receipt.value) {
      errorMsg.value = 'El comprobante es obligatorio.'
      return
    }
    if (!activeWallet.value) return

    loading.value  = true
    errorMsg.value = null

    try {
      await getCsrfCookie()

      const formData = new FormData()
      formData.append('wallet_id', activeWallet.value.id)
      formData.append('amount', parsedAmount.value.toString())
      formData.append('receipt', receipt.value)
      if (reference.value) formData.append('reference', reference.value)
      if (notes.value) formData.append('notes', notes.value)

      const { data } = await backendClient.post<WalletOperationResponse>(
        'wallet/deposit',
        formData
        // No pasar Content-Type manualmente: axios lo genera con el boundary correcto
      )

      if (data.success) {
        successMsg.value = data.message
        step.value = 3
      } else {
        errorMsg.value = data.message
      }
    } catch (err: any) {
      errorMsg.value = _extractError(err)
    } finally {
      loading.value = false
    }
  }

  // ── Submit retiro ──────────────────────────────────────────────────────
  async function submitWithdraw() {
    if (!selectedDirectory.value) {
      errorMsg.value = 'Selecciona un destino de retiro.'
      return
    }
    if (!activeWallet.value) return

    loading.value  = true
    errorMsg.value = null

    try {
      await getCsrfCookie()

      const { data } = await backendClient.post<WalletOperationResponse>('wallet/withdraw', {
        wallet_id:      activeWallet.value.id,
        amount:         parsedAmount.value,
        directory_id:   selectedDirectory.value.id,
        directory_type: selectedDirectory.value.type,
        notes:          notes.value || undefined,
      })

      if (data.success) {
        successMsg.value = data.message
        step.value = 3

        // Notificar navbar si el backend devuelve balance actualizado
        if (data.balance !== undefined) {
          _updateLocalBalance(data.balance)
        }

        await loadMovements(1)
      } else {
        errorMsg.value = data.message
      }
    } catch (err: any) {
      errorMsg.value = _extractError(err)
    } finally {
      loading.value = false
    }
  }

  // Dispatcher principal (llamado desde el template)
  function submit() {
    if (operation.value === 'deposit') return submitDeposit()
    return submitWithdraw()
  }

  // ── Billetera activa ───────────────────────────────────────────────────
  async function selectWallet(wallet: Wallet) {
    activeWallet.value      = wallet
    selectedDirectory.value = null
    directories.value       = []
    errorMsg.value          = null
    successMsg.value        = null
    await loadMovements()
  }

  // ── Historial ──────────────────────────────────────────────────────────
  async function loadMovements(page = 1) {
    if (!activeWallet.value) return
    movementsLoading.value = true
    try {
      const { data } = await backendClient.get<WalletMovementsResponse>(
        `wallet/${activeWallet.value.id}/movements`,
        { params: { page, per_page: 20 } },
      )
      if (data.success) {
        movements.value     = page === 1 ? data.data : [...movements.value, ...data.data]
        movementsMeta.value = data.meta
      }
    } catch {
      // silencioso
    } finally {
      movementsLoading.value = false
    }
  }

  async function loadMoreMovements() {
    if (!movementsMeta.value) return
    if (movementsMeta.value.current_page >= movementsMeta.value.last_page) return
    await loadMovements(movementsMeta.value.current_page + 1)
  }

  // ── Helpers privados ───────────────────────────────────────────────────
  function _extractError(err: any): string {
    const errors = err?.response?.data?.errors
    if (errors) return Object.values(errors).flat().join(' ')
    return err?.response?.data?.message ?? 'Error inesperado. Intenta nuevamente.'
  }

  function _updateLocalBalance(balance: number) {
    if (!activeWallet.value) return
    const idx = wallets.value.findIndex(w => w.id === activeWallet.value!.id)
    if (idx !== -1) {
      wallets.value[idx] = { ...wallets.value[idx], balance }
      activeWallet.value = wallets.value[idx]
    }
    window.dispatchEvent(new CustomEvent('wallet:updated', {
      detail: {
        balance,
        currency_symbol: activeWallet.value?.currency.symbol ?? '',
        decimal_places:  activeWallet.value?.currency.decimal_places ?? 2,
      }
    }))
  }

  // Cargar historial al montar
  loadMovements()

  return {
    // estado
    wallets, activeWallet,
    movements, movementsMeta, movementsLoading,
    modalOpen, operation, step,
    amount, selectedQuickAmount,
    receipt, reference, notes,
    directories, directoriesLoading, selectedDirectory,
    loading, errorMsg, successMsg,
    // computed
    canWithdraw, formattedBalance, parsedAmount,
    canGoToStep2, canSubmitDeposit, canSubmitWithdraw,
    // acciones
    openModal, closeModal,
    goToStep1, goToStep2,
    selectWallet, loadMoreMovements,
    submit,
  }
}
