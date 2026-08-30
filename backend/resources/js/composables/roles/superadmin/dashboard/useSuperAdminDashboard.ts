import { computed } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type { DashboardSuperAdminPageProps, SuperAdminStats, EntityCount, SystemHealth, SalesTrendPoint } from '@/js/types/roles/superadmin/dashboard/dashboard-superadmin'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const DEFAULT_STATS: StatCardData[] = [
    { label: 'Usuarios totales',        value: '—', icon: 'fas fa-users',        variant: 'primary' },
    { label: 'Administradores',         value: '—', icon: 'fas fa-user-shield',  variant: 'danger' },
    { label: 'Salas de Bingo activas',  value: '—', icon: 'fas fa-dice',         variant: 'info'    },
    { label: 'Permisos totales',        value: '—', icon: 'fas fa-key',          variant: 'warning' },
    { label: 'KYC pendientes',          value: '—', icon: 'fas fa-id-card',      variant: 'secondary' },
]

const DEFAULT_HEALTH: SystemHealth = {
    status: 'healthy',
    label: 'Saludable',
    issues: 0,
    issueDetails: [],
    failedJobsCount: 0,
    pendingJobsCount: 0,
    inactiveAdminsCount: 0,
    broadcastLive: false,
    failedCommandsCount: 0,
    activeSessionsCount: 0,
}

export function useSuperAdminDashboard(props: DashboardSuperAdminPageProps) {
    const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

    // Orden fijo de presentación cuando la clave está presente — el backend
    // filtra por permiso/visibilidad y puede omitir cualquier subconjunto,
    // así que se deriva de las claves realmente recibidas en vez de asumir
    // que las 5 siempre llegan (ver SuperAdminStats).
    const STAT_ORDER: (keyof SuperAdminStats)[] = ['totalUsers', 'totalAdmins', 'activeRooms', 'totalPermissions', 'pendingKyc']

    const statCards = computed<StatCardData[]>(() => {
        if (!props.stats) return DEFAULT_STATS
        const s = props.stats as SuperAdminStats
        return STAT_ORDER.map((key) => s[key]).filter((stat): stat is StatCardData => stat !== undefined)
    })

    const entityCounts = computed<EntityCount[]>(() => props.entityCounts ?? [])

    const systemHealth = computed<SystemHealth>(() => props.systemHealth ?? DEFAULT_HEALTH)

    const adminActivity = computed<ActivityItem[]>(() => props.adminActivity ?? [])

    const salesTrend = computed<SalesTrendPoint[]>(() => props.salesTrend ?? [])

    const pendingWalletCount = computed<number>(() => props.pendingWalletCount ?? 0)

    return { statCards, sortedActivity, entityCounts, systemHealth, adminActivity, salesTrend, pendingWalletCount, prependActivity }
}
