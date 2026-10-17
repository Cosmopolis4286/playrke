<script setup lang="ts">
  /**
   * Página Dashboard → Comercio
   * Arriba lo accionable: pagos por revisar, premios, próximas salas y la
   * salud de la red de vendedores.
   */

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner       from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard            from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection             from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed        from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinks          from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import DashboardEntityList          from '@/js/Components/Common/Dashboard/DashboardEntityList.vue'
  import DashboardTrendChart          from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
  import DashboardSellerSalesList     from '@/js/Components/Common/Dashboard/DashboardSellerSalesList.vue'
  import DashboardWalletBalances      from '@/js/Components/Common/Dashboard/DashboardWalletBalances.vue'
  import DashboardConfigSummary       from '@/js/Components/Common/Dashboard/DashboardConfigSummary.vue'
  import DashboardNetworkHealth       from '@/js/Components/Common/Dashboard/DashboardNetworkHealth.vue'
  import DashboardNetworkPending      from '@/js/Components/Common/Dashboard/DashboardNetworkPending.vue'
  import CommerceUpcomingRooms        from '@/js/Components/Roles/Commerce/Dashboard/CommerceUpcomingRooms.vue'
  import type { QuickLink }           from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCommerceDashboard }     from '@/js/composables/roles/commerce/dashboard/useCommerceDashboard'
  import { useCreatorSalesRealtime }  from '@/js/composables/common/dashboard/useCreatorSalesRealtime'
  import { useDashboardMountLoading } from '@/js/composables/common/dashboard/useDashboardMountLoading'
  import { useDashboardFormatters }   from '@/js/composables/common/dashboard/useDashboardFormatters'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardCommercePageProps } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardCommercePageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const {
    statCards, sortedActivity, entityCounts, salesTrend,
    salesBySeller, wallets, configuration, prependActivity,
  } = useCommerceDashboard(props)

  const { isLive, cardsSoldToday, amountSoldToday } = useCreatorSalesRealtime(prependActivity)
  const { loading: sectionsLoading } = useDashboardMountLoading()
  const { formatUsd } = useDashboardFormatters()

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Gestionar Salas',
      description: 'Salas de bingo activas',
      icon:        'fas fa-dice',
      href:        '/bingo-rooms',
      variant:     'primary',
    },
    {
      label:       'Reporte de Ventas',
      description: 'Resumen por sala',
      icon:        'fas fa-chart-bar',
      href:        '/bingo/sales-report',
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
    {
      label:       'Mis Sucursales',
      description: 'Gestionar agencias/sucursales',
      icon:        'fas fa-code-branch',
      href:        '/admin/subsidiaries',
      variant:     'secondary',
    },
    {
      label:       'Mi Perfil',
      description: 'Datos del comercio',
      icon:        'fas fa-id-card',
      href:        '/commerce/profile',
      variant:     'danger',
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
          { label: 'Gestionar salas', icon: 'fas fa-dice', href: '/bingo-rooms', variant: 'outline-primary' },
          { label: 'Ver ventas', icon: 'fas fa-chart-bar', href: '/bingo/sales-report', variant: 'primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat">
            <span class="wb-stat__label">
              Ventas del mes
              <span v-if="isLive" class="wb-live-dot" title="Escuchando en vivo"></span>
            </span>
            <span class="wb-stat__value wb-stat__value--accent">{{ statCards[0]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat" v-if="cardsSoldToday > 0">
            <span class="wb-stat__label">Ventas (sesión)</span>
            <span class="wb-stat__value wb-stat__value--accent">{{ cardsSoldToday }} · ${{ amountSoldToday.toFixed(2) }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Salas activas</span>
            <span class="wb-stat__value">{{ statCards[2]?.value ?? '—' }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Mi comisión</span>
            <span class="wb-stat__value">{{ statCards[4]?.value ?? '—' }}</span>
          </div>
        </template>
      </DashboardWelcomeBanner>

      <!-- Deuda RKE con la plataforma: ventas cuyo dinero cobró el comercio
           (vendedores con comprobante / venta en mano). Se liquida con la
           plataforma; desaparece cuando el superadmin la marca cobrada. -->
      <div v-if="props.rkeReceivables?.length" class="alert alert-warning d-flex align-items-start mt-3 mb-0" style="gap: 0.6rem; font-size: 0.85rem">
        <i class="fas fa-file-invoice-dollar mt-1"></i>
        <div>
          <strong>Pendiente de liquidar con la plataforma:</strong>
          <div v-for="r in props.rkeReceivables" :key="r.currency_id">
            {{ r.symbol }} {{ formatUsd(r.total) }} {{ r.currency }} ({{ r.sales }} venta{{ r.sales === 1 ? '' : 's' }})
            <span class="text-muted" style="font-size: 0.78rem">
              — RKE {{ formatUsd(r.rke) }} · bote {{ formatUsd(r.prize_pool) }} · jackpot {{ formatUsd(r.jackpot) }}
            </span>
          </div>
          <div class="text-muted" style="font-size: 0.78rem">
            Ventas cobradas por tu red fuera de la billetera. Tu red conserva sus comisiones (vendedores, agencias y la
            tuya); el bote y el jackpot los paga la plataforma a los ganadores. Coordiná el pago con la administración.
          </div>
        </div>
      </div>

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

      <!-- Pendientes + próximas salas -->
      <div class="row g-3">
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Pendientes"
            icon="fas fa-list-check"
            subtitle="Pagos por revisar, rechazos y premios"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardNetworkPending
              :payments="props.networkPayments"
              :prizes="props.prizesToDeliver"
              review-href="/bingo/card-payments"
            />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Próximas salas"
            icon="fas fa-calendar-day"
            subtitle="Tus salas de hoy y mañana"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <CommerceUpcomingRooms :rooms="props.upcomingRooms ?? []" />
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
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Tendencia + Entidades -->
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
            title="Mi red de vendedores"
            icon="fas fa-layer-group"
            subtitle="Vendedores directos y en agencias"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardEntityList :items="entityCounts" />
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
            subtitle="Últimas acciones en tu cuenta"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="8" />
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

      <!-- Ventas por vendedor + salud de la red -->
      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Ventas por vendedor"
            icon="fas fa-user-tie"
            subtitle="Top 10 este mes · en USD"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardSellerSalesList :rows="salesBySeller" :format-usd="formatUsd" />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Salud de tu red"
            icon="fas fa-heart-pulse"
            subtitle="Quién vende y quién necesita un empujón"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardNetworkHealth :health="props.networkHealth" sellers-href="/sellers" />
          </DashboardSection>
        </div>
      </div>

      <!-- Configuración activa -->
      <DashboardSection
        title="Configuración activa"
        icon="fas fa-sliders-h"
        subtitle="Monedas y juegos habilitados"
        :loading="sectionsLoading"
        skeleton-variant="list"
      >
        <DashboardConfigSummary :configuration="configuration" />
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

</style>
