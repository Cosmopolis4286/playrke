import { z } from 'zod'

// Enums para pagos
export enum TransactionType {
  CREDIT = 'CREDIT',
  DEBIT = 'DEBIT',
  TRANSFER = 'TRANSFER'
}

export enum TransactionStatus {
  PENDING = 'PENDING',
  COMPLETED = 'COMPLETED',
  FAILED = 'FAILED',
  CANCELLED = 'CANCELLED'
}

export enum PaymentMethod {
  WALLET = 'WALLET',
  CREDIT_CARD = 'CREDIT_CARD',
  DEBIT_CARD = 'DEBIT_CARD',
  BANK_TRANSFER = 'BANK_TRANSFER',
  CRYPTO = 'CRYPTO',
  PAYPAL = 'PAYPAL',
  OTHER = 'OTHER'
}

// Schemas de validación
export const CreateWalletSchema = z.object({
  userId: z.string().uuid(),
  currencyId: z.string().uuid(),
  initialBalance: z.number().min(0).optional(),
  isPrimary: z.boolean().optional()
})

export const AddFundsSchema = z.object({
  walletId: z.string().uuid(),
  amount: z.number().min(0.01),
  description: z.string().min(1).max(255),
  metadata: z.record(z.any()).optional()
})

export const WithdrawFundsSchema = z.object({
  walletId: z.string().uuid(),
  amount: z.number().min(0.01),
  description: z.string().min(1).max(255),
  metadata: z.record(z.any()).optional()
})

export const TransferFundsSchema = z.object({
  fromWalletId: z.string().uuid(),
  toWalletId: z.string().uuid(),
  amount: z.number().min(0.01),
  description: z.string().max(255).optional()
})

export const CreatePaymentSchema = z.object({
  userId: z.string().uuid(),
  amount: z.number().min(0.01),
  currencyId: z.string().uuid(),
  method: z.nativeEnum(PaymentMethod),
  description: z.string().min(1).max(255),
  metadata: z.record(z.any()).optional()
})

export const ProcessPaymentSchema = z.object({
  paymentId: z.string().uuid(),
  paymentData: z.record(z.any())
})

// Tipos derivados
export type CreateWalletRequest = z.infer<typeof CreateWalletSchema>
export type AddFundsRequest = z.infer<typeof AddFundsSchema>
export type WithdrawFundsRequest = z.infer<typeof WithdrawFundsSchema>
export type WalletTransferRequest = z.infer<typeof TransferFundsSchema>
export type CreatePaymentRequest = z.infer<typeof CreatePaymentSchema>
export type ProcessPaymentRequest = z.infer<typeof ProcessPaymentSchema>

// Interfaces de respuesta
export interface CurrencyInfo {
  id: string
  code: string
  name: string
  symbol: string
  decimals: number
}

export interface WalletResponse {
  id: string
  userId: string
  currency: CurrencyInfo
  balance: number
  isActive: boolean
  isPrimary: boolean
  user: {
    id: string
    username: string
    email?: string
  }
  createdAt: Date
  updatedAt: Date
}

export interface WalletBalance {
  walletId: string
  balance: number
  currency: CurrencyInfo
  lastUpdated: Date
}

export interface TransactionResponse {
  id: string
  userId: string
  walletId?: string
  type: TransactionType
  amount: number
  currency: CurrencyInfo
  description: string
  status: TransactionStatus
  reference: string
  metadata: Record<string, any>
  createdAt: Date
  updatedAt: Date
}

export interface WalletTransactionHistory {
  walletId: string
  transactions: Array<{
    id: string
    type: TransactionType
    amount: number
    currency: CurrencyInfo
    description: string
    status: TransactionStatus
    reference: string
    metadata: Record<string, any>
    createdAt: Date
  }>
  total: number
  page: number
  totalPages: number
}

export interface PaymentResponse {
  id: string
  userId: string
  amount: number
  currency: CurrencyInfo
  method: PaymentMethod
  status: TransactionStatus
  description: string
  reference: string
  paymentUrl?: string
  metadata: Record<string, any>
  createdAt: Date
  updatedAt: Date
  expiresAt?: Date
}

export interface PaymentListResponse {
  payments: PaymentResponse[]
  total: number
  page: number
  totalPages: number
}

export interface WalletSummary {
  totalWallets: number
  totalBalance: number
  activeCurrencies: string[]
  primaryWallet: WalletResponse | null
  recentTransactions: Array<{
    id: string
    type: TransactionType
    amount: number
    currency: string
    description: string
    createdAt: Date
  }>
}

// Tipos para reportes y estadísticas
export interface WalletStats {
  totalBalance: number
  totalTransactions: number
  transactionVolume: {
    daily: number
    weekly: number
    monthly: number
  }
  transactionsByType: {
    credits: number
    debits: number
    transfers: number
  }
  currencyBreakdown: Array<{
    currency: CurrencyInfo
    balance: number
    transactionCount: number
  }>
}

export interface PaymentStats {
  totalPayments: number
  totalAmount: number
  successfulPayments: number
  failedPayments: number
  pendingPayments: number
  paymentsByMethod: Array<{
    method: PaymentMethod
    count: number
    totalAmount: number
  }>
  averagePaymentAmount: number
  paymentTrends: Array<{
    date: Date
    count: number
    amount: number
  }>
}

// Configuraciones de métodos de pago
export interface PaymentMethodConfig {
  method: PaymentMethod
  isEnabled: boolean
  minAmount: number
  maxAmount: number
  fee: {
    type: 'FIXED' | 'PERCENTAGE'
    value: number
  }
  supportedCurrencies: string[]
  processingTime: string
  description: string
}

// Webhooks y notificaciones
export interface PaymentWebhookData {
  paymentId: string
  status: TransactionStatus
  amount: number
  currency: string
  reference: string
  gatewayData: Record<string, any>
  timestamp: Date
}

export interface WalletNotification {
  userId: string
  walletId: string
  type: 'CREDIT' | 'DEBIT' | 'TRANSFER' | 'STATUS_CHANGE'
  amount?: number
  currency?: string
  message: string
  metadata: Record<string, any>
  timestamp: Date
}

// Errores específicos de pagos
export enum PaymentErrorCode {
  INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS',
  WALLET_INACTIVE = 'WALLET_INACTIVE',
  CURRENCY_MISMATCH = 'CURRENCY_MISMATCH',
  PAYMENT_EXPIRED = 'PAYMENT_EXPIRED',
  PAYMENT_ALREADY_PROCESSED = 'PAYMENT_ALREADY_PROCESSED',
  INVALID_PAYMENT_METHOD = 'INVALID_PAYMENT_METHOD',
  AMOUNT_BELOW_MINIMUM = 'AMOUNT_BELOW_MINIMUM',
  AMOUNT_ABOVE_MAXIMUM = 'AMOUNT_ABOVE_MAXIMUM',
  DAILY_LIMIT_EXCEEDED = 'DAILY_LIMIT_EXCEEDED',
  MONTHLY_LIMIT_EXCEEDED = 'MONTHLY_LIMIT_EXCEEDED'
}