import { type Ref, type ComputedRef, ref } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface UseBingoDetectionOptions {
  routeKey:        Ref<string>
  isMyWin:         Ref<boolean>
  isFinished:      ComputedRef<boolean>
  onBingoConfirmed: () => void
  onBingoError?:   (err: unknown) => void
}

export function useBingoDetection(options: UseBingoDetectionOptions) {
  const { routeKey, isMyWin, isFinished, onBingoConfirmed, onBingoError } = options

  const claimingBingo = ref(false)

  async function handleBingoDetected(card: { id: string | number }) {
    if (isMyWin.value || isFinished.value || claimingBingo.value) return

    claimingBingo.value = true
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
    } catch (err) {
      onBingoError?.(err)
    } finally {
      claimingBingo.value = false
    }
  }

  return { handleBingoDetected, claimingBingo }
}
