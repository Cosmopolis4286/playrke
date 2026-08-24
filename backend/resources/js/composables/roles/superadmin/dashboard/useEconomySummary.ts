import { ref } from 'vue'
import axios from 'axios'

export interface EconomyByGame {
  game: string
  game_label: string
  income: number
  commission: number | null
}

export interface EconomyByCurrency {
  currency_id: string
  iso_code: string
  symbol: string
  bingo_income: number
  trivia_income: number
}

export interface EconomyByCommerce {
  commerce_id: string
  commerce_name: string
  income: number
}

export interface EconomyTotals {
  total_income: number
  total_prizes_paid: number
  total_deposits: number
  total_withdrawals: number
  active_players_7d: number
}

export interface EconomySummary {
  by_game: EconomyByGame[]
  by_currency: EconomyByCurrency[]
  by_commerce: EconomyByCommerce[]
  totals: EconomyTotals
}

/**
 * Resumen económico consolidado cross-juego (dinero ingresado por
 * juego/moneda/comercializadora) — hasta ahora RkeSummary y TriviaSummary
 * reportaban aislados por juego sin ninguna vista cruzada.
 */
export function useEconomySummary() {
  const economySummary = ref<EconomySummary | null>(null)
  const economyLoading = ref(false)
  const economyError   = ref<string | null>(null)

  async function loadEconomySummary() {
    economyLoading.value = true
    economyError.value   = null
    try {
      const { data } = await axios.get('/superadmin/economy-summary')
      economySummary.value = data
    } catch {
      economyError.value = 'No se pudo cargar el resumen económico.'
    } finally {
      economyLoading.value = false
    }
  }

  return { economySummary, economyLoading, economyError, loadEconomySummary }
}
