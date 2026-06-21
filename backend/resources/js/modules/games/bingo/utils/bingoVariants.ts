/**
 * Fuente de verdad única (frontend) para la estructura de cada variante de
 * bingo. Debe mantenerse en espejo con `App\Domains\Games\Bingo\ValueObjects\BingoVariant`
 * (backend) — mismos rangos, conteos y casillas libres por columna.
 */

export interface BingoVariantColumn {
  label: string
  min: number
  max: number
  /** Cantidad de números por columna (BINGO_90 acepta hasta este máximo, no fijo) */
  count: number
  /** Índice de fila (0-based) con casilla libre, o null */
  freeRow: number | null
  /** Índice de color para la cabecera de columna (paleta cíclica) */
  colorIdx: number
}

export interface BingoVariantDef {
  numRows: number
  totalChips: number
  totalCells: number
  /** true si la variante tiene celdas vacías además de la casilla libre (BINGO_90) */
  hasEmptyCells: boolean
  columns: BingoVariantColumn[]
}

export const BINGO_VARIANT_DEFS: Record<string, BingoVariantDef> = {
  BINGO_75: {
    numRows: 5,
    totalChips: 75,
    totalCells: 24, // 25 - 1 libre
    hasEmptyCells: false,
    columns: [
      { label: 'B', min:  1, max: 15, count: 5, freeRow: null, colorIdx: 0 },
      { label: 'I', min: 16, max: 30, count: 5, freeRow: null, colorIdx: 1 },
      { label: 'N', min: 31, max: 45, count: 4, freeRow: 2,    colorIdx: 2 },
      { label: 'G', min: 46, max: 60, count: 5, freeRow: null, colorIdx: 3 },
      { label: 'O', min: 61, max: 75, count: 5, freeRow: null, colorIdx: 4 },
    ],
  },
  BINGO_90: {
    numRows: 3,
    totalChips: 90,
    totalCells: 15, // 3 filas × 5 números por fila
    hasEmptyCells: true,
    columns: [
      { label: '1', min:  1, max:  9, count: 3, freeRow: null, colorIdx: 0 },
      { label: '2', min: 10, max: 19, count: 3, freeRow: null, colorIdx: 1 },
      { label: '3', min: 20, max: 29, count: 3, freeRow: null, colorIdx: 2 },
      { label: '4', min: 30, max: 39, count: 3, freeRow: null, colorIdx: 3 },
      { label: '5', min: 40, max: 49, count: 3, freeRow: null, colorIdx: 4 },
      { label: '6', min: 50, max: 59, count: 3, freeRow: null, colorIdx: 0 },
      { label: '7', min: 60, max: 69, count: 3, freeRow: null, colorIdx: 1 },
      { label: '8', min: 70, max: 79, count: 3, freeRow: null, colorIdx: 2 },
      { label: '9', min: 80, max: 90, count: 3, freeRow: null, colorIdx: 3 },
    ],
  },
  BINGO_80: {
    numRows: 4,
    totalChips: 80,
    totalCells: 16,
    hasEmptyCells: false,
    columns: [
      { label: '1', min:  1, max: 20, count: 4, freeRow: null, colorIdx: 0 },
      { label: '2', min: 21, max: 40, count: 4, freeRow: null, colorIdx: 1 },
      { label: '3', min: 41, max: 60, count: 4, freeRow: null, colorIdx: 2 },
      { label: '4', min: 61, max: 80, count: 4, freeRow: null, colorIdx: 3 },
    ],
  },
  BINGO_30: {
    numRows: 3,
    totalChips: 30,
    totalCells: 9,
    hasEmptyCells: false,
    columns: [
      { label: '1', min:  1, max: 10, count: 3, freeRow: null, colorIdx: 0 },
      { label: '2', min: 11, max: 20, count: 3, freeRow: null, colorIdx: 1 },
      { label: '3', min: 21, max: 30, count: 3, freeRow: null, colorIdx: 2 },
    ],
  },
}

export function getBingoVariantDef(variant?: string | null): BingoVariantDef {
  return BINGO_VARIANT_DEFS[variant ?? 'BINGO_75'] ?? BINGO_VARIANT_DEFS.BINGO_75
}

/** Paleta cíclica de colores por columna, compartida entre todas las grillas de cartón. */
export const BINGO_COL_COLORS = [
  { bg: '#3b82f6', text: '#fff' }, // 0 blue
  { bg: '#8b5cf6', text: '#fff' }, // 1 violet
  { bg: '#ec4899', text: '#fff' }, // 2 pink
  { bg: '#f59e0b', text: '#000' }, // 3 amber
  { bg: '#10b981', text: '#fff' }, // 4 emerald
]
