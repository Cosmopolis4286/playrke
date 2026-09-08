import { ref } from 'vue'
import axios from 'axios'
import { showToast } from '@js/utils/toast'

export interface AdminUser {
  id: string
  name: string
}

export interface AdminPaymentOrder {
  id: string
  status: string
  network: string
  token: string
  deposit_address: string
  expected_amount: number
  received_amount: number | null
  user: AdminUser | null
  product: { code: string; name: string } | null
  detected_at: string | null
  confirmed_at: string | null
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
 * Panel admin de órdenes de pago USDT-TRC20 — filtros server-side reales
 * (status, búsqueda por id/dirección/usuario), mismo criterio que
 * useEscrowAdmin. reevaluate() nunca puede duplicar una entrega (ver
 * PaymentOrderAdminController::reevaluate()).
 */
export function usePaymentOrderAdmin() {
  const orders = ref<AdminPaymentOrder[]>([])
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
      const { data } = await axios.get('/payments-orders/fetch-list', {
        params: { status: status.value || undefined, search: search.value || undefined, page: page.value },
      })
      orders.value = data.data
      meta.value = data.meta
    } catch {
      error.value = 'No se pudieron cargar las órdenes de pago.'
    } finally {
      loading.value = false
    }
  }

  async function reevaluate(orderId: string) {
    try {
      const { data } = await axios.post(`/payments-orders/${orderId}/reevaluate`)
      await fetchList()
      showToast('Orden reevaluada.', 'success')
      return { success: true, message: 'Orden reevaluada.', data: data.data }
    } catch (e: any) {
      const message = e?.response?.data?.message ?? 'No se pudo reevaluar la orden.'
      showToast(message, 'error')
      return { success: false, message }
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

  return { orders, meta, loading, error, status, search, page, fetchList, reevaluate, setPage, applyFilters }
}
