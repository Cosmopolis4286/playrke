import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

// --------------------------------------------------------------------------
// Stats específicos del Jugador
// --------------------------------------------------------------------------

export interface PlayerStats {
  walletBalance:   StatCardData
  bonusBalance:    StatCardData
  activeRooms:     StatCardData
  cardsThisMonth:  StatCardData
  gamesPlayed:     StatCardData
}

// --------------------------------------------------------------------------
// Premios ganados, actividad Trivia, estado KYC
// --------------------------------------------------------------------------

export interface PrizeWonRow {
  id:           string
  room_name:    string
  prize_type:   string | null
  prize_label:  string
  amount_usd:   number
  status:       string
  status_label: string
  created_at:   string | null
}

export interface TriviaActivityRow {
  id:              string
  room_id:         string
  status:          string
  final_rank:      number | null
  prize_won:       number
  correct_answers: number | null
  wrong_answers:   number | null
  registered_at:   string | null
}

export interface KycStatus {
  status:           string
  rejection_reason: string | null
  reviewed_at:      string | null
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
  /** Premios ganados con detalle */
  prizesWon?: PrizeWonRow[]
  /** Participaciones recientes en Trivia */
  triviaActivity?: TriviaActivityRow[]
  /** Estado del documento KYC propio */
  kycStatus?: KycStatus | null
  /** Período activo (e.g. "Marzo 2026") */
  period?: string
}
