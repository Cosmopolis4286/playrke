/**
 * Bingo related types
 */

export interface BingoRoom {
  id: string
  name: string
  status: 'pending' | 'active' | 'finished' | 'cancelled'
  creator_type: string
  creator_id: string
  start_date: string
  max_players: number
  current_players: number
  prize_pool: number
  currency: string
  config: any
}

export interface BingoCard {
  id: string
  room_id: string
  player_id: string
  numbers: number[][]
  price: number
  status: 'pending' | 'active' | 'winner' | 'loser'
  created_at: string
}

export interface BingoDraw {
  id: string
  room_id: string
  numbers_called: number[]
  current_number: number | null
  status: 'active' | 'finished'
}
