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

  import DashboardWelcomeBanner from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard      from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection       from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed  from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinks    from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import DashboardEntityList    from '@/js/Components/Common/Dashboard/DashboardEntityList.vue'
  import type { QuickLink }     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import PlatformWalletPanel    from '@/js/Components/Roles/SuperAdmin/Dashboard/PlatformWalletPanel.vue'
  import PlatformCashModal      from '@/js/Components/Roles/SuperAdmin/Dashboard/PlatformCashModal.vue'
  import RkeCommissionsSection  from '@/js/Components/Roles/SuperAdmin/Dashboard/RkeCommissionsSection.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { onMounted }                from 'vue'
  import { useSuperAdminDashboard }   from '@/js/composables/roles/superadmin/dashboard/useSuperAdminDashboard'
  import { useAdminActivityRealtime } from '@/js/composables/common/dashboard/useAdminActivityRealtime'
  import { useMovementTypeCatalog }   from '@/js/composables/wallet/useMovementTypeCatalog'
  import { onlineCount, isPresenceLive } from '@/js/composables/realtime/useOnlinePresence'
  import { useRkeSummary }          from '@/js/composables/roles/superadmin/dashboard/useRkeSummary'
  import { usePlatformWallet }      from '@/js/composables/roles/superadmin/dashboard/usePlatformWallet'
  import { usePlatformCashModal }   from '@/js/composables/roles/superadmin/dashboard/usePlatformCashModal'

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

  const { statCards, sortedActivity, entityCounts, systemHealth, adminActivity, prependActivity } = useSuperAdminDashboard(props)
  const { isLive, newUsersToday, cardsSoldToday } = useAdminActivityRealtime(prependActivity)

  const { rkeSummary, rkeLoading, rkeError, loadRkeSummary } = useRkeSummary()

  const {
    platformWallets, activePlatformWallet,
    platformMovements, platformMovMeta,
    platformLoading, platformMovLoading, platformError,
    loadPlatformWallets, loadPlatformMovements, selectPlatformWallet,
  } = usePlatformWallet()

  const { cashModal, openCashModal, closeCashModal, withdrawConfirmed, submitCash } =
    usePlatformCashModal(activePlatformWallet, loadPlatformWallets)

  // Catálogo de tipos de movimiento (MovementType), gestionable por admin/
  // superadmin vía CRUD — esta página no recibe el catálogo por props
  // Inertia, así que lo obtiene con su propio fetch.
  const { metaFor: movMeta, fetchMovementTypes } = useMovementTypeCatalog()

  onMounted(() => {
    loadRkeSummary()
    loadPlatformWallets()
    fetchMovementTypes()
  })

  // ==========================================================================
  // Helpers de formato
  // ==========================================================================

  function formatUsd(v: number, decimals = 2) {
    return new Intl.NumberFormat('es-VE', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(v)
  }

  function formatDate(iso: string | null) {
    if (!iso) return '—'
    return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
  }

  function formatDateTime(iso: string) {
    return new Intl.DateTimeFormat('es-VE', {
      day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    }).format(new Date(iso))
  }

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Administradores',
      description: 'Gestionar administradores del sistema',
      icon:        'fas fa-user-shield',
      href:        '/admins',
      variant:     'danger',
    },
    {
      label:       'Permisos',
      description: 'Configurar permisos de roles',
      icon:        'fas fa-key',
      href:        '/permissions',
      variant:     'warning',
    },
    {
      label:       'Usuarios',
      description: 'Gestionar todos los usuarios',
      icon:        'fas fa-users',
      href:        '/admin/users',
      variant:     'primary',
    },
    {
      label:       'Comercios',
      description: 'Gestionar comercializadoras',
      icon:        'fas fa-building',
      href:        '/admin/commerces',
      variant:     'info',
    },
    {
      label:       'Vendedores',
      description: 'Gestionar vendedores',
      icon:        'fas fa-user-tie',
      href:        '/admin/sellers',
      variant:     'success',
    },
    {
      label:       'Clientes',
      description: 'Gestionar clientes',
      icon:        'fas fa-handshake',
      href:        '/admin/customers',
      variant:     'warning',
    },
    {
      label:       'Jugadores',
      description: 'Gestionar jugadores',
      icon:        'fas fa-gamepad',
      href:        '/admin/players',
      variant:     'secondary',
    },
    {
      label:       'Tesoreros',
      description: 'Gestionar tesoreros',
      icon:        'fas fa-piggy-bank',
      href:        '/admin/treasurers',
      variant:     'secondary',
    },
  ]
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
      >
        <DashboardQuickLinks :links="quickLinks" :columns="4" />
      </DashboardSection>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimos usuarios registrados"
            class="ds-scroll-section"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="10" class="ds-activity-compact" />
          </DashboardSection>
        </div>

        <!-- Conteos por entidad -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Entidades por rol"
            icon="fas fa-layer-group"
            subtitle="Totales registrados"
            class="ds-scroll-section"
          >
            <DashboardEntityList :items="entityCounts" />
          </DashboardSection>
        </div>

      </div>

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

/* Altura igual con scroll solo en el contenido (card-body) */
.ds-scroll-section :deep(.card-body) {
  max-height: 320px;
  overflow-y: auto;
}

/* Items de actividad más compactos y minimalistas */
.ds-activity-compact :deep(.activity-feed__item) {
  padding: 0.5rem 0.75rem;
  gap: 0.75rem;
  border: none;
  border-bottom: 1px solid var(--ds-border);
  border-radius: 0;
  background: transparent;
}

.ds-activity-compact :deep(.activity-feed__item:last-child) {
  border-bottom: none;
}

.ds-activity-compact :deep(.activity-feed__item:hover) {
  background: var(--ds-surface-2);
  box-shadow: none;
  transform: none;
}

.ds-activity-compact :deep(.activity-feed__icon) {
  width: 32px !important;
  height: 32px !important;
  font-size: 0.75rem;
}

.ds-activity-compact :deep(.activity-feed__item p) {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.ds-activity-compact :deep(.activity-feed__item .small) {
  font-size: 0.75rem;
}
</style>
