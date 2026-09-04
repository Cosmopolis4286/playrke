import { computed } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type {
    DashboardSubsidiaryPageProps, SubsidiaryStats, SalesTrendPoint,
    SellerSalesRow, SubsidiaryWallet, ReportsTo,
} from '@/js/types/roles/subsidiary/dashboard/dashboard-subsidiary'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const DEFAULT_STATS: StatCardData[] = [
    { label: 'Ventas del mes',        value: '—', icon: 'fas fa-shopping-cart', variant: 'primary' },
    { label: 'Cartones vendidos hoy', value: '—', icon: 'fas fa-ticket-alt',    variant: 'success' },
    { label: 'Ganadores hoy',         value: '—', icon: 'fas fa-trophy',        variant: 'warning' },
    { label: 'Vendedores',            value: '—', icon: 'fas fa-user-tie',      variant: 'info'    },
    { label: 'Mi comisión',           value: '—', icon: 'fas fa-percentage',    variant: 'secondary' },
]

export function useSubsidiaryDashboard(props: DashboardSubsidiaryPageProps) {
    const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

    const statCards = computed<StatCardData[]>(() => {
        if (!props.stats) return DEFAULT_STATS
        const s = props.stats as SubsidiaryStats
        return [s.totalSales, s.cardsSoldToday, s.winnersToday, s.sellersCount, s.commission]
    })

    const reportsTo = computed<ReportsTo | null>(() => props.reportsTo ?? null)

    const salesTrend = computed<SalesTrendPoint[]>(() => props.salesTrend ?? [])

    const salesBySeller = computed<SellerSalesRow[]>(() => props.salesBySeller ?? [])

    const wallets = computed<SubsidiaryWallet[]>(() => props.wallets ?? [])

    const pendingWalletCount = computed<number>(() => props.pendingWalletCount ?? 0)

    return {
        statCards, sortedActivity, reportsTo, salesTrend,
        salesBySeller, wallets, pendingWalletCount,
        prependActivity,
    }
}
