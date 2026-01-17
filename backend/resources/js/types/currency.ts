/**
 * Información de una moneda
 */
export interface Currency {
  readonly id: number
  readonly symbol: string
  readonly description: string
  readonly name: string
  readonly country_id: number // ID del país asociado
}
