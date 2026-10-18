/** Fuente de la comisión (ver CommissionLedgerService::SOURCES). */
export type CommissionSource = 'seller' | 'subsidiary' | 'network' | 'room'

/** Cómo se cobró: efectivo (ya en mano), billetera (al cerrar sala) o custodiada (venta cruzada). */
export type CommissionHow = 'cash' | 'wallet' | 'custody'

export type CommissionStatus = 'paid' | 'pending' | 'refunded'

export interface CommissionRow {
  id: string
  date: string | null
  source: CommissionSource
  source_label: string
  room: string | null
  buyer: string | null
  cards: number
  currency_id: string
  currency: string
  symbol: string
  sale_amount: number
  commission: number
  how: CommissionHow
  status: CommissionStatus
  status_label: string
}

export interface CommissionSummary {
  currency_id: string
  currency: string
  count: number
  total: string
  paid: string
  pending: string
  in_wallet: string
}

export interface CommissionLedger {
  rows: CommissionRow[]
  summary: CommissionSummary[]
  sources: CommissionSource[]
  range: { from: string; to: string }
  truncated: boolean
}

export interface CommissionFilters {
  from: string | null
  to: string | null
  source: CommissionSource | null
  status: CommissionStatus | null
}

export interface CommissionsPageProps {
  ledger: CommissionLedger
  filters: CommissionFilters
}
