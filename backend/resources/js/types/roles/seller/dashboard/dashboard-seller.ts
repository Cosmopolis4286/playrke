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
}
