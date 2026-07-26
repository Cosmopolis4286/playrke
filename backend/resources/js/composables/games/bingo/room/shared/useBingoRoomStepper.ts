import { ref, computed } from 'vue'

export type BingoRoomStep = 'basic' | 'currencies' | 'rounds' | 'options' | 'review'

const STEPS: BingoRoomStep[] = ['basic', 'currencies', 'rounds', 'options', 'review']

export const STEP_LABELS: Record<BingoRoomStep, string> = {
    basic:      'Datos básicos',
    currencies: 'Monedas y precios',
    rounds:     'Rondas',
    options:    'Más / Opcional',
    review:     'Revisión',
}

export const STEP_ERROR_KEYS: Record<BingoRoomStep, string[]> = {
    basic:      ['date', 'time', 'days', 'roomType', 'connectionType', 'prizePoolType', 'roomCommn', 'timeChip', 'rounds', 'jackpotPct', 'amountAddedPrizePool'],
    currencies: ['allowedCurrencies', 'cardPriceUsd', 'cardPriceByCurrency', 'usdExchangeRoundStep'],
    rounds:     ['roundsPct', 'roundsConfig', 'percentageSum'],
    options:    ['serialsPct', 'numSerials', 'customCardPricePct', 'maxSpecialCards'],
    review:     [],
}

/**
 * Gestiona el paso activo del wizard de creación/edición de sala de bingo.
 *
 * Uso:
 *   const { step, stepIndex, stepLabel, steps, stepLabels, goNext, goPrev,
 *           goTo, canGoNext, stepHasErrors } = useBingoRoomStepper(errors)
 *
 * @param errors  Computed de errores de validateBingoRoomForm (Record<string, any>).
 */
export function useBingoRoomStepper(
    errors: { value: Record<string, unknown> }
) {
    const stepIndex = ref(0)

    const step = computed<BingoRoomStep>(() => STEPS[stepIndex.value])

    const stepLabel = computed(() => STEP_LABELS[step.value])

    function stepHasErrors(s: BingoRoomStep): boolean {
        const keys = STEP_ERROR_KEYS[s]
        return keys.some(k => k in errors.value)
    }

    const canGoNext = computed(() =>
        stepIndex.value < STEPS.length - 1 && !stepHasErrors(step.value)
    )

    const canGoPrev = computed(() => stepIndex.value > 0)

    function goNext(): void {
        if (canGoNext.value) stepIndex.value++
    }

    function goPrev(): void {
        if (canGoPrev.value) stepIndex.value--
    }

    function goTo(s: BingoRoomStep): void {
        const idx = STEPS.indexOf(s)
        if (idx !== -1) stepIndex.value = idx
    }

    function reset(): void {
        stepIndex.value = 0
    }

    return {
        step,
        stepIndex,
        stepLabel,
        steps:      STEPS,
        stepLabels: STEP_LABELS,
        canGoNext,
        canGoPrev,
        goNext,
        goPrev,
        goTo,
        reset,
        stepHasErrors,
    }
}
