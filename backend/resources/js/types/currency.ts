import { Country } from "./country"

/**
 * Información de una moneda
 */
export interface Currency {
    readonly id: number
    readonly symbol: string
    readonly description: string
    readonly name: string
    readonly country_id: number
    readonly iso_code: string
    readonly type: string
    readonly country?: Country // relación opcional incluida
}
