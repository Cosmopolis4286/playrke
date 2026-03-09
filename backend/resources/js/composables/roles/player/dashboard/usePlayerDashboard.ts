import { computed } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type { DashboardPlayerPageProps, PlayerStats } from '@/js/types/roles/player/dashboard/dashboard-player'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const DEFAULT_STATS: StatCardData[] = [
  { label: 'Saldo disponible',  value: '—', icon: 'fas fa-wallet',      variant: 'primary' },
  { label: 'Salas disponibles', value: '—', icon: 'fas fa-hat-wizard',  variant: 'success' },
  { label: 'Cartones este mes', value: '—', icon: 'fas fa-ticket-alt',  variant: 'info'    },
]

export function usePlayerDashboard(props: DashboardPlayerPageProps) {
  const { sortedActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

  const statCards = computed<StatCardData[]>(() => {
    if (!props.stats) return DEFAULT_STATS
    const s = props.stats as PlayerStats
    return [s.walletBalance, s.activeRooms, s.cardsThisMonth]
  })

  return { statCards, sortedActivity }
}
