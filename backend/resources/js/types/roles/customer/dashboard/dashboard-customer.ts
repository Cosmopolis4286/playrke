import type { StatCardData, ActivityItem, UpcomingRoom } from '@/js/types/common/dashboard/dashboard'

export interface CustomerStats {
    activeRooms:    StatCardData
    cardsToday:     StatCardData
    salesThisMonth: StatCardData
    commission:     StatCardData
}

export interface CustomerWallet {
    currency_code: string
    symbol:        string
    balance:       number
    bonus_balance: number
}

export interface DashboardCustomerPageProps {
    userName?:       string
    stats?:          CustomerStats
    /** Próximas salas propias (hoy y mañana) */
    upcomingRooms?:  UpcomingRoom[]
    /** Cartones vendidos por día en sus salas (últimos 14 días) */
    salesTrend?:     { date: string; count: number }[]
    recentActivity?: ActivityItem[]
    wallets?:        CustomerWallet[]
    period?:         string
}
