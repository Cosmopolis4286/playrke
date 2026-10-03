import { ref } from 'vue'
import backendClient from '@js/plugins/axios'

export interface MovementStats {
  pending_count: number
  /** PENDING con más de 24h esperando (mismo umbral que el banner de escalamiento). */
  critical_pending_count: number
  /** created_at (ISO) del PENDING más antiguo del conjunto filtrado — null si no hay pendientes. */
  oldest_pending_at: string | null
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

  // Solo se aplica la respuesta más reciente — ver fetchSeq en useWalletMovementsList.
  let seq = 0

  async function fetchStats(params: Record<string, string | undefined>) {
    const current = ++seq
    loading.value = true
    try {
      const { data } = await backendClient.get('/wallet-movements/stats', { params })
      if (current === seq) stats.value = data.data ?? null
    } catch (e) {
      console.error('[movements] Error cargando stats:', e)
    } finally {
      if (current === seq) loading.value = false
    }
  }

  return { stats, loading, fetchStats }
}
