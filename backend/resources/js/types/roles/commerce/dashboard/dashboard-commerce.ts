import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Inertia page props para el Dashboard de Comercio
// --------------------------------------------------------------------------

export interface CommerceStats {
    totalSales: StatCardData
    totalRevenue: StatCardData
    activeGames: StatCardData
    pendingPayouts: StatCardData
}

export interface DashboardCommercePageProps {
    /** Nombre del usuario autenticado */
    userName?: string
    /** Stats principales */
    stats?: CommerceStats
    /** Actividad reciente */
    recentActivity?: ActivityItem[]
    /** Período activo (e.g. "Febrero 2026") */
    period?: string
}
