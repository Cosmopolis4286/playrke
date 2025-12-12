/**
 * Wallet and transaction types
 */

export interface Wallet {
  id: string
  user_id: string
  currency: string
  balance: number
  status: 'active' | 'frozen' | 'suspended'
  created_at: string
  updated_at: string
}

export interface Transaction {
  id: string
  wallet_id: string
  type: 'deposit' | 'withdrawal' | 'purchase' | 'prize' | 'refund'
  amount: number
  currency: string
  status: 'pending' | 'completed' | 'failed' | 'cancelled'
  description: string
  metadata?: any
  created_at: string
  updated_at: string
}

export interface DepositData {
  wallet_id: string
  amount: number
  payment_method: string
  payment_proof?: File
}

export interface WithdrawalData {
  wallet_id: string
  amount: number
  destination_account: string
}
