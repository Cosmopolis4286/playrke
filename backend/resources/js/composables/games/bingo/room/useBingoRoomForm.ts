import { computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/bingo-room'

/**
 * Composable para formulario de sala de bingo
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva
 * - Exponer estado de edición
 *
 * ❌ Sin watchers
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
     * Computed
     * ======================= */

    /**
     * Validaciones reactivas
     */
    const errors = computed<Record<string, string>>(() => {
        const e: Record<string, string> = {}

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
            e.ctnPrice = 'El precio del cartón es requerido.'
        }

        return e
    })

    /**
     * Estado de validez
     */
    const isValid = computed(() => Object.keys(errors.value).length === 0)

    return {
        form,
        errors,
        isValid
    }
}
