/**
 * Types for Bingo Sales by Room Report
 */

export interface SalesByRoomColumn {
  key: string
  label: string
  sortable: boolean
}

export interface SalesByRoomRow {
  id: number
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
  currencies: Currency[]
  paymentMethods: PaymentMethod[]
  selectedCurrency?: string | null
  roomId: string
}
