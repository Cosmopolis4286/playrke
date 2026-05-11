/**
 * Tipos para Configuración de Salas de Bingo
 * Basado en: 2024_06_14_07_create_bingo_room_config_table
 * Actualizado con: 2026_05_10_00_add_parent_automatic_id_and_is_scheduled
 */

/**
 * Estado de origen/programación de la sala
 * N = Manual (No programada)
 * Y = Programada
 * A = Automática
 */
export type IsScheduledType = 'N' | 'Y' | 'A'

/**
 * Modelo BingoRoomConfig
 * Configuración específica de una sala de bingo
 */
export interface BingoRoomConfig {
    readonly id: string
    readonly room_id: string
    readonly template_id?: string | null  // FK a bingo_room_templates (2026_05_10_02)
    readonly parent_automatic_id?: string | null  // FK a bingo_rooms_automatic (2026_05_10_00)
    readonly is_scheduled?: IsScheduledType  // 'N', 'Y', 'A' (2026_05_10_00)
    readonly room_type_id?: string | null
    readonly creator_user_id?: string | null
    readonly game_mode_id?: string | null
    readonly creation?: string | null
    readonly status?: string | null
    readonly date?: string | null
    readonly time?: string | null
    readonly unix_date?: number | null
    readonly rke_commission?: number
    readonly room_commission?: number
    readonly use_base_usd?: boolean
    readonly card_price_usd?: number | null
    readonly usd_exchange_round_step?: number | null
    readonly allowed_currencies?: (number | string)[] | null
    readonly prize_pool_type_id?: string | null
    readonly amount_added_prize_pool?: number | null
    readonly time_chip?: string | null
    readonly rounds?: string | null
    readonly rounds_pct?: number | null
    readonly use_serials?: boolean
    readonly serials_pct?: number | null
    readonly num_serials?: string | null
    readonly jackpot_pct?: number | null
    readonly in_live?: 'Y' | 'N'
    readonly created_at?: string
    readonly updated_at?: string

    // Relaciones opcionales
    readonly room?: Record<string, unknown> | null
    readonly template?: Record<string, unknown> | null
    readonly parent_automatic?: Record<string, unknown> | null
    readonly room_type?: Record<string, unknown> | null
    readonly creator_user?: Record<string, unknown> | null
    readonly game_mode?: Record<string, unknown> | null
    readonly prize_pool_type?: Record<string, unknown> | null
}

/**
 * Campos del formulario de configuración de sala
 */
export interface BingoRoomConfigFormData {
    roomId?: string
    templateId?: string | null
    parentAutomaticId?: string | null
    isScheduled?: IsScheduledType
    roomTypeId?: string | null
    gameModeId?: string | null
    date?: string | null
    time?: string | null
    rkeCommission?: number
    roomCommission?: number
    useBaseUsd?: boolean
    cardPriceUsd?: number | null
    usdExchangeRoundStep?: number | null
    allowedCurrencies?: (number | string)[]
    prizePoolTypeId?: string | null
    amountAddedPrizePool?: number | null
    timeChip?: string | null
    rounds?: string | null
    roundsPct?: number | null
    useSerials?: boolean
    serialsPct?: number | null
    numSerials?: string | null
    jackpotPct?: number | null
    inLive?: 'Y' | 'N'
}
