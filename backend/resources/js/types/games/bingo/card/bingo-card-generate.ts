export type RoomStep = 'date' | 'roomType' | 'gameMode'

/**
 * Configuración asociada a una sala de bingo.
 */
export interface RoomConfig {
    card_price_usd: number | null
    date?: string
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
    country_id: number | null
    player: {
        phone: string
        name: string
        last_name: string
    }
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
    | 'ready'
