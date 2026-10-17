import type { StatCardData, ActivityItem, EntityCount } from '@/js/types/common/dashboard/dashboard'

export type { EntityCount }

// --------------------------------------------------------------------------
// Stats específicos del SuperAdmin
// --------------------------------------------------------------------------

/**
 * Claves parciales/opcionales: el backend filtra cada stat por permiso y
 * por visibilidad configurable (ver DashboardWidgetVisibilityService), así
 * que un usuario puede recibir solo un subconjunto de estas claves — nunca
 * asumir que las 5 siempre están presentes.
 */
export interface SuperAdminStats {
    totalUsers?:         StatCardData
    totalAdmins?:        StatCardData
    activeRooms?:        StatCardData
    totalPermissions?:   StatCardData
    pendingKyc?:         StatCardData
}

// --------------------------------------------------------------------------
// Salud del sistema
// --------------------------------------------------------------------------

export interface SystemHealthIssue {
    code:  string
    label: string
    count: number
    /** Solo las alertas de dinero (OperationalAlertsService) traen su severidad y destino */
    severity?:     'critical' | 'warning'
    href?:         string | null
    action_label?: string | null
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
    /** Labels de accesos rápidos visibles para este usuario (permiso + visibilidad configurable) */
    allowedQuickLinks?:   string[]
}
