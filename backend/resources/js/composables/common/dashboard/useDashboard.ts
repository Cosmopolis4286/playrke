import { ref, computed } from 'vue'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

/**
 * Composable genérico para dashboards.
 * Provee estado de carga, stats y actividad reciente.
 * Cada rol puede instanciarlo con sus propias props de Inertia.
 */
export function useDashboard(initialStats?: Partial<Record<string, StatCardData>>, initialActivity?: ActivityItem[]) {
    // ------------------------------------------------------------------
    // Estado
    // ------------------------------------------------------------------

    const loading = ref(false)
    const stats = ref<Record<string, StatCardData>>(initialStats ?? {})
    const recentActivity = ref<ActivityItem[]>(initialActivity ?? [])

    // ------------------------------------------------------------------
    // Computed
    // ------------------------------------------------------------------

    /** Convierte el mapa de stats en array ordenable para v-for */
    const statsArray = computed<StatCardData[]>(() => Object.values(stats.value))

    /** Actividad ordenada del más reciente al más antiguo */
    const sortedActivity = computed<ActivityItem[]>(() =>
        [...recentActivity.value].sort(
            (a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
        )
    )

    // ------------------------------------------------------------------
    // Acciones
    // ------------------------------------------------------------------

    function setStats(newStats: Record<string, StatCardData>): void {
        stats.value = newStats
    }

    function setActivity(items: ActivityItem[]): void {
        recentActivity.value = items
    }

    function prependActivity(item: ActivityItem): void {
        if (recentActivity.value.some((existing) => existing.id === item.id)) return
        recentActivity.value.unshift(item)
    }

    return {
        loading,
        stats,
        recentActivity,
        statsArray,
        sortedActivity,
        setStats,
        setActivity,
        prependActivity,
    }
}
