/**
 * Types for Bingo Sales Report
 */

export interface SalesColumn {
  key: string
  label: string
  sortable: boolean
}

export interface SalesRow {
  id: string
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

export interface SalesCurrencyTotals {
  amount: number
  commission: number
}

export interface SalesSummary {
  total_rooms: number
  total_cards_sold: number
  /** Totales de venta y comisión por código ISO de moneda (una sala puede vender en varias monedas). */
  by_currency: Record<string, SalesCurrencyTotals>
  total_commission: number
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
