export interface WalletCurrency {
  iso_code: string
  symbol: string
  name: string
  decimal_places: number
}

export interface Wallet {
  id: string
  balance: number
  status: 'ACTIVE' | 'INACTIVE' | 'BLOCKED'
  currency_id: string
  currency: WalletCurrency
}

export interface WalletPageProps {
  wallets: Wallet[]
}

export interface WalletOperationPayload {
  wallet_id: string
  amount: number
}

export interface WalletOperationResponse {
  success: boolean
  message: string
  balance?: number
}

export type WalletOperation = 'deposit' | 'withdraw'

// Tipos del backend (snake_case, tal como llegan del API)
export type WalletMovementType = 'DEPOSIT' | 'WITHDRAWAL' | 'PURCHASE' | 'PRIZE'

export interface WalletMovement {
  id: string
  wallet_id: string
  user_id: string
  type: WalletMovementType
  amount: number
  balance_before: number
  balance_after: number
  description: string | null
  reference_id: string | null
  created_at: string
  updated_at: string
}

export interface WalletMovementsMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface WalletMovementsResponse {
  success: boolean
  data: WalletMovement[]
  meta: WalletMovementsMeta
}
