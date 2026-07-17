import { computed, type ComputedRef } from 'vue'
import type { BingoVariantDef, BingoVariantColumn } from '@/js/modules/games/bingo/utils/bingoVariants'
import type { BingoCard, CartonFilter } from '@/js/types/games/bingo/card/in-game/bingo-card'

export function isFreeCell(col: BingoVariantColumn, r: number): boolean {
  return col.freeRow !== null && col.freeRow === r - 1
}

export function num(card: BingoCard, colLabel: string, r: number): number | null {
  const m = card.cardMatrix as Record<string, (number | null)[]> | null
  if (!m) return null
  const val = m[colLabel]?.[r - 1]
  return (val === undefined || val === null) ? null : val
}

/**
 * Estadísticas puras por cartón (hits/pct/hot) derivadas de los cartones y las
 * fichas sorteadas — sin timers ni estado de detección de BINGO (ver useBingoCardDetection).
 */
export function useBingoCardStats(
  cartons: ComputedRef<BingoCard[]>,
  drawnSet: ComputedRef<Set<number>>,
  variantDef: ComputedRef<BingoVariantDef>,
  hotThreshold: ComputedRef<number>,
) {
  function drawn(card: BingoCard, colLabel: string, r: number): boolean {
    const n = num(card, colLabel, r)
    return n !== null && drawnSet.value.has(n)
  }

  function cellCls(card: BingoCard, colLabel: string, r: number) {
    const col = variantDef.value.columns.find(c => c.label === colLabel)
    if (col && isFreeCell(col, r)) return 'ctn-cell--free'
    if (drawn(card, colLabel, r)) return 'ctn-cell--hit'
    return ''
  }

  function computeHits(card: BingoCard): number {
    let h = 0
    for (const col of variantDef.value.columns) {
      for (let r = 1; r <= variantDef.value.numRows; r++) {
        if (isFreeCell(col, r)) continue
        if (drawn(card, col.label, r)) h++
      }
    }
    return h
  }

  // Memoiza hits por cartón: se recalcula solo cuando cambian los cartones o las fichas sorteadas,
  // en vez de recalcular en cada llamada desde el template (footer, progreso, filtros).
  const hitsMap = computed(() => {
    const map = new Map<string, number>()
    for (const card of cartons.value) map.set(card.id, computeHits(card))
    return map
  })

  function hits(card: BingoCard): number { return hitsMap.value.get(card.id) ?? computeHits(card) }
  function pct(card: BingoCard) { return Math.round((hits(card) / variantDef.value.totalCells) * 100) }
  function isHot(card: BingoCard) { return hits(card) >= hotThreshold.value }

  function fullBingo(card: BingoCard): boolean {
    for (const col of variantDef.value.columns) {
      for (let r = 1; r <= variantDef.value.numRows; r++) {
        if (isFreeCell(col, r)) continue
        const n = num(card, col.label, r)
        // n === null: celda vacía por diseño (BINGO_90 hasEmptyCells) — no cuenta como pendiente
        if (n === null) continue
        if (!drawnSet.value.has(n)) return false
      }
    }
    return true
  }

  return { drawn, cellCls, computeHits, hitsMap, hits, pct, isHot, fullBingo }
}

/** Conteo de cartones por filtro (all/hot/win) — depende de isWinner externo (detección). */
export function useCartonFilterCounts(
  cartons: ComputedRef<BingoCard[]>,
  isWinner: (card: BingoCard) => boolean,
  isHot: (card: BingoCard) => boolean,
) {
  const filterCounts = computed(() => {
    let win = 0, hot = 0
    for (const c of cartons.value) {
      if (isWinner(c)) { win++; continue }
      if (isHot(c)) hot++
    }
    return { all: cartons.value.length, hot, win }
  })

  function getFilterCount(f: CartonFilter): number {
    return filterCounts.value[f]
  }

  return { filterCounts, getFilterCount }
}
