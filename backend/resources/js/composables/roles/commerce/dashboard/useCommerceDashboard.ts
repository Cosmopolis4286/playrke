import { computed } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type {
    DashboardCommercePageProps, CommerceStats, EntityCount, SalesTrendPoint,
    SellerSalesRow, CommerceWallet, CommerceConfiguration,
} from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const DEFAULT_STATS: StatCardData[] = [
    { label: 'Ventas del mes',           value: '—', icon: 'fas fa-shopping-cart', variant: 'primary' },
    { label: 'Cartones vendidos hoy',    value: '—', icon: 'fas fa-ticket-alt',    variant: 'success' },
    { label: 'Salas activas',            value: '—', icon: 'fas fa-dice',          variant: 'info'    },
    { label: 'Movimientos pendientes',   value: '—', icon: 'fas fa-clock',         variant: 'warning' },
    { label: 'Mi comisión',              value: '—', icon: 'fas fa-percentage',    variant: 'secondary' },
]

export function useCommerceDashboard(props: DashboardCommercePageProps) {
    const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

    const statCards = computed<StatCardData[]>(() => {
        if (!props.stats) return DEFAULT_STATS
        const s = props.stats as CommerceStats
        return [s.totalSales, s.totalRevenue, s.activeGames, s.pendingPayouts, s.commission]
    })

    const entityCounts = computed<EntityCount[]>(() => props.entityCounts ?? [])

    const salesTrend = computed<SalesTrendPoint[]>(() => props.salesTrend ?? [])

    const salesBySeller = computed<SellerSalesRow[]>(() => props.salesBySeller ?? [])

    const wallets = computed<CommerceWallet[]>(() => props.wallets ?? [])

    const configuration = computed<CommerceConfiguration>(() => props.configuration ?? { currencies: [], games: [] })

    const pendingWalletCount = computed<number>(() => props.pendingWalletCount ?? 0)

    return {
        statCards, sortedActivity, entityCounts, salesTrend,
        salesBySeller, wallets, configuration, pendingWalletCount,
        prependActivity,
    }
}
