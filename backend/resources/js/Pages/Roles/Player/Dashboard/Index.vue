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
  import DashboardPrizesList    from '@/js/Components/Common/Dashboard/DashboardPrizesList.vue'
  import DashboardTriviaActivityList from '@/js/Components/Common/Dashboard/DashboardTriviaActivityList.vue'
  import DashboardKycStatusPanel from '@/js/Components/Common/Dashboard/DashboardKycStatusPanel.vue'
  import DashboardGamificationPanel from '@/js/Components/Common/Dashboard/DashboardGamificationPanel.vue'
  import DailyMissionsTab       from '@/js/Components/Profile/DailyMissionsTab.vue'
  import PlayerUpcomingGames    from '@/js/Components/Roles/Player/Dashboard/PlayerUpcomingGames.vue'
  import type { QuickLink }     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { usePlayerDashboard }          from '@/js/composables/roles/player/dashboard/usePlayerDashboard'
  import { usePlayerDashboardRealtime }  from '@/js/composables/roles/player/dashboard/usePlayerDashboardRealtime'
  import { useDashboardMountLoading }    from '@/js/composables/common/dashboard/useDashboardMountLoading'
  import { useDashboardFormatters }      from '@/js/composables/common/dashboard/useDashboardFormatters'
  import { useGamification }             from '@/js/composables/roles/player/dashboard/useGamification'
  import { useDailyMissions }            from '@/js/composables/roles/player/dashboard/useDailyMissions'
  import { onMounted }                   from 'vue'

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

  const {
    statCards, sortedActivity, prizesWon, triviaActivity, kycStatus,
    showCrossGameBridge, dismissCrossGameBridge,
  } = usePlayerDashboard(props)
  const { liveBalance, isLive } = usePlayerDashboardRealtime(props.stats?.walletBalance.value ?? '—')
  const { loading: sectionsLoading } = useDashboardMountLoading()
  const { formatUsd } = useDashboardFormatters()

  const { levelInfo, achievementStats, loading: gamificationLoading, error: gamificationError, loadGamification } = useGamification()
  const { missions: dailyMissions, loading: dailyMissionsLoading, loadDailyMissions } = useDailyMissions()
  onMounted(() => {
    loadGamification()
    loadDailyMissions()
  })

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Oportunidades',
      description: 'Salas de Bingo y Trivia activas ahora',
      icon:        'fas fa-fire',
      href:        '/opportunities',
      variant:     'warning',
    },
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

      <!-- Mis próximas partidas -->
      <DashboardSection
        title="Mis próximas partidas"
        icon="fas fa-play-circle"
        subtitle="Salas donde ya tienes cartones"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <PlayerUpcomingGames :games="props.upcomingGames ?? []" />
      </DashboardSection>

      <!-- Puente cross-game: invitación a Trivia tras jugar Bingo -->
      <div v-if="showCrossGameBridge" class="cgb-banner">
        <div class="cgb-banner__icon">
          <i class="fas fa-brain"></i>
        </div>
        <div class="cgb-banner__body">
          <p class="cgb-banner__title">¿Ya probaste Trivia?</p>
          <p class="cgb-banner__text">Gana <strong>150 XP extra</strong> en tu primera partida — es gratis probar.</p>
        </div>
        <div class="cgb-banner__actions">
          <a href="/opportunities" class="cgb-banner__cta">
            Jugar Trivia <i class="fas fa-arrow-right"></i>
          </a>
          <button type="button" class="cgb-banner__dismiss" title="Cerrar" @click="dismissCrossGameBridge">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Mis opciones"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Actividad reciente -->
      <DashboardSection
        title="Mis cartones recientes"
        icon="fas fa-th"
        subtitle="Últimos cartones adquiridos"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardActivityFeed :items="sortedActivity" :maxItems="8" :columns="2" />
      </DashboardSection>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Premios ganados -->
        <div class="col-12 col-lg-6">
          <DashboardSection
            title="Premios ganados"
            icon="fas fa-trophy"
            subtitle="Detalle de tus victorias"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardPrizesList :prizes="prizesWon" :format-usd="formatUsd" />
          </DashboardSection>
        </div>

        <!-- Estado de verificación -->
        <div class="col-12 col-lg-6">
          <DashboardSection
            title="Verificación de identidad"
            icon="fas fa-id-card"
            subtitle="Estado de tu cuenta"
            :loading="sectionsLoading"
            skeleton-variant="stat"
          >
            <DashboardKycStatusPanel :status="kycStatus" />
          </DashboardSection>
        </div>

      </div>

      <!-- Nivel y logros -->
      <DashboardSection
        title="Mi progreso"
        icon="fas fa-star"
        subtitle="Nivel y logros desbloqueados"
      >
        <DashboardGamificationPanel
          :level-info="levelInfo"
          :achievement-stats="achievementStats"
          :loading="gamificationLoading"
          :error="gamificationError"
        />
      </DashboardSection>

      <!-- Misiones diarias -->
      <DashboardSection
        title="Misiones de hoy"
        icon="fas fa-bullseye"
        subtitle="Complétalas antes de medianoche para ganar XP"
      >
        <DailyMissionsTab :missions="dailyMissions" :loading="dailyMissionsLoading" />
      </DashboardSection>

      <!-- Actividad Trivia -->
      <DashboardSection
        title="Mi actividad en Trivia"
        icon="fas fa-brain"
        subtitle="Últimas participaciones"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardTriviaActivityList :rows="triviaActivity" />
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

.cgb-banner {
  display: flex; align-items: center; gap: 0.9rem;
  padding: 0.85rem 1.1rem; border-radius: 14px;
  background: linear-gradient(135deg, #ede9fe, #f5f3ff);
  border: 1px solid #ddd6fe;
  margin-top: 0.75rem;
}
.cgb-banner__icon {
  flex-shrink: 0; width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #7c3aed, #a855f7);
  color: #fff; font-size: 1.1rem;
}
.cgb-banner__body { flex: 1; min-width: 0; }
.cgb-banner__title { margin: 0; font-size: 0.9rem; font-weight: 700; color: #1a202c; }
.cgb-banner__text { margin: 2px 0 0; font-size: 0.78rem; color: #5b21b6; }
.cgb-banner__actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.cgb-banner__cta {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 700;
  background: #7c3aed; color: #fff; text-decoration: none; white-space: nowrap;
  transition: background 0.15s;
}
.cgb-banner__cta:hover { background: #6d28d9; color: #fff; }
.cgb-banner__dismiss {
  border: none; background: transparent; color: #8a94a6; cursor: pointer;
  width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  transition: background 0.15s;
}
.cgb-banner__dismiss:hover { background: rgba(0,0,0,0.06); }

@media (max-width: 576px) {
  .cgb-banner { flex-wrap: wrap; }
  .cgb-banner__actions { width: 100%; justify-content: space-between; margin-top: 4px; }
}

</style>
