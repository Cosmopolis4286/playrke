import { ref } from 'vue'
import backendClient from '@js/plugins/axios'

export interface MovementStats {
  pending_count: number
  approved_today: number
  rejected_today: number
  rejection_rate_today: number
  avg_resolution_minutes: number | null
  approved_volume_today: number
}

/**
 * KPIs del header del panel — agregados sobre el conjunto filtrado completo
 * (GET /wallet-movements/stats), no solo la página visible en la tabla.
 * Se refresca junto con cada fetch de la lista (mismos filtros activos) para
 * que los números siempre reflejen lo que el admin está viendo.
 */
export function useWalletMovementsStats() {
  const stats  = ref<MovementStats | null>(null)
  const loading = ref(false)

  async function fetchStats(params: Record<string, string | undefined>) {
    loading.value = true
    try {
      const { data } = await backendClient.get('/wallet-movements/stats', { params })
      stats.value = data.data ?? null
    } catch (e) {
      console.error('[movements] Error cargando stats:', e)
    } finally {
      loading.value = false
    }
  }

  return { stats, loading, fetchStats }
}
