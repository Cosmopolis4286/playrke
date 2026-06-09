export interface GameHistoryPrize {
  type: string
  usd_amount: number
  local_amount: number
  currency: string
  status: string
}

export interface GameHistoryRow {
  room_id: string
  room_code: string
  game_date: string | null
  room_status: string | null
  start_mode: 'MANUAL' | 'SCHEDULED'
  cards_purchased: number
  won: boolean
  prizes_count: number
  total_usd: number
  total_local: number
  prizes: GameHistoryPrize[]
  joined_at: string | null
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
