/**
 * Tipos para Plantillas de Salas de Bingo
 * Basado en: 2026_05_10_02_create_bingo_room_templates_table
 */

/**
 * Tipo de sala en vivo
 */
export type InLiveType = 'Y' | 'N'

/**
 * Modelo BingoRoomTemplate
 * Plantilla reutilizable para crear salas de bingo
 */
export interface BingoRoomTemplate {
    readonly id: string
    readonly name?: string | null
    readonly description?: string | null
    readonly room_type_id?: string | null
    readonly creator_user_id?: string | null
    readonly game_mode_id?: string | null
    readonly in_live?: InLiveType
    readonly use_base_usd?: boolean
    readonly card_price_usd?: number | null
    readonly usd_exchange_round_step?: number | null
    readonly allowed_currencies?: (number | string)[] | null
    readonly prize_pool_type_id?: string | null
    readonly amount_added_prize_pool?: number | null
    readonly time_chip?: string | null
    readonly rounds?: number
    readonly rounds_pct?: number | null
    readonly use_serials?: boolean
    readonly serials_pct?: number | null
    readonly num_serials?: number | null
    readonly jackpot_pct?: number | null
    readonly created_at?: string
    readonly updated_at?: string

    // Relaciones opcionales
    readonly room_type?: Record<string, unknown> | null
    readonly creator_user?: Record<string, unknown> | null
    readonly game_mode?: Record<string, unknown> | null
    readonly prize_pool_type?: Record<string, unknown> | null
}

/**
 * Campos del formulario de creación/edición de plantilla
 */
export interface BingoRoomTemplateFormData {
    name?: string | null
    description?: string | null
    roomTypeId?: string | null
    gameModeId?: string | null
    inLive?: InLiveType
    useBaseUsd?: boolean
    cardPriceUsd?: number | null
    usdExchangeRoundStep?: number | null
    allowedCurrencies?: (number | string)[]
    prizePoolTypeId?: string | null
    amountAddedPrizePool?: number | null
    timeChip?: string | null
    rounds?: number | null
    roundsPct?: number | null
    useSerials?: boolean
    serialsPct?: number | null
    numSerials?: number | null
    jackpotPct?: number | null
}
