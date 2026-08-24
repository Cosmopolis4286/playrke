import type { StatCardData, ActivityItem, EntityCount } from '@/js/types/common/dashboard/dashboard'

export type { EntityCount }

// --------------------------------------------------------------------------
// Stats específicos del SuperAdmin
// --------------------------------------------------------------------------

export interface SuperAdminStats {
    totalUsers:         StatCardData
    totalAdmins:        StatCardData
    activeRooms:        StatCardData
    totalPermissions:   StatCardData
    pendingKyc:         StatCardData
}

// --------------------------------------------------------------------------
// Salud del sistema
// --------------------------------------------------------------------------

export interface SystemHealthIssue {
    code:  string
    label: string
    count: number
}

export interface SystemHealth {
    status:               'healthy' | 'warning' | 'critical'
    label:                 string
    issues:                number
    issueDetails:           SystemHealthIssue[]
    failedJobsCount:       number
    pendingJobsCount:      number
    inactiveAdminsCount:   number
    broadcastLive:         boolean
    /** Comandos programados cuya última ejecución fue un error (command_logs). */
    failedCommandsCount:   number
    /** Sesiones con actividad en los últimos 15 minutos (tabla sessions). */
    activeSessionsCount:   number
    /** ISO 8601 — momento en que se calculó este snapshot (server-side). */
    checkedAt?:             string
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
