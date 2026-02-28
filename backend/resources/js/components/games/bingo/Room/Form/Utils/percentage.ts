// percentage.ts
import { z } from 'zod'

/**
 * Configuración base para porcentaje estándar (0 - 100)
 */
export const percentageSchema = z
    .number({
        required_error: 'El porcentaje es requerido',
        invalid_type_error: 'Debe ser un número válido'
    })
    .min(0, 'No puede ser menor a 0')
    .max(100, 'No puede ser mayor a 100')

/**
 * Porcentaje entero obligatorio
 */
export const integerPercentageSchema = percentageSchema.refine(
    (val) => Number.isInteger(val),
    { message: 'Debe ser un número entero' }
)

/**
 * Porcentaje múltiplo configurable
 * Ej: múltiplos de 5
 */
export const multipleOfPercentage = (step: number) =>
    percentageSchema.refine(
        (val) => val % step === 0,
        { message: `Debe ser múltiplo de ${step}` }
    )

/**
 * Porcentaje con máximo de decimales configurable
 */
export const decimalPercentage = (maxDecimals: number) =>
    percentageSchema.refine((val) => {
        const decimals = val.toString().split('.')[1]
        return !decimals || decimals.length <= maxDecimals
    }, {
        message: `Máximo ${maxDecimals} decimales permitidos`
    })

/**
 * Normaliza porcentaje redondeando a step
 * Ej: step 5 → 10.347 → 10.5 o 10
 */
export function normalizeToStep(value: number, step: number): number {
    return Math.round(value / step) * step
}

/**
 * Convierte porcentaje a valor real
 * Ej: 25% de 200 = 50
 */
export function applyPercentage(
    base: number,
    percentage: number
): number {
    return (base * percentage) / 100
}

/**
 * Calcula qué porcentaje representa un valor sobre otro
 */
export function calculatePercentage(
    part: number,
    total: number
): number {
    if (total === 0) return 0
    return (part / total) * 100
}
