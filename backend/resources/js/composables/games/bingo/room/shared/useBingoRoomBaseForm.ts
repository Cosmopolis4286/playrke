import { computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
import {
    BINGO_ROOM_PERCENTAGE_LIMITS,
    validateBingoRoomForm,
} from './bingoRoomFormSchema'

export { BINGO_ROOM_PERCENTAGE_LIMITS }

export function useBingoRoomBaseForm<T extends BingoRoomFormData>(
    modelValueGetter: () => T,
    emit: (event: 'update:modelValue', value: T) => void,
    automaticRoom: () => boolean,
    useBaseUsd: () => boolean = () => true,
    isEdit: () => boolean = () => false,
    currenciesWithActiveRate: () => string[] = () => []
) {
    /* ── v-model computado ── */

    const form = computed<T>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    /* ── Monedas sin tasa activa ── */

    const currenciesMissingRate = computed<string[]>(() => {
        const allowed = form.value.allowedCurrencies ?? []
        if (allowed.length <= 1) return []
        const activeRates = currenciesWithActiveRate()
        return allowed.filter(cur => !activeRates.includes(String(cur))).map(String)
    })

    /* ── Derivados compartidos con la función de validación ── */

    const roundsCount = computed(() => Math.max(1, Number(form.value.rounds) || 1))

    const availablePct = computed(() => {
        const commn = Number(form.value.roomCommn) || 0
        const jk    = Number(form.value.jackpotPct) || 0
        const ser   = form.value.useSerials ? (Number(form.value.serialsPct) || 0) : 0
        return Math.max(0, Math.round((100 - commn - jk - ser) * 100) / 100)
    })

    /* ── Validación con Zod ── */

    const errors = computed<Record<string, string | Record<string, string>>>(() =>
        validateBingoRoomForm(form.value, {
            automaticRoom:         automaticRoom(),
            isEdit:                isEdit(),
            useBaseUsd:            useBaseUsd(),
            currenciesMissingRate: currenciesMissingRate.value,
            availablePct:          availablePct.value,
            roundsCount:           roundsCount.value,
        })
    )

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    /* ── Computeds de porcentaje (v-model para FormInput) ── */

    const roomCommn = computed<string | number>({
        get: () => form.value.roomCommn ?? '',
        set: value => { form.value = { ...form.value, roomCommn: value as number } }
    })

    const jackpotPct = computed<string | number>({
        get: () => form.value.jackpotPct ?? '',
        set: value => { form.value = { ...form.value, jackpotPct: value as number } }
    })

    const roundsPct = computed<string | number>({
        get: () => form.value.roundsPct ?? '',
        set: value => { form.value = { ...form.value, roundsPct: value as number } }
    })

    const serialsPct = computed<string | number>({
        get: () => form.value.serialsPct ?? '',
        set: value => { form.value = { ...form.value, serialsPct: value as number } }
    })

    return {
        form,
        errors,
        isValid,
        roomCommn,
        jackpotPct,
        roundsPct,
        serialsPct,
        availablePct,
        currenciesMissingRate,
        percentageLimits: BINGO_ROOM_PERCENTAGE_LIMITS,
    }
}
