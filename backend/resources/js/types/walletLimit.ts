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

/** Consumo real de UNA moneda específica dentro de un límite compartido (fallback global). */
export interface WalletLimitUsageByCurrency {
    currency_id: string
    iso_code: string
    symbol: string
    decimal_places: number
    daily: number
    weekly: number
    monthly: number
    transaction_count: number
}

/**
 * Resumen de consumo agregado de la plataforma para UN WalletLimit, indexado
 * por su id en el mapa de respuesta. Cuando is_shared_across_currencies es
 * true (límite "Todas las monedas"), daily/weekly/monthly son una SUMA de
 * monedas con escalas distintas (ej. USDT + VES) — no mostrar esos totales
 * solos; usar by_currency para el desglose real por moneda.
 */
export interface WalletLimitUsage {
    is_shared_across_currencies: boolean
    daily: WalletLimitUsagePeriod
    weekly: WalletLimitUsagePeriod
    monthly: WalletLimitUsagePeriod
    transaction_count: number
    by_currency: WalletLimitUsageByCurrency[]
}

/** Respuesta de GET /wallet-limits/usage-summary: mapa id de WalletLimit -> uso agregado. */
export type WalletLimitUsageSummary = Record<string, WalletLimitUsage>

/** Campos recomendados por GET /wallet-limits/recommend, escalados desde un baseline USD según la tasa de cambio activa. */
export interface WalletLimitRecommendation {
    min_deposit: number
    max_deposit: number
    min_per_transaction: number
    max_per_transaction: number
    max_daily: number
    max_weekly: number
    max_monthly: number
}
