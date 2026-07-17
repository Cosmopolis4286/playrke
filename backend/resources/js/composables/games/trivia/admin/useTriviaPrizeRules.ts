import { computed, type Ref } from 'vue'
import { getMinLocalDatetime } from '@js/composables/useTimezone'
import type { PrizePayout } from './useTriviaPrizePayouts'

interface PrizeRulesForm {
  prize_model: 'guaranteed' | 'pool'
  prize_currency_id: string
  sponsor_prize: number
  platform_rake_percent: number
  scheduled_at: string
}

/**
 * Reglas de validación cliente compartidas entre crear/editar sala — espejo
 * de las reglas del backend para dar feedback inmediato.
 */
export function useTriviaPrizeRules(form: PrizeRulesForm, prizePayouts: Ref<PrizePayout[]>) {
  const minScheduledAt = computed(() => getMinLocalDatetime())

  const scheduledAtError = computed(() => {
    if (!form.scheduled_at) return ''
    if (new Date(form.scheduled_at) <= new Date()) {
      return 'La fecha y hora de inicio debe ser futura'
    }
    return ''
  })

  // Si hay aporte de sponsor, se debe seleccionar la moneda del premio
  const sponsorPrizeError = computed(() => {
    const value = Number(form.sponsor_prize)
    if (value < 0) return 'El aporte del sponsor no puede ser negativo'
    if (value > 0 && !form.prize_currency_id) {
      return 'Selecciona la moneda del premio cuando defines un aporte de sponsor'
    }
    return ''
  })

  const platformRakeError = computed(() => {
    const value = Number(form.platform_rake_percent)
    if (!Number.isFinite(value) || value < 0) return 'La comisión no puede ser negativa'
    if (value > 30) return 'La comisión de la plataforma no puede superar 30%'
    return ''
  })

  // La suma de porcentajes de prize_payouts debe ser ≈ 100% cuando el modelo es 'pool'
  const prizePayoutsError = computed(() => {
    if (form.prize_model !== 'pool') return ''
    if (prizePayouts.value.length === 0) return ''
    const total = prizePayouts.value.reduce((sum, p) => sum + (Number(p.percent) || 0), 0)
    if (Math.abs(total - 1) > 0.01) {
      return `La suma de los porcentajes debe ser 100% (actual: ${Math.round(total * 100)}%)`
    }
    return ''
  })

  return { minScheduledAt, scheduledAtError, sponsorPrizeError, platformRakeError, prizePayoutsError }
}
