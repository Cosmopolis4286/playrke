/**
 * Types for Bingo Sales Report
 */

export interface SalesColumn {
  key: string
  label: string
  sortable: boolean
}

export interface SalesRow {
  id: number
  room_name: string
  room_type: string
  total_cards_sold: number
  total_amount: number
  currency: string
  commission: number
  net_amount: number
  sale_date: string
  status: string
}

export interface SalesSummary {
  total_rooms: number
  total_cards_sold: number
  total_amount_usd: number
  total_amount_eur: number
  total_amount_ves: number
  total_commission: number
  total_net_amount: number
}

export interface SalesData {
  columns: SalesColumn[]
  rows: SalesRow[]
  summary: SalesSummary
}

export interface Currency {
  id: number
  iso_code: string
}

export interface RoomType {
  value: string
  label: string
}

export interface SalesPageProps {
  title: string
  sales: SalesData
  currencies: Currency[]
  roomTypes: RoomType[]
  selectedCurrency?: string | null
  dateFilter?: string | null
}
