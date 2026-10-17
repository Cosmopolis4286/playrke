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

export interface SellerRejectedPayment {
    id:          string
    buyer:       string
    phone:       string | null
    cards:       number
    amount:      string
    room:        string | null
    rejected_at: string | null
}

export interface SellerPaymentReview {
    pending_count:     number
    /** Monto de la moneda principal ya formateado ("Bs 120,00 +1 moneda(s)") */
    pending_amount:    string | null
    pending_cards:     number
    oldest_pending_at: string | null
    /** Rechazados de los últimos 14 días: hay que devolverle el dinero al comprador */
    rejected:          SellerRejectedPayment[]
}

export interface SellerPrizesToDeliver {
    count:  number
    amount: string | null
}

export interface SellerPurchaseCodes {
    active:         number
    remaining_uses: number
    expiring_soon:  number
}

export interface SellerClosingRoom {
    id:             string
    uuid_code:      string
    time:           string | null
    starts_at:      string | null
    is_manual:      boolean
    sales_close_at: string | null
    total_players:  number
    total_cards:    number
    card_price_usd: number
    prize_pool_usd: number
    is_own:         boolean
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
    /** Ventas con pago en revisión y rechazadas recientes */
    paymentReview?: SellerPaymentReview
    /** Salas cuya venta cierra en las próximas 24 h (y las de inicio manual de hoy) */
    closingRooms?: SellerClosingRoom[]
    /** Códigos de compra otorgados a compradores recurrentes */
    purchaseCodes?: SellerPurchaseCodes
    /** Premios de sus compradores aún sin entregar */
    prizesToDeliver?: SellerPrizesToDeliver
}
