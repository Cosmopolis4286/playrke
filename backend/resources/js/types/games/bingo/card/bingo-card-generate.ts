export interface Room {
    id: string
    uuid_code: string
    prize_pool: number
    config?: {
        card_price_usd: number | null
    }
}

export interface fetchRoomData {
    exists: boolean
    rooms: Room
}

export interface Player {
    phone: string
    name: string
    lastName: string
}

export interface generateCardPayload {
    room_id: string
    num_ctns: number
    country_id: number | null
    player: {
        phone: string
        name: string
        last_name: string
    }
}
