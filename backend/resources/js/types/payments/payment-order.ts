export type PaymentOrderStatus =
  | 'PENDING'
  | 'DETECTED'
  | 'CONFIRMING'
  | 'CONFIRMED'
  | 'UNDERPAID'
  | 'OVERPAID'
  | 'EXPIRED'
  | 'FAILED'
  | 'CANCELLED'
  | 'CREDITED'

export interface PaymentOrder {
  id: string
  status: PaymentOrderStatus
  network: string
  token: string
  deposit_address: string
  expected_amount: number
  received_amount: number | null
  expires_at: string | null
  detected_at: string | null
  confirmed_at: string | null
  product?: { code: string; name: string }
}
