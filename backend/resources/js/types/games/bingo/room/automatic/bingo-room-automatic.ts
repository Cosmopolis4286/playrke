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
 * Modelo BingoRoomAutomatic
 * Basado en: 2024_06_14_04_create_bingo_rooms_table
 */
export interface BingoRoomAutomatic {
    readonly id: number | string
    readonly status_id: number | string
    readonly room_type: BingoRoomAutomaticType
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

    // Campos del servicio (summary / listado)
    readonly name?: string
    readonly phone?: string
    readonly status?: BingoRoomAutomaticStatus
    readonly country_id?: number | string | null
    readonly user_id?: number | string | null
    readonly currency_id?: number | string | null
    readonly time_zone_id?: number | string | null
    readonly username?: string
    readonly email?: string
    readonly commission?: string | number
    readonly other_currencies?: (number | string)[]
    readonly allowed_games?: (number | string)[]

    // Relaciones opcionales
    readonly country?: BingoRoomAutomaticCountry | null
    readonly user?: BingoRoomAutomaticUser | null
    readonly status_relation?: BingoRoomAutomaticGameStatus | null
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
export interface BingoRoomAutomaticFormData {
    date?: string | null
    time?: string | null
    roomType?: BingoRoomAutomaticType | null
    gameMode?: number | string | null
    prizePoolType?: number | string | null
    allowedCurrencies?: (number | string)[]
    amountAddedPrizePool?: number | null
    roomCommn?: number | null
    jackpotPct?: number | null
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
