/**
 * Types for Bingo Winners Report
 */

export interface WinnersColumn {
  key: string
  label: string
  sortable: boolean
}

export interface WinnersRow {
  id: number
  winner_name: string
  room_name: string
  prize_type: string
  prize_amount: number
  currency: string
  win_date: string
  status: string
}

export interface WinnersData {
  columns: WinnersColumn[]
  rows: WinnersRow[]
}

export interface Currency {
  id: number
  iso_code: string
}

export interface PrizeType {
  value: string
  label: string
}

export interface WinnersPageProps {
  title: string
  winners: WinnersData
  currencies: Currency[]
  prizeTypes: PrizeType[]
  selectedType?: string | null
  dateFilter?: string | null
  selectedCurrency?: string | null
}
