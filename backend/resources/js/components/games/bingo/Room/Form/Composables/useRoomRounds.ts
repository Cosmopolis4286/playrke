// useRoomRounds.ts
import { ref, computed, watch } from 'vue'
import { z } from 'zod'

/**
 * Schema base porcentaje 0 - 100
 */
const percentageSchema = z.number().min(0).max(100)

/**
 * Composable para manejar rondas de sala
 */
export function useRoomRounds(options?: {
    maxRounds?: number
    percentageStep?: number
    autoNormalize?: boolean
}) {
    const maxRounds = options?.maxRounds ?? 15
    const percentageStep = options?.percentageStep ?? 5
    const autoNormalize = options?.autoNormalize ?? true

    const rounds = ref<number>(1)
    const roundsPct = ref<number>(0)

    const errors = ref<{
        rounds?: string
        roundsPct?: string
    }>({})

    /**
     * Validar rondas
     */
    function validateRounds() {
        if (rounds.value < 1) {
            errors.value.rounds = 'Debe haber al menos 1 ronda'
        } else if (rounds.value > maxRounds) {
            errors.value.rounds = `Máximo ${maxRounds} rondas`
        } else {
            errors.value.rounds = undefined
        }
    }

    /**
     * Validar porcentaje
     */
    function validatePercentage() {
        try {
            percentageSchema.parse(roundsPct.value)

            if (roundsPct.value % percentageStep !== 0) {
                errors.value.roundsPct = `Debe ser múltiplo de ${percentageStep}`
            } else {
                errors.value.roundsPct = undefined
            }
        } catch {
            errors.value.roundsPct = 'Porcentaje inválido (0 - 100)'
        }
    }

    /**
     * Normalizar automáticamente al múltiplo configurado
     */
    function normalizePercentage() {
        if (!autoNormalize) return
        roundsPct.value =
            Math.round(roundsPct.value / percentageStep) * percentageStep
    }

    /**
     * Aplicar porcentaje a un valor base
     */
    function applyPercentage(base: number): number {
        return (base * roundsPct.value) / 100
    }

    /**
     * Computed helpers
     */
    const isValid = computed(() => {
        return !errors.value.rounds && !errors.value.roundsPct
    })

    const percentageDecimal = computed(() => {
        return roundsPct.value / 100
    })

    /**
     * Watchers automáticos
     */
    watch(rounds, validateRounds, { immediate: true })
    watch(roundsPct, () => {
        normalizePercentage()
        validatePercentage()
    }, { immediate: true })

    return {
        rounds,
        roundsPct,
        errors,
        isValid,
        percentageDecimal,
        validateRounds,
        validatePercentage,
        normalizePercentage,
        applyPercentage
    }
}
