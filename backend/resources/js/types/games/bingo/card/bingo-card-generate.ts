export type RoomStep = 'date' | 'roomType' | 'gameMode'

/**
 * Configuración asociada a una sala de bingo.
 */
export interface RoomConfig {
    card_price_usd: number | null
    date?: string
    time?: string
    room_type_id?: number
    game_mode_id?: number
}

/**
 * Representa una sala de bingo disponible.
 */
export interface Room {
    id: string
    uuid_code: string
    prize_pool: number
    total_players: number
    creator_user_id?: number
    config: RoomConfig
}

/**
 * Respuesta del endpoint /bingo-card/fetch-room
 */
export interface FetchRoomResponse {
    success: boolean
    data: {
        exists: boolean
        rooms: Room[]
    }
}

/**
 * Datos del jugador.
 */
export interface Player {
    phone: string
    name: string
    lastName: string
}

/**
 * Payload para generar cartones.
 */
export interface GenerateCardPayload {
    room_id: string
    num_ctns: number
    /** Snowflake ID del país, enviado como string para evitar truncado. */
    country_id: string | null
    player: {
        phone: string
        name: string
        last_name: string
    }
}

/**
 * Respuesta del endpoint GET /bingo-card/{room}/card-price?country_id=X
 *
 * - card_price:      Precio calculado del cartón en la moneda local del país.
 * - currency_symbol: Símbolo de la moneda (ej: "Bs.", "R$", "$").
 * - exchange_rate:   Tasa de cambio aplicada (commerce → moneda del país).
 * - is_custom_price: true si el precio viene de bingo_card_prices, false si
 *                    es el precio base de la sala × tasa.
 */
export interface CardPriceResponse {
    card_price: number
    currency_symbol: string
    exchange_rate: number
    is_custom_price: boolean
}

/**
 * Estado del proceso de selección de salas.
 */
export type RoomStepStatus =
    | 'idle'
    | 'loading'
    | 'noDateRooms'
    | 'noRoomTypeRooms'
    | 'noGameModeRooms'
    | 'noPrizePoolTypeRooms'
    | 'ready'
