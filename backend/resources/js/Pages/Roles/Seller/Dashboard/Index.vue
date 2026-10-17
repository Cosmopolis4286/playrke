<script setup lang="ts">
  /**
   * Página Dashboard → Vendedor
   * Stats: cartones vendidos, comisión, ganadores.
   * Pendientes (pagos en revisión, devoluciones, premios, códigos) y salas por cerrar venta.
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
  import DashboardTrendChart       from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
  import DashboardRoomCommissionList from '@/js/Components/Common/Dashboard/DashboardRoomCommissionList.vue'
  import SellerPendingActions      from '@/js/Components/Roles/Seller/Dashboard/SellerPendingActions.vue'
  import SellerClosingRooms        from '@/js/Components/Roles/Seller/Dashboard/SellerClosingRooms.vue'
  import type { QuickLink }        from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useDashboard }             from '@/js/composables/common/dashboard/useDashboard'
  import { useCreatorSalesRealtime }  from '@/js/composables/common/dashboard/useCreatorSalesRealtime'
  import { useDashboardMountLoading } from '@/js/composables/common/dashboard/useDashboardMountLoading'

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

  const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity)
  const { isLive, cardsSoldToday, amountSoldToday } = useCreatorSalesRealtime(prependActivity)
  const { loading: sectionsLoading } = useDashboardMountLoading()

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
      href: '/bingo-card/generate',
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
      href: '/bingo/winners',
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
      description: 'Datos de pago',
      icon: 'fas fa-university',
      href: '/directory',
      variant: 'secondary',
    },
    {
      label: 'Mi Perfil',
      description: 'Datos de contacto',
      icon: 'fas fa-id-card',
      href: '/seller/profile',
      variant: 'danger',
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
        roleKey="seller"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Generar cartón', icon: 'fas fa-plus', href: '/bingo-card/generate', variant: 'primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat">
            <span class="wb-stat__label">
              Vendidos hoy
              <span v-if="isLive" class="wb-live-dot" title="Escuchando en vivo"></span>
            </span>
            <span class="wb-stat__value">{{ statCards[0]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat" v-if="cardsSoldToday > 0">
            <span class="wb-stat__label">Vendidos (sesión)</span>
            <span class="wb-stat__value wb-stat__value--accent">{{ cardsSoldToday }} · US${{ amountSoldToday.toFixed(2) }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Comisión (mes)</span>
            <span class="wb-stat__value wb-stat__value--accent">{{ statCards[2]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Ganadores hoy</span>
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

      <!-- Pendientes + salas para vender hoy -->
      <div class="row g-3">
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Pendientes"
            icon="fas fa-list-check"
            subtitle="Lo que requiere tu atención"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <SellerPendingActions
              :payment-review="paymentReview"
              :prizes-to-deliver="prizesToDeliver"
              :purchase-codes="purchaseCodes"
            />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Salas por cerrar venta"
            icon="fas fa-door-open"
            subtitle="Próximas 24 h, ordenadas por cierre"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <SellerClosingRooms :rooms="closingRooms ?? []" />
          </DashboardSection>
        </div>
      </div>

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Operaciones frecuentes"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <!-- 6 links → columns=3 forma 2 filas completas (con columns=4
             quedaría una segunda fila de solo 2, con 2 huecos vacíos). -->
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Tendencia + Panel de comisión -->
      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Tendencia de ventas"
            icon="fas fa-chart-area"
            subtitle="Cartones vendidos por día · últimos 14 días"
            :loading="sectionsLoading"
            skeleton-variant="chart"
          >
            <DashboardTrendChart :data="salesTrend ?? []" label="Cartones vendidos" />
          </DashboardSection>
        </div>

        <!-- Panel de comisión -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Mi comisión"
            icon="fas fa-percentage"
            subtitle="Información de tu tasa"
            :loading="sectionsLoading"
            skeleton-variant="stat"
          >
            <DashboardCommissionPanel :commission="commission" :reports-to="reportsTo" />
          </DashboardSection>

          <!-- Ranking mensual -->
          <DashboardSection
            title="Ranking del mes"
            icon="fas fa-ranking-star"
            subtitle="Tu posición entre todos los vendedores"
            :loading="sectionsLoading"
            skeleton-variant="stat"
          >
            <div v-if="ranking" class="ranking-panel">
              <div class="ranking-panel__rank">
                <span class="ranking-panel__rank-num">#{{ ranking.rank }}</span>
                <span class="ranking-panel__rank-total">de {{ ranking.total_sellers }}</span>
              </div>
              <div class="ranking-panel__body">
                <!-- En USD: el ranking compara vendedores de distintas monedas. -->
                <p class="ranking-panel__commission">US$ {{ ranking.commission_amount.toFixed(2) }} <small>de comisión este mes</small></p>
                <span class="ranking-panel__goal" :class="{ 'ranking-panel__goal--reached': ranking.goal_reached }">
                  {{ ranking.goal_reached ? `✓ Meta de US$${ranking.goal} alcanzada` : `Meta: US$${ranking.goal}` }}
                </span>
              </div>
            </div>
            <p v-else class="ranking-panel__empty">Aún no registras ventas este mes — vende un cartón para entrar al ranking.</p>
          </DashboardSection>
        </div>
      </div>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimas operaciones"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="8" />
          </DashboardSection>
        </div>

        <!-- Comisiones especiales por sala -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Comisiones por sala"
            icon="fas fa-door-open"
            subtitle="Tasas especiales configuradas"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardRoomCommissionList :rows="roomCommissions ?? []" />
          </DashboardSection>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.wb-stat { display: flex; flex-direction: column; gap: 1px; }
.wb-stat__label { font-size: 0.68rem; color: #8a94a6; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
.wb-stat__value { font-size: 0.8rem; font-weight: 700; color: #1a202c; transition: color 0.2s; }
.wb-stat__value--accent { color: #db2777; }

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

.ranking-panel { display: flex; align-items: center; gap: 1rem; padding: 0.25rem 0; }
.ranking-panel__rank {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  width: 56px; height: 56px; border-radius: 12px; flex-shrink: 0;
  background: linear-gradient(135deg, #f59e0b, #f97316);
}
.ranking-panel__rank-num { font-size: 1.1rem; font-weight: 800; color: #fff; line-height: 1; }
.ranking-panel__rank-total { font-size: 0.6rem; color: rgba(255,255,255,.85); }

.ranking-panel__body { display: flex; flex-direction: column; gap: 0.3rem; }
.ranking-panel__commission { font-size: 1.05rem; font-weight: 700; color: #1a202c; margin: 0; }
.ranking-panel__commission small { font-size: 0.7rem; font-weight: 500; color: #8a94a6; }
.ranking-panel__goal {
  display: inline-block; align-self: flex-start;
  font-size: 0.72rem; font-weight: 600; padding: 2px 9px; border-radius: 20px;
  background: #f1f5f9; color: #64748b;
}
.ranking-panel__goal--reached { background: rgba(16,185,129,.15); color: #059669; }

.ranking-panel__empty { font-size: 0.82rem; color: #8a94a6; margin: 0; }
</style>
