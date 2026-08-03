import type { StatCardData, ActivityItem, StatCardVariant } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Stats específicos del SuperAdmin
// --------------------------------------------------------------------------

export interface SuperAdminStats {
    totalUsers:         StatCardData
    totalAdmins:        StatCardData
    activeSystems:      StatCardData
    totalPermissions:   StatCardData
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
// Salud del sistema
// --------------------------------------------------------------------------

export interface SystemHealth {
    status:           'healthy' | 'warning' | 'critical'
    label:             string
    issues:            number
    failedJobsCount:   number
    broadcastLive:     boolean
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

export interface DashboardSuperAdminPageProps {
    /** Nombre del usuario autenticado */
    userName?:            string
    /** Stats principales del sistema */
    stats?:               SuperAdminStats
    /** Actividad reciente (últimos registros) */
    recentActivity?:      ActivityItem[]
    /** Actividad de administradores */
    adminActivity?:       ActivityItem[]
    /** Conteos de entidades por rol */
    entityCounts?:        EntityCount[]
    /** Salud del sistema */
    systemHealth?:        SystemHealth
    /** Serie diaria de cartones vendidos (últimos 14 días) */
    salesTrend?:          SalesTrendPoint[]
    /** Movimientos de billetera pendientes de aprobación */
    pendingWalletCount?:  number
    /** Período activo (e.g. "Marzo 2026") */
    period?:              string
}
