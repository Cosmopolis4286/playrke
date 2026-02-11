/**
 * Tipos de sala de bingo
 */
export type BingoRoomType = 'PV' | 'PB'

/**
 * Indica si los premios fueron pagados
 */
export type BingoPrizesPaid = 'Y' | 'N'

/**
 * Modelo BingoRoom
 * Basado en: 2024_06_14_04_create_bingo_rooms_table
 */
export interface BingoRoom {
    readonly id: number | string
    readonly encrypted_code: string
    readonly status_id: number | string
    readonly room_type: BingoRoomType
    readonly creator_user_id: number | string
    readonly prizes_paid: BingoPrizesPaid
    readonly publication_date: string | null
    readonly total_players: number
    readonly total_cards: number
    readonly total_sales: number
    readonly rke_commission: number
    readonly room_commission: number
    readonly seller_commission: number
    readonly prize_pool: number
    readonly full_house_prize: number
    readonly serial_prize: number
    readonly closed_at: string | null
    readonly uuid_code: string | null
    readonly created_at: string
    readonly updated_at: string

    // Appended accessors
    readonly room_type_description: string
    readonly is_closed: boolean
    readonly total_commissions: number
}
