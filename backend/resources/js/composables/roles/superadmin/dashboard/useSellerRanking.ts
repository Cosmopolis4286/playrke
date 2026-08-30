import { ref } from 'vue'
import axios from 'axios'

export interface SellerRankingRow {
  rank: number
  seller_user_id: string
  seller_name: string
  cards_sold: number
  sale_amount: number
  commission_amount: number
  goal_reached: boolean
}

export interface SellerRanking {
  rows: SellerRankingRow[]
  total_sellers: number
  goal: number
}

/**
 * Ranking mensual de vendedores por comisión generada — hasta ahora no
 * existía ningún mecanismo de visibilidad de desempeño entre vendedores.
 */
export function useSellerRanking() {
  const ranking = ref<SellerRanking | null>(null)
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function loadSellerRanking() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get('/superadmin/seller-ranking')
      ranking.value = data
    } catch {
      error.value = 'No se pudo cargar el ranking de vendedores.'
    } finally {
      loading.value = false
    }
  }

  return { ranking, loading, error, loadSellerRanking }
}
