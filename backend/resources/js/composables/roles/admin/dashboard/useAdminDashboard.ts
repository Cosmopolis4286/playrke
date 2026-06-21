import { computed } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type { DashboardAdminPageProps, AdminStats, EntityCount } from '@/js/types/roles/admin/dashboard/dashboard-admin'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const DEFAULT_STATS: StatCardData[] = [
    { label: 'Usuarios totales',   value: '—', icon: 'fas fa-users',       variant: 'primary' },
    { label: 'Nuevos este mes',    value: '—', icon: 'fas fa-user-plus',   variant: 'success' },
    { label: 'Cartones hoy',       value: '—', icon: 'fas fa-ticket-alt',  variant: 'info'    },
    { label: 'Salas activas',      value: '—', icon: 'fas fa-store',       variant: 'warning' },
]

export function useAdminDashboard(props: DashboardAdminPageProps) {
    const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

    const statCards = computed<StatCardData[]>(() => {
        if (!props.stats) return DEFAULT_STATS
        const s = props.stats as AdminStats
        return [s.totalUsers, s.newUsersThisMonth, s.cardsToday, s.activeRooms]
    })

    const entityCounts = computed<EntityCount[]>(() => props.entityCounts ?? [])

    return { statCards, sortedActivity, entityCounts, prependActivity }
}
