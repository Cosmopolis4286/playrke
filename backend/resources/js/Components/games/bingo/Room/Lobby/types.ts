export interface LobbyRoom {
  id: string
  uuid_code?: string
  status_code?: string
  image_url?: string | null
  prize_pool: number
  full_house_prize?: number | null
  serial_prize?: number | null
  total_players: number
  total_cards: number
  close_room?: boolean
  currency_symbol?: string
  card_price?: number
  card_price_usd?: number
  room_type_description?: string
  game_mode_description?: string
  unix_date?: number | null
  date?: string
  time?: string
  time_chip?: number
  rounds?: number
  min_cards_to_start?: number | null
  max_cards_to_start?: number | null
  max_players?: number | null
  min_players?: number | null
  game_variant?: string | null
  is_private?: boolean
  is_access_verified?: boolean
}

/** @deprecated Use LobbyLiveState */
export type LobbyLive = LobbyLiveState

export interface LobbyLiveState {
  prizePool: number
  totalPlayers: number
  totalCards: number
  userCards: number
  statusCode: string
  cardPrice: number
  currencySymbol: string
}
