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

/**
 * Descripción corta de cada paso, mostrada bajo el título del step activo
 * (ver BingoRoomForm.vue) — ayuda al usuario a entender de un vistazo qué
 * configura cada tab sin tener que abrirlos todos para descubrirlo.
 */
export const STEP_DESCRIPTIONS: Record<BingoRoomStep, string> = {
    basic:      'Identidad de la sala (tipo, conexión, modo, variante, bote), horario, acceso privado si aplica, comisiones y capacidad (cartones/jugadores mín. y máx.).',
    currencies: 'Monedas en las que se vende el cartón y su precio — en USD base o manual por cada moneda.',
    rounds:     'Cantidad de rondas y cómo se reparte el bote entre ellas (porcentaje, monto fijo o premio).',
    options:    'Configuración opcional: cartón personalizado, cartón especial y seriales.',
    review:     'Resumen completo antes de guardar — revisa cada sección y activa la sala si corresponde.',
}

export const STEP_ERROR_KEYS: Record<BingoRoomStep, string[]> = {
    basic:      ['date', 'time', 'days', 'roomType', 'connectionType', 'gameMode', 'prizePoolType', 'roomCommn', 'timeChip', 'rounds', 'jackpotPct', 'amountAddedPrizePool', 'accessCode', 'roomPassword', 'hostUserId', 'minCardsToStart', 'maxCardsToStart', 'minPlayers', 'maxPlayers'],
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
    const stepDescription = computed(() => STEP_DESCRIPTIONS[step.value])

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
        stepDescription,
        steps:      STEPS,
        stepLabels: STEP_LABELS,
        stepDescriptions: STEP_DESCRIPTIONS,
        canGoNext,
        canGoPrev,
        goNext,
        goPrev,
        goTo,
        reset,
        stepHasErrors,
    }
}
