<script setup lang="ts">
  /**
   * Página Dashboard → Comercio
   */

  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard      from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection       from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed  from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinks    from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import type { QuickLink }     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardCommercePageProps } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
  import type { StatCardData }               from '@/js/types/common/dashboard/dashboard'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardCommercePageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const { sortedActivity } = useDashboard(undefined, props.recentActivity)

  // ==========================================================================
  // Stats: si no vienen del backend, muestra placeholders vacíos
  // ==========================================================================

  const DEFAULT_STATS: StatCardData[] = [
    { label: 'Total ventas',     value: '—', icon: 'fas fa-shopping-cart', variant: 'primary' },
    { label: 'Ingresos',         value: '—', icon: 'fas fa-dollar-sign',   variant: 'success' },
    { label: 'Juegos activos',   value: '—', icon: 'fas fa-gamepad',       variant: 'info'    },
    { label: 'Pagos pendientes', value: '—', icon: 'fas fa-clock',         variant: 'warning' },
  ]

  const statCards = computed<StatCardData[]>(() => {
    if (!props.stats) return DEFAULT_STATS
    return [
      props.stats.totalSales,
      props.stats.totalRevenue,
      props.stats.activeGames,
      props.stats.pendingPayouts,
    ]
  })

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Juegos',
      description: 'Ver juegos disponibles',
      icon:        'fas fa-gamepad',
      href:        '/games',
      variant:     'primary',
    },
    {
      label:       'Ventas',
      description: 'Ver historial de ventas',
      icon:        'fas fa-chart-bar',
      href:        '/sales',
      variant:     'success',
    },
    {
      label:       'Directorio',
      description: 'Métodos de pago disponibles',
      icon:        'fas fa-university',
      href:        '/directory',
      variant:     'info',
    },
  ]
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Bienvenida -->
      <DashboardWelcomeBanner
        :userName="userName ?? 'Usuario'"
        roleName="Comercio"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Ver juegos', icon: 'fas fa-gamepad', href: '/games', variant: 'outline-primary' },
          { label: 'Ver ventas', icon: 'fas fa-chart-bar', href: '/sales', variant: 'primary' },
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
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Actividad reciente -->
      <DashboardSection
        title="Actividad reciente"
        icon="fas fa-history"
        subtitle="Últimas acciones en tu cuenta"
      >
        <DashboardActivityFeed :items="sortedActivity" :maxItems="8" />
      </DashboardSection>

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
