export interface LobbyRoom {
  id: string
  uuid_code?: string
  status_code?: string
  prize_pool: number
  full_house_prize?: number
  serial_prize?: number
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
  in_live?: boolean
  min_cards_to_start?: number | null
  game_variant?: string | null
}

export interface LobbyLive {
  prizePool: number
  totalPlayers: number
  totalCards: number
  userCards: number
  statusCode: string
  cardPrice: number
  currencySymbol: string
}
