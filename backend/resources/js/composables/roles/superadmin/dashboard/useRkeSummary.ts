import { ref } from 'vue'
import axios from 'axios'

export interface RkeRoom {
  room_id:       string
  room_name:     string | null
  creator_name:  string
  total_sales:   number
  total_rke:     number
  rke_pct:       number
  first_sale_at: string | null
  last_sale_at:  string | null
  status:        string | null
}

export interface RkeSummary {
  totals: { total_rke: number; total_sales: number; room_count: number }
  rooms:  RkeRoom[]
}

/**
 * Resumen de comisiones RKE cobradas por sala, para el panel de SuperAdmin.
 */
export function useRkeSummary() {
  const rkeSummary = ref<RkeSummary | null>(null)
  const rkeLoading = ref(false)
  const rkeError   = ref<string | null>(null)

  async function loadRkeSummary() {
    rkeLoading.value = true
    rkeError.value   = null
    try {
      const { data } = await axios.get('/superadmin/rke-summary')
      rkeSummary.value = data
    } catch {
      rkeError.value = 'No se pudo cargar el resumen de comisiones RKE.'
    } finally {
      rkeLoading.value = false
    }
  }

  return { rkeSummary, rkeLoading, rkeError, loadRkeSummary }
}
