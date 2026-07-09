/* ─── Tipos ──────────────────────────────────────────────────────────────── */

export interface BingoPatternFormData {
    name:            string
    type_pattern:    'UNIQUE' | 'MULTIPLE'
    default_pattern?: 'Y' | 'N'
    boxes:           string
}

/* ─── Helpers internos ───────────────────────────────────────────────────── */

const COL_BASE: Record<string, number> = { B: 1, I: 16, N: 31, G: 46, O: 61 }

function parseBox(raw: string): number | null {
    const s = raw.trim().toUpperCase()
    const m = s.match(/^([BINGO])(\d)$/)
    if (m) {
        const row = parseInt(m[2], 10)
        return row >= 1 && row <= 5 ? COL_BASE[m[1]] + (row - 1) : null
    }
    const n = parseInt(s, 10)
    return !isNaN(n) && n >= 1 && n <= 75 ? n : null
}

/* ─── Validación reactiva ────────────────────────────────────────────────── */

export function validateBingoPatternForm(
    data: Partial<BingoPatternFormData>
): Record<string, string> {
    const e: Record<string, string> = {}

    if (!data.name?.trim()) {
        e.name = 'El nombre es requerido.'
    } else if (data.name.length > 30) {
        e.name = 'Máx. 30 caracteres.'
    }

    if (!data.type_pattern) {
        e.type_pattern = 'El tipo de patrón es requerido.'
    }

    if (!data.boxes?.trim()) {
        e.boxes = 'Las casillas son requeridas.'
    } else {
        const parts = data.boxes.split(';').filter(b => b.trim())
        const invalid = parts.find(b => parseBox(b) === null)
        if (invalid) {
            e.boxes = `Casilla inválida: "${invalid.trim()}". Use formato numérico (1-75) o letra+fila (B1-O5).`
        }
    }

    return e
}
