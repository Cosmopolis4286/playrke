import type { StatCardData, ActivityItem, EntityCount, NetworkHealth, NetworkPayments, PrizesSummary } from '@/js/types/common/dashboard/dashboard'

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
    /** Salud de la red: vendieron hoy, sin vender en 7 días, no activos */
    networkHealth?: NetworkHealth
    /** Pagos de la red por revisar (los revisa el comercio) */
    networkPayments?: NetworkPayments
    /** Premios de cartones de su red aún sin entregar */
    prizesToDeliver?: PrizesSummary
    /** Próximas salas propias (hoy y mañana) */
    upcomingRooms?: CommerceUpcomingRoom[]
}

export interface CommerceUpcomingRoom {
    id:             string
    uuid_code:      string
    starts_at:      string | null
    is_manual:      boolean
    sales_open:     boolean
    total_players:  number
    max_players:    number | null
    total_cards:    number
    card_price_usd: number
    prize_pool_usd: number
}

export interface CommerceRkeReceivable {
    currency_id: string
    currency:    string
    symbol:      string
    sales:       number
    /** rke + jackpot + prize_pool */
    total:       number
    rke:         number
    jackpot:     number
    prize_pool:  number
    oldest:      string
}
