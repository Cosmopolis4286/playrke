import { type Ref, type ComputedRef, ref } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface UseBingoDetectionOptions {
  /** Prefijo de API — `/bingo/${routeKey}` o `/bingo/external/${link}` (ver Game/Index.vue). */
  apiBase:         Ref<string> | ComputedRef<string>
  isMyWin:         Ref<boolean>
  isFinished:      ComputedRef<boolean>
  onBingoConfirmed: () => void
  onBingoError?:   (err: unknown) => void
}

export function useBingoDetection(options: UseBingoDetectionOptions) {
  const { apiBase, isMyWin, isFinished, onBingoConfirmed, onBingoError } = options

  const claimingBingo = ref(false)

  async function handleBingoDetected(cards: { id: string | number }[]) {
    if (isMyWin.value || isFinished.value || claimingBingo.value) return
    if (!cards.length) return

    claimingBingo.value = true
    if ('vibrate' in navigator) navigator.vibrate([80, 30, 80])
    try {
      const { data } = await backendClient.post<{
        success: boolean
        hasBingo: boolean
        winner:   Record<string, unknown> | null
      }>(`${apiBase.value}/check-bingo`, {
        card_id:  cards[0].id,
        card_ids: cards.map(c => c.id),
      })

      if (isFinished.value || isMyWin.value) return

      if (data.success && data.hasBingo && data.winner) {
        isMyWin.value = true
        onBingoConfirmed()
      }
    } catch (err) {
      if (onBingoError) {
        onBingoError(err)
      } else {
        console.error('[useBingoDetection] Error al reclamar BINGO:', err)
      }
    } finally {
      claimingBingo.value = false
    }
  }

  return { handleBingoDetected, claimingBingo }
}
