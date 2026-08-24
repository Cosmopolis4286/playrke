import { ref } from 'vue'
import axios from 'axios'

export interface ActivePlayers {
  daily: number
  weekly: number
  monthly: number
}

export interface RecurringBySeller {
  seller_user_id: string
  seller_name: string
  total_buyers: number
  recurring_buyers: number
  recurring_rate: number
}

export interface PlayerActivitySummary {
  active_players: ActivePlayers
  recurring_by_seller: RecurringBySeller[]
}

/**
 * Jugadores activos por ventana (diaria/semanal/mensual) y clientes
 * recurrentes por vendedor — hasta ahora solo existía conteo total de
 * entidades, no "activos en el tiempo".
 */
export function usePlayerActivitySummary() {
  const activitySummary = ref<PlayerActivitySummary | null>(null)
  const activityLoading = ref(false)
  const activityError   = ref<string | null>(null)

  async function loadActivitySummary() {
    activityLoading.value = true
    activityError.value   = null
    try {
      const { data } = await axios.get('/superadmin/player-activity-summary')
      activitySummary.value = data
    } catch {
      activityError.value = 'No se pudo cargar la actividad de jugadores.'
    } finally {
      activityLoading.value = false
    }
  }

  return { activitySummary, activityLoading, activityError, loadActivitySummary }
}
