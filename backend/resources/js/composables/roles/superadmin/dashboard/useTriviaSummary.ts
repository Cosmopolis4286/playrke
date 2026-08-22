import { ref } from 'vue'
import axios from 'axios'

export interface TriviaSummaryRoom {
  room_id:            string
  title:              string | null
  status:             string
  participant_count:  number
  commission_percent: number
  commission_amount:  number
  prize_type:          string | null
  prize_amount:         number
  created_at:           string | null
}

export interface TriviaSummary {
  totals: { active_rooms: number; total_participants: number; total_commission: number }
  rooms:  TriviaSummaryRoom[]
}

/**
 * Resumen de actividad Trivia para el panel de SuperAdmin — hoy Trivia solo
 * aparece como línea suelta en el feed de actividad genérico, sin panel
 * dedicado pese a ser un negocio propio (comisión, entry_fee, premios).
 */
export function useTriviaSummary() {
  const triviaSummary = ref<TriviaSummary | null>(null)
  const triviaLoading = ref(false)
  const triviaError   = ref<string | null>(null)

  async function loadTriviaSummary() {
    triviaLoading.value = true
    triviaError.value   = null
    try {
      const { data } = await axios.get('/superadmin/trivia-summary')
      triviaSummary.value = data
    } catch {
      triviaError.value = 'No se pudo cargar el resumen de Trivia.'
    } finally {
      triviaLoading.value = false
    }
  }

  return { triviaSummary, triviaLoading, triviaError, loadTriviaSummary }
}
