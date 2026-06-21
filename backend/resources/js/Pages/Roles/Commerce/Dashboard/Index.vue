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

  import { useDashboard }            from '@/js/composables/common/dashboard/useDashboard'
  import { useCreatorSalesRealtime } from '@/js/composables/common/dashboard/useCreatorSalesRealtime'

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

  const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity)
  const { isLive, cardsSoldToday, amountSoldToday } = useCreatorSalesRealtime(prependActivity)

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
        roleKey="commerce"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Ver juegos', icon: 'fas fa-gamepad', href: '/games', variant: 'outline-primary' },
          { label: 'Ver ventas', icon: 'fas fa-chart-bar', href: '/sales', variant: 'primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat">
            <span class="wb-stat__label">
              Ingresos
              <span v-if="isLive" class="wb-live-dot" title="Escuchando en vivo"></span>
            </span>
            <span class="wb-stat__value wb-stat__value--accent">{{ statCards[1]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat" v-if="cardsSoldToday > 0">
            <span class="wb-stat__label">Ventas (sesión)</span>
            <span class="wb-stat__value wb-stat__value--accent">{{ cardsSoldToday }} · ${{ amountSoldToday.toFixed(2) }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Juegos activos</span>
            <span class="wb-stat__value">{{ statCards[2]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Pagos pendientes</span>
            <span class="wb-stat__value">{{ statCards[3]?.value ?? '—' }}</span>
          </div>
        </template>
      </DashboardWelcomeBanner>

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
.wb-stat { display: flex; flex-direction: column; gap: 1px; }
.wb-stat__label { font-size: 0.68rem; color: #8a94a6; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
.wb-stat__value { font-size: 0.8rem; font-weight: 700; color: #1a202c; transition: color 0.2s; }
.wb-stat__value--accent { color: #2563eb; }

.wb-live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #22c55e; box-shadow: 0 0 5px #22c55e;
  animation: wbLivePulse 1.8s ease-in-out infinite;
  display: inline-block;
}
@keyframes wbLivePulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}

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
