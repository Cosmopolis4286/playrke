/**
 * Types for Bingo Cards Sold Report
 */

export interface CardsSoldColumn {
  key: string
  label: string
  sortable: boolean
}

export interface CardsSoldRow {
  id: number
  card_number: string
  room_name: string
  player_name: string
  sale_date: string
  amount: number
  currency: string
  status: string
}

export interface CardsSoldData {
  columns: CardsSoldColumn[]
  rows: CardsSoldRow[]
}

export interface Currency {
  id: number
  iso_code: string
}

export interface CardsSoldPageProps {
  title: string
  cards: CardsSoldData
  currencies: Currency[]
  selectedCurrency?: string | null
  dateFilter?: string | null
}
