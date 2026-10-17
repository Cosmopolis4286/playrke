import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Stats específicos del Vendedor
// --------------------------------------------------------------------------

export interface SellerStats {
    /** Cartones vendidos hoy */
    cartonsSoldToday: StatCardData
    /** Total cartones del mes */
    cartonsThisMonth: StatCardData
    /** Comisión ganada este mes */
    commissionThisMonth: StatCardData
    /** Ganadores registrados hoy */
    winnersToday: StatCardData
}

// --------------------------------------------------------------------------
// Acceso rápido
// --------------------------------------------------------------------------

export interface SellerQuickLink {
    label: string
    description: string
    icon: string
    href: string
    variant?: 'primary' | 'success' | 'info' | 'warning' | 'secondary'
}

// --------------------------------------------------------------------------
// A quién reporta / comisiones por sala / tendencia
// --------------------------------------------------------------------------

export interface SellerReportsTo {
    name:          string
    type:          'commerce' | 'subsidiary'
    commerce_name: string
}

export interface SellerRoomCommission {
    room_id:    string
    room_name:  string
    commission: number
}

export interface SalesTrendPoint {
    date:  string
    count: number
}

export interface SellerRankingStanding {
    rank:               number
    seller_user_id:     string
    seller_name:        string
    cards_sold:         number
    sale_amount:        number
    commission_amount:  number
    goal_reached:       boolean
    total_sellers:      number
    goal:               number
}

export interface SellerPendingAction {
    key:     'in_review' | 'rejected' | 'prizes'
    label:   string
    count:   number
    /** Monto de la moneda principal ya formateado ("Bs 120,00 +1 moneda(s)") */
    amount:  string | null
    hint:    string
    icon:    string
    variant: 'info' | 'danger' | 'warning'
    href:    string
}

export interface SellerUpcomingRoom {
    id:             string
    name:           string
    is_own:         boolean
    is_manual:      boolean
    starts_at:      string | null
    sales_close_at: string | null
    card_price_usd: number
    prize_pool_usd: number
    total_cards:    number
}

// --------------------------------------------------------------------------
// Inertia page props
// --------------------------------------------------------------------------

export interface DashboardSellerPageProps {
    /** Nombre del usuario autenticado */
    userName?: string
    /** Comisión configurada (e.g. "5.00") */
    commission?: string
    /** Stats principales */
    stats?: SellerStats
    /** Actividad reciente */
    recentActivity?: ActivityItem[]
    /** A quién reporta el vendedor */
    reportsTo?: SellerReportsTo | null
    /** Comisiones especiales configuradas por sala */
    roomCommissions?: SellerRoomCommission[]
    /** Serie diaria de cartones vendidos (últimos 14 días) */
    salesTrend?: SalesTrendPoint[]
    /** Período activo (e.g. "Marzo 2026") */
    period?: string
    /** Posición del vendedor en el ranking mensual — null si no vendió nada este mes */
    ranking?: SellerRankingStanding | null
    /** Pendientes: pagos en revisión, rechazados (a devolver) y premios por entregar */
    pendingActions?: SellerPendingAction[]
    /** Próximas salas de hoy con la venta abierta */
    upcomingRooms?: SellerUpcomingRoom[]
}
