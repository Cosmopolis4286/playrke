/**
 * Types for Bingo Sales by Room Report
 */
import type { Pagination } from '@/js/types/common/table/pagination'

export interface SalesByRoomColumn {
  key: string
  label: string
  sortable: boolean
}

export interface SalesByRoomRow {
  id: string
  card_number: string
  player_name: string
  card_price: number
  currency: string
  sale_date: string
  payment_method: string
  status: string
}

export interface RoomInfo {
  id: string
  name: string
  type: string
  total_cards_sold: number
  total_amount: number
  currency: string
  commission: number
  net_amount: number
}

export interface SalesByRoomData {
  columns: SalesByRoomColumn[]
  rows: SalesByRoomRow[]
  room_info: RoomInfo
}

export interface Currency {
  id: number
  iso_code: string
}

export interface PaymentMethod {
  value: string
  label: string
}

export interface SalesByRoomPageProps {
  title: string
  roomSales: SalesByRoomData
  /** Paginación server-side de la página actual (ver BingoReportController::salesReportByRoom). */
  room_sales_pagination: Pagination
  currencies: Currency[]
  paymentMethods: PaymentMethod[]
  selectedCurrency?: string | null
  roomId: string
  /** true si el usuario es vendedor: solo SUS cartones de la sala y SU comisión. */
  sellerView?: boolean
}
