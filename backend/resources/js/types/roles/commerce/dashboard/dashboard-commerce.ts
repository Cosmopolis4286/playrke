import type { StatCardData, ActivityItem, EntityCount } from '@/js/types/common/dashboard/dashboard'

export type { EntityCount }

// --------------------------------------------------------------------------
// Inertia page props para el Dashboard de Comercio
// --------------------------------------------------------------------------

export interface CommerceStats {
    totalSales:     StatCardData
    totalRevenue:   StatCardData
    activeGames:    StatCardData
    pendingPayouts: StatCardData
    commission:     StatCardData
}

export interface SalesTrendPoint {
    date:  string
    count: number
}

export interface SellerSalesRow {
    seller_user_id:   string | null
    seller_name:      string
    total_sales:      number
    total_cards:      number
    total_commission: number
}

export interface CommerceWallet {
    currency_code: string
    symbol:        string
    balance:       number
    bonus_balance: number
}

export interface CommerceConfiguration {
    currencies: { code: string; symbol: string }[]
    games:      string[]
}

export interface DashboardCommercePageProps {
    /** Nombre del usuario autenticado */
    userName?: string
    /** Stats principales */
    stats?: CommerceStats
    /** Actividad reciente */
    recentActivity?: ActivityItem[]
    /** Totales de la red de vendedores propia (vendedores, agencias) */
    entityCounts?: EntityCount[]
    /** Serie diaria de cartones vendidos (últimos 14 días) */
    salesTrend?: SalesTrendPoint[]
    /** Ventas agrupadas por vendedor (top 10, este mes) */
    salesBySeller?: SellerSalesRow[]
    /** Billeteras propias del comercio (saldo por moneda) */
    wallets?: CommerceWallet[]
    /** Monedas y juegos habilitados para el comercio */
    configuration?: CommerceConfiguration
    /** Movimientos de billetera propios pendientes de aprobación */
    pendingWalletCount?: number
    /** Período activo (e.g. "Febrero 2026") */
    period?: string
    /** RKE que el comercio le debe a la plataforma por ventas fuera del sistema, por moneda */
    rkeReceivables?: CommerceRkeReceivable[]
}

export interface CommerceRkeReceivable {
    currency_id: string
    currency:    string
    symbol:      string
    sales:       number
    total:       number
    oldest:      string
}
