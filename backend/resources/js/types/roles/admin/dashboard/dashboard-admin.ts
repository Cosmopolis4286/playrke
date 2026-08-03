import type { StatCardData, ActivityItem, StatCardVariant } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Stats específicos del Admin
// --------------------------------------------------------------------------

export interface AdminStats {
    totalUsers:         StatCardData
    newUsersThisMonth:  StatCardData
    cardsToday:         StatCardData
    activeRooms:        StatCardData
}

// --------------------------------------------------------------------------
// Conteo de entidades por rol
// --------------------------------------------------------------------------

export interface EntityCount {
    label:   string
    value:   number
    icon:    string
    variant: StatCardVariant
}

// --------------------------------------------------------------------------
// Tendencia de ventas
// --------------------------------------------------------------------------

export interface SalesTrendPoint {
    date:  string
    count: number
}

// --------------------------------------------------------------------------
// Inertia page props
// --------------------------------------------------------------------------

export interface DashboardAdminPageProps {
    /** Nombre del usuario autenticado */
    userName?:            string
    /** Stats principales del sistema */
    stats?:               AdminStats
    /** Actividad reciente (últimos registros) */
    recentActivity?:      ActivityItem[]
    /** Conteos de entidades por rol */
    entityCounts?:        EntityCount[]
    /** Serie diaria de cartones vendidos (últimos 14 días) */
    salesTrend?:          SalesTrendPoint[]
    /** Movimientos de billetera pendientes de aprobación */
    pendingWalletCount?:  number
    /** Período activo (e.g. "Marzo 2026") */
    period?:              string
}
