export type KycStatus = 'NONE' | 'PENDING' | 'VERIFIED' | 'REJECTED'
export type KycLevel  = 0 | 1 | 2

export interface KycState {
  kyc_level:        KycLevel
  kyc_status:       KycStatus
  kyc_verified_at:  string | null
  rejection_reason: string | null
  has_pending:      boolean
  submitted_at:     string | null
}

export interface WithdrawalLimitUsage {
  kyc_level: KycLevel
  /** true si su nivel KYC no alcanza para retirar (debe verificarse). */
  withdrawal_requires_kyc?: boolean
  deposit:   { min: number; max: number }
  per_tx:    { max: number; min: number }
  daily:     { used: number; max: number }
  weekly:    { used: number; max: number }
  monthly:   { used: number; max: number }
}
