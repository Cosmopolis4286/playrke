import { computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/**
 * Límites de porcentajes (expuestos para usar en atributos min/max del FormInput)
 */
const PERCENTAGE_LIMITS = {
  roomCommn: { min: 10, max: 50 },
  jackpotPct: { min: 1, max: 15 },
  roundsPct: { min: 3, max: 30 },
  serialsPct: { min: 2, max: 35 },
} as const

/**
 * Composable para formulario de sala de bingo
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva de campos requeridos
 * - Validación de suma de porcentajes
 * - Exponer límites de porcentajes
 *
 * ✅ Sin lógica de clamping (delegado al FormInput)
 * ✅ v-model computado
 */
export function useBingoRoomForm(
    modelValueGetter: () => BingoRoomFormData,
    emit: (event: 'update:modelValue', value: BingoRoomFormData) => void
) {
    /* =======================
     * v-model computado
     * ======================= */

    const form = computed<BingoRoomFormData>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    /* =======================
     * Computed validaciones
     * ======================= */

    /**
     * Validaciones reactivas
     */
    const errors = computed<Record<string, string>>(() => {
        const e: Record<string, string> = {}

        if (!form.value.date) {
            e.date = 'La fecha es requerida.'
        }

        if (!form.value.time) {
            e.time = 'La hora es requerida.'
        }

        if (!form.value.gameMode) {
            e.gameMode = 'El modo de juego es requerido.'
        }

        if (!form.value.prizePoolType) {
            e.prizePoolType = 'El tipo de bote es requerido.'
        }

        if (!form.value.allowedCurrencies || form.value.allowedCurrencies.length === 0) {
            e.allowedCurrencies = 'Debe seleccionar al menos una moneda.'
        }

        if (!form.value.roomCommn) {
            e.roomCommn = 'El % de sala es requerido.'
        }

        if (!form.value.timeChip) {
            e.timeChip = 'El tiempo de ficha es requerido.'
        }

        if (!form.value.rounds) {
            e.rounds = 'El número de rondas es requerido.'
        }

        if (!form.value.serialsPct) {
            e.serialsPct = 'El % de seriales es requerido.'
        }

        if (!form.value.numSerials) {
            e.numSerials = 'El número de seriales es requerido.'
        }

        if (!form.value.ctnPrice) {
            // Si hay más de una moneda permitida, aceptamos precios por moneda
            const allowed = form.value.allowedCurrencies
            if (Array.isArray(allowed) && allowed.length > 1) {
                const byCurrency = form.value.ctnPriceByCurrency ?? {}
                const missing = allowed.some(a => {
                    const key = String(a)
                    return byCurrency[key] === undefined || byCurrency[key] === null || byCurrency[key] === ''
                })
                if (missing) {
                    e.ctnPrice = 'Debe ingresar el precio del cartón para todas las monedas seleccionadas.'
                }
            } else {
                e.ctnPrice = 'El precio del cartón es requerido.'
            }
        }

        // Validar que la suma de porcentajes no exceda 100%
        const roomCommn = Number(form.value.roomCommn) || 0
        const jackpotPct = Number(form.value.jackpotPct) || 0
        const serialsPct = Number(form.value.serialsPct) || 0
        const roundsPct = Number(form.value.roundsPct) || 0
        const rounds = Number(form.value.rounds) || 1

        // Si hay más de 1 ronda, incluir roundsPct en la suma
        const totalPct = roundsPct > 0 && rounds > 1
            ? roomCommn + jackpotPct + serialsPct + roundsPct
            : roomCommn + jackpotPct + serialsPct

        if (totalPct > 100) {
            e.percentageSum = `La suma de porcentajes (${totalPct.toFixed(2)}%) no puede exceder el 100%.`
        }

        return e
    })

    /**
     * Estado de validez
     */
    const isValid = computed(() => Object.keys(errors.value).length === 0)

    /* =======================
     * Computeds para porcentajes
     * ======================= */

    const roomCommn = computed<string | number>({
        get: () => form.value.roomCommn ?? '',
        set: value => {
            form.value = { ...form.value, roomCommn: value as number }
        }
    })

    const jackpotPct = computed<string | number>({
        get: () => form.value.jackpotPct ?? '',
        set: value => {
            form.value = { ...form.value, jackpotPct: value as number }
        }
    })

    const roundsPct = computed<string | number>({
        get: () => form.value.roundsPct ?? '',
        set: value => {
            form.value = { ...form.value, roundsPct: value as number }
        }
    })

    const serialsPct = computed<string | number>({
        get: () => form.value.serialsPct ?? '',
        set: value => {
            form.value = { ...form.value, serialsPct: value as number }
        }
    })

    return {
        form,
        errors,
        isValid,
        roomCommn,
        jackpotPct,
        roundsPct,
        serialsPct,
        percentageLimits: PERCENTAGE_LIMITS,
    }
}
