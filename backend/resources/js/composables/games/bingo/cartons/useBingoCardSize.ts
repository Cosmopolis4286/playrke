import { ref, watch, type Ref } from 'vue'

/**
 * Preferencia de tamaño de cartón (sm/md), persistida en localStorage.
 * Auto-sm en mobile si hay más de 2 cartones al montar.
 */
export function useBingoCardSize(cartonsCount: number) {
  const isMobile = typeof window !== 'undefined' && window.innerWidth <= 600
  const savedSize = typeof localStorage !== 'undefined'
    ? (localStorage.getItem('bingo-card-size') as 'sm' | 'md' | null)
    : null

  const cardSize: Ref<'sm' | 'md'> = ref(savedSize ?? (isMobile && cartonsCount > 2 ? 'sm' : 'md'))

  watch(cardSize, v => {
    if (typeof localStorage !== 'undefined') localStorage.setItem('bingo-card-size', v)
  })

  return { cardSize }
}
