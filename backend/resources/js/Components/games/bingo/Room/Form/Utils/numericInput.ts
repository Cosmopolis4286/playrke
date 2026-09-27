// numericInput.ts

/**
 * Unifica separadores a "." decimal para textos pegados en formato local:
 * - con "." y "," a la vez, el ÚLTIMO es el decimal y el otro es de miles
 *   ("1.234,50" → "1234.50", "1,234.50" → "1234.50");
 * - solo "," → decimal ("1,5" → "1.5");
 * - varios "." sin "," → son de miles ("1.234.567" → "1234567").
 */
function normalizeSeparators(raw: string): string {
    const lastDot = raw.lastIndexOf('.')
    const lastComma = raw.lastIndexOf(',')
    if (lastDot !== -1 && lastComma !== -1) {
        const thousands = lastComma > lastDot ? '.' : ','
        return raw.split(thousands).join('').replace(',', '.')
    }
    if (lastComma !== -1) return raw.replace(/,/g, (_m, i) => (i === lastComma ? '.' : ''))
    if (raw.indexOf('.') !== lastDot) return raw.replace(/\./g, '')
    return raw
}

export interface NumericInputLimits {
    min: number
    max: number
    /** Solo dígitos (sin punto decimal). */
    integer?: boolean
    /** Máximo de decimales permitidos (ignorado si integer). Default: 2. */
    decimals?: number
}

/**
 * Sanea un <input type="text"> numérico mientras se escribe/pega y devuelve el
 * número resultante (null si quedó vacío):
 * - descarta todo lo que no sea dígito (y un único punto si admite decimales;
 *   la coma se toma como punto) — así un pegado como "1.234,50 Bs" no rompe el campo;
 * - recorta los decimales sobrantes;
 * - al escribir, recorta de inmediato al máximo; el mínimo NO se fuerza todavía
 *   (con min=10 el usuario debe poder teclear "1" camino a "15");
 * - al pegar (inputType insertFromPaste) o con forceMin=true (blur/change)
 *   recorta también al mínimo.
 *
 * Reescribe el DOM directamente: si el valor saneado coincide con el del
 * modelo, Vue no re-renderiza y quedaría visible el texto crudo.
 */
export function sanitizeNumericInput(
    event: Event,
    limits: NumericInputLimits,
    forceMin = false
): number | null {
    const el = event.target as HTMLInputElement
    const clampMin = forceMin || (event as InputEvent).inputType === 'insertFromPaste'
    const decimals = limits.integer ? 0 : (limits.decimals ?? 2)

    let text = normalizeSeparators(el.value)
    if (decimals === 0) {
        text = text.split('.')[0].replace(/\D/g, '')
    } else {
        text = text.replace(/[^\d.]/g, '')
        const dot = text.indexOf('.')
        if (dot !== -1) {
            text = text.slice(0, dot + 1) + text.slice(dot + 1).replace(/\./g, '').slice(0, decimals)
        }
    }
    // Sin ceros a la izquierda ("007" → "7"), pero conservando "0.5"
    text = text.replace(/^0+(?=\d)/, '')

    if (text === '' || text === '.') {
        el.value = text === '.' ? '0.' : ''
        return null
    }

    let value = Number(text)
    if (value > limits.max) {
        value = limits.max
        text = String(value)
    } else if (clampMin && value < limits.min) {
        value = limits.min
        text = String(value)
    } else if (clampMin && text.endsWith('.')) {
        text = text.slice(0, -1)
    }

    el.value = text
    return value
}
