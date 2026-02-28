<script setup lang="ts">
  /**
   * Página Dashboard → Comercio
   */

  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardCommercePageProps } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
  import type { StatCardData } from '@/js/types/common/dashboard/dashboard'

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
    { label: 'Total ventas',      value: '—', icon: 'fas fa-shopping-cart',  variant: 'primary'  },
    { label: 'Ingresos',          value: '—', icon: 'fas fa-dollar-sign',     variant: 'success'  },
    { label: 'Juegos activos',    value: '—', icon: 'fas fa-gamepad',         variant: 'info'     },
    { label: 'Pagos pendientes',  value: '—', icon: 'fas fa-clock',           variant: 'warning'  },
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
</script>

<template>
  <div class="dc-page">
    <div class="dc-inner">

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

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-6">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimas acciones en tu cuenta"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="8" />
          </DashboardSection>
        </div>

        <!-- Widget resumen (extensible) -->
        <div class="col-12 col-lg-6">
          <DashboardSection
            title="Resumen"
            icon="fas fa-chart-pie"
            subtitle="Métricas del período"
          >
            <slot name="summary">
              <div class="dc-empty-widget">
                <i class="fas fa-chart-line"></i>
                <p class="mb-0">Aquí se mostrará el resumen gráfico.</p>
              </div>
            </slot>
          </DashboardSection>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* Contenedor raíz: ocupa el espacio disponible sin overflow horizontal */
.dc-page {
  width: 100%;
  min-height: 100%;
  overflow-x: hidden;
  padding: 1.25rem 1rem;
  box-sizing: border-box;
}

/* Columna interna con ancho máximo y sin desbordamiento */
.dc-inner {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Placeholder vacío para widgets futuros */
.dc-empty-widget {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: #adb5bd;
  font-size: 0.85rem;
  text-align: center;
}
.dc-empty-widget i {
  font-size: 2rem;
}
</style>
