import { Country } from "./country"

/**
 * Información de una moneda.
 * `level` viene del pivot commerces_currencies (1 = principal).
 */
export interface Currency {
    readonly id: number
    readonly symbol: string
    readonly description: string
    readonly name: string
    readonly country_id: number
    readonly iso_code: string
    readonly type: string
    readonly level?: number    // nivel de asignación en el pivot (1 = primario)
    readonly country?: Country // relación opcional incluida
}
