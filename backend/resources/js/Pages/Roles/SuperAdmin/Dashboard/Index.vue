<script setup lang="ts">
  /**
   * Página Dashboard → SuperAdmin
   * Stats: usuarios totales, administradores, sistemas activos, permisos.
   * Conteos de entidades por rol extendido.
   * Actividad reciente y salud del sistema.
   */

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner       from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard            from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection             from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed        from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinksGroup     from '@/js/Components/Common/Dashboard/DashboardQuickLinksGroup.vue'
  import DashboardEntityList          from '@/js/Components/Common/Dashboard/DashboardEntityList.vue'
  import DashboardTrendChart          from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
  import DashboardPendingWalletWidget from '@/js/Components/Common/Dashboard/DashboardPendingWalletWidget.vue'
  import PlatformWalletPanel          from '@/js/Components/Roles/SuperAdmin/Dashboard/PlatformWalletPanel.vue'
  import PlatformCashModal            from '@/js/Components/Roles/SuperAdmin/Dashboard/PlatformCashModal.vue'
  import RkeCommissionsSection        from '@/js/Components/Roles/SuperAdmin/Dashboard/RkeCommissionsSection.vue'
  import TriviaSummarySection         from '@/js/Components/Roles/SuperAdmin/Dashboard/TriviaSummarySection.vue'
  import CommerceHierarchySection     from '@/js/Components/Roles/SuperAdmin/Dashboard/CommerceHierarchySection.vue'
  import ExchangeRateSection          from '@/js/Components/Roles/SuperAdmin/Dashboard/ExchangeRateSection.vue'
  import FailedJobsModal              from '@/js/Components/Roles/SuperAdmin/Dashboard/FailedJobsModal.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { onMounted, ref }           from 'vue'
  import { Link }                     from '@inertiajs/vue3'
  import { useSuperAdminDashboard }   from '@/js/composables/roles/superadmin/dashboard/useSuperAdminDashboard'
  import { useAdminActivityRealtime } from '@/js/composables/common/dashboard/useAdminActivityRealtime'
  import { useActivityLogReconciliation } from '@/js/composables/common/dashboard/useActivityLogReconciliation'
  import { useWalletMovementsRealtime, type WalletMovementRequestedPayload } from '@/js/composables/wallet/admin/useWalletMovementsRealtime'
  import { useMovementTypeCatalog }   from '@/js/composables/wallet/useMovementTypeCatalog'
  import { useDashboardQuickLinks }   from '@/js/composables/common/dashboard/useDashboardQuickLinks'
  import { useDashboardFormatters }   from '@/js/composables/common/dashboard/useDashboardFormatters'
  import { useDashboardMountLoading } from '@/js/composables/common/dashboard/useDashboardMountLoading'
  import { onlineCount, isPresenceLive } from '@/js/composables/realtime/useOnlinePresence'
  import { useRkeSummary }          from '@/js/composables/roles/superadmin/dashboard/useRkeSummary'
  import { useTriviaSummary }       from '@/js/composables/roles/superadmin/dashboard/useTriviaSummary'
  import { useCommerceHierarchy }   from '@/js/composables/roles/superadmin/dashboard/useCommerceHierarchy'
  import { useExchangeRateSummary } from '@/js/composables/roles/superadmin/dashboard/useExchangeRateSummary'
  import { usePlatformWallet }      from '@/js/composables/roles/superadmin/dashboard/usePlatformWallet'
  import { usePlatformCashModal }   from '@/js/composables/roles/superadmin/dashboard/usePlatformCashModal'
  import { useFailedJobs }          from '@/js/composables/roles/superadmin/dashboard/useFailedJobs'
  import type { ActivityItem }      from '@/js/types/common/dashboard/dashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardSuperAdminPageProps } from '@/js/types/roles/superadmin/dashboard/dashboard-superadmin'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardSuperAdminPageProps>()

  // ==========================================================================
  // Composables de dominio
  // ==========================================================================

  const { statCards, sortedActivity, entityCounts, systemHealth, adminActivity, salesTrend, pendingWalletCount, prependActivity } = useSuperAdminDashboard(props)

  const { loading: sectionsLoading } = useDashboardMountLoading()

  const { noteRealtimeItem } = useActivityLogReconciliation(
    (items) => items.forEach((item) => prependActivity(item)),
  )

  function onLiveActivity(item: ActivityItem) {
    prependActivity(item)
    noteRealtimeItem(item)
  }

  const { isLive, newUsersToday, cardsSoldToday, winnersToday } = useAdminActivityRealtime(onLiveActivity, 'superadmin')

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

  const { rkeSummary, rkeLoading, rkeError, loadRkeSummary } = useRkeSummary()
  const { triviaSummary, triviaLoading, triviaError, loadTriviaSummary } = useTriviaSummary()
  const { hierarchy: commerceHierarchy, loading: hierarchyLoading, error: hierarchyError, loadHierarchy } = useCommerceHierarchy()
  const { exchangeRates, loading: exchangeRatesLoading, error: exchangeRatesError, loadExchangeRates } = useExchangeRateSummary()

  const {
    platformWallets, activePlatformWallet,
    platformMovements, platformMovMeta,
    platformLoading, platformMovLoading, platformError,
    loadPlatformWallets, loadPlatformMovements, selectPlatformWallet,
  } = usePlatformWallet()

  const { cashModal, openCashModal, closeCashModal, withdrawConfirmed, submitCash } =
    usePlatformCashModal(activePlatformWallet, loadPlatformWallets)

  // Modal de jobs fallidos (widget "Salud del sistema")
  const showFailedJobsModal = ref(false)
  const {
    jobs: failedJobsList, loading: failedJobsLoading, retryingUuid, retryingAll,
    loadJobs: loadFailedJobs, retryJob, retryAll: retryAllFailedJobs, forgetJob,
  } = useFailedJobs()

  function openFailedJobsModal() {
    showFailedJobsModal.value = true
    loadFailedJobs()
  }

  // Catálogo de tipos de movimiento (MovementType), gestionable por admin/
  // superadmin vía CRUD — esta página no recibe el catálogo por props
  // Inertia, así que lo obtiene con su propio fetch.
  const { metaFor: movMeta, fetchMovementTypes } = useMovementTypeCatalog()

  onMounted(() => {
    loadRkeSummary()
    loadTriviaSummary()
    loadHierarchy()
    loadExchangeRates()
    loadPlatformWallets()
    fetchMovementTypes()
  })

  // ==========================================================================
  // Helpers de formato
  // ==========================================================================

  const { formatUsd, formatDate, formatDateTime } = useDashboardFormatters()

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinkGroups = useDashboardQuickLinks('superadmin', entityCounts)
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Bienvenida -->
      <DashboardWelcomeBanner
        :userName="props.userName ?? 'SuperAdmin'"
        roleName="Super Administrador"
        roleKey="superadmin"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Administradores', icon: 'fas fa-user-shield', href: '/admins', variant: 'outline-danger' },
          { label: 'Permisos', icon: 'fas fa-key', href: '/permissions', variant: 'outline-warning' },
        ]"
      >
        <template #highlights>
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
          <div class="wb-stat" v-if="rkeSummary">
            <span class="wb-stat__label">RKE acumulado</span>
            <span class="wb-stat__value wb-stat__value--accent">$ {{ formatUsd(rkeSummary.totals.total_rke) }}</span>
          </div>
          <div class="wb-stat" v-if="activePlatformWallet">
            <span class="wb-stat__label">Caja plataforma</span>
            <span class="wb-stat__value wb-stat__value--success">{{ activePlatformWallet.currency_symbol }} {{ formatUsd(activePlatformWallet.balance) }}</span>
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

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Gestión completa del sistema"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardQuickLinksGroup :groups="quickLinkGroups" :columns="4" />
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
            class="ds-scroll-section"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardEntityList :items="entityCounts" />
          </DashboardSection>
        </div>

      </div>

      <!-- Salud del sistema -->
      <DashboardSection
        title="Salud del sistema"
        icon="fas fa-heartbeat"
        :subtitle="systemHealth.label"
        :loading="sectionsLoading"
      >
        <template #actions>
          <button
            v-if="systemHealth.failedJobsCount > 0"
            class="sh-detail-btn"
            @click="openFailedJobsModal"
          >
            Ver jobs fallidos <i class="fas fa-arrow-right ml-1"></i>
          </button>
        </template>

        <div class="row g-3">
          <div class="col-12 col-sm-3">
            <div class="sh-item">
              <span class="sh-item__label">Estado general</span>
              <span class="sh-item__value" :class="`sh-item__value--${systemHealth.status}`">{{ systemHealth.label }}</span>
            </div>
          </div>
          <div class="col-12 col-sm-3">
            <div class="sh-item">
              <span class="sh-item__label">Jobs fallidos</span>
              <button
                v-if="systemHealth.failedJobsCount > 0"
                class="sh-item__link sh-item__value--warning"
                @click="openFailedJobsModal"
              >
                {{ systemHealth.failedJobsCount }} <i class="fas fa-arrow-up-right-from-square ml-1"></i>
              </button>
              <span v-else class="sh-item__value">0</span>
            </div>
          </div>
          <div class="col-12 col-sm-3">
            <div class="sh-item">
              <span class="sh-item__label">Jobs en cola</span>
              <span class="sh-item__value" :class="{ 'sh-item__value--warning': systemHealth.pendingJobsCount > 20 }">
                {{ systemHealth.pendingJobsCount }}
              </span>
            </div>
          </div>
          <div class="col-12 col-sm-3">
            <div class="sh-item">
              <span class="sh-item__label">Broadcast (Reverb)</span>
              <span class="sh-item__value" :class="systemHealth.broadcastLive ? 'sh-item__value--healthy' : 'sh-item__value--warning'">
                {{ systemHealth.broadcastLive ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Desglose de advertencias -->
        <ul v-if="systemHealth.issueDetails.length" class="sh-issues">
          <li v-for="issue in systemHealth.issueDetails" :key="issue.code" class="sh-issue">
            <i class="fas fa-circle-exclamation sh-issue__icon"></i>
            <span v-if="issue.code === 'inactive_admins'">
              <Link :href="`/admin/users?last_login_before=${encodeURIComponent(new Date(Date.now() - 30*86400000).toISOString())}`" class="sh-issue__link">
                {{ issue.label }} ({{ issue.count }})
              </Link>
            </span>
            <span v-else>{{ issue.label }} ({{ issue.count }})</span>
          </li>
        </ul>
      </DashboardSection>

      <FailedJobsModal
        v-model="showFailedJobsModal"
        :jobs="failedJobsList"
        :loading="failedJobsLoading"
        :retrying-uuid="retryingUuid"
        :retrying-all="retryingAll"
        @retry="retryJob"
        @retry-all="retryAllFailedJobs"
        @forget="forgetJob"
      />

      <!-- ══ BILLETERA DE PLATAFORMA ══════════════════════════════════ -->
      <PlatformWalletPanel
        :wallets="platformWallets"
        :active-wallet="activePlatformWallet"
        :movements="platformMovements"
        :movements-meta="platformMovMeta"
        :loading="platformLoading"
        :mov-loading="platformMovLoading"
        :error="platformError"
        :rke-total="rkeSummary?.totals.total_rke ?? null"
        :mov-meta="movMeta"
        :format-usd="formatUsd"
        :format-date-time="formatDateTime"
        @refresh="loadPlatformWallets"
        @refresh-movements="loadPlatformMovements"
        @select-wallet="selectPlatformWallet"
        @open-cash-modal="openCashModal"
      />

      <!-- ══ MODAL DEPÓSITO / RETIRO ══════════════════════════════════ -->
      <PlatformCashModal
        v-model="cashModal"
        :active-wallet="activePlatformWallet"
        :withdraw-confirmed="withdrawConfirmed"
        :format-usd="formatUsd"
        @close="closeCashModal"
        @submit="submitCash"
      />

      <!-- ══ COMISIONES RKE POR SALA ════════════════════════════════════ -->
      <RkeCommissionsSection
        :summary="rkeSummary"
        :loading="rkeLoading"
        :error="rkeError"
        :format-usd="formatUsd"
        :format-date="formatDate"
        @refresh="loadRkeSummary"
      />

      <!-- ══ COMERCIOS Y SU RED ══════════════════════════════════════════ -->
      <CommerceHierarchySection
        :hierarchy="commerceHierarchy"
        :loading="hierarchyLoading"
        :error="hierarchyError"
        @refresh="loadHierarchy"
      />

      <!-- ══ ACTIVIDAD TRIVIA ════════════════════════════════════════════ -->
      <TriviaSummarySection
        :summary="triviaSummary"
        :loading="triviaLoading"
        :error="triviaError"
        :format-usd="formatUsd"
        @refresh="loadTriviaSummary"
      />

      <!-- ══ TASAS DE CAMBIO ═════════════════════════════════════════════ -->
      <ExchangeRateSection
        :summary="exchangeRates"
        :loading="exchangeRatesLoading"
        :error="exchangeRatesError"
        :format-date="formatDate"
        @refresh="loadExchangeRates"
      />
    </div>
  </div>
</template>

<style scoped>
/* Highlights del welcome banner */
.wb-stat { display: flex; flex-direction: column; gap: 1px; }
.wb-stat__label { font-size: 0.68rem; color: var(--ds-text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
.wb-stat__value { font-size: 0.8rem; font-weight: 700; color: var(--ds-text); transition: color 0.2s; }
.wb-stat__value--accent  { color: #a78bfa; }
.wb-stat__value--success { color: #34d399; }

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

/* Altura igual con scroll solo en el contenido (card-body) — usado por
   "Entidades por rol", que no tiene scroll propio como el activity feed. */
.ds-scroll-section :deep(.card-body) {
  max-height: 320px;
  overflow-y: auto;
}

/* Salud del sistema */
.sh-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 0.6rem 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}
.sh-item__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .03em;
  color: var(--ds-text-2);
}
.sh-item__value {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ds-text);
}
.sh-item__value--healthy  { color: #34d399; }
.sh-item__value--warning  { color: #f59e0b; }
.sh-item__value--critical { color: #ef4444; }

.sh-item__link {
  font-size: 0.95rem;
  font-weight: 700;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  text-align: left;
}

.sh-detail-btn {
  font-size: 0.78rem;
  font-weight: 600;
  color: #667eea;
  background: none;
  border: none;
  cursor: pointer;
}
.sh-detail-btn:hover { text-decoration: underline; }

.sh-issues { list-style: none; margin: 0.85rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.sh-issue {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--ds-text-2);
}
.sh-issue__icon { color: #f59e0b; font-size: 0.72rem; }
.sh-issue__link { color: #667eea; text-decoration: none; }
.sh-issue__link:hover { text-decoration: underline; }
</style>
