export interface MovementUser {
  id:         string
  first_name: string | null
  last_name:  string | null
  email:      string
  username:   string | null
}

export interface MovementAdmin {
  id:         string
  first_name: string | null
  last_name:  string | null
  email:      string
}

export interface MovementCurrency {
  iso_code:       string
  symbol:         string
  decimal_places: number
}

export interface MovementMetadata {
  directory_id?:   string
  directory_type?: string
  [key: string]:   unknown
}

export interface MovementRecentEntry {
  id:         string
  type:       string
  status:     string
  amount:     number
  created_at: string
}

/**
 * Solo presente cuando el detalle se cargó vía GET /wallet-movements/{id}
 * (show) — el listado (fetchList) no las calcula, para no meter N+1 queries
 * en la tabla paginada.
 */
export interface MovementFraudSignals {
  duplicate_receipt_movement_id: string | null
  deposits_today:                number | null
  deposits_today_max:            number | null
  withdrawals_today:             number | null
  withdrawals_today_max:         number | null
  duplicate_reference:           boolean
  recent_movements:              MovementRecentEntry[]
  rejection_rate:                number | null
}

/** Fila de auditoría del movimiento — ver WalletMovementController::mapAuditRow(). */
export interface MovementAuditEntry {
  id:    string
  date:  string
  actor: string
  event: string
}

export interface Movement {
  id:                    string
  type:                  string
  status:                'PENDING' | 'COMPLETED' | 'CANCELLED' | 'REJECTED'
  amount:                number
  balance_before:        number
  balance_after:         number
  description:           string | null
  reference_id:          string | null
  receipt_path:          string | null
  receipt_url:           string | null
  metadata:               MovementMetadata | null
  prize_type:            'DIGITAL' | 'PHYSICAL' | null
  game_id:               string | null
  admin_notes:           string | null
  player_message:        string | null
  processed_at:          string | null
  processed_by:          MovementAdmin | null
  assigned_at:           string | null
  assigned_to:           MovementAdmin | null
  /** Saldo ACTUAL de la wallet del usuario — distinto de balance_before/after, que son de ESTE movimiento puntual. */
  wallet_balance:        number
  wallet_bonus_balance:  number
  created_at:            string
  currency:               MovementCurrency | null
  user:                  MovementUser | null
  fraud_signals?:        MovementFraudSignals
  recent_audits?:        MovementAuditEntry[]
  has_more_audits?:      boolean
}

export interface MovementsMeta {
  current_page: number
  last_page:    number
  per_page:     number
  total:        number
}
