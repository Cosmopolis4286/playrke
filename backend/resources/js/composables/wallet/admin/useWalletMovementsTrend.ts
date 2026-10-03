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

  // Solo se aplica la respuesta más reciente — ver fetchSeq en useWalletMovementsList.
  let seq = 0

  async function fetchTrend(params: Record<string, string | undefined>, days = 30) {
    const current = ++seq
    loading.value = true
    try {
      const { data } = await backendClient.get('/wallet-movements/trend', { params: { ...params, days } })
      if (current === seq) trend.value = data.data ?? []
    } catch (e) {
      console.error('[movements] Error cargando tendencia:', e)
      // Se conserva la última serie buena en vez de vaciar el gráfico por un fallo puntual.
    } finally {
      if (current === seq) loading.value = false
    }
  }

  return { trend, loading, fetchTrend }
}
