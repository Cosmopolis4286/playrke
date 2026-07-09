/* ─── Parser compartido de casillas de bingo ─────────────────────────────
 * Usado por BingoCardSelector.vue y bingoPatternFormSchema.ts.
 * ─────────────────────────────────────────────────────────────────────── */

export const BINGO_LETTERS = ['B', 'I', 'N', 'G', 'O'] as const
export type BingoLetter = typeof BINGO_LETTERS[number]

export const COL_BASE: Record<BingoLetter, number> = { B: 1, I: 16, N: 31, G: 46, O: 61 }

export function cellNumber(letter: BingoLetter, row: number): number {
    return COL_BASE[letter] + (row - 1)
}

/**
 * Convierte una casilla en cualquier formato ('B1', 'b1', '1', '16') a su número 1-75.
 * Devuelve null si el formato es inválido.
 */
export function parseBox(raw: string): number | null {
    const s = raw.trim().toUpperCase()
    const m = s.match(/^([BINGO])(\d)$/)
    if (m) {
        const row = parseInt(m[2], 10)
        return row >= 1 && row <= 5 ? COL_BASE[m[1] as BingoLetter] + (row - 1) : null
    }
    const n = parseInt(s, 10)
    return !isNaN(n) && n >= 1 && n <= 75 ? n : null
}
