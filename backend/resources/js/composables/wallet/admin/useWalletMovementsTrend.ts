import { ref } from 'vue'
import backendClient from '@js/plugins/axios'

export interface MovementTrendPoint {
  date: string
  approved: number
  rejected: number
  avg_resolution_minutes: number | null
}

/**
 * Serie diaria (GET /wallet-movements/trend) para el gráfico de tendencia
 * histórica del dashboard — mismos filtros activos que useWalletMovementsStats,
 * refrescada junto con ellos para que el gráfico refleje siempre lo mismo que
 * ve el admin en la tabla.
 */
export function useWalletMovementsTrend() {
  const trend   = ref<MovementTrendPoint[]>([])
  const loading = ref(false)

  async function fetchTrend(params: Record<string, string | undefined>, days = 30) {
    loading.value = true
    try {
      const { data } = await backendClient.get('/wallet-movements/trend', { params: { ...params, days } })
      trend.value = data.data ?? []
    } catch (e) {
      console.error('[movements] Error cargando tendencia:', e)
      trend.value = []
    } finally {
      loading.value = false
    }
  }

  return { trend, loading, fetchTrend }
}
