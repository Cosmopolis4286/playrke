export interface WinnerEntry {
  user_id: number | string
  user_name: string
  card_id: string
  card_serial: string
  prize: number
  pattern?: string
  round?: number
}

export interface GameEndData {
  hasWinner: boolean
  totalDrawn: number
  winners: WinnerEntry[]
  prize: number
  endTime: string
}

export interface PlayerProgress {
  user_id: number | string
  user_name: string
  missing: number
}
