import { ref, computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

export type BingoRoomStep = 'basic' | 'currencies' | 'rounds' | 'options' | 'review'

export type StepStatus = 'error' | 'incomplete' | 'complete'

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
    basic:      ['date', 'time', 'days', 'roomType', 'connectionType', 'gameMode', 'prizePoolType', 'roomCommn', 'timeChip', 'jackpotPct', 'amountAddedPrizePool', 'accessCode', 'roomPassword', 'hostUserId', 'minCardsToStart', 'maxCardsToStart', 'minPlayers', 'maxPlayers'],
    currencies: ['allowedCurrencies', 'cardPriceUsd', 'cardPriceByCurrency', 'usdExchangeRoundStep'],
    // 'rounds' (cantidad de rondas) vive en el input de este mismo tab (RoomRoundsSection.vue)
    // pese a que el % de comisión que también depende de él está en "Datos básicos" — el ícono
    // de error debe reflejar dónde está el INPUT visualmente, no dónde vive el dato relacionado.
    rounds:     ['rounds', 'roundsPct', 'roundsConfig', 'percentageSum'],
    options:    ['serialsPct', 'numSerials', 'customCardPricePct', 'maxSpecialCards'],
    review:     [],
}

/**
 * Campos OPCIONALES (nunca generan error si están vacíos) que, si se dejan
 * sin completar, marcan la sección como "incomplete" (amarillo) en vez de
 * "complete" (verde) — la validación del formulario es eager (corre desde
 * el primer render, sin trackear qué tabs visitó el usuario), así que no
 * existe una señal real de "no revisado todavía"; el semáforo usa esta lista
 * en su lugar como proxy de "hay más para configurar acá, aunque ya se
 * pueda guardar así". 'currencies' y 'rounds' no tienen entrada porque todo
 * lo que contienen es obligatorio — para esos dos tabs el semáforo es
 * estrictamente rojo/verde.
 */
const STEP_OPTIONAL_KEYS: Partial<Record<BingoRoomStep, (keyof BingoRoomFormData)[]>> = {
    basic: ['jackpotPct', 'minCardsToStart', 'maxCardsToStart', 'minPlayers', 'maxPlayers'],
}

/**
 * Gestiona el paso activo del wizard de creación/edición de sala de bingo.
 *
 * Uso:
 *   const { step, stepIndex, stepLabel, steps, stepLabels, goNext, goPrev,
 *           goTo, canGoNext, stepHasErrors, stepStatus } = useBingoRoomStepper(errors, formGetter)
 *
 * @param errors     Computed de errores de validateBingoRoomForm (Record<string, any>).
 * @param formGetter Getter del form actual — opcional, solo necesario para stepStatus()
 *                    (semáforo de 3 estados). Sin él, stepStatus() cae a error/complete.
 */
export function useBingoRoomStepper(
    errors: { value: Record<string, unknown> },
    formGetter?: () => BingoRoomFormData
) {
    const stepIndex = ref(0)

    const step = computed<BingoRoomStep>(() => STEPS[stepIndex.value])

    const stepLabel = computed(() => STEP_LABELS[step.value])
    const stepDescription = computed(() => STEP_DESCRIPTIONS[step.value])

    function stepHasErrors(s: BingoRoomStep): boolean {
        const keys = STEP_ERROR_KEYS[s]
        return keys.some(k => k in errors.value)
    }

    /**
     * Semáforo de 3 estados para el tab, en orden de severidad:
     * - 'error': al menos un campo obligatorio de la sección está vacío/inválido.
     * - 'incomplete': sin errores, pero al menos un campo opcional relevante
     *   sigue vacío (ver STEP_OPTIONAL_KEYS) — para 'options', "vacío" significa
     *   los 3 toggles (seriales/cartón personalizado/cartón especial) apagados.
     * - 'complete': sin errores y todo lo relevante (obligatorio + opcional) cargado.
     */
    function stepStatus(s: BingoRoomStep): StepStatus {
        if (stepHasErrors(s)) return 'error'
        if (!formGetter) return 'complete'
        const form = formGetter()

        if (s === 'options') {
            const hasAnyOption = !!form.useSerials || !!form.allowCustomCard || !!form.allowSpecialCard
            return hasAnyOption ? 'complete' : 'incomplete'
        }

        const optionalKeys = STEP_OPTIONAL_KEYS[s]
        if (!optionalKeys) return 'complete'
        const allEmpty = optionalKeys.every(k => form[k] == null)
        return allEmpty ? 'incomplete' : 'complete'
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
        stepStatus,
    }
}
