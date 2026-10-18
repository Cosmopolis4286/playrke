import { z } from 'zod'
import { percentageSchema } from '@/js/Components/games/bingo/Room/Form/Utils/percentage'

/* ─── Rangos de porcentajes ─────────────────────────────────────────── */

export const BINGO_ROOM_PERCENTAGE_LIMITS = {
    roomCommn:  { min: 10, max: 50  },
    jackpotPct: { min: 1,  max: 15  },
    roundsPct:  { min: 5,  max: 90  },
    serialsPct: { min: 2,  max: 35  },
} as const

/* ─── Rangos de campos enteros (espejo de BingoRoomRequest / BingoRoomAutomaticRequest) ── */

export const BINGO_ROOM_INT_LIMITS = {
    customCardEveryX:     { min: 2, max: 10     },
    numSerials:           { min: 1, max: 30     },
    maxSpecialCards:      { min: 1, max: 5      },
    timeChip:             { min: 3, max: 10     },
    rounds:               { min: 1, max: 15     },
    cards:                { min: 1, max: 10000  },
    players:              { min: 1, max: 100000 },
    salesStartOffsetDays: { min: 1, max: 30     },
} as const

/* ─── Reglas de capacidad (espejo de BingoRoomCapacityRules.php) ──────
 * Un bingo necesita competencia: con 1 jugador siempre gana él y el bote le
 * vuelve, así que el piso de jugadores es 2 — salvo el modo Solitario (SOLO).
 * Cada jugador tiene ≥ 1 cartón → mín. cartones ≥ mín. jugadores. Si al vencer
 * no se alcanzan los mínimos, la sala se cancela y se reembolsa. */

export const GAME_MODE_SOLO = 'SOLO'

export const BINGO_ROOM_CAPACITY_DEFAULTS = {
    minPlayers:      2,
    minCardsToStart: 2,
} as const

export function minPlayersFloor(gameModeCode?: string | null): number {
    return gameModeCode === GAME_MODE_SOLO ? 1 : 2
}

/* ─── Tipo de bote ↔ rondas (espejo de BingoRoomPrizeRules.php) ───────
 * Premio/Servicio (PRIZE): necesita ≥ 1 ronda de premio físico (P/S).
 *  - PURO (sin rondas %): no acumula bote en dinero ni usa "Valor Fijo/Extra".
 *  - HÍBRIDO (con alguna ronda %): acumula bote para pagar esas rondas.
 * Resto de tipos: si TODAS las rondas son P/S, el bote en dinero nunca se
 * repartiría → se exige el tipo Premio/Servicio. */

export const POOL_PRIZE = 'PRIZE'
export const POOL_ACCUMULATIVE = 'ACCUMULATIVE'

type RoundPrizeType = 'percentage' | 'fixed' | 'prize'

export function usesMoneyPool(roundTypes: RoundPrizeType[]): boolean {
    return roundTypes.includes('percentage')
}

export function usesInitialAmount(poolCode: string | null | undefined, roundTypes: RoundPrizeType[]): boolean {
    if (!poolCode || poolCode === POOL_ACCUMULATIVE) return false
    return poolCode !== POOL_PRIZE || usesMoneyPool(roundTypes)
}

export function prizeRoundsError(poolCode: string | null | undefined, roundTypes: RoundPrizeType[]): string | null {
    if (!poolCode || roundTypes.length === 0) return null
    const hasPrizeRound = roundTypes.includes('prize')
    const allPrizeRounds = roundTypes.every(t => t === 'prize')
    if (poolCode === POOL_PRIZE && !hasPrizeRound) {
        return 'Un bote Premio/Servicio necesita al menos una ronda de premio físico (P/S).'
    }
    if (poolCode !== POOL_PRIZE && allPrizeRounds) {
        return 'Si todas las rondas son de premio físico (P/S), el tipo de bote debe ser Premio/Servicio: si no, el bote en dinero nunca se repartiría.'
    }
    return null
}

/* ─── Rangos de montos / decimales (espejo de BingoRoomRequest) ─────── */

export const BINGO_ROOM_AMOUNT_LIMITS = {
    cardPriceUsd:          { min: 0.5,  max: 500     },
    usdExchangeRoundStep:  { min: 0.01, max: 100     },
    cardPriceByCurrency:   { min: 1,    max: 5000    },
    amountAddedPrizePool:  { min: 20,   max: 10000   },
    customCardPricePct:    { min: 1,    max: 5       },
    moveFreeSpacePricePct: { min: 1,    max: 5       },
    roundPrizePct:         { min: 0.01, max: 100     },
    roundPrizeFixed:       { min: 0.01, max: 1000000 },
} as const

/* ─── Longitudes de texto (sala privada) ─────────────────────────────── */

export const BINGO_ROOM_TEXT_LIMITS = {
    accessCode:   { min: 4, max: 20  },
    roomPassword: { min: 6, max: 100 },
} as const

/** Formatea un número para mensajes: 1000000 → "1.000.000", 0.5 → "0,5". */
function fmt(n: number): string {
    return n.toLocaleString('es-VE', { maximumFractionDigits: 2 })
}

/**
 * Valida un número opcional/requerido contra un rango. Devuelve el mensaje de
 * error o null. `label` se antepone al mensaje de rango.
 */
function rangeError(
    value: unknown,
    limits: { min: number; max: number },
    opts: { integer?: boolean; required?: string; label?: string } = {}
): string | null {
    if (value === null || value === undefined || value === '') return opts.required ?? null
    const n = Number(value)
    const say = (msg: string) => (opts.label ? `${opts.label} ${msg}` : msg.charAt(0).toUpperCase() + msg.slice(1))
    if (!Number.isFinite(n)) return say('debe ser un número válido.')
    if ((opts.integer && !Number.isInteger(n)) || n < limits.min || n > limits.max) {
        return opts.integer
            ? say(`debe ser un entero entre ${fmt(limits.min)} y ${fmt(limits.max)}.`)
            : say(`debe estar entre ${fmt(limits.min)} y ${fmt(limits.max)}.`)
    }
    return null
}

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
    roomType:         z.union([z.number(), z.string()]).nullable().optional(),
    connectionType:   z.union([z.number(), z.string()]).nullable().optional(),
    gameMode:         z.union([z.number(), z.string()]).nullable().optional(),
    prizePoolType:    z.union([z.number(), z.string()]).nullable().optional(),
    accessCode:       z.string().nullable().optional(),
    roomPassword:     z.string().nullable().optional(),
    hostUserId:       z.union([z.number(), z.string()]).nullable().optional(),
    allowedCurrencies: z.array(z.union([z.number(), z.string()])).optional(),
    roomsPerCurrency:  z.boolean().optional(),
    roomCommn:    z.number().nullable().optional(),
    jackpotPct:   z.number().nullable().optional(),
    timeChip:     z.number().nullable().optional(),
    minCardsToStart: z.number().nullable().optional(),
    maxCardsToStart: z.number().nullable().optional(),
    maxPlayers:      z.number().nullable().optional(),
    minPlayers:      z.number().nullable().optional(),
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
    allowNetworkSales: z.boolean().optional(),
    customCardMode:  z.enum(['paid', 'by_range', 'every_x', 'free']).nullable().optional(),
    customCardPricePct: z.number().nullable().optional(),
    customCardEveryX:   z.number().nullable().optional(),
    moveFreeSpaceMode:  z.enum(['paid', 'by_range', 'free']).nullable().optional(),
    moveFreeSpacePricePct: z.number().nullable().optional(),
    allowSpecialCard:   z.boolean().optional(),
    maxSpecialCards:    z.number().nullable().optional(),
    jackpotType:          z.union([z.number(), z.string()]).nullable().optional(),
    amountAddedPrizePool: z.number().nullable().optional(),
    // Solo salas automáticas — enum validado en runtime
    days: z.array(z.enum(DAY_OF_WEEK)).optional(),
    salesStartOffsetDays: z.number().nullable().optional(),
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
    /** ID (snowflake) del RoomType con código PRIVATE — null si el catálogo aún no cargó. */
    privateRoomTypeId: string | number | null
    /** true si el Tipo Bote elegido no es ACCUMULATIVE (exige "Valor Fijo/Extra");
     *  null si aún no hay tipo elegido o el catálogo no cargó — no se valida. */
    requiresAmountAddedPrizePool?: boolean | null
    /** Código del Tipo Bote elegido (ACCUMULATIVE/FIXED/MIXED/PRIZE); null si no hay. */
    prizePoolCode?: string | null
    /** IDs de jackpots activos (opciones del select) — null si no se pasaron. */
    activeJackpotTypeIds?: string[] | null
    /** Código del modo de juego elegido (SOLO permite 1 jugador mínimo). */
    gameModeCode?: string | null
}

export function validateBingoRoomForm(
    data: BingoRoomFormInput,
    ctx: BingoRoomValidationContext
): Record<string, string | Record<string, string>> {

    const e: Record<string, string | Record<string, string>> = {}
    const isManualStart = !ctx.automaticRoom && data.startMode === 'MANUAL'

    /* ── Modo de inicio (solo salas normales; automáticas no tienen este campo) ──
     * Sin esto, un startMode vacío/null (ej. vaciado por el Multiselect al
     * deseleccionar la opción activa) pasaba sin error visible en el wizard y
     * solo se descubría al fallar la validación del backend en el último paso. */
    if (!ctx.automaticRoom && !data.startMode) {
        e.startMode = 'El modo de inicio es obligatorio.'
    }

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
    if (!data.connectionType) e.connectionType = 'Tipo de conexión requerido.'
    if (!data.prizePoolType) e.prizePoolType = 'El tipo de bote es requerido.'
    if (!data.gameMode)      e.gameMode      = 'El modo de juego es requerido.'

    /* ── Sala privada: código de acceso + contraseña (obligatoria solo al crear) + anfitrión ── */
    const isPrivateRoom = ctx.privateRoomTypeId !== null && String(data.roomType) === String(ctx.privateRoomTypeId)
    if (isPrivateRoom) {
        const code = (data.accessCode ?? '').trim()
        const { accessCode: codeLim, roomPassword: pwdLim } = BINGO_ROOM_TEXT_LIMITS
        if (!code) {
            e.accessCode = 'El código de acceso es requerido para salas privadas.'
        } else if (code.length < codeLim.min || code.length > codeLim.max) {
            e.accessCode = `El código de acceso debe tener entre ${codeLim.min} y ${codeLim.max} caracteres.`
        }

        // En edición la contraseña vacía significa "no cambiar" — solo se valida si se escribió una.
        const pwd = data.roomPassword ?? ''
        if (!ctx.isEdit && !pwd) {
            e.roomPassword = 'La contraseña es requerida para salas privadas.'
        } else if (pwd && (pwd.length < pwdLim.min || pwd.length > pwdLim.max)) {
            e.roomPassword = `La contraseña debe tener entre ${pwdLim.min} y ${pwdLim.max} caracteres.`
        }

        if (!data.hostUserId) e.hostUserId = 'El anfitrión es requerido para salas privadas.'
    }

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

    /* ── Jackpot (opcional, pero tipo y % van juntos) ──
     * Un % sin tipo se descuenta del bote sin ir a ningún pozo; un tipo sin %
     * nunca recibe aportes. Espejo de required_with en BingoRoomRequest. */
    const jkLim = BINGO_ROOM_PERCENTAGE_LIMITS.jackpotPct
    const hasJkPct = data.jackpotPct != null && (data.jackpotPct as unknown) !== ''
    if (data.jackpotType) {
        const active = ctx.activeJackpotTypeIds
        if (active && !active.includes(String(data.jackpotType))) {
            e.jackpotType = 'El jackpot seleccionado ya no está activo. Elige otro o quítalo.'
        }
        const jkErr = rangeError(data.jackpotPct, jkLim, {
            label: 'El % de jackpot',
            required: `Ingrese el % de jackpot (${jkLim.min} a ${jkLim.max}) o quite el tipo de jackpot.`,
        })
        if (jkErr) e.jackpotPct = jkErr
    } else if (hasJkPct) {
        e.jackpotPct = 'Elija un tipo de Jackpot para usar este %.'
    }

    /* ── Valor Fijo/Extra: obligatorio en bote no acumulativo; en acumulativo no aplica ── */
    if (ctx.requiresAmountAddedPrizePool) {
        const addErr = rangeError(data.amountAddedPrizePool, BINGO_ROOM_AMOUNT_LIMITS.amountAddedPrizePool, {
            label: 'El valor fijo/extra',
            required: 'El valor fijo/extra es obligatorio para este tipo de bote.',
        })
        if (addErr) e.amountAddedPrizePool = addErr
    }

    /* ── Tiempo ficha / rondas (requeridos, enteros) ── */
    const I = BINGO_ROOM_INT_LIMITS
    const tcErr = rangeError(data.timeChip || null, I.timeChip, {
        integer: true, required: 'El tiempo de ficha es requerido.', label: 'El tiempo de ficha',
    })
    if (tcErr) e.timeChip = tcErr
    const rdErr = rangeError(data.rounds || null, I.rounds, {
        integer: true, required: 'El número de rondas es requerido.', label: 'El número de rondas',
    })
    if (rdErr) e.rounds = rdErr

    /* ── Días de anticipación (solo salas automáticas, opcional) ── */
    if (ctx.automaticRoom) {
        const sdErr = rangeError(data.salesStartOffsetDays, I.salesStartOffsetDays, { integer: true, label: 'Los días de anticipación' })
        if (sdErr) e.salesStartOffsetDays = sdErr
    }

    /* ── Capacidad: mínimos obligatorios, máximos opcionales (enteros) ── */
    const playersFloor = minPlayersFloor(ctx.gameModeCode)
    const capacity: [keyof BingoRoomFormInput, { min: number; max: number }, string, string | undefined][] = [
        ['minCardsToStart', I.cards,   'El mínimo de cartones',       'Indique el mínimo de cartones para iniciar el sorteo.'],
        ['maxCardsToStart', I.cards,   'El máximo de cartones',       undefined],
        ['minPlayers',      I.players, 'El mínimo de jugadores',      'Indique el mínimo de jugadores para iniciar el sorteo.'],
        ['maxPlayers',      I.players, 'El cupo máximo de jugadores', undefined],
    ]
    for (const [key, lim, label, required] of capacity) {
        const err = rangeError(data[key], lim, { integer: true, label, required })
        if (err) e[key] = err
    }

    /* ── Coherencia entre límites (espejo de BingoRoomCapacityRules::crossErrors) ── */
    const num = (v: unknown) => (v === null || v === undefined || v === '' ? null : Number(v))
    const minCards = e.minCardsToStart ? null : num(data.minCardsToStart)
    const maxCards = e.maxCardsToStart ? null : num(data.maxCardsToStart)
    const minPl    = e.minPlayers      ? null : num(data.minPlayers)
    const maxPl    = e.maxPlayers      ? null : num(data.maxPlayers)

    if (minPl !== null && minPl < playersFloor) {
        e.minPlayers = ctx.gameModeCode === GAME_MODE_SOLO
            ? `El mínimo de jugadores debe ser al menos ${playersFloor}.`
            : `Un bingo necesita al menos ${playersFloor} jugadores (solo el modo Solitario permite 1).`
    }
    if (minCards !== null && minPl !== null && minCards < minPl) {
        e.minCardsToStart = 'No puede ser menor al mínimo de jugadores (cada jugador tiene al menos un cartón).'
    }
    if (minCards !== null && maxCards !== null && minCards > maxCards) {
        e.maxCardsToStart = 'El máximo de cartones debe ser mayor o igual al mínimo.'
    }
    if (minPl !== null && maxPl !== null && minPl > maxPl) {
        e.maxPlayers = 'El máximo de jugadores debe ser mayor o igual al mínimo.'
    }
    if (!e.maxCardsToStart && maxCards !== null && maxPl !== null && maxCards < maxPl) {
        e.maxCardsToStart = 'No puede ser menor al máximo de jugadores: parte del cupo no podría comprar.'
    }

    /* ── Seriales (pre-compiled schema) ── */
    if (data.useSerials) {
        if (!data.serialsPct) {
            e.serialsPct = 'El % de seriales es requerido.'
        } else {
            const r = serialsPctSchema.safeParse(data.serialsPct)
            if (!r.success) e.serialsPct = r.error.issues[0].message
        }
        const nsErr = rangeError(data.numSerials, BINGO_ROOM_INT_LIMITS.numSerials, {
            integer: true, required: 'Ingrese cantidad de seriales.',
        })
        if (nsErr) e.numSerials = nsErr
    }

    /* ── Precio del cartón ── */
    const A = BINGO_ROOM_AMOUNT_LIMITS
    if (ctx.useBaseUsd) {
        const usdErr = rangeError(data.cardPriceUsd || null, A.cardPriceUsd, { required: 'Valor en USD requerido.' })
        if (usdErr) e.cardPriceUsd = usdErr
        const stepErr = rangeError(data.usdExchangeRoundStep, A.usdExchangeRoundStep, { label: 'El redondeo' })
        if (stepErr) e.usdExchangeRoundStep = stepErr
    } else {
        const allowed = data.allowedCurrencies ?? []
        const byCurrency = data.cardPriceByCurrency ?? {}
        const currencyErrors: Record<string, string> = {}
        allowed.forEach(cur => {
            const key = String(cur)
            const err = rangeError(byCurrency[key] || null, A.cardPriceByCurrency, { required: 'Ingrese un precio válido.' })
            if (err) currencyErrors[key] = err
        })
        if (Object.keys(currencyErrors).length) e.cardPriceByCurrency = currencyErrors
    }

    /* ── % Rondas — usa roundsCount y availablePct del composable ── */
    const { roundsCount: rounds } = ctx
    const rPct = Number(data.roundsPct) || 0
    const roundTypes = (data.roundsConfig ?? []).map(r => r.prizeType) as RoundPrizeType[]

    /* ── Tipo de bote ↔ rondas (Premio/Servicio puro o híbrido) ── */
    const poolRoundsErr = prizeRoundsError(ctx.prizePoolCode, roundTypes)
    if (poolRoundsErr && !e.prizePoolType) e.prizePoolType = poolRoundsErr

    // %Rondas solo aplica si alguna ronda paga un % del bote: con rondas solo
    // fijas o de premio físico (P/S) no hay nada que repartir por porcentaje.
    if (rounds > 1 && usesMoneyPool(roundTypes)) {
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
                const err = rangeError(val || null, BINGO_ROOM_AMOUNT_LIMITS.roundPrizeFixed, {
                    required: 'El valor para la moneda es requerido.',
                })
                if (err) roundErrors[`${i}_prizeValue_${key}`] = err
            })
        } else if (round.prizeType === 'prize') {
            if (!round.prizeId) roundErrors[`${i}_prizeValue`] = 'Debe seleccionar un premio.'
        } else if (rounds > 1) {
            const err = rangeError(Number(round.prizeValue) || null, BINGO_ROOM_AMOUNT_LIMITS.roundPrizePct, {
                required: 'El valor es requerido.',
            })
            if (err) roundErrors[`${i}_prizeValue`] = err
        } else if (round.prizeValue === null || round.prizeValue === '' || Number(round.prizeValue) <= 0) {
            // Con 1 sola ronda el % es readonly (se toma de %Rondas) — solo se exige que exista.
            roundErrors[`${i}_prizeValue`] = 'El valor es requerido.'
        }
    })

    /* La suma de las rondas en % debe cuadrar con %Rondas (antes solo se
     * pintaba en rojo en la UI, sin bloquear el paso ni el guardado). */
    const pctRounds = configs.filter(r => r.prizeType === 'percentage')
    if (rounds > 1 && rPct > 0 && pctRounds.length > 0) {
        const sum = pctRounds.reduce((acc, r) => acc + (Number(r.prizeValue) || 0), 0)
        if (Math.abs(sum - rPct) >= 0.01) {
            roundErrors.sum = `La suma de las rondas en % (${sum.toFixed(2)}) debe ser igual a %Rondas (${rPct}).`
        }
    }

    if (Object.keys(roundErrors).length) e.roundsConfig = roundErrors

    /* ── Cartón personalizado (pre-compiled schema) ── */
    if (data.allowCustomCard) {
        if (!data.customCardMode) {
            e.customCardMode = 'Debe seleccionar una modalidad de cartón personalizado.'
        } else if (data.customCardMode === 'paid') {
            const r = customCardPricePctSchema.safeParse(Number(data.customCardPricePct))
            if (!r.success) e.customCardPricePct = r.error.issues[0].message
        } else if (data.customCardMode === 'every_x') {
            const { min, max } = BINGO_ROOM_INT_LIMITS.customCardEveryX
            const everyX = Number(data.customCardEveryX)
            if (!data.customCardEveryX || !Number.isInteger(everyX) || everyX < min || everyX > max) {
                e.customCardEveryX = `Debe ser un entero entre ${min} y ${max}.`
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
        const limits = BINGO_ROOM_INT_LIMITS.maxSpecialCards
        const max = Number(data.maxSpecialCards)
        if (!max || !Number.isInteger(max) || max < limits.min || max > limits.max) {
            e.maxSpecialCards = `La cantidad de cartones especiales debe ser un entero entre ${limits.min} y ${limits.max}.`
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
