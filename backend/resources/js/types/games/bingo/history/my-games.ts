export interface GameHistoryPrize {
  type: string
  prize_type: string
  usd_amount: number
  local_amount: number
  currency: string
  currency_symbol: string
  decimal_places: number
  is_usd: boolean
  status: string
  status_label: string
  card_id: string | null
  card_serial: string | null
  card_matrix: Record<string, (number | null)[]>
}

export interface GameHistoryPlayerCard {
  id: string
  serial: string
  status: number
  is_winner: boolean
  card_matrix: Record<string, (number | null)[]>
}

export interface GameHistoryRound {
  round: number
  status: string
  prize_type: string
  pattern_id: string | null
}

export interface GameHistoryRow {
  room_id: string
  room_code: string
  game_date: string | null
  room_status: string | null
  start_mode: 'MANUAL' | 'SCHEDULED'
  game_variant: string
  cards_purchased: number
  won: boolean
  prizes_count: number
  total_usd: number
  total_local: number
  currency: string
  currency_symbol: string
  decimal_places: number
  is_usd: boolean
  joined_at: string | null
}

export interface GameHistoryDetail {
  room_id: string
  room_code: string
  game_date: string | null
  room_status: string | null
  room_type: string | null
  game_mode: string | null
  prize_pool_type: string | null
  prize_pool: number
  prize_pool_local: number
  game_variant: string
  cards_purchased: number
  won: boolean
  total_usd: number
  total_local: number
  currency: string
  currency_symbol: string
  decimal_places: number
  is_usd: boolean
  rounds: GameHistoryRound[]
  player_cards: GameHistoryPlayerCard[]
  prizes: GameHistoryPrize[]
}

export interface GameHistoryPagination {
  total: number
  per_page: number
  current_page: number
  last_page: number
  from: number | null
  to: number | null
}

export interface MyGamesPageProps {
  title: string
  history: GameHistoryRow[]
  pagination: GameHistoryPagination
}
