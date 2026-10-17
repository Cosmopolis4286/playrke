<script setup lang="ts">
  /**
   * Página Dashboard → Admin
   * Stats: usuarios totales, nuevos del mes, cartones hoy, salas activas.
   * Conteos de entidades por rol: comercios, vendedores, clientes, tesoreros.
   * Actividad reciente: últimos usuarios registrados.
   */

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner       from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard            from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardOperationalAlerts from '@/js/Components/Common/Dashboard/DashboardOperationalAlerts.vue'
  import DashboardSection             from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed        from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinksGroup     from '@/js/Components/Common/Dashboard/DashboardQuickLinksGroup.vue'
  import DashboardEntityList          from '@/js/Components/Common/Dashboard/DashboardEntityList.vue'
  import DashboardTrendChart          from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
  import DashboardPendingWalletWidget from '@/js/Components/Common/Dashboard/DashboardPendingWalletWidget.vue'
  import DashboardRkeTotalsWidget     from '@/js/Components/Common/Dashboard/DashboardRkeTotalsWidget.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { ref }                         from 'vue'
  import { useAdminDashboard }           from '@/js/composables/roles/admin/dashboard/useAdminDashboard'
  import { useAdminActivityRealtime }    from '@/js/composables/common/dashboard/useAdminActivityRealtime'
  import { useActivityLogReconciliation } from '@/js/composables/common/dashboard/useActivityLogReconciliation'
  import { useWalletMovementsRealtime, type WalletMovementRequestedPayload } from '@/js/composables/wallet/admin/useWalletMovementsRealtime'
  import { useDashboardQuickLinks }      from '@/js/composables/common/dashboard/useDashboardQuickLinks'
  import { onlineCount, isPresenceLive } from '@/js/composables/realtime/useOnlinePresence'
  import { useDashboardMountLoading }    from '@/js/composables/common/dashboard/useDashboardMountLoading'
  import { useDashboardFormatters }      from '@/js/composables/common/dashboard/useDashboardFormatters'
  import type { ActivityItem }           from '@/js/types/common/dashboard/dashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardAdminPageProps } from '@/js/types/roles/admin/dashboard/dashboard-admin'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardAdminPageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const { statCards, sortedActivity, entityCounts, salesTrend, rkeTotals, pendingWalletCount, prependActivity } = useAdminDashboard(props)

  const { loading: sectionsLoading } = useDashboardMountLoading()

  const { formatUsd } = useDashboardFormatters()

  const { noteRealtimeItem } = useActivityLogReconciliation(
    (items) => items.forEach((item) => prependActivity(item)),
  )

  function onLiveActivity(item: ActivityItem) {
    prependActivity(item)
    noteRealtimeItem(item)
  }

  const { isLive, newUsersToday, cardsSoldToday, winnersToday } = useAdminActivityRealtime(onLiveActivity, 'admin')

  const recentMovements = ref<ActivityItem[]>([])

  function prependMovement(payload: WalletMovementRequestedPayload) {
    const item: ActivityItem = {
      id: `movement-${payload.movement_id}`,
      type: 'alert',
      title: payload.movement_type === 'DEPOSIT' ? 'Depósito pendiente' : 'Retiro pendiente',
      description: `${payload.user_name} · ${payload.currency_symbol}${payload.amount.toFixed(2)}`,
      timestamp: new Date().toISOString(),
    }
    recentMovements.value = [item, ...recentMovements.value].slice(0, 5)
  }

  const { pendingCount: livePendingCount, isLive: isWalletLive } =
    useWalletMovementsRealtime(pendingWalletCount.value, prependMovement)

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinkGroups = useDashboardQuickLinks('admin', entityCounts, props.allowedQuickLinks)
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Bienvenida -->
      <DashboardWelcomeBanner
        :userName="props.userName ?? 'Admin'"
        roleName="Administrador"
        roleKey="admin"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Usuarios', icon: 'fas fa-users', href: '/admin/users', variant: 'outline-primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat" v-if="entityCounts?.[0]">
            <span class="wb-stat__label">{{ entityCounts[0].label }}</span>
            <span class="wb-stat__value">{{ entityCounts[0].value }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">
              En línea
              <span v-if="isPresenceLive" class="wb-live-dot" title="Conteo en vivo"></span>
            </span>
            <span class="wb-stat__value wb-stat__value--success">{{ onlineCount }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">
              Nuevos hoy
              <span v-if="isLive" class="wb-live-dot" title="Escuchando en vivo"></span>
            </span>
            <span class="wb-stat__value wb-stat__value--accent">{{ newUsersToday }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Cartones vendidos (sesión)</span>
            <span class="wb-stat__value">{{ cardsSoldToday }}</span>
          </div>
        </template>
      </DashboardWelcomeBanner>

      <!-- Stats principales -->
      <div class="row g-3 mt-0">
        <div
          v-for="(stat, i) in statCards"
          :key="i"
          class="col-12 col-sm-6 col-xl-3"
        >
          <DashboardStatCard v-bind="stat" />
        </div>
      </div>

      <!-- Atención requerida: lo que deja a un comprador esperando -->
      <DashboardSection
        title="Atención requerida"
        icon="fas fa-bell"
        subtitle="Pagos, reembolsos, premios y movimientos pendientes"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardOperationalAlerts :alerts="props.operationalAlerts ?? []" />
      </DashboardSection>

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Gestión del sistema"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardQuickLinksGroup :groups="quickLinkGroups" :columns="3" />
      </DashboardSection>

      <!-- Tendencia + Wallet pendientes -->
      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Tendencia de ventas"
            icon="fas fa-chart-area"
            subtitle="Cartones vendidos · últimos 14 días"
            :loading="sectionsLoading"
            skeleton-variant="chart"
          >
            <DashboardTrendChart :data="salesTrend" label="Cartones vendidos" />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-5">
          <DashboardPendingWalletWidget
            :pending-count="livePendingCount"
            :is-live="isWalletLive"
            :recent-movements="recentMovements"
            :loading="sectionsLoading"
          />
        </div>
      </div>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Usuarios, ventas, ganadores y movimientos en vivo"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardActivityFeed
              :items="sortedActivity"
              :maxItems="30"
              :maxHeight="320"
              filterable
              :isLive="isLive"
            />
          </DashboardSection>
        </div>

        <!-- Conteos por entidad -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Entidades por rol"
            icon="fas fa-layer-group"
            subtitle="Totales registrados"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardEntityList :items="entityCounts" />
          </DashboardSection>
        </div>

        <!-- Comisiones RKE (resumen) -->
        <div class="col-12">
          <DashboardSection
            title="Comisiones RKE"
            icon="fas fa-percentage"
            subtitle="Cobrado en la venta de cartones — ver detalle completo en el panel de SuperAdmin"
            :loading="sectionsLoading"
            skeleton-variant="stat"
          >
            <DashboardRkeTotalsWidget :totals="rkeTotals" :format-usd="formatUsd" />
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
.wb-stat__value--accent  { color: #667eea; }
.wb-stat__value--success { color: #16a34a; }

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
