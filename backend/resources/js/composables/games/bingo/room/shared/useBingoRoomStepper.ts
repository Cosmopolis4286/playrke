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
    basic:      ['startMode', 'date', 'time', 'days', 'roomType', 'allowNetworkSales', 'connectionType', 'gameMode', 'gameVariant', 'prizePoolType', 'roomCommn', 'timeChip', 'jackpotType', 'jackpotPct', 'amountAddedPrizePool', 'salesStartOffsetDays', 'accessCode', 'roomPassword', 'hostUserId', 'minCardsToStart', 'maxCardsToStart', 'minPlayers', 'maxPlayers'],
    currencies: ['allowedCurrencies', 'cardPriceUsd', 'cardPriceByCurrency', 'usdExchangeRoundStep'],
    // 'rounds' (cantidad de rondas) vive en el input de este mismo tab (RoomRoundsSection.vue)
    // pese a que el % de comisión que también depende de él está en "Datos básicos" — el ícono
    // de error debe reflejar dónde está el INPUT visualmente, no dónde vive el dato relacionado.
    rounds:     ['rounds', 'roundsPct', 'roundsConfig', 'percentageSum'],
    // Debe listar TODAS las claves de error que valida validateBingoRoomForm para este tab —
    // una clave faltante deja el semáforo en verde y "Siguiente" habilitado pese al error.
    options:    ['serialsPct', 'numSerials', 'customCardMode', 'customCardPricePct', 'customCardEveryX', 'moveFreeSpaceMode', 'moveFreeSpacePricePct', 'maxSpecialCards'],
    review:     [],
}

const ALL_MAPPED_ERROR_KEYS = new Set(Object.values(STEP_ERROR_KEYS).flat())

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
    // minCardsToStart/minPlayers ya no están acá: son obligatorios (dan error si faltan).
    basic: ['jackpotPct', 'maxCardsToStart', 'maxPlayers'],
}

/** Los 4 tabs editables — 'review' no tiene campos propios, su semáforo se deriva de estos. */
const EDITABLE_STEPS: Exclude<BingoRoomStep, 'review'>[] = ['basic', 'currencies', 'rounds', 'options']

export function stepHasErrorsFor(s: BingoRoomStep, errors: Record<string, unknown>): boolean {
    return STEP_ERROR_KEYS[s].some(k => k in errors)
}

function singleStepStatus(
    s: Exclude<BingoRoomStep, 'review'>,
    errors: Record<string, unknown>,
    form?: BingoRoomFormData
): StepStatus {
    if (stepHasErrorsFor(s, errors)) return 'error'
    if (!form) return 'complete'

    if (s === 'options') {
        const hasAnyOption = !!form.useSerials || !!form.allowCustomCard || !!form.allowSpecialCard
        return hasAnyOption ? 'complete' : 'incomplete'
    }

    const optionalKeys = STEP_OPTIONAL_KEYS[s]
    if (!optionalKeys) return 'complete'
    const allEmpty = optionalKeys.every(k => form[k] == null)
    return allEmpty ? 'incomplete' : 'complete'
}

/**
 * Semáforo de 3 estados para un tab del formulario de sala de Bingo, en
 * orden de severidad — función pura de módulo (no atada al estado interno
 * del stepper) para poder reutilizarla tanto en la barra de tabs
 * (BingoRoomForm.vue) como en el indicador de progreso de Revisión
 * (BingoRoomReviewStep.vue) sin duplicar las reglas ni instanciar un
 * segundo stepper solo para leer esto.
 *
 * - 'error': al menos un campo obligatorio de la sección está vacío/inválido.
 * - 'incomplete': sin errores, pero al menos un campo opcional relevante
 *   sigue vacío (ver STEP_OPTIONAL_KEYS) — para 'options', "vacío" significa
 *   los 3 toggles (seriales/cartón personalizado/cartón especial) apagados.
 * - 'complete': sin errores y todo lo relevante (obligatorio + opcional) cargado.
 *
 * 'review' no tiene campos propios (STEP_ERROR_KEYS.review está vacío a
 * propósito), así que su color se deriva del PEOR estado entre los 4 tabs
 * editables — nunca se evalúa de forma aislada, o siempre daría "complete"
 * sin importar que las otras secciones tuvieran errores reales.
 *
 * @param form Form actual — opcional; sin él, el resultado cae a error/complete
 *             (sin distinguir 'incomplete', ya que esa distinción necesita leer valores del form).
 */
export function getStepStatus(
    s: BingoRoomStep,
    errors: Record<string, unknown>,
    form?: BingoRoomFormData
): StepStatus {
    if (s === 'review') {
        const statuses = EDITABLE_STEPS.map(step => singleStepStatus(step, errors, form))
        if (statuses.includes('error')) return 'error'
        // Red de seguridad: una clave de error no mapeada en STEP_ERROR_KEYS no pinta
        // ningún tab, pero igual bloquea el guardado (isValid) — sin esto Revisión
        // quedaría en verde con "Guardar" deshabilitado y sin pista del porqué.
        if (Object.keys(errors).some(k => !ALL_MAPPED_ERROR_KEYS.has(k))) return 'error'
        if (statuses.includes('incomplete')) return 'incomplete'
        return 'complete'
    }
    return singleStepStatus(s, errors, form)
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
        return stepHasErrorsFor(s, errors.value)
    }

    function stepStatus(s: BingoRoomStep): StepStatus {
        return getStepStatus(s, errors.value, formGetter?.())
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
