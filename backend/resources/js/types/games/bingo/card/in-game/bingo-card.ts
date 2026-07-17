/** Estructura mínima que el backend serializa en toFrontendFormat(). */
export interface BingoCard {
  id: string
  serialNumber: string
  cardMatrix: Record<string, (number | null)[]>
}

export type CartonFilter = 'all' | 'hot' | 'win'
