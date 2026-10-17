import type { StatCardData } from '@/js/types/common/dashboard/dashboard'
import type { ExchangeRateSummary } from '@js/composables/roles/superadmin/dashboard/useExchangeRateSummary'

export interface TreasurerStats {
    /** Recargas/retiros que esperan aprobación */
    pendingMovements: StatCardData
    /** Deuda de los comercios con la plataforma (moneda principal) */
    receivables:      StatCardData
    /** Tasas oficiales sin actualizar en 24 h */
    staleRates:       StatCardData
    /** Premios en disputa o suspendidos */
    stuckPrizes:      StatCardData
}

/** Deuda pendiente de un comercio en una moneda (PlatformReceivableService::pendingSummary) */
export interface TreasurerReceivable {
    debtor_user_id: string
    debtor:         string
    currency_id:    string
    currency:       string
    symbol:         string
    sales:          number
    total:          number
    rke:            number
    jackpot:        number
    prize_pool:     number
    creator:        number
    oldest:         string
}

export interface DashboardTreasurerPageProps {
    userName?:      string
    stats?:         TreasurerStats
    exchangeRates?: ExchangeRateSummary
    receivables?:   TreasurerReceivable[]
    period?:        string
}
