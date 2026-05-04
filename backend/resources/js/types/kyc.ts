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
  per_tx:    { max: number; min: number }
  daily:     { used: number; max: number }
  weekly:    { used: number; max: number }
  monthly:   { used: number; max: number }
}
