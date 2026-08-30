export interface Opportunity {
  game: 'bingo' | 'trivia'
  game_label: string
  room_id: string
  room_code: string | null
  title?: string | null
  entry: number | null
  entry_currency_symbol?: string | null
  prize: number | null
  currency_symbol: string | null
  starts_at: number | null
  total_players: number
  max_players?: number | null
}

export interface OpportunityFeed {
  opportunities: Opportunity[]
}
