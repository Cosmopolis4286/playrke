import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

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
    variant: string
}

// --------------------------------------------------------------------------
// Inertia page props
// --------------------------------------------------------------------------

export interface DashboardAdminPageProps {
    /** Nombre del usuario autenticado */
    userName?:       string
    /** Stats principales del sistema */
    stats?:          AdminStats
    /** Actividad reciente (últimos registros) */
    recentActivity?: ActivityItem[]
    /** Conteos de entidades por rol */
    entityCounts?:   EntityCount[]
    /** Período activo (e.g. "Marzo 2026") */
    period?:         string
}
