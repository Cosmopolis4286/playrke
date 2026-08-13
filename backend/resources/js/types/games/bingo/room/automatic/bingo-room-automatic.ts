import type { BingoRoomFormData, RoundConfig, RoundPrizeType } from '@/js/types/games/bingo/room/bingo-room'

export type { RoundConfig, RoundPrizeType }

/**
 * Tipos de sala de bingo
 */
export type BingoRoomAutomaticType = 'PRIVATE' | 'PUBLIC' | 'VIP' | 'DEMO'

/**
 * Indica si los premios fueron pagados
 */
export type BingoPrizesPaid = 'Y' | 'N'

/**
 * Estado de la sala
 */
export type BingoRoomAutomaticStatus = 'ACTIVE' | 'INACTIVE' | 'SUSPENDED'

/**
 * Tipo de recurrencia del horario
 */
export type RecurrenceType = 'DAILY' | 'WEEKLY' | 'MONTHLY' | 'CUSTOM'

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
 * Actualizado con: 2026_05_10_03_consolidate_automatic_tables
 */
export interface BingoRoomAutomatic {
    readonly id: string
    readonly template_id?: string | null  // FK a bingo_room_templates
    readonly date?: string | null
    readonly days?: DayOfWeek[] | null
    readonly time?: string | null
    readonly in_live?: 'Y' | 'N'
    readonly connection_type_id?: string | null
    readonly game_mode_id?: string | null
    readonly room_type_id?: string | null
    readonly access_code?: string | null
    readonly has_password?: boolean
    readonly host_user_id?: string | null
    readonly rke_commission?: number
    readonly room_commission?: number
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
    readonly creator_user_id?: string
    readonly status?: BingoRoomAutomaticStatus
    readonly created_at?: string
    readonly updated_at?: string
    // Nuevos campos de programación avanzada
    readonly recurrence_type?: RecurrenceType
    readonly recurrence_days?: number[] | null // 0=domingo, 6=sábado
    readonly game_time?: string | null
    readonly start_date?: string | null
    readonly end_date?: string | null
    readonly default_config?: Record<string, unknown> | null
    readonly default_card_price?: number | null
    readonly default_currency_id?: string | null
    readonly auto_create_room?: boolean
    readonly max_rooms_per_day?: number | null
    readonly min_cards_to_start?: number | null
    readonly max_cards_to_start?: number | null
    readonly min_players?: number | null
    readonly max_players?: number | null
    readonly sales_start_offset_days?: number | null
    readonly rooms_created?: number
    readonly last_room_created_at?: string | null
    // Campos consolidados de bingo_rounds_automatic (JSON)
    readonly rounds_config?: Record<string, unknown> | null
    readonly prize_types?: unknown[] | null
    readonly bingo_patterns?: unknown[] | null
    readonly prize_values?: Record<string, number> | null
    // Campos consolidados de bingo_card_price_automatic (JSON)
    readonly card_currencies?: (number | string)[] | null
    readonly card_prices?: Record<string, number> | null
}

/**
 * Campos del formulario de creación/edición de sala automática
 * Extiende BingoRoomFormData con campos exclusivos de salas automáticas
 */
export interface BingoRoomAutomaticFormData extends BingoRoomFormData {
    days?: DayOfWeek[]
    // Nuevos campos de programación avanzada
    recurrenceType?: RecurrenceType
    recurrenceDays?: number[] // 0=domingo, 6=sábado
    gameTime?: string | null
    startDate?: string | null
    endDate?: string | null
    defaultConfig?: Record<string, unknown> | null
    defaultCardPrice?: number | null
    defaultCurrencyId?: string | null
    autoCreateRoom?: boolean
    maxRoomsPerDay?: number | null
    // Ventana de ventas / mínimo cartones
    minCardsToStart?: number | null
    salesStartOffsetDays?: number | null
}
