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
    /** Período activo (e.g. "Marzo 2026") */
    period?: string
}
