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
