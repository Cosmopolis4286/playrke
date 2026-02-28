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
  // Stats en array para v-for
  // ==========================================================================

  const statCards = computed<StatCardData[]>(() => {
    if (!props.stats) return []
    return [
      props.stats.totalSales,
      props.stats.totalRevenue,
      props.stats.activeGames,
      props.stats.pendingPayouts,
    ]
  })
</script>

<template>
  <div class="dashboard-commerce">

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
    <div v-if="statCards.length" class="row g-3 mt-1">
      <div
        v-for="(stat, i) in statCards"
        :key="i"
        class="col-12 col-sm-6 col-xl-3"
      >
        <DashboardStatCard v-bind="stat" />
      </div>
    </div>

    <!-- Sin stats: skeleton placeholder -->
    <div v-else class="row g-3 mt-1">
      <div v-for="n in 4" :key="n" class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="skeleton-block mb-2" style="height:12px;width:50%;border-radius:4px;"></div>
            <div class="skeleton-block" style="height:28px;width:70%;border-radius:4px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila inferior -->
    <div class="row g-3 mt-1">

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

      <!-- Slot libre para widgets adicionales -->
      <div class="col-12 col-lg-6">
        <DashboardSection
          title="Resumen"
          icon="fas fa-chart-pie"
          subtitle="Métricas del período"
        >
          <slot name="summary">
            <p class="text-muted small text-center py-4 mb-0">
              <i class="fas fa-chart-line d-block mb-2 fs-4"></i>
              Aquí se mostrará el resumen gráfico.
            </p>
          </slot>
        </DashboardSection>
      </div>

    </div>
  </div>
</template>

<style scoped>
.dashboard-commerce {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.skeleton-block {
  background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
