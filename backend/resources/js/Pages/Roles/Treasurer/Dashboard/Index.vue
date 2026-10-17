<script setup lang="ts">
  /**
   * Página Dashboard → Tesorería
   * Rol financiero: tasas oficiales, deuda de comercios con la plataforma,
   * movimientos pendientes, premios trabados y flujo de caja cross-juego.
   */

  import { computed, onMounted } from 'vue'
  import { router } from '@inertiajs/vue3'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard      from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection       from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardQuickLinks    from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import DashboardEmptyState    from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import EconomySummarySection  from '@/js/Components/Roles/SuperAdmin/Dashboard/EconomySummarySection.vue'
  import ExchangeRateSection    from '@/js/Components/Roles/SuperAdmin/Dashboard/ExchangeRateSection.vue'
  import type { QuickLink }     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useDashboardMountLoading } from '@/js/composables/common/dashboard/useDashboardMountLoading'
  import { useDashboardFormatters }   from '@/js/composables/common/dashboard/useDashboardFormatters'
  import { useEconomySummary }        from '@/js/composables/roles/superadmin/dashboard/useEconomySummary'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardTreasurerPageProps } from '@/js/types/roles/treasurer/dashboard/dashboard-treasurer'
  import type { StatCardData }                from '@/js/types/common/dashboard/dashboard'

  const props = defineProps<DashboardTreasurerPageProps>()

  const { loading: sectionsLoading } = useDashboardMountLoading()
  const { formatUsd, formatDate, formatDateTime } = useDashboardFormatters()
  // /superadmin/economy-summary admite treasury.reports (rol financiero).
  const { economySummary, economyLoading, economyError, loadEconomySummary } = useEconomySummary()

  onMounted(() => loadEconomySummary())

  const statCards = computed<StatCardData[]>(() => props.stats
    ? [props.stats.pendingMovements, props.stats.receivables, props.stats.staleRates, props.stats.stuckPrizes]
    : [])

  // Las tasas llegan del servidor (el endpoint de superadmin no admite
  // tesorería); "Actualizar" recarga solo esa prop.
  function reloadRates() {
    router.reload({ only: ['exchangeRates'] })
  }

  function formatMoney(symbol: string, amount: number): string {
    return `${symbol} ${amount.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`.trim()
  }

  const quickLinks: QuickLink[] = [
    { label: 'Movimientos',        description: 'Aprobar recargas y retiros',  icon: 'fas fa-money-bill-transfer', href: '/wallet-movements', variant: 'danger' },
    { label: 'Tasa de Cambio',     description: 'Tasas oficiales e histórico', icon: 'fas fa-arrows-rotate',    href: '/exchange-rate', variant: 'primary' },
    { label: 'Estadísticas Trivia', description: 'Recaudación y ranking',      icon: 'fas fa-chart-line',       href: '/trivia/stats',  variant: 'info' },
    { label: 'Gestión de Premios', description: 'Catálogo de premios',         icon: 'fas fa-gift',             href: '/prizes',        variant: 'warning' },
    { label: 'Directorio',         description: 'Métodos de pago',             icon: 'fas fa-university',       href: '/directory',     variant: 'secondary' },
    { label: 'Mi Tesorería',       description: 'Datos de contacto',           icon: 'fas fa-id-card',          href: '/treasurer/profile', variant: 'success' },
  ]
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <DashboardWelcomeBanner
        :userName="userName ?? 'Tesorería'"
        roleName="Tesorería"
        roleKey="treasurer"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Aprobar movimientos', icon: 'fas fa-money-bill-transfer', href: '/wallet-movements', variant: 'primary' },
          { label: 'Tasa de cambio', icon: 'fas fa-arrows-rotate', href: '/exchange-rate', variant: 'outline-primary' },
        ]"
      />

      <!-- Stats: lo que requiere atención -->
      <div class="row g-3 mt-0">
        <div v-for="(stat, i) in statCards" :key="i" class="col-12 col-sm-6 col-xl-3">
          <DashboardStatCard v-bind="stat" />
        </div>
      </div>

      <!-- Deuda de comercios + accesos -->
      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Deuda de comercios"
            icon="fas fa-file-invoice-dollar"
            subtitle="Ventas cobradas por el comercio pendientes de liquidar a la plataforma"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <table v-if="receivables?.length" class="tr-table">
              <thead>
                <tr><th>Comercio</th><th class="tr-num">Ventas</th><th class="tr-num">Total</th><th class="tr-num">Desde</th></tr>
              </thead>
              <tbody>
                <tr v-for="row in receivables" :key="`${row.debtor_user_id}-${row.currency_id}`">
                  <td>{{ row.debtor }}</td>
                  <td class="tr-num">{{ row.sales }}</td>
                  <td class="tr-num tr-amount">{{ formatMoney(row.symbol, row.total) }}</td>
                  <td class="tr-num tr-muted">{{ formatDate(row.oldest) }}</td>
                </tr>
              </tbody>
            </table>
            <DashboardEmptyState
              v-else
              icon="fas fa-circle-check"
              title="Sin deuda pendiente"
              description="Ningún comercio debe ventas a la plataforma."
              compact
            />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Accesos rápidos"
            icon="fas fa-bolt"
            subtitle="Operaciones frecuentes"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardQuickLinks :links="quickLinks" :columns="2" />
          </DashboardSection>
        </div>
      </div>

      <EconomySummarySection
        :summary="economySummary"
        :loading="economyLoading"
        :error="economyError"
        :format-usd="formatUsd"
        @refresh="loadEconomySummary"
      />

      <ExchangeRateSection
        :summary="exchangeRates ?? null"
        :loading="false"
        :error="null"
        :format-date="formatDateTime"
        @refresh="reloadRates"
      />
    </div>
  </div>
</template>

<style scoped>
.tr-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.tr-table th {
  text-align: left; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
  color: var(--ds-text-muted); padding: 0 0.5rem 0.5rem; border-bottom: 1px solid var(--ds-border);
}
.tr-table td { padding: 0.55rem 0.5rem; border-bottom: 1px solid var(--ds-border); color: var(--ds-text); }
.tr-num { text-align: right; white-space: nowrap; }
.tr-amount { font-weight: 700; }
.tr-muted { color: var(--ds-text-muted); }
</style>
