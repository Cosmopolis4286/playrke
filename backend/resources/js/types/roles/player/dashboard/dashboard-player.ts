import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Stats específicos del Jugador
// --------------------------------------------------------------------------

export interface PlayerStats {
  walletBalance:   StatCardData
  activeRooms:     StatCardData
  cardsThisMonth:  StatCardData
}

// --------------------------------------------------------------------------
// Inertia page props para el Dashboard del Jugador
// --------------------------------------------------------------------------

export interface DashboardPlayerPageProps {
  /** Nombre del usuario autenticado */
  userName?: string
  /** Stats principales */
  stats?: PlayerStats
  /** Actividad reciente (cartones comprados) */
  recentActivity?: ActivityItem[]
  /** Período activo (e.g. "Marzo 2026") */
  period?: string
}
