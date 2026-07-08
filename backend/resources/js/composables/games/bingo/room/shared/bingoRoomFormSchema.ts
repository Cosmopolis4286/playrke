import { z } from 'zod'
import { percentageSchema } from '@/js/Components/Games/Bingo/Room/Form/Utils/percentage'

/* ─── Rangos de porcentajes ─────────────────────────────────────────── */

export const BINGO_ROOM_PERCENTAGE_LIMITS = {
    roomCommn:  { min: 10, max: 50  },
    jackpotPct: { min: 1,  max: 15  },
    roundsPct:  { min: 5,  max: 30  },
    serialsPct: { min: 2,  max: 35  },
} as const

/* ─── Sub-schemas ────────────────────────────────────────────────────── */

const roundConfigSchema = z.object({
    bingoPattern: z.union([z.number(), z.string()]).nullable(),
    prizeType:    z.enum(['percentage', 'fixed', 'prize']),
    prizeValue:   z.union([z.number(), z.string()]).nullable(),
    prizeValueByCurrency: z.record(z.number().nullable()).optional(),
    prizeId:   z.union([z.number(), z.string()]).nullable().optional(),
    prizeName: z.string().nullable().optional(),
})

/* ─── Schema base del formulario ─────────────────────────────────────── */

export const bingoRoomFormSchema = z.object({
    date:             z.string().nullable().optional(),
    time:             z.string().nullable().optional(),
    startMode:        z.enum(['SCHEDULED', 'MANUAL']).optional(),
    roomType:         z.enum(['PRIVATE', 'PUBLIC', 'VIP', 'DEMO']).nullable().optional(),
    gameMode:         z.union([z.number(), z.string()]).nullable().optional(),
    prizePoolType:    z.union([z.number(), z.string()]).nullable().optional(),
    allowedCurrencies: z.array(z.union([z.number(), z.string()])).optional(),
    roomsPerCurrency:  z.boolean().optional(),
    roomCommn:    z.number().nullable().optional(),
    jackpotPct:   z.number().nullable().optional(),
    timeChip:     z.number().nullable().optional(),
    rounds:       z.number().nullable().optional(),
    roundsPct:    z.number().nullable().optional(),
    roundsConfig: z.array(roundConfigSchema).optional(),
    useSerials:   z.boolean().optional(),
    serialsPct:   z.number().nullable().optional(),
    numSerials:   z.number().nullable().optional(),
    useBaseUsd:   z.boolean().optional(),
    cardPriceUsd: z.number().nullable().optional(),
    usdExchangeRoundStep: z.number().nullable().optional(),
    cardPriceByCurrency:  z.record(z.number().nullable()).optional(),
    activateRoom:    z.boolean().optional(),
    allowCustomCard: z.boolean().optional(),
    customCardPricePct: z.number().nullable().optional(),
    allowSpecialCard:   z.boolean().optional(),
    maxSpecialCards:    z.number().nullable().optional(),
    // Solo salas automáticas
    days: z.array(z.string()).optional(),
})

export type BingoRoomFormInput = z.input<typeof bingoRoomFormSchema>

/* ─── Función que construye errores contextuales ─────────────────────── */

export interface BingoRoomValidationContext {
    automaticRoom:  boolean
    isEdit:         boolean
    useBaseUsd:     boolean
    currenciesMissingRate: string[]
}

export function validateBingoRoomForm(
    data: BingoRoomFormInput,
    ctx: BingoRoomValidationContext
): Record<string, string | Record<string, string>> {

    const e: Record<string, string | Record<string, string>> = {}
    const isManualStart = !ctx.automaticRoom && data.startMode === 'MANUAL'

    /* ── Fecha ── */
    if (!ctx.automaticRoom && !isManualStart) {
        if (!data.date) {
            e.date = 'La fecha es requerida.'
        } else if (!ctx.isEdit) {
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            if (new Date(data.date + 'T00:00:00') < today) {
                e.date = 'No puedes registrar una sala en una fecha anterior a hoy.'
            }
        }
    }

    /* ── Días (automática) ── */
    if (ctx.automaticRoom && (!data.days || data.days.length === 0)) {
        e.days = 'Debe seleccionar al menos un día.'
    }

    /* ── Hora ── */
    if (!isManualStart && !data.time) {
        e.time = 'La hora es requerida.'
    } else if (!ctx.isEdit && !isManualStart && !ctx.automaticRoom && data.date && data.time) {
        const now = new Date()
        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
        const minDateTime = new Date(now.getTime() + 10 * 60 * 1000)
        const selectedDateTime = new Date(`${data.date}T${data.time}:00`)
        if (data.date === todayStr && selectedDateTime < minDateTime) {
            const h = String(minDateTime.getHours()).padStart(2, '0')
            const m = String(minDateTime.getMinutes()).padStart(2, '0')
            e.time = `La hora debe ser al menos 10 minutos desde ahora (${h}:${m} o posterior).`
        }
    }

    /* ── Campos requeridos simples ── */
    if (!data.roomType)      e.roomType     = 'El tipo de sala es requerido.'
    if (!data.gameMode)      e.gameMode     = 'Modo de juego requerido.'
    if (!data.prizePoolType) e.prizePoolType = 'El tipo de bote es requerido.'

    /* ── Monedas ── */
    if (!data.allowedCurrencies || data.allowedCurrencies.length === 0) {
        e.allowedCurrencies = 'Debe seleccionar al menos una moneda.'
    } else if (ctx.currenciesMissingRate.length > 0) {
        e.allowedCurrencies = 'Hay monedas seleccionadas sin tasa de cambio activa.'
    }

    /* ── Porcentajes con rango ── */
    if (!data.roomCommn) {
        e.roomCommn = 'El % de sala es requerido.'
    } else {
        const r = percentageSchema
            .min(BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.min, `Mínimo ${BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.min}%.`)
            .max(BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.max, `Máximo ${BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.max}%.`)
            .safeParse(data.roomCommn)
        if (!r.success) e.roomCommn = r.error.issues[0].message
    }

    /* ── Otros campos requeridos ── */
    if (!data.timeChip) e.timeChip = 'El tiempo de ficha es requerido.'
    if (!data.rounds)   e.rounds   = 'El número de rondas es requerido.'

    /* ── Seriales ── */
    if (data.useSerials) {
        if (!data.serialsPct) {
            e.serialsPct = 'El % de seriales es requerido.'
        } else {
            const r = percentageSchema
                .min(BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.min, `Mínimo ${BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.min}%.`)
                .max(BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.max, `Máximo ${BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.max}%.`)
                .safeParse(data.serialsPct)
            if (!r.success) e.serialsPct = r.error.issues[0].message
        }
        if (!data.numSerials) e.numSerials = 'Ingrese cantidad de seriales.'
    }

    /* ── Precio del cartón ── */
    if (ctx.useBaseUsd) {
        if (!data.cardPriceUsd) e.cardPriceUsd = 'Valor en USD requerido.'
    } else {
        const allowed = data.allowedCurrencies ?? []
        const byCurrency = data.cardPriceByCurrency ?? {}
        const currencyErrors: Record<string, string> = {}
        allowed.forEach(cur => {
            const key = String(cur)
            const val = Number(byCurrency[key])
            if (isNaN(val) || val <= 0) currencyErrors[key] = 'Ingrese un precio válido.'
        })
        if (Object.keys(currencyErrors).length) e.cardPriceByCurrency = currencyErrors
    }

    /* ── % Rondas ── */
    const rounds = Number(data.rounds) || 1
    const rPct   = Number(data.roundsPct) || 0
    if (rounds > 1) {
        const roomCommn_  = Number(data.roomCommn) || 0
        const jackpot_    = Number(data.jackpotPct) || 0
        const serials_    = data.useSerials ? (Number(data.serialsPct) || 0) : 0
        const maxRounds   = Math.max(0, 100 - roomCommn_ - jackpot_ - serials_)

        if (!data.roundsPct || rPct === 0) {
            e.roundsPct = 'El % de rondas es requerido.'
        } else {
            const r = percentageSchema
                .min(BINGO_ROOM_PERCENTAGE_LIMITS.roundsPct.min, `Mínimo ${BINGO_ROOM_PERCENTAGE_LIMITS.roundsPct.min}%.`)
                .max(maxRounds, `El % de rondas no puede superar el disponible (${maxRounds.toFixed(2)}%).`)
                .safeParse(rPct)
            if (!r.success) e.roundsPct = r.error.issues[0].message
        }
    }

    /* ── Config de rondas ── */
    const configs    = data.roundsConfig ?? []
    const currencies = data.allowedCurrencies ?? []
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
            if (!round.prizeId) roundErrors[`${i}_prizeValue`] = 'Debe seleccionar un premio.'
        } else {
            if (round.prizeValue === null || round.prizeValue === '' || Number(round.prizeValue) <= 0) {
                roundErrors[`${i}_prizeValue`] = 'El valor es requerido.'
            }
        }
    })

    if (Object.keys(roundErrors).length) e.roundsConfig = roundErrors

    /* ── Cartón personalizado ── */
    if (data.allowCustomCard) {
        const pricePct = Number(data.customCardPricePct)
        if (!pricePct || pricePct < 1 || pricePct > 10) {
            e.customCardPricePct = 'El % del precio del cartón personalizado debe estar entre 1 y 10.'
        }
    }

    /* ── Cartón especial ── */
    if (data.allowSpecialCard) {
        const max = Number(data.maxSpecialCards)
        if (!max || !Number.isInteger(max) || max < 1 || max > 5) {
            e.maxSpecialCards = 'La cantidad de cartones especiales debe ser un entero entre 1 y 5.'
        }
    }

    /* ── Suma total de porcentajes ── */
    const roomCommn  = Number(data.roomCommn)  || 0
    const jackpotPct = Number(data.jackpotPct) || 0
    const serialsPct = data.useSerials ? (Number(data.serialsPct) || 0) : 0
    const roundsPct  = Number(data.roundsPct)  || 0
    const totalPct   = roomCommn + jackpotPct + serialsPct + roundsPct
    if (totalPct > 100) {
        e.percentageSum = `La suma (${totalPct.toFixed(2)}%) excede el 100%.`
    }

    return e
}
