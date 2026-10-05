import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import type { ExternalPlayerDetail } from '@/js/types/roles/admin/external-player'

/**
 * Modal de ficha de un jugador externo — datos básicos + historial de
 * compras/premios/auditoría. Mismo patrón que useWalletMovementDetail.ts.
 */
export function useExternalPlayerDetail() {
  const showDetail    = ref(false)
  const detailLoading = ref(false)
  const detail        = ref<ExternalPlayerDetail | null>(null)

  async function openDetail(id: string) {
    detail.value        = null
    showDetail.value    = true
    detailLoading.value = true
    try {
      const { data } = await backendClient.get(`/admin/external-players/${id}`)
      detail.value = data.player ?? null
    } catch (e) {
      console.error('[external-players] Error cargando detalle:', e)
      showDetail.value = false
    } finally {
      detailLoading.value = false
    }
  }

  return { showDetail, detailLoading, detail, openDetail }
}
