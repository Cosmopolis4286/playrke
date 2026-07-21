import { z } from 'zod'
import { percentageSchema } from '@/js/Components/Games/Bingo/Room/Form/Utils/percentage'

/* ─── Rangos de porcentajes ─────────────────────────────────────────── */

export const BINGO_ROOM_PERCENTAGE_LIMITS = {
    roomCommn:  { min: 10, max: 50  },
    jackpotPct: { min: 1,  max: 15  },
    roundsPct:  { min: 5,  max: 90  },
    serialsPct: { min: 2,  max: 35  },
} as const

/* ─── Schemas de porcentaje pre-compilados (fix: no reconstruir en cada computed) ── */

const roomCommnSchema = percentageSchema
    .min(BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.min,  `Mínimo ${BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.min}%.`)
    .max(BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.max,  `Máximo ${BINGO_ROOM_PERCENTAGE_LIMITS.roomCommn.max}%.`)

const serialsPctSchema = percentageSchema
    .min(BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.min, `Mínimo ${BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.min}%.`)
    .max(BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.max, `Máximo ${BINGO_ROOM_PERCENTAGE_LIMITS.serialsPct.max}%.`)

const customCardPricePctSchema = percentageSchema
    .min(1, 'El % del precio del cartón personalizado debe estar entre 1 y 5.')
    .max(5, 'El % del precio del cartón personalizado debe estar entre 1 y 5.')

const moveFreeSpacePricePctSchema = percentageSchema
    .min(1, 'El % de recargo por mover la casilla libre debe estar entre 1 y 5.')
    .max(5, 'El % de recargo por mover la casilla libre debe estar entre 1 y 5.')

const roundsPctBaseSchema = percentageSchema
    .min(BINGO_ROOM_PERCENTAGE_LIMITS.roundsPct.min, `Mínimo ${BINGO_ROOM_PERCENTAGE_LIMITS.roundsPct.min}%.`)

/* ─── Días válidos de sala automática ────────────────────────────────── */

const DAY_OF_WEEK = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const

/* ─── Sub-schemas ────────────────────────────────────────────────────── */

const roundConfigSchema = z.object({
    bingoPattern: z.union([z.number(), z.string()]).nullable(),
    prizeType:    z.enum(['percentage', 'fixed', 'prize']),
    prizeValue:   z.union([z.number(), z.string()]).nullable(),
    prizeValueByCurrency: z.record(z.string(), z.number().nullable()).optional(),
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
    minCardsToStart: z.number().nullable().optional(),
    maxPlayers:      z.number().nullable().optional(),
    rounds:       z.number().nullable().optional(),
    roundsPct:    z.number().nullable().optional(),
    roundsConfig: z.array(roundConfigSchema).optional(),
    useSerials:   z.boolean().optional(),
    serialsPct:   z.number().nullable().optional(),
    numSerials:   z.number().nullable().optional(),
    useBaseUsd:   z.boolean().optional(),
    cardPriceUsd: z.number().nullable().optional(),
    usdExchangeRoundStep: z.number().nullable().optional(),
    cardPriceByCurrency:  z.record(z.string(), z.number().nullable()).optional(),
    activateRoom:    z.boolean().optional(),
    allowCustomCard: z.boolean().optional(),
    customCardMode:  z.enum(['paid', 'by_range', 'every_x', 'free']).nullable().optional(),
    customCardPricePct: z.number().nullable().optional(),
    customCardEveryX:   z.number().nullable().optional(),
    moveFreeSpaceMode:  z.enum(['paid', 'by_range', 'free']).nullable().optional(),
    moveFreeSpacePricePct: z.number().nullable().optional(),
    allowSpecialCard:   z.boolean().optional(),
    maxSpecialCards:    z.number().nullable().optional(),
    // Solo salas automáticas — enum validado en runtime
    days: z.array(z.enum(DAY_OF_WEEK)).optional(),
})

export type BingoRoomFormInput = z.input<typeof bingoRoomFormSchema>

/* ─── Función que construye errores contextuales ─────────────────────── */

export interface BingoRoomValidationContext {
    automaticRoom:  boolean
    isEdit:         boolean
    useBaseUsd:     boolean
    currenciesMissingRate: string[]
    /** Porcentaje disponible para rondas: 100 - roomCommn - jackpot - serials.
     *  Calculado una sola vez en useBingoRoomBaseForm. */
    availablePct: number
    /** Número de rondas ya resuelto como entero (≥ 1).
     *  Calculado una sola vez en useBingoRoomBaseForm. */
    roundsCount: number
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

    /* ── Edición: fecha/hora programada ya vencida ──
     * En creación, la fecha/hora futura ya se exige arriba. En edición no se
     * fuerza a reprogramar solo por abrir el formulario (el usuario puede
     * querer ajustar otro campo sin tocar el horario) — pero si el valor
     * actual del campo (tocado o no) queda en el pasado, sí se bloquea el
     * guardado: de lo contrario la sala queda con un horario vencido sin
     * ningún aviso hasta que el usuario intenta activarla manualmente. */
    if (ctx.isEdit && !ctx.automaticRoom && !isManualStart && data.date && data.time && !e.date && !e.time) {
        const scheduledAt = new Date(`${data.date}T${data.time}:00`)
        if (scheduledAt < new Date()) {
            e.date = 'La fecha/hora programada ya pasó. Actualízala para continuar.'
            e.time = 'La fecha/hora programada ya pasó. Actualízala para continuar.'
        }
    }

    /* ── Campos requeridos simples ── */
    if (!data.roomType)      e.roomType      = 'El tipo de sala es requerido.'
    if (!data.gameMode)      e.gameMode      = 'Modo de juego requerido.'
    if (!data.prizePoolType) e.prizePoolType = 'El tipo de bote es requerido.'

    /* ── Monedas ── */
    if (!data.allowedCurrencies || data.allowedCurrencies.length === 0) {
        e.allowedCurrencies = 'Debe seleccionar al menos una moneda.'
    } else if (ctx.currenciesMissingRate.length > 0) {
        e.allowedCurrencies = 'Hay monedas seleccionadas sin tasa de cambio activa.'
    }

    /* ── % Sala (pre-compiled schema) ── */
    if (data.roomCommn === null || data.roomCommn === undefined) {
        e.roomCommn = 'El % de sala es requerido.'
    } else {
        const r = roomCommnSchema.safeParse(data.roomCommn)
        if (!r.success) e.roomCommn = r.error.issues[0].message
    }

    /* ── Otros campos requeridos ── */
    if (data.timeChip === null || data.timeChip === undefined || data.timeChip === 0) {
        e.timeChip = 'El tiempo de ficha es requerido.'
    }
    if (data.rounds === null || data.rounds === undefined || data.rounds < 1) {
        e.rounds = 'El número de rondas debe ser al menos 1.'
    }
    if (data.minCardsToStart !== null && data.minCardsToStart !== undefined) {
        const min = Number(data.minCardsToStart)
        if (!Number.isInteger(min) || min < 1 || min > 10000) {
            e.minCardsToStart = 'La cantidad mínima de cartones debe ser un entero entre 1 y 10000.'
        }
    }
    if (data.maxPlayers !== null && data.maxPlayers !== undefined) {
        const max = Number(data.maxPlayers)
        if (!Number.isInteger(max) || max < 1 || max > 100000) {
            e.maxPlayers = 'El cupo máximo de jugadores debe ser un entero entre 1 y 100000.'
        }
    }

    /* ── Seriales (pre-compiled schema) ── */
    if (data.useSerials) {
        if (!data.serialsPct) {
            e.serialsPct = 'El % de seriales es requerido.'
        } else {
            const r = serialsPctSchema.safeParse(data.serialsPct)
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

    /* ── % Rondas — usa roundsCount y availablePct del composable ── */
    const { roundsCount: rounds } = ctx
    const rPct = Number(data.roundsPct) || 0
    if (rounds > 1) {
        if (!data.roundsPct || rPct === 0) {
            e.roundsPct = 'El % de rondas es requerido.'
        } else {
            // .min() validado con el schema precompilado; .max() comparado imperativamente
            // para evitar crear un objeto Zod nuevo en cada render (ctx.availablePct es dinámico).
            const r = roundsPctBaseSchema.safeParse(rPct)
            if (!r.success) {
                e.roundsPct = r.error.issues[0].message
            } else if (rPct > ctx.availablePct) {
                e.roundsPct = `El % de rondas no puede superar el disponible (${ctx.availablePct.toFixed(2)}%).`
            }
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

    /* ── Cartón personalizado (pre-compiled schema) ── */
    if (data.allowCustomCard) {
        if (!data.customCardMode) {
            e.customCardMode = 'Debe seleccionar una modalidad de cartón personalizado.'
        } else if (data.customCardMode === 'paid') {
            const r = customCardPricePctSchema.safeParse(Number(data.customCardPricePct))
            if (!r.success) e.customCardPricePct = r.error.issues[0].message
        } else if (data.customCardMode === 'every_x') {
            const everyX = Number(data.customCardEveryX)
            if (!data.customCardEveryX || !Number.isInteger(everyX) || everyX < 2 || everyX > 10) {
                e.customCardEveryX = 'Debe ser un entero entre 2 y 10.'
            }
        }
    }

    /* ── Mover casilla libre (requiere cartón personalizado; modalidad propia de precio) ── */
    if (data.moveFreeSpaceMode) {
        if (!data.allowCustomCard) {
            e.moveFreeSpaceMode = 'Requiere activar "Permitir cartón personalizado".'
        } else if (data.moveFreeSpaceMode === 'paid') {
            const r = moveFreeSpacePricePctSchema.safeParse(Number(data.moveFreeSpacePricePct))
            if (!r.success) e.moveFreeSpacePricePct = r.error.issues[0].message
        }
    }

    /* ── Cartón especial ── */
    if (data.allowSpecialCard) {
        const max = Number(data.maxSpecialCards)
        if (!max || !Number.isInteger(max) || max < 1 || max > 5) {
            e.maxSpecialCards = 'La cantidad de cartones especiales debe ser un entero entre 1 y 5.'
        }
    }

    /* ── Suma total de porcentajes ──
     * roundsPct solo cuenta cuando rounds > 1; cuando es 1 el campo no aplica. */
    const roomCommn  = Number(data.roomCommn)  || 0
    const jackpotPct = Number(data.jackpotPct) || 0
    const serialsPct = data.useSerials ? (Number(data.serialsPct) || 0) : 0
    const roundsPct  = ctx.roundsCount > 1 ? rPct : 0
    const totalPct   = roomCommn + jackpotPct + serialsPct + roundsPct
    if (totalPct > 100) {
        e.percentageSum = `La suma (${totalPct.toFixed(2)}%) excede el 100%.`
    }

    return e
}
