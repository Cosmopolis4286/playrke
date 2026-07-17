import { ref, computed, watch } from 'vue'
import backendClient from '@js/plugins/axios'
import type { GameHistoryRow, GameHistoryDetail } from '@/js/types/games/bingo/history/my-games'

/**
 * Modal de detalle de una partida + carrusel de cartones del jugador.
 * El carrusel vive aquí (no en un composable aparte) porque su reset
 * depende de `activeCardTab`, que también se maneja en este mismo estado.
 */
export function useMyGamesDetail() {
  const showDetail    = ref(false)
  const detailLoading = ref(false)
  const detail         = ref<GameHistoryDetail | null>(null)
  const activeCardTab  = ref<'all' | 'winners'>('all')

  const displayCards = computed(() => {
    if (!detail.value) return []
    if (activeCardTab.value === 'winners') {
      return detail.value.player_cards.filter(c => c.is_winner)
    }
    return detail.value.player_cards
  })

  // ── Carrusel de cartones ──────────────────────────────────────────────────
  const cardIndex = ref(0)

  watch(displayCards, () => { cardIndex.value = 0 })

  /** Clamp defensivo: evita index-out-of-bounds si displayCards se acorta
   * (cambio de tab a "winners") antes de que el watcher reinicie cardIndex. */
  const activeCard = computed(() => {
    const cards = displayCards.value
    return cards[cardIndex.value] ?? cards[0] ?? null
  })

  function prevCard() {
    cardIndex.value = cardIndex.value === 0 ? displayCards.value.length - 1 : cardIndex.value - 1
  }
  function nextCard() {
    cardIndex.value = cardIndex.value === displayCards.value.length - 1 ? 0 : cardIndex.value + 1
  }

  async function openDetail(row: GameHistoryRow) {
    detail.value        = null
    activeCardTab.value = 'all'
    showDetail.value    = true
    detailLoading.value = true
    try {
      const { data } = await backendClient.get(`/bingo/my-games/${row.room_id}/detail`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      detail.value = data.data ?? null
      if (detail.value?.player_cards.some(c => c.is_winner)) {
        activeCardTab.value = 'winners'
      }
    } catch {
      showDetail.value = false
    } finally {
      detailLoading.value = false
    }
  }

  return {
    showDetail, detailLoading, detail, activeCardTab,
    displayCards, cardIndex, activeCard,
    prevCard, nextCard, openDetail,
  }
}
