import { ref, onUnmounted, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { WinnerEntry } from '@js/Components/Games/Bingo/views/inGame/types'

export interface WinningPosition { col: string; row: number; free: boolean }
export interface ViewedCard {
  serialNumber: string
  cardMatrix: number[][]
  colLabels?: string[]
  loading?: boolean
  winningPositions?: WinningPosition[]
}

function toRowMatrix(cardNumbers: Record<string, (number | null)[]> | number[][]): { matrix: number[][]; cols: string[] } {
  if (Array.isArray(cardNumbers)) {
    return { matrix: cardNumbers as number[][], cols: ['B', 'I', 'N', 'G', 'O'] }
  }
  const obj = cardNumbers as Record<string, (number | null)[]>
  // Columnas dinámicas: funciona con BINGO_75, 90, 80, 30
  const cols = Object.keys(obj)
  if (!cols.length) {
    return { matrix: [], cols: [] }
  }
  const numRows = obj[cols[0]]?.length ?? 0
  const matrix: number[][] = []
  for (let r = 0; r < numRows; r++) {
    matrix.push(cols.map(c => (obj[c]?.[r] ?? 0) as number))
  }
  return { matrix, cols }
}

/**
 * Visor de un cartón ganador específico (fetch + caché en memoria por partida).
 * El caché persiste durante la sesión de juego (cartones ganadores son
 * inmutables) y se limpia en onUnmounted para no llevar datos entre partidas.
 */
export function useCardViewer(deps: { roomRouteKey: Ref<string>; roomId: Ref<string> }) {
  const { roomRouteKey, roomId } = deps

  const cardCache = new Map<string, ViewedCard>()
  onUnmounted(() => cardCache.clear())

  const viewingCard      = ref<ViewedCard | null>(null)
  const viewingCardOwner = ref('')
  const loadingCardId    = ref<string | null>(null)

  async function openCardViewer(winner: WinnerEntry) {
    if (!winner.card_id || loadingCardId.value === winner.card_id) return

    const cached = cardCache.get(winner.card_id)
    if (cached) {
      viewingCardOwner.value = winner.user_name
      viewingCard.value      = cached
      return
    }

    const requestedCardId  = winner.card_id
    loadingCardId.value    = requestedCardId
    viewingCardOwner.value = winner.user_name
    viewingCard.value      = { serialNumber: winner.card_serial, cardMatrix: [], loading: true }
    try {
      const routeKey = roomRouteKey.value || roomId.value
      const { data } = await backendClient.get(`/bingo/${routeKey}/card/${requestedCardId}`)
      if (data.success && data.card) {
        const { matrix, cols } = toRowMatrix(data.card.cardMatrix ?? {})
        const resolved: ViewedCard = {
          serialNumber:     data.card.serialNumber ?? winner.card_serial,
          cardMatrix:       matrix,
          colLabels:        cols,
          loading:          false,
          winningPositions: data.winningPositions ?? [],
        }
        // Solo cachear si el cartón tiene datos reales — evita fijar permanentemente respuestas vacías
        if (matrix.length > 0) cardCache.set(requestedCardId, resolved)
        // Descartar si el usuario ya navegó a otro cartón mientras este cargaba
        if (loadingCardId.value === requestedCardId) viewingCard.value = resolved
      } else {
        if (loadingCardId.value === requestedCardId) viewingCard.value = null
      }
    } catch {
      if (loadingCardId.value === requestedCardId) viewingCard.value = null
    } finally {
      if (loadingCardId.value === requestedCardId) loadingCardId.value = null
    }
  }

  function closeCardViewer() {
    viewingCard.value = null
    viewingCardOwner.value = ''
  }

  return { viewingCard, viewingCardOwner, loadingCardId, openCardViewer, closeCardViewer }
}
