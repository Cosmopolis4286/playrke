<script setup lang="ts">
  /**
   * Página Dashboard → Subsidiary (agencia/sucursal)
   *
   * A diferencia de Commerce: sin useCreatorSalesRealtime (el canal
   * creators.activity.{userId} solo notifica al creador de la sala —
   * Subsidiary nunca crea salas, ver BingoCardSold::broadcastOn()), sin
   * "Mi red de vendedores" (no tiene sub-agencias, solo sus propios
   * vendedores, ya reflejados en el stat "Vendedores") y sin "Configuración
   * activa" (monedas/juegos son propiedad del Commerce padre, no de la
   * agencia).
   */

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner       from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard            from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection             from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed        from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinks          from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import DashboardTrendChart          from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
  import DashboardSellerSalesList     from '@/js/Components/Common/Dashboard/DashboardSellerSalesList.vue'
  import DashboardWalletBalances      from '@/js/Components/Common/Dashboard/DashboardWalletBalances.vue'
  import type { QuickLink }           from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useSubsidiaryDashboard }   from '@/js/composables/roles/subsidiary/dashboard/useSubsidiaryDashboard'
  import { useDashboardMountLoading } from '@/js/composables/common/dashboard/useDashboardMountLoading'
  import { useDashboardFormatters }   from '@/js/composables/common/dashboard/useDashboardFormatters'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardSubsidiaryPageProps } from '@/js/types/roles/subsidiary/dashboard/dashboard-subsidiary'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardSubsidiaryPageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const {
    statCards, sortedActivity, reportsTo, salesTrend,
    salesBySeller, wallets,
  } = useSubsidiaryDashboard(props)

  const { loading: sectionsLoading } = useDashboardMountLoading()
  const { formatUsd } = useDashboardFormatters()

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
    {
      label:       'Mis Vendedores',
      description: 'Gestionar taquillas/vendedores',
      icon:        'fas fa-user-tie',
      href:        '/sellers',
      variant:     'secondary',
    },
  ]
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Bienvenida -->
      <DashboardWelcomeBanner
        :userName="userName ?? 'Usuario'"
        roleName="Agencia"
        roleKey="subsidiary"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Ver juegos', icon: 'fas fa-gamepad', href: '/games', variant: 'outline-primary' },
          { label: 'Ver ventas', icon: 'fas fa-chart-bar', href: '/sales', variant: 'primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat">
            <span class="wb-stat__label">Ventas del mes</span>
            <span class="wb-stat__value wb-stat__value--accent">{{ statCards[0]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Cartones vendidos hoy</span>
            <span class="wb-stat__value">{{ statCards[1]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Vendedores</span>
            <span class="wb-stat__value">{{ statCards[3]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat" v-if="reportsTo">
            <span class="wb-stat__label">Reporto a</span>
            <span class="wb-stat__value">{{ reportsTo.name }}</span>
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
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Tendencia + Actividad -->
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
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimas ventas de tus vendedores"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="8" />
          </DashboardSection>
        </div>
      </div>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Ventas por vendedor -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Ventas por vendedor"
            icon="fas fa-user-tie"
            subtitle="Top 10 este mes"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardSellerSalesList :rows="salesBySeller" :format-usd="formatUsd" />
          </DashboardSection>
        </div>

        <!-- Billeteras propias -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Mi billetera"
            icon="fas fa-wallet"
            subtitle="Saldo por moneda"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardWalletBalances :wallets="wallets" />
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
.wb-stat__value--accent { color: #2563eb; }
</style>
