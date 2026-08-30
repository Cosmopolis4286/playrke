import { computed } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type { DashboardAdminPageProps, AdminStats, EntityCount, SalesTrendPoint, RkeTotals } from '@/js/types/roles/admin/dashboard/dashboard-admin'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const DEFAULT_STATS: StatCardData[] = [
    { label: 'Usuarios totales',   value: '—', icon: 'fas fa-users',       variant: 'primary' },
    { label: 'Nuevos este mes',    value: '—', icon: 'fas fa-user-plus',   variant: 'success' },
    { label: 'Cartones hoy',       value: '—', icon: 'fas fa-ticket-alt',  variant: 'info'    },
    { label: 'Salas activas',      value: '—', icon: 'fas fa-store',       variant: 'warning' },
]

export function useAdminDashboard(props: DashboardAdminPageProps) {
    const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

    // Orden fijo de presentación cuando la clave está presente — el backend
    // filtra por permiso/visibilidad y puede omitir cualquier subconjunto,
    // así que se deriva de las claves realmente recibidas en vez de asumir
    // que las 4 siempre llegan (ver AdminStats).
    const STAT_ORDER: (keyof AdminStats)[] = ['totalUsers', 'newUsersThisMonth', 'cardsToday', 'activeRooms']

    const statCards = computed<StatCardData[]>(() => {
        if (!props.stats) return DEFAULT_STATS
        const s = props.stats as AdminStats
        return STAT_ORDER.map((key) => s[key]).filter((stat): stat is StatCardData => stat !== undefined)
    })

    const entityCounts = computed<EntityCount[]>(() => props.entityCounts ?? [])

    const salesTrend = computed<SalesTrendPoint[]>(() => props.salesTrend ?? [])

    const rkeTotals = computed<RkeTotals | null>(() => props.rkeTotals ?? null)

    const pendingWalletCount = computed<number>(() => props.pendingWalletCount ?? 0)

    return { statCards, sortedActivity, entityCounts, salesTrend, rkeTotals, pendingWalletCount, prependActivity }
}
