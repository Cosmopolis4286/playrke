import { type Ref, type ComputedRef } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface UseBingoDetectionOptions {
  routeKey:        Ref<string>
  isMyWin:         Ref<boolean>
  isFinished:      ComputedRef<boolean>
  onBingoConfirmed: () => void
}

export function useBingoDetection(options: UseBingoDetectionOptions) {
  const { routeKey, isMyWin, isFinished, onBingoConfirmed } = options

  async function handleBingoDetected(card: { id: string | number }) {
    if (isMyWin.value || isFinished.value) return

    try {
      const { data } = await backendClient.post<{
        success: boolean
        hasBingo: boolean
        winner:   boolean
      }>(`/bingo/${routeKey.value}/check-bingo`, { card_id: card.id })

      if (isFinished.value) return

      if (data.success && data.hasBingo && data.winner) {
        isMyWin.value = true
        onBingoConfirmed()
      }
    } catch {
      // axios ya loguea errores de red
    }
  }

  return { handleBingoDetected }
}
