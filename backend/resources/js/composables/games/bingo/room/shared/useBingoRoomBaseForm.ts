import { computed } from 'vue'
import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'

/**
 * Límites de porcentajes (expuestos para usar en atributos min/max del FormInput)
 */
export const BINGO_ROOM_PERCENTAGE_LIMITS = {
    roomCommn: { min: 10, max: 50 },
    jackpotPct: { min: 1, max: 15 },
    roundsPct: { min: 5, max: 30 },
    serialsPct: { min: 2, max: 35 },
} as const

/**
 * Composable base para formularios de sala de bingo (normal y automática)
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva de campos requeridos
 * - Validación de suma de porcentajes
 * - Exponer límites de porcentajes
 *
 * ✅ Sin lógica de clamping (delegado al FormInput)
 * ✅ v-model computado genérico
 */
export function useBingoRoomBaseForm<T extends BingoRoomFormData>(
    modelValueGetter: () => T,
    emit: (event: 'update:modelValue', value: T) => void,
    automaticRoom: () => boolean,
    useBaseUsd: () => boolean = () => true,
    isEdit: () => boolean = () => false
) {
    /* =======================
     * v-model computado
     * ======================= */

    const form = computed<T>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    /* =======================
     * Computed validaciones
     * ======================= */

    const errors = computed<Record<string, string | Record<string, string>>>(() => {
        const e: Record<string, string | Record<string, string>> = {}

        const isManualStart = !automaticRoom() && (form.value as BingoRoomFormData).startMode === 'MANUAL'

        if (!automaticRoom() && !isManualStart) {
            if (!form.value.date) {
                e.date = 'La fecha es requerida.'
            } else if (!isEdit()) {
                const today = new Date()
                today.setHours(0, 0, 0, 0)
                const selected = new Date(form.value.date + 'T00:00:00')
                if (selected < today) {
                    e.date = 'No puedes registrar una sala en una fecha anterior a hoy.'
                }
            }
        }

        if (automaticRoom() && (!(form.value as any).days || (form.value as any).days.length === 0)) {
            e.days = 'Debe seleccionar al menos un día.'
        }

        if (!isManualStart && !form.value.time) {
            e.time = 'La hora es requerida.'
        } else if (!isEdit() && !isManualStart && !automaticRoom() && form.value.date) {
            const now = new Date()
            const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
            const minDateTime = new Date(now.getTime() + 10 * 60 * 1000)
            const selectedDateTime = new Date(`${form.value.date}T${form.value.time}:00`)

            if (form.value.date === todayStr && selectedDateTime < minDateTime) {
                const h = String(minDateTime.getHours()).padStart(2, '0')
                const m = String(minDateTime.getMinutes()).padStart(2, '0')
                e.time = `La hora debe ser al menos 10 minutos desde ahora (${h}:${m} o posterior).`
            }
        }

        if (!form.value.roomType) {
            e.roomType = 'El tipo de sala es requerido.'
        }

        if (!form.value.gameMode) {
            e.gameMode = 'Modo de juego requerido.'
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

        if (form.value.useSerials) {
            if (!form.value.serialsPct) {
                e.serialsPct = 'El % de seriales es requerido.'
            }
            if (!form.value.numSerials) {
                e.numSerials = 'Ingrese cantidad de seriales.'
            }
        }

        const allowedCurrencies = form.value.allowedCurrencies ?? []

        if (useBaseUsd()) {
            if (!form.value.cardPriceUsd) {
                e.cardPriceUsd = 'Valor en USD requerido.'
            }
        } else {
            const byCurrency = form.value.cardPriceByCurrency ?? {}
            const currencyErrors: Record<string, string> = {}

            allowedCurrencies.forEach(cur => {
                const key = String(cur)
                const value = Number(byCurrency[key])
                if (isNaN(value) || value <= 0) {
                    currencyErrors[key] = 'Ingrese un precio válido.'
                }
            })

            if (Object.keys(currencyErrors).length) {
                e.cardPriceByCurrency = currencyErrors
            }
        }

        const rounds = Number(form.value.rounds) || 1
        const rPct = Number(form.value.roundsPct) || 0

        if (rounds > 1) {
            const roomCommn_ = Number(form.value.roomCommn) || 0
            const jackpot_ = Number(form.value.jackpotPct) || 0
            const serials_ = form.value.useSerials ? (Number(form.value.serialsPct) || 0) : 0
            const maxRounds = Math.max(0, 100 - roomCommn_ - jackpot_ - serials_)

            if (!form.value.roundsPct || rPct === 0) {
                e.roundsPct = 'El % de rondas es requerido.'
            } else if (rPct < BINGO_ROOM_PERCENTAGE_LIMITS.roundsPct.min) {
                e.roundsPct = `El % de rondas no puede ser menor a ${BINGO_ROOM_PERCENTAGE_LIMITS.roundsPct.min}.`
            } else if (rPct > maxRounds) {
                e.roundsPct = `El % de rondas no puede superar el disponible (${maxRounds.toFixed(2)}%).`
            }
        }

        const configs = form.value.roundsConfig ?? []
        const currencies = form.value.allowedCurrencies ?? []
        const roundErrors: Record<string, string> = {}

        configs.forEach((round, i) => {
            if (!round.bingoPattern) {
                roundErrors[`${i}_bingoPattern`] = 'El patrón es requerido.'
            }
            if (round.prizeType === 'fixed') {
                currencies.forEach(cur => {
                    const key = String(cur)
                    const val = round.prizeValueByCurrency?.[key]
                    if (val === null || val === undefined || Number(val) <= 0) {
                        roundErrors[`${i}_prizeValue_${key}`] = 'El valor para la moneda es requerido.'
                    }
                })
            } else if (round.prizeType === 'prize') {
                if (!round.prizeId) {
                    roundErrors[`${i}_prizeValue`] = 'Debe seleccionar un premio.'
                }
            } else {
                if (round.prizeValue === null || round.prizeValue === '' || Number(round.prizeValue) <= 0) {
                    roundErrors[`${i}_prizeValue`] = 'El valor es requerido.'
                }
            }
        })

        if (Object.keys(roundErrors).length) {
            e.roundsConfig = roundErrors
        }

        if ((form.value as any).allowCustomCard) {
            const price = Number((form.value as any).customCardPrice)
            if (!price || price < 1 || price > 10) {
                e.customCardPrice = 'El precio del cartón personalizado debe estar entre 1 y 10.'
            }
            const maxCards = Number((form.value as any).maxCustomCards)
            if (!maxCards || !Number.isInteger(maxCards) || maxCards < 1 || maxCards > 5) {
                e.maxCustomCards = 'La cantidad de cartones personalizados debe ser un entero entre 1 y 5.'
            }
        }

        const roomCommn = Number(form.value.roomCommn) || 0
        const jackpotPct = Number(form.value.jackpotPct) || 0
        const serialsPct = form.value.useSerials ? (Number(form.value.serialsPct) || 0) : 0
        const roundsPct = Number(form.value.roundsPct) || 0
        const totalPct = roomCommn + jackpotPct + serialsPct + roundsPct

        if (totalPct > 100) {
            e.percentageSum = `La suma (${totalPct.toFixed(2)}%) excede el 100%.`
        }

        return e
    })

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    /* =======================
     * Computeds para porcentajes
     * ======================= */

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

    /**
     * Porcentaje disponible para %Rondas = 100 - %Sala - %Jackpot - %Seriales (si useSerials)
     */
    const availablePct = computed(() => {
        const commn = Number(form.value.roomCommn) || 0
        const jk = Number(form.value.jackpotPct) || 0
        const ser = form.value.useSerials ? (Number(form.value.serialsPct) || 0) : 0
        return Math.max(0, Math.round((100 - commn - jk - ser) * 100) / 100)
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
        percentageLimits: BINGO_ROOM_PERCENTAGE_LIMITS,
    }
}
