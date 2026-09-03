import { ref } from 'vue'
import axios from 'axios'

export interface AdminUser {
  id: string
  name: string
}

export interface AdminEscrow {
  id: string
  order_id: string
  status: string
  amount: number
  fee_amount: number
  seller_amount: number
  currency_symbol: string
  buyer: AdminUser
  seller: AdminUser
  asset: { code: string; name: string }
  dispute_reason: string | null
  resolution: string | null
  resolved_by: AdminUser | null
  resolved_at: string | null
  expires_at: string | null
  created_at: string
}

interface Meta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

/**
 * Panel de moderación de Escrows — filtros server-side reales (status,
 * búsqueda por id/order/comprador/vendedor), mismo criterio que el resto de
 * paneles admin del proyecto (ver memoria: filtros nunca solo client-side).
 */
export function useEscrowAdmin() {
  const escrows = ref<AdminEscrow[]>([])
  const meta = ref<Meta>({ current_page: 1, last_page: 1, total: 0, per_page: 20 })
  const loading = ref(false)
  const error = ref<string | null>(null)
  const status = ref('')
  const search = ref('')
  const page = ref(1)

  async function fetchList() {
    loading.value = true
    error.value = null
    try {
      const { data } = await axios.get('/marketplace-escrows/fetch-list', {
        params: { status: status.value || undefined, search: search.value || undefined, page: page.value },
      })
      escrows.value = data.data
      meta.value = data.meta
    } catch {
      error.value = 'No se pudieron cargar los escrows.'
    } finally {
      loading.value = false
    }
  }

  async function resolve(escrowId: string, resolution: 'BUYER_WINS' | 'SELLER_WINS' | 'CANCELLED') {
    try {
      const { data } = await axios.post(`/marketplace-escrows/${escrowId}/resolve`, { resolution })
      await fetchList()
      return { success: true, message: 'Disputa resuelta.', data: data.data }
    } catch (e: any) {
      return { success: false, message: e?.response?.data?.message ?? 'No se pudo resolver la disputa.' }
    }
  }

  function setPage(p: number) {
    page.value = p
    fetchList()
  }

  function applyFilters() {
    page.value = 1
    fetchList()
  }

  return { escrows, meta, loading, error, status, search, page, fetchList, resolve, setPage, applyFilters }
}
