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

  import { usePlayerDashboard }          from '@/js/composables/roles/player/dashboard/usePlayerDashboard'
  import { usePlayerDashboardRealtime }  from '@/js/composables/roles/player/dashboard/usePlayerDashboardRealtime'

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
  const { liveBalance, isLive } = usePlayerDashboardRealtime(props.stats?.walletBalance.value ?? '—')

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
    {
      label:       'Mis Partidas',
      description: 'Historial de partidas jugadas',
      icon:        'fas fa-history',
      href:        '/bingo/my-games',
      variant:     'warning',
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
        roleKey="player"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Ir al Bingo', icon: 'fas fa-hat-wizard', href: '/bingo/rooms', variant: 'primary' },
          { label: 'Mi Billetera', icon: 'fas fa-wallet', href: '/wallet', variant: 'outline-primary' },
        ]"
      >
        <template #highlights v-if="props.stats">
          <div class="wb-stat">
            <span class="wb-stat__label">
              Saldo
              <span v-if="isLive" class="wb-live-dot" title="Actualizado en vivo"></span>
            </span>
            <span class="wb-stat__value wb-stat__value--accent">{{ liveBalance }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Salas activas</span>
            <span class="wb-stat__value">{{ props.stats.activeRooms.value }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Cartones (mes)</span>
            <span class="wb-stat__value">{{ props.stats.cardsThisMonth.value }}</span>
          </div>
        </template>
      </DashboardWelcomeBanner>

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
.wb-stat { display: flex; flex-direction: column; gap: 1px; }
.wb-stat__label { font-size: 0.68rem; color: var(--ds-text-2); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
.wb-stat__value { font-size: 0.8rem; font-weight: 700; color: var(--ds-text); transition: color 0.2s; }
.wb-stat__value--accent { color: #7c3aed; }

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

</style>
