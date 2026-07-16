import { ref } from 'vue'
import axios from 'axios'

export interface PlatformWallet {
  id:              string
  currency_id:     string
  currency_code:   string
  currency_symbol: string
  balance:         number
  status:          string
}

export interface PlatformMovement {
  id:             string
  type:           string
  status:         string
  amount:         number
  balance_before: number
  balance_after:  number
  description:    string | null
  created_at:     string
}

export interface PlatformMovementsMeta {
  current_page: number
  last_page:    number
  total:        number
}

/**
 * Billetera de plataforma (caja de Playrke) para el panel de SuperAdmin:
 * saldos por moneda, selección de moneda activa, y sus movimientos paginados.
 */
export function usePlatformWallet() {
  const platformWallets      = ref<PlatformWallet[]>([])
  const activePlatformWallet = ref<PlatformWallet | null>(null)
  const platformMovements    = ref<PlatformMovement[]>([])
  const platformMovMeta      = ref<PlatformMovementsMeta | null>(null)
  const platformLoading      = ref(false)
  const platformMovLoading   = ref(false)
  const platformError        = ref<string | null>(null)

  async function loadPlatformWallets() {
    platformLoading.value = true
    platformError.value   = null
    try {
      const { data } = await axios.get('/superadmin/platform-wallets')
      platformWallets.value = data
      if (data.length && !activePlatformWallet.value) {
        activePlatformWallet.value = data[0]
        await loadPlatformMovements(1)
      }
    } catch {
      platformError.value = 'No se pudo cargar la billetera de plataforma.'
    } finally {
      platformLoading.value = false
    }
  }

  async function loadPlatformMovements(page = 1) {
    if (!activePlatformWallet.value) return
    platformMovLoading.value = true
    try {
      const { data } = await axios.get('/superadmin/platform-movements', {
        params: { currency_id: activePlatformWallet.value.currency_id, page, per_page: 15 }
      })
      platformMovements.value = data.data ?? []
      platformMovMeta.value   = { current_page: data.current_page, last_page: data.last_page, total: data.total }
    } finally {
      platformMovLoading.value = false
    }
  }

  async function selectPlatformWallet(w: PlatformWallet) {
    activePlatformWallet.value = w
    platformMovements.value    = []
    await loadPlatformMovements(1)
  }

  return {
    platformWallets, activePlatformWallet,
    platformMovements, platformMovMeta,
    platformLoading, platformMovLoading, platformError,
    loadPlatformWallets, loadPlatformMovements, selectPlatformWallet,
  }
}
