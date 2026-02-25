/**
 * Tipos de sala de bingo
 */
export type BingoRoomAutomaticType = 'PRIVATE' | 'PUBLIC' | 'VIP' | 'MIXED'

/**
 * Indica si los premios fueron pagados
 */
export type BingoPrizesPaid = 'Y' | 'N'

/**
 * Estado de la sala
 */
export type BingoRoomAutomaticStatus = 'ACTIVE' | 'INACTIVE' | 'DELETED'

/**
 * Relación: estado de la sala
 */
export interface BingoRoomAutomaticGameStatus {
    readonly id: number | string
}

/**
 * Relación: país
 */
export interface BingoRoomAutomaticCountry {
    readonly id: number | string
    readonly name: string
}

/**
 * Relación: usuario
 */
export interface BingoRoomAutomaticUser {
    readonly id: number | string
    readonly email: string
    readonly username?: string
    readonly time_zone_id?: number | string | null
}

/**
 * Días de la semana disponibles
 */
export type DayOfWeek = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'

/**
 * Modelo BingoRoomAutomatic
 * Basado en: 2024_06_15_07_create_bingo_rooms_automatic_table
 */
export interface BingoRoomAutomatic {
    readonly id: string
    readonly description?: string | null
    readonly date?: string | null
    readonly days?: DayOfWeek[] | null
    readonly time?: string | null
    readonly game_mode_id?: string | null
    readonly in_live?: 'Y' | 'N'
    readonly room_type_id?: string | null
    readonly prize_pool_type_id?: string | null
    readonly amount_added_prize_pool?: number | null
    readonly rke_commission?: number
    readonly room_commission?: number
    readonly card_price?: number
    readonly allowed_currencies?: (number | string)[] | null
    readonly time_chip?: number | null
    readonly rounds?: number
    readonly rounds_pct?: number | null
    readonly serials_pct?: number | null
    readonly num_serials?: number | null
    readonly jackpot_pct?: number | null
    readonly plays_pct?: number | null
    readonly bingo_patterns?: (number | string)[] | null
    readonly creator_user_id?: string
    readonly status?: BingoRoomAutomaticStatus
    readonly created_at?: string
    readonly updated_at?: string
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
 * Campos del formulario de creación/edición de sala automática
 * Basado en: bingo_rooms_automatic
 */
export interface BingoRoomAutomaticFormData {
    description?: string | null
    date?: string | null
    days?: DayOfWeek[]
    time?: string | null
    roomType?: BingoRoomAutomaticType | null
    gameMode?: number | string | null
    prizePoolType?: number | string | null
    allowedCurrencies?: (number | string)[]
    amountAddedPrizePool?: number | null
    roomCommn?: number | null
    jackpotPct?: number | null
    playsPct?: number | null
    timeChip?: number | null
    rounds?: number | null
    roundsPct?: number | null
    roundsConfig?: RoundConfig[]
    serialsPct?: number | null
    numSerials?: number | null
    cardPrice?: number | null
    cardPriceByCurrency?: Record<string, number | null>
    isDraft?: boolean
}
