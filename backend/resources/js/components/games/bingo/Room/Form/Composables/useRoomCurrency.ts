// useRoomCurrency.ts

import type { SelectOption } from '@/js/types/common/form/form'

/**
 * Actualiza el mapa de precios por moneda de manera inmutable.
 */
export function updateCtnPriceForCurrency(
  cardPriceByCurrency: Record<string, number | null> | undefined,
  currency: string | number,
  value: string | number | null
): Record<string, number | null> {
  const key = String(currency)
  const current = { ...(cardPriceByCurrency ?? {}) }
  const parsed = value === null || value === '' ? null : Number(value)
  current[key] = isNaN(Number(parsed)) ? null : (parsed as number)
  return current
}

/**
 * Obtiene el label de una moneda a partir de su id.
 */
export function getCurrencyLabel(
  id: string | number,
  options: SelectOption[]
): string {
  const found = options.find(opt => String(opt.value) === String(id))
  return found ? String(found.label) : String(id)
}
