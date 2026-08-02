/**
 * Tipos de sala de bingo
 */
export type BingoRoomType = 'PRIVATE' | 'PUBLIC' | 'VIP' | 'DEMO'

/**
 * Indica si los premios fueron pagados
 */
export type BingoPrizesPaid = 'Y' | 'N'

/**
 * Estado de la sala
 */
export type BingoRoomStatus = 'ACTIVE' | 'INACTIVE' | 'DELETED'

/**
 * Modo de inicio de la sala:
 * - SCHEDULED: inicia automáticamente según fecha/hora programada
 * - MANUAL: sin horario; requiere activación manual del anfitrión o un admin
 */
export type BingoRoomStartMode = 'SCHEDULED' | 'MANUAL'

/**
 * Relación: estado de la sala
 */
export interface BingoRoomGameStatus {
    readonly id: number | string
}

/**
 * Relación: país
 */
export interface BingoRoomCountry {
    readonly id: number | string
    readonly name: string
}

/**
 * Relación: usuario
 */
export interface BingoRoomUser {
    readonly id: number | string
    readonly email: string
    readonly username?: string
    readonly time_zone_id?: number | string | null
}

/**
 * Último usuario que editó una sala (derivado de la tabla audits, evento 'updated').
 */
export interface BingoRoomLastUpdatedBy {
    user_id: string
    username: string | null
    at: string | null
}

/**
 * Desglose por moneda de las métricas monetarias de una sala (en moneda local).
 * Claves = currency_id.
 */
export type BingoRoomMetricsByCurrency = Record<string, {
    total_sales: number
    total_cards: number
    prize_pool: number
    full_house_prize: number
    rke_commission: number
    room_commission: number
    seller_commission: number
}>

/**
 * Modelo BingoRoom
 * Basado en: 2024_06_14_04_create_bingo_rooms_table
 */
export interface BingoRoom {
    readonly id: number | string
    readonly status_id: number | string
    readonly room_type: BingoRoomType
    readonly creator_user_id: number | string
    readonly paid_prizes: BingoPrizesPaid
    readonly published_at: string | null
    readonly total_players: number
    readonly total_cards: number
    readonly total_sales: number
    readonly rke_commission: number
    readonly room_commission: number
    readonly seller_commission: number
    readonly prize_pool: number
    readonly full_house_prize: number
    readonly serial_prize: number
    readonly finished_at: string | null
    readonly uuid_code: string | null
    readonly created_at: string
    readonly updated_at: string

    // Appended accessors
    readonly is_closed: boolean
    readonly total_commissions: number

    // Desglose de métricas por moneda (en moneda local de cada currency_id)
    readonly metrics_by_currency?: BingoRoomMetricsByCurrency

    // Resuelto en el resource: si el usuario autenticado puede activar manualmente esta sala
    readonly can_activate_now?: boolean

    // Último usuario que editó la sala (derivado de la tabla audits, evento 'updated').
    // Null si la sala nunca se editó vía HTTP autenticado (solo creación).
    readonly last_updated_by?: BingoRoomLastUpdatedBy | null

    // Campo meta: modo de inicio (MANUAL | SCHEDULED), expuesto en el listado
    readonly start_mode?: 'MANUAL' | 'SCHEDULED' | null

    // Campo meta: unix timestamp de la fecha+hora programada (desde config)
    readonly unix_date?: number | null

    // Columna "Moneda(s)" del listado: códigos ISO separados por coma (ej. "VES, COP")
    readonly config_allowed_currencies?: string

    // Campo meta (no visible): IDs de monedas permitidas, para filtrado client-side
    readonly currency_ids?: (string | number)[]

    // Columna "Moneda(s)" del listado de salas automáticas (mismo formato que config_allowed_currencies)
    readonly allowed_currencies?: string

    // Campos del servicio (summary / listado)
    readonly name?: string
    readonly phone?: string
    readonly status?: BingoRoomStatus
    readonly country_id?: number | string | null
    readonly user_id?: number | string | null
    readonly currency_id?: number | string | null
    readonly time_zone_id?: number | string | null
    readonly username?: string
    readonly email?: string
    readonly commission?: string | number
    readonly other_currencies?: (number | string)[]
    readonly allowed_games?: (number | string)[]

    // Configuración completa (cargada en show)
    readonly config?: {
        room_type_id?: string | number | null
        access_code?: string | null
        has_password?: boolean
        host_user_id?: string | number | null
        host_user_label?: string | null
        start_mode?: string | null
        min_cards_to_start?: number | null
        max_players?: number | null
        date?: string | null
        time?: string | null
        connection_type_id?: string | number | null
        game_mode_id?: string | number | null
        prize_pool_type_id?: string | number | null
        amount_added_prize_pool?: number | string | null
        room_commission?: number | string | null
        rke_commission?: number | string | null
        use_base_usd?: boolean
        card_price_usd?: number | string | null
        usd_exchange_round_step?: number | string | null
        jackpot_pct?: number | string | null
        game_variant?: string | null
        time_chip?: number | string | null
        rounds?: number | string | null
        rounds_pct?: number | string | null
        use_serials?: boolean
        serials_pct?: number | string | null
        num_serials?: number | string | null
        in_live?: string | null
        unix_date?: number | null
        allow_custom_card?: boolean
        custom_card_price_pct?: number | string | null
        custom_card_mode?: CustomCardMode | null
        custom_card_every_x?: number | null
        move_free_space_mode?: MoveFreeSpaceMode | null
        move_free_space_price_pct?: number | string | null
        allow_special_card?: boolean
        max_special_cards?: number | null
    } | null

    // Precios por moneda (cargados en show)
    readonly card_price_by_currency?: Record<string, number>

    // Ganadores de la sala (cargados en show, solo para salas finalizadas)
    readonly winners?: Array<{
        id: string
        user_id: string
        username: string | null
        round_id: string
        round_number: number | null
        winning_card_id: string
        serial_number: string | null
        winning_pattern: string | null
        prize_type: string | null
        usd_amount: number
        local_amount: number
        currency_id: string | null
        prize_status: string
        created_at: string | null
    }>

    // Configuración de rondas (cargada en show)
    readonly rounds_config?: Array<{
        prizeType: string
        bingoPattern: number | string | null
        prizeValue: number | string | null
        prizeValueByCurrency?: Record<string, number | null>
        prizeId?: number | string | null
        prizeName?: string | null
    }>

    // Relaciones opcionales
    readonly country?: BingoRoomCountry | null
    readonly user?: BingoRoomUser | null
    readonly status_relation?: BingoRoomGameStatus | null
}

/**
 * Tipo de premio por ronda
 */
export type RoundPrizeType = 'percentage' | 'fixed' | 'prize'

/**
 * Configuración individual de cada ronda
 */
export interface RoundConfig {
    prizeType: RoundPrizeType
    bingoPattern: number | string | null
    prizeValue: number | string | null
    prizeValueByCurrency?: Record<string, number | null>
    prizeId?: number | string | null
    prizeName?: string | null
}

/**
 * Campos del formulario de creación/edición de sala
 * Basado en: bingo_rooms_config + bingo_rooms_automatic
 */
export type BingoGameVariant = 'BINGO_75' | 'BINGO_90' | 'BINGO_80' | 'BINGO_30'

/**
 * Modalidad del cartón personalizado (solo aplica si allowCustomCard = true):
 * - paid: costo adicional (%) sobre el precio base del cartón, configurado manualmente
 * - by_range: costo adicional (%) calculado automáticamente según el precio del
 *   cartón (nunca supera 5%), sin configuración manual
 * - every_x: cartón personalizado gratis cada X cartones comprados
 * - free: uso libre, sin restricción ni costo adicional
 */
export type CustomCardMode = 'paid' | 'by_range' | 'every_x' | 'free'

/**
 * Modalidad de "mover casilla libre" (solo aplica si allowCustomCard = true y
 * gameVariant = BINGO_75). Recargo independiente y sumado al de customCardMode:
 * - paid: costo adicional (%) manual por mover la libre
 * - by_range: costo adicional (%) calculado automáticamente (nunca supera 5%)
 * - free: mover la libre sin costo adicional
 * No incluye 'every_x' — esa restricción no aplica a mover la libre.
 */
export type MoveFreeSpaceMode = 'paid' | 'by_range' | 'free'

export interface BingoRoomFormData {
    date?: string | null
    time?: string | null
    startMode?: BingoRoomStartMode
    minCardsToStart?: number | null
    maxPlayers?: number | null
    roomType?: BingoRoomType | null
    connectionType?: number | string | null
    gameMode?: number | string | null
    gameVariant?: BingoGameVariant | null
    prizePoolType?: number | string | null
    allowedCurrencies?: (number | string)[]
    roomsPerCurrency?: boolean
    amountAddedPrizePool?: number | null
    roomCommn?: number | null
    jackpotPct?: number | null
    timeChip?: number | null
    rounds?: number | null
    roundsPct?: number | null
    roundsConfig?: RoundConfig[]
    useSerials?: boolean
    serialsPct?: number | null
    numSerials?: number | null
    useBaseUsd?: boolean,
    cardPriceUsd?: number | null
    usdExchangeRoundStep?: number | null
    cardPriceByCurrency?: Record<string, number | null>
    activateRoom?: boolean
    allowCustomCard?: boolean
    customCardMode?: CustomCardMode | null
    customCardPricePct?: number | null
    customCardEveryX?: number | null
    moveFreeSpaceMode?: MoveFreeSpaceMode | null
    moveFreeSpacePricePct?: number | null
    allowSpecialCard?: boolean
    maxSpecialCards?: number | null
    accessCode?: string | null
    // Solo se envía si el admin la cambia; en edición inicia vacío/null (el
    // backend nunca reenvía el hash) — null = no modificar la contraseña actual.
    roomPassword?: string | null
    hostUserId?: string | number | null
}
