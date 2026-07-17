import { computed } from 'vue'
import { useTriviaFeeByCurrency } from './useTriviaFeeByCurrency'

interface EditForm {
  title: string
  description: string
  max_players: number
  prize_currency_id: string
  prize_amount: number
}

/**
 * Validaciones cliente adicionales exclusivas del formulario de edición
 * (espejo de las reglas del backend) — no aplican en creación porque esos
 * campos ahí usan sus propios valores por defecto ya válidos.
 */
export function useTriviaRoomEditValidation(
  form: EditForm,
  feeByCurrency: ReturnType<typeof useTriviaFeeByCurrency>['feeByCurrency'],
  getCurrencyById: ReturnType<typeof useTriviaFeeByCurrency>['getCurrencyById'],
) {
  const titleError = computed(() => {
    const value = (form.title || '').trim()
    if (!value) return 'El título es obligatorio'
    if (value.length > 255) return 'El título no puede exceder 255 caracteres'
    return ''
  })

  const descriptionError = computed(() => {
    if ((form.description || '').length > 1000) return 'La descripción no puede exceder 1000 caracteres'
    return ''
  })

  const maxPlayersError = computed(() => {
    const value = Number(form.max_players)
    if (!form.max_players && form.max_players !== 0) return 'Debes indicar el máximo de jugadores'
    if (!Number.isInteger(value)) return 'Debe ser un número entero'
    if (value < 2) return 'La sala debe permitir al menos 2 jugadores'
    if (value > 500) return 'Máximo 500 jugadores permitidos'
    return ''
  })

  // Cada precio de entrada debe ser mayor a 0 si la moneda está activa
  const feeByCurrencyError = computed(() => {
    for (const cid of Object.keys(feeByCurrency.value)) {
      const amount = Number(feeByCurrency.value[cid])
      if (!Number.isFinite(amount) || amount <= 0) {
        const currency = getCurrencyById(cid)
        return `Indica un precio de entrada mayor a 0 para ${currency?.iso_code ?? 'la moneda seleccionada'}`
      }
    }
    return ''
  })

  // Si hay un monto de premio, se debe seleccionar la moneda del premio
  const prizeCurrencyError = computed(() => {
    if (Number(form.prize_amount) > 0 && !form.prize_currency_id) {
      return 'Selecciona la moneda del premio cuando indicas un monto'
    }
    return ''
  })

  const prizeAmountError = computed(() => {
    const value = Number(form.prize_amount)
    if (form.prize_amount !== null && form.prize_amount !== undefined && value < 0) {
      return 'El monto del premio no puede ser negativo'
    }
    return ''
  })

  return { titleError, descriptionError, maxPlayersError, feeByCurrencyError, prizeCurrencyError, prizeAmountError }
}
