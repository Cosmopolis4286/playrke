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

// ─── Directorio de retiro ──────────────────────────────────────────────────
export type DirectoryType = 'pago_movil' | 'pix' | 'binance'

export interface UserDirectory {
  id: string
  type: DirectoryType
  label: string   // Nombre del banco / tipo de cuenta
  detail: string  // Detalle: número, clave PIX, email, etc.
  holder: string | null
}

export interface DirectoriesResponse {
  success: boolean
  data: UserDirectory[]
}

// ─── Depósito con comprobante ──────────────────────────────────────────────
export interface DepositPayload {
  wallet_id: string
  amount: number
  receipt: File
  reference?: string
  notes?: string
}

// ─── Retiro con directorio ─────────────────────────────────────────────────
export interface WithdrawPayload {
  wallet_id: string
  amount: number
  directory_id: string
  directory_type: DirectoryType
  notes?: string
}

export interface WalletOperationResponse {
  success: boolean
  message: string
  balance?: number
}

export type WalletMovementStatus = 'PENDING' | 'COMPLETED' | 'CANCELLED' | 'REJECTED'

export interface PendingOperation {
  id: string
  type: 'DEPOSIT' | 'WITHDRAWAL'
  status: WalletMovementStatus
  amount: number
  description: string | null
  created_at: string
}

export interface PendingOperationResponse {
  success: boolean
  pending: PendingOperation | null
}

export type WalletOperation = 'deposit' | 'withdraw'

// ─── Movimientos ───────────────────────────────────────────────────────────
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
