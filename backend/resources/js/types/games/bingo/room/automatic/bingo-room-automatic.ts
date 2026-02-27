import type { BingoRoomFormData, RoundConfig, RoundPrizeType } from '@/js/types/games/bingo/room/bingo-room'

export type { RoundConfig, RoundPrizeType }

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
    readonly date?: string | null
    readonly days?: DayOfWeek[] | null
    readonly time?: string | null
    readonly in_live?: 'Y' | 'N'
    readonly game_mode_id?: string | null
    readonly room_type_id?: string | null
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
    readonly use_serials?: 'Y' | 'N'
    readonly serials_pct?: number | null
    readonly num_serials?: number | null
    readonly jackpot_pct?: number | null
    readonly creator_user_id?: string
    readonly status?: BingoRoomAutomaticStatus
    readonly created_at?: string
    readonly updated_at?: string
}

/**
 * Campos del formulario de creación/edición de sala automática
 * Extiende BingoRoomFormData con campos exclusivos de salas automáticas
 */
export interface BingoRoomAutomaticFormData extends BingoRoomFormData {
    days?: DayOfWeek[]
}
