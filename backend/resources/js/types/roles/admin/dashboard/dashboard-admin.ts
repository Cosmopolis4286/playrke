import type { StatCardData, ActivityItem, EntityCount, OperationalAlert } from '@/js/types/common/dashboard/dashboard'

export type { EntityCount }

// --------------------------------------------------------------------------
// Stats específicos del Admin
// --------------------------------------------------------------------------

/**
 * Claves parciales/opcionales: el backend filtra cada stat por permiso y
 * por visibilidad configurable (ver DashboardWidgetVisibilityService), así
 * que un usuario puede recibir solo un subconjunto de estas claves — nunca
 * asumir que las 4 siempre están presentes.
 */
export interface AdminStats {
    totalUsers?:         StatCardData
    newUsersThisMonth?:  StatCardData
    cardsToday?:         StatCardData
    activeRooms?:        StatCardData
}

// --------------------------------------------------------------------------
// Tendencia de ventas
// --------------------------------------------------------------------------

export interface SalesTrendPoint {
    date:  string
    count: number
}

// --------------------------------------------------------------------------
// Totales de comisiones RKE (resumen, sin desglose por sala)
// --------------------------------------------------------------------------

export interface RkeTotals {
    total_rke:   number
    total_sales: number
    room_count:  number
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
    /** Totales de comisiones RKE cobradas */
    rkeTotals?:           RkeTotals
    /** Movimientos de billetera pendientes de aprobación */
    pendingWalletCount?:  number
    /** Período activo (e.g. "Marzo 2026") */
    period?:              string
    /** Labels de accesos rápidos visibles para este usuario (permiso + visibilidad configurable) */
    allowedQuickLinks?:   string[]
    /** Alertas de dinero: pagos por revisar, reembolsos fallidos, premios trabados, movimientos */
    operationalAlerts?:   OperationalAlert[]
}
