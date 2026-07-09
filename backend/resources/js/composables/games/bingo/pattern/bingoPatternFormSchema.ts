import { parseBox } from './bingoBoxParser'

/* ─── Tipos ──────────────────────────────────────────────────────────────── */

export interface BingoPatternFormData {
    name:            string
    type_pattern:    'UNIQUE' | 'MULTIPLE'
    default_pattern?: 'Y' | 'N'
    boxes:           string
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
