import { ref } from 'vue'

interface Currency { id: string; symbol: string; name: string; iso_code: string }

/**
 * Widget de monedas múltiples para el fee de entrada — un mismo cuadro
 * { currencyId: amount } compartido por los formularios de crear/editar sala.
 */
export function useTriviaFeeByCurrency(currencies: () => Currency[], initial: Record<string, number> = {}) {
  const feeByCurrency = ref<Record<string, number>>({ ...initial })

  function toggleCurrency(id: string) {
    if (feeByCurrency.value[id] !== undefined) {
      const copy = { ...feeByCurrency.value }
      delete copy[id]
      feeByCurrency.value = copy
    } else {
      feeByCurrency.value = { ...feeByCurrency.value, [id]: 0 }
    }
  }

  function getCurrencyById(id: string): Currency | undefined {
    return currencies().find(c => c.id === id)
  }

  function setFee(id: string, value: number) {
    feeByCurrency.value[id] = Math.max(0, value)
  }

  return { feeByCurrency, toggleCurrency, getCurrencyById, setFee }
}

/** Soporta tanto el nuevo formato { id: amount } como el legado entry_fee + entry_currency_id. */
export function initFeeByCurrencyFromRoom(room: { fee_by_currency?: Record<string, number>; entry_currency_id?: string; entry_fee?: number }): Record<string, number> {
  if (room.fee_by_currency && Object.keys(room.fee_by_currency).length > 0) {
    return { ...room.fee_by_currency }
  }
  if (room.entry_currency_id && Number(room.entry_fee) > 0) {
    return { [room.entry_currency_id]: Number(room.entry_fee) }
  }
  return {}
}
