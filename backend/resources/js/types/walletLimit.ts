/**
 * Configuración de límites de depósito/retiro de billetera, por moneda y
 * nivel KYC. Basado en la migración y modelo de WalletLimit.
 */
export interface WalletLimitCurrency {
    id: string
    iso_code: string
    symbol: string
    name: string
    decimal_places: number
}

export interface WalletLimit {
    readonly id: number
    currency_id: string | null // null = aplica a todas las monedas (fallback global)
    currency?: WalletLimitCurrency | null
    kyc_level: 0 | 1 | 2 // 0 = sin verificar, 1 = básico, 2 = completo
    min_deposit: number
    max_deposit: number
    min_per_transaction: number // mínimo por retiro
    max_per_transaction: number // máximo por retiro
    max_daily: number
    max_weekly: number
    max_monthly: number
    is_active: boolean
    readonly created_at?: string
    readonly updated_at?: string
}

export interface WalletLimitPageProps {
    walletLimits: WalletLimit[]
    currencies: WalletLimitCurrency[]
}

/** Consumo real (retiros) de un período frente a su tope configurado. */
export interface WalletLimitUsagePeriod {
    used: number
    max: number
}

/** Resumen de consumo agregado de la plataforma para UN WalletLimit, indexado por su id en el mapa de respuesta. */
export interface WalletLimitUsage {
    daily: WalletLimitUsagePeriod
    weekly: WalletLimitUsagePeriod
    monthly: WalletLimitUsagePeriod
    transaction_count: number
}

/** Respuesta de GET /wallet-limits/usage-summary: mapa id de WalletLimit -> uso agregado. */
export type WalletLimitUsageSummary = Record<string, WalletLimitUsage>
