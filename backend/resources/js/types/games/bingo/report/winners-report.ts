/**
 * Types for Bingo Winners Report
 */
import type { Pagination } from '@/js/types/common/table/pagination'

export interface WinnersColumn {
  key: string
  label: string
  sortable: boolean
}

export interface WinnersRow {
  id: string
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
  /** Paginación server-side de la página actual (ver BingoReportController::winners). */
  winners_pagination: Pagination
  currencies: Currency[]
  prizeTypes: PrizeType[]
  selectedType?: string | null
  dateFilter?: string | null
  selectedCurrency?: string | null
}
