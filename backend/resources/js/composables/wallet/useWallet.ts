import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import echo from '@js/plugins/echo'
import type {
  Wallet,
  WalletMovement,
  WalletMovementStatus,
  WalletMovementsMeta,
  WalletMovementsResponse,
  WalletOperation,
  WalletOperationResponse,
  UserDirectory,
  DirectoriesResponse,
  PendingOperation,
  PendingOperationResponse,
} from '@js/types/wallet'

// Pasos del flujo
// deposit: 1=monto  → 2=comprobante → 3=confirmado
// withdraw: 1=monto → 2=directorio  → 3=confirmado
export type WalletStep = 1 | 2 | 3

// Payload del evento wallet.updated (incluyendo campos nuevos)
export interface WalletUpdatedPayload {
  wallet_id:       string
  balance:         number
  currency_symbol: string
  movement_type:   string
  amount:          number
  movement_status: string | null   // COMPLETED | REJECTED | null
  movement_id:     string | null
}

export function useWallet(initialWallets: Wallet[], userId?: string) {
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

  // Solicitud pendiente
  const pendingOperation = ref<PendingOperation | null>(null)
  const pendingLoading   = ref(false)

  // Estado general
  const loading    = ref(false)
  const errorMsg   = ref<string | null>(null)
  const successMsg = ref<string | null>(null)

  // Feedback en tiempo real del resultado de aprobación/rechazo
  const resultToast = ref<{
    type: 'approved' | 'rejected'
    movementType: 'DEPOSIT' | 'WITHDRAWAL'
    amount: number
    currencySymbol: string
  } | null>(null)
  let resultToastTimer: ReturnType<typeof setTimeout> | null = null

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
  async function openModal(op: WalletOperation) {
    operation.value           = op
    step.value                = 1
    amount.value              = ''
    selectedQuickAmount.value = null
    receipt.value             = null
    reference.value           = ''
    notes.value               = ''
    selectedDirectory.value   = null
    directories.value         = []
    errorMsg.value            = null
    successMsg.value          = null
    pendingOperation.value    = null
    modalOpen.value           = true

    // Consultar si hay operación pendiente
    await checkPendingOperation()
  }

  function closeModal() {
    modalOpen.value = false
  }

  // ── Verificar operación pendiente ──────────────────────────────────────
  async function checkPendingOperation() {
    if (!activeWallet.value) return
    pendingLoading.value = true
    try {
      const type = operation.value === 'deposit' ? 'DEPOSIT' : 'WITHDRAWAL'
      const { data } = await backendClient.get<PendingOperationResponse>(
        `wallet/${activeWallet.value.id}/pending-operation`,
        { params: { type } }
      )
      pendingOperation.value = data.success ? data.pending : null
    } catch {
      pendingOperation.value = null
    } finally {
      pendingLoading.value = false
    }
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
    directories.value = []
    try {
      const { data } = await backendClient.get<DirectoriesResponse>(
        `wallet/${activeWallet.value.id}/directories`
      )
      directories.value = data.success ? data.data : []
    } catch (err) {
      console.error('[wallet] Error cargando directorios:', err)
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
        // Recargar movimientos para mostrar el depósito PENDING recién creado
        loadMovements(1)
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

  // ── Tiempo real: wallet.updated (saldo, nuevo movimiento, resultado) ──
  function _handleWalletUpdated(payload: WalletUpdatedPayload) {
    // 1. Actualizar saldo local inmediatamente
    if (payload.balance !== undefined) {
      const idx = wallets.value.findIndex(w => w.id === payload.wallet_id)
      if (idx !== -1) {
        wallets.value[idx] = { ...wallets.value[idx], balance: payload.balance }
        if (activeWallet.value?.id === payload.wallet_id) {
          activeWallet.value = wallets.value[idx]
        }
      }
      window.dispatchEvent(new CustomEvent('wallet:updated', {
        detail: {
          balance:         payload.balance,
          currency_symbol: payload.currency_symbol,
        }
      }))
    }

    // 2. Si es resultado de aprobación/rechazo: actualizar movimiento en lista
    //    de forma optimista y mostrar toast
    if (payload.movement_status === 'COMPLETED' || payload.movement_status === 'REJECTED') {
      if (payload.movement_id) {
        const idx = movements.value.findIndex(m => m.id === payload.movement_id)
        if (idx !== -1) {
          const statusDescriptions: Record<string, Record<string, string>> = {
            DEPOSIT:    { COMPLETED: 'Depósito acreditado', REJECTED: 'Depósito rechazado' },
            WITHDRAWAL: { COMPLETED: 'Retiro procesado',    REJECTED: 'Retiro rechazado — saldo reintegrado' },
          }
          const desc = statusDescriptions[payload.movement_type]?.[payload.movement_status]
          movements.value[idx] = {
            ...movements.value[idx],
            status:      payload.movement_status as WalletMovementStatus,
            description: desc ?? movements.value[idx].description,
          }
        }
      }

      if (resultToastTimer) clearTimeout(resultToastTimer)
      resultToast.value = {
        type:           payload.movement_status === 'COMPLETED' ? 'approved' : 'rejected',
        movementType:   payload.movement_type as 'DEPOSIT' | 'WITHDRAWAL',
        amount:         payload.amount,
        currencySymbol: payload.currency_symbol,
      }
      resultToastTimer = setTimeout(() => { resultToast.value = null }, 8000)

      if (modalOpen.value && pendingOperation.value) {
        checkPendingOperation()
      }
    }

    // 3. Recargar lista en cualquier caso para mantener el historial y
    //    el resumen de actividad sincronizados sin refresh manual
    loadMovements(1)
  }

  function dismissResultToast() {
    resultToast.value = null
    if (resultToastTimer) clearTimeout(resultToastTimer)
  }

  // Suscribir al canal privado del usuario si se provee userId
  onMounted(() => {
    if (!userId) return
    echo.private(`App.Models.User.${userId}`)
        .listen('.wallet.updated', _handleWalletUpdated)
  })

  onBeforeUnmount(() => {
    if (!userId) return
    echo.leave(`App.Models.User.${userId}`)
    if (resultToastTimer) clearTimeout(resultToastTimer)
  })

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
    pendingOperation, pendingLoading,
    loading, errorMsg, successMsg,
    resultToast,
    // computed
    canWithdraw, formattedBalance, parsedAmount,
    canGoToStep2, canSubmitDeposit, canSubmitWithdraw,
    // acciones
    openModal, closeModal,
    goToStep1, goToStep2,
    selectWallet, loadMovements, loadMoreMovements,
    submit, dismissResultToast,
  }
}
