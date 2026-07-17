import { ref, computed, type ComputedRef } from 'vue'
import backendClient from '@js/plugins/axios'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'

export type WinnerEntry = NonNullable<BingoRoom['winners']>[number]

export const PRIZE_STATUS_LABELS: Record<string, string> = {
  waiting:    'Pendiente',
  processing: 'Procesando pago',
  claimed:    'Pagado',
  cancelled:  'Cancelado',
  suspended:  'Suspendido',
}

// Variant column defs (reused from game page — mirror of BingoVariant.php)
const CARD_VIEWER_COLS: Record<string, { label: string; min: number; max: number; freeRow: number | null; bg: string }[]> = {
  BINGO_75: [
    { label: 'B', min:  1, max: 15, freeRow: null, bg: '#1a5fff' },
    { label: 'I', min: 16, max: 30, freeRow: null, bg: '#cc1010' },
    { label: 'N', min: 31, max: 45, freeRow: 2,    bg: '#b08000' },
    { label: 'G', min: 46, max: 60, freeRow: null, bg: '#0a8040' },
    { label: 'O', min: 61, max: 75, freeRow: null, bg: '#8010d0' },
  ],
  BINGO_90: [
    { label: '1', min:  1, max:  9, freeRow: null, bg: '#1a5fff' },
    { label: '2', min: 10, max: 19, freeRow: null, bg: '#cc1010' },
    { label: '3', min: 20, max: 29, freeRow: null, bg: '#b08000' },
    { label: '4', min: 30, max: 39, freeRow: null, bg: '#0a8040' },
    { label: '5', min: 40, max: 49, freeRow: null, bg: '#8010d0' },
    { label: '6', min: 50, max: 59, freeRow: null, bg: '#1a5fff' },
    { label: '7', min: 60, max: 69, freeRow: null, bg: '#cc1010' },
    { label: '8', min: 70, max: 79, freeRow: null, bg: '#b08000' },
    { label: '9', min: 80, max: 90, freeRow: null, bg: '#0a8040' },
  ],
  BINGO_80: [
    { label: '1', min:  1, max: 20, freeRow: null, bg: '#1a5fff' },
    { label: '2', min: 21, max: 40, freeRow: null, bg: '#cc1010' },
    { label: '3', min: 41, max: 60, freeRow: null, bg: '#b08000' },
    { label: '4', min: 61, max: 80, freeRow: null, bg: '#0a8040' },
  ],
  BINGO_30: [
    { label: '1', min:  1, max: 10, freeRow: null, bg: '#1a5fff' },
    { label: '2', min: 11, max: 20, freeRow: null, bg: '#cc1010' },
    { label: '3', min: 21, max: 30, freeRow: null, bg: '#b08000' },
  ],
}
const CARD_VIEWER_ROWS: Record<string, number> = { BINGO_75: 5, BINGO_90: 3, BINGO_80: 4, BINGO_30: 3 }

/**
 * Modal de "ver cartón ganador": fetch del cartón, grid de números por
 * variante de bingo, y estado de carga/error. Aislado de BingoRoomDetails
 * porque no comparte estado con el resto de las secciones de la sala.
 */
export function useWinnerCardViewer(room: ComputedRef<BingoRoom>) {
  const loadingCard      = ref<string | null>(null)
  const cardViewerOpen   = ref(false)
  const cardViewerError  = ref(false)
  const cardViewerData   = ref<{ serialNumber: string; cardMatrix: Record<string, (number | null)[]> } | null>(null)
  const cardViewerWinner = ref<WinnerEntry | null>(null)

  const cardViewerVariant = computed(() => room.value.config?.game_variant ?? 'BINGO_75')
  const cardViewerCols = computed(() => CARD_VIEWER_COLS[cardViewerVariant.value] ?? CARD_VIEWER_COLS.BINGO_75)
  const cardViewerRows = computed(() => CARD_VIEWER_ROWS[cardViewerVariant.value] ?? 5)

  function isFreeViewerCell(col: { freeRow: number | null }, r: number): boolean {
    return col.freeRow !== null && col.freeRow === r - 1
  }

  function viewerNum(colLabel: string, r: number): number | null {
    const m = cardViewerData.value?.cardMatrix
    if (!m) return null
    const val = m[colLabel]?.[r - 1]
    return (val === undefined || val === null) ? null : val
  }

  async function openCardViewer(w: WinnerEntry): Promise<void> {
    loadingCard.value = w.id
    cardViewerOpen.value = true
    cardViewerError.value = false
    cardViewerData.value = null
    cardViewerWinner.value = w

    try {
      // room.id as route key — the controller uses route model binding
      const routeKey = room.value.id
      const { data: json } = await backendClient.get(`/bingo/${routeKey}/card/${w.winning_card_id}`)
      const card = json.card ?? json.data?.card
      if (card) {
        cardViewerData.value = { serialNumber: card.serialNumber, cardMatrix: card.cardMatrix }
      } else {
        cardViewerError.value = true
      }
    } catch {
      cardViewerError.value = true
    } finally {
      loadingCard.value = null
    }
  }

  function closeCardViewer() {
    cardViewerOpen.value = false
  }

  /** Username del ganador, o "Sin usuario" si el usuario fue eliminado/no resuelve. */
  function winnerUsername(w: WinnerEntry): string {
    return w.username ? `@${w.username}` : 'Sin usuario'
  }

  return {
    loadingCard, cardViewerOpen, cardViewerError, cardViewerData, cardViewerWinner,
    cardViewerCols, cardViewerRows,
    isFreeViewerCell, viewerNum, openCardViewer, closeCardViewer, winnerUsername,
  }
}
