<script setup lang="ts">
  /**
   * Página Dashboard → Jugador
   * Stats: saldo billetera, salas disponibles, cartones del mes.
   * Actividad reciente: últimos cartones comprados.
   * Accesos rápidos: Billetera, Bingo.
   */

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

  import { usePlayerDashboard } from '@/js/composables/roles/player/dashboard/usePlayerDashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardPlayerPageProps } from '@/js/types/roles/player/dashboard/dashboard-player'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardPlayerPageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const { statCards, sortedActivity } = usePlayerDashboard(props)

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Billetera',
      description: 'Ver saldo y realizar operaciones',
      icon:        'fas fa-wallet',
      href:        '/wallet',
      variant:     'primary',
    },
    {
      label:       'Bingo',
      description: 'Ver salas disponibles y jugar',
      icon:        'fas fa-hat-wizard',
      href:        '/bingo/rooms',
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
        :userName="props.userName ?? 'Jugador'"
        roleName="Jugador"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Ir al Bingo', icon: 'fas fa-hat-wizard', href: '/bingo/rooms', variant: 'primary' },
          { label: 'Mi Billetera', icon: 'fas fa-wallet', href: '/wallet', variant: 'outline-primary' },
        ]"
      />

      <!-- Stats principales -->
      <div class="row g-3 mt-0">
        <div
          v-for="(stat, i) in statCards"
          :key="i"
          class="col-12 col-sm-6 col-xl-4"
        >
          <DashboardStatCard v-bind="stat" />
        </div>
      </div>

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Mis opciones"
      >
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Actividad reciente -->
      <DashboardSection
        title="Mis cartones recientes"
        icon="fas fa-th"
        subtitle="Últimos cartones adquiridos"
      >
        <DashboardActivityFeed :items="sortedActivity" :maxItems="8" :columns="2" />
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
