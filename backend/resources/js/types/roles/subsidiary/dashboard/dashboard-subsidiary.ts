import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Inertia page props para el Dashboard de Subsidiary (agencia/sucursal)
// --------------------------------------------------------------------------

export interface SubsidiaryStats {
    totalSales:     StatCardData
    cardsSoldToday: StatCardData
    winnersToday:   StatCardData
    sellersCount:   StatCardData
    commission:     StatCardData
}

export interface SalesTrendPoint {
    date:  string
    count: number
}

export interface SellerSalesRow {
    seller_user_id: string | null
    seller_name:    string
    total_sales:    number
    total_cards:    number
}

export interface SubsidiaryWallet {
    currency_code: string
    symbol:        string
    balance:       number
    bonus_balance: number
}

/** Comercializadora a la que reporta esta agencia. */
export interface ReportsTo {
    name: string
    type: 'commerce'
}

export interface DashboardSubsidiaryPageProps {
    /** Nombre del usuario autenticado */
    userName?: string
    /** Stats principales */
    stats?: SubsidiaryStats
    /** Actividad reciente */
    recentActivity?: ActivityItem[]
    /** Comercializadora a la que reporta esta agencia */
    reportsTo?: ReportsTo | null
    /** Serie diaria de cartones vendidos (últimos 14 días) */
    salesTrend?: SalesTrendPoint[]
    /** Ventas agrupadas por vendedor (top 10, este mes) */
    salesBySeller?: SellerSalesRow[]
    /** Billeteras propias de la agencia (saldo por moneda) */
    wallets?: SubsidiaryWallet[]
    /** Movimientos de billetera propios pendientes de aprobación */
    pendingWalletCount?: number
    /** Período activo (e.g. "Febrero 2026") */
    period?: string
}
