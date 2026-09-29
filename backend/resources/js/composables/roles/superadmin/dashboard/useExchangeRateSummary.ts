import { ref } from 'vue'
import axios from 'axios'

export interface ExchangeRateHistoryPoint {
  date:   string | null
  amount: number
}

export interface ExchangeRateRow {
  currency_id:   string
  currency_code: string
  symbol:        string
  amount:        number
  percentage:    number
  updated_at:    string | null
  /** Últimos puntos reales (ACTIVE + OLD) en orden ascendente — ver
   *  ExchangeRateSummaryService::buildHistory(). Ya viene en el orden
   *  correcto para graficar (a diferencia del histórico de
   *  Historical.vue, que llega descendente). */
  history:       ExchangeRateHistoryPoint[]
}

export interface ExchangeRateSummary {
  rates: ExchangeRateRow[]
}

/**
 * Tasas de cambio oficiales activas por moneda, para el panel de
 * SuperAdmin — sistema multi-moneda sin ningún panel de tasas vigentes.
 */
export function useExchangeRateSummary() {
  const exchangeRates = ref<ExchangeRateSummary | null>(null)
  const loading        = ref(false)
  const error           = ref<string | null>(null)

  async function loadExchangeRates() {
    loading.value = true
    error.value   = null
    try {
      const { data } = await axios.get('/superadmin/exchange-rate-summary')
      exchangeRates.value = data
    } catch {
      error.value = 'No se pudieron cargar las tasas de cambio.'
    } finally {
      loading.value = false
    }
  }

  return { exchangeRates, loading, error, loadExchangeRates }
}
