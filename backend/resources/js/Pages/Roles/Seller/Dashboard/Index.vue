<script setup lang="ts">
  /**
   * Página Dashboard → Vendedor
   * Stats: cartones vendidos, comisión, ganadores.
   * Accesos rápidos: Generar Cartón, Cartones, Ganadores, Reporte de Ventas, Directorio.
   */

  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner    from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard         from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection          from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed     from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinks       from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import DashboardCommissionPanel  from '@/js/Components/Common/Dashboard/DashboardCommissionPanel.vue'
  import type { QuickLink }        from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardSellerPageProps } from '@/js/types/roles/seller/dashboard/dashboard-seller'
  import type { StatCardData }             from '@/js/types/common/dashboard/dashboard'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardSellerPageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const { sortedActivity } = useDashboard(undefined, props.recentActivity)

  // ==========================================================================
  // Stats — placeholder estático cuando el backend aún no los envía
  // ==========================================================================

  const DEFAULT_STATS: StatCardData[] = [
    {
      label: 'Cartones vendidos hoy',
      value: '—',
      icon: 'fas fa-ticket-alt',
      variant: 'primary',
    },
    {
      label: 'Cartones este mes',
      value: '—',
      icon: 'fas fa-layer-group',
      variant: 'info',
    },
    {
      label: 'Comisión del mes',
      value: '—',
      icon: 'fas fa-hand-holding-usd',
      variant: 'success',
      description: props.commission ? `Tasa: ${props.commission}%` : undefined,
    },
    {
      label: 'Ganadores hoy',
      value: '—',
      icon: 'fas fa-trophy',
      variant: 'warning',
    },
  ]

  const statCards = computed<StatCardData[]>(() => {
    if (!props.stats) return DEFAULT_STATS
    return [
      props.stats.cartonsSoldToday,
      props.stats.cartonsThisMonth,
      props.stats.commissionThisMonth,
      props.stats.winnersToday,
    ]
  })

  // ==========================================================================
  // Accesos rápidos — rutas del sidebar del vendedor
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label: 'Generar Cartón',
      description: 'Vender un cartón',
      icon: 'fas fa-plus-circle',
      href: '/bingo/generate-card',
      variant: 'primary',
    },
    {
      label: 'Cartones',
      description: 'Cartones vendidos',
      icon: 'fas fa-layer-group',
      href: '/bingo/cards-sold',
      variant: 'info',
    },
    {
      label: 'Ganadores',
      description: 'Registros de hoy',
      icon: 'fas fa-trophy',
      href: '/bingo/winners/private/today',
      variant: 'warning',
    },
    {
      label: 'Reporte de Ventas',
      description: 'Resumen por sala',
      icon: 'fas fa-chart-bar',
      href: '/bingo/sales-report',
      variant: 'success',
    },
    {
      label: 'Directorio',
      description: 'Salas disponibles',
      icon: 'fas fa-university',
      href: '/directory',
      variant: 'secondary',
    },
  ]
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Bienvenida -->
      <DashboardWelcomeBanner
        :userName="userName ?? 'Vendedor'"
        roleName="Vendedor"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Generar cartón', icon: 'fas fa-plus', href: '/bingo/generate-card', variant: 'primary' },
        ]"
      />

      <!-- Stats -->
      <div class="row g-3 mt-0">
        <div
          v-for="(stat, i) in statCards"
          :key="i"
          class="col-12 col-sm-6 col-xl-3"
        >
          <DashboardStatCard v-bind="stat" />
        </div>
      </div>

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Operaciones frecuentes"
      >
        <DashboardQuickLinks :links="quickLinks" :columns="4" />
      </DashboardSection>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimas operaciones"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="8" />
          </DashboardSection>
        </div>

        <!-- Panel de comisión -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Mi comisión"
            icon="fas fa-percentage"
            subtitle="Información de tu tasa"
          >
            <DashboardCommissionPanel :commission="commission" />
          </DashboardSection>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.ds-page {
  width: 100%;
  min-height: 100%;
  overflow-x: hidden;
  padding: 1.25rem 1rem;
  box-sizing: border-box;
}

.ds-inner {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
</style>
