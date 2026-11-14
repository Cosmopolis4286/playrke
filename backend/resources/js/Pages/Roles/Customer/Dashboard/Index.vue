<script setup lang="ts">
/**
   * Página Dashboard → Cliente API
   * Organiza sus propias salas (sin red de vendedores) y juega con saldo
   * propio: salas próximas, ventas en sus salas, comisión de sala y billetera.
   */

import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// ==========================================================================
// Componentes
// ==========================================================================

import DashboardWelcomeBanner  from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
import DashboardStatCard       from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
import DashboardSection        from '@/js/Components/Common/Dashboard/DashboardSection.vue'
import NetworkExchangeRateWidget from '@/js/Components/Common/ExchangeRate/NetworkExchangeRateWidget.vue'
import DashboardQuickLinks     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
import DashboardTrendChart     from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
import DashboardActivityFeed   from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
import DashboardWalletBalances from '@/js/Components/Common/Dashboard/DashboardWalletBalances.vue'
import DashboardUpcomingRooms  from '@/js/Components/Common/Dashboard/DashboardUpcomingRooms.vue'
import type { QuickLink }      from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

// ==========================================================================
// Composables
// ==========================================================================

import { useDashboard }             from '@/js/composables/common/dashboard/useDashboard'
import { useCreatorSalesRealtime }  from '@/js/composables/common/dashboard/useCreatorSalesRealtime'
import { useDashboardMountLoading } from '@/js/composables/common/dashboard/useDashboardMountLoading'

// ==========================================================================
// Types
// ==========================================================================

import type { DashboardCustomerPageProps } from '@/js/types/roles/customer/dashboard/dashboard-customer'
import type { StatCardData }               from '@/js/types/common/dashboard/dashboard'

const props = defineProps<DashboardCustomerPageProps>()

const { sortedActivity, prependActivity } = useDashboard(undefined, props.recentActivity)
// Es el creador de sus salas: recibe creators.activity.{userId} en vivo.
const { isLive, cardsSoldToday } = useCreatorSalesRealtime(prependActivity)
const { loading: sectionsLoading } = useDashboardMountLoading()

const statCards = computed<StatCardData[]>(() => props.stats
  ? [props.stats.activeRooms, props.stats.cardsToday, props.stats.salesThisMonth, props.stats.commission]
  : [])

// Módulos apagados desde el panel (HandleInertiaRequests::disabledModules).
const disabledModules = computed(() => (usePage().props.disabledModules as string[] | undefined) ?? [])

const allQuickLinks: QuickLink[] = [
  { label: 'Gestionar Salas', description: 'Crea y programa tus salas', icon: 'fas fa-dice',       href: '/bingo-rooms',      variant: 'primary' },
  { label: 'Jugar Bingo',     description: 'Salas disponibles',          icon: 'fas fa-play',       href: '/bingo/game-rooms', variant: 'success' },
  { label: 'Trivia',          description: 'Juega y compite',            icon: 'fas fa-brain',      href: '/trivia/lobby',     variant: 'info' },
  { label: 'Mi Billetera',    description: 'Saldo y movimientos',        icon: 'fas fa-wallet',     href: '/wallet',           variant: 'warning' },
  { label: 'Directorio',      description: 'Métodos de pago',            icon: 'fas fa-university', href: '/directory',        variant: 'secondary' },
  { label: 'Mi Cliente',      description: 'Datos de tu cuenta',         icon: 'fas fa-id-card',    href: '/customer/profile', variant: 'danger' }
]
const quickLinks = computed(() => allQuickLinks.filter(
  link => !(link.href.startsWith('/trivia') && disabledModules.value.includes('games_trivia'))
))
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">
      <DashboardWelcomeBanner
        :user-name="userName ?? 'Cliente'"
        role-name="Cliente"
        role-key="customer"
        :message="period ? `Período: ${period}` : undefined"
        :actions="[
          { label: 'Gestionar salas', icon: 'fas fa-dice', href: '/bingo-rooms', variant: 'primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat">
            <span class="wb-stat__label">
              Vendidos hoy
              <span v-if="isLive" class="wb-live-dot" title="Escuchando en vivo" />
            </span>
            <span class="wb-stat__value">{{ statCards[1]?.value ?? '—' }}</span>
          </div>
          <div v-if="cardsSoldToday > 0" class="wb-stat">
            <span class="wb-stat__label">Vendidos (sesión)</span>
            <span class="wb-stat__value wb-stat__value--accent">{{ cardsSoldToday }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Salas activas</span>
            <span class="wb-stat__value">{{ statCards[0]?.value ?? '—' }}</span>
          </div>
        </template>
      </DashboardWelcomeBanner>

      <div class="row g-3 mt-0">
        <div v-for="(stat, i) in statCards" :key="i" class="col-12 col-sm-6 col-xl-3">
          <DashboardStatCard v-bind="stat" />
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Próximas salas"
            icon="fas fa-calendar-day"
            subtitle="Tus salas de hoy y mañana"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardUpcomingRooms :rooms="upcomingRooms ?? []" />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Mi billetera"
            icon="fas fa-wallet"
            subtitle="Saldo por moneda"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardWalletBalances :wallets="wallets ?? []" />
          </DashboardSection>
        </div>
      </div>

      <!-- Tasa de cambio de la red + accesos rápidos -->
      <div class="row g-3">
        <div class="col-12 col-lg-5">
          <NetworkExchangeRateWidget />
        </div>
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Accesos rápidos"
            icon="fas fa-bolt"
            subtitle="Operaciones frecuentes"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardQuickLinks :links="quickLinks" :columns="3" />
          </DashboardSection>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Tendencia de ventas"
            icon="fas fa-chart-area"
            subtitle="Cartones vendidos en tus salas · últimos 14 días"
            :loading="sectionsLoading"
            skeleton-variant="chart"
          >
            <DashboardTrendChart :data="salesTrend ?? []" label="Cartones vendidos" />
          </DashboardSection>
        </div>
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimas ventas en tus salas"
            :loading="sectionsLoading"
            skeleton-variant="list"
          >
            <DashboardActivityFeed :items="sortedActivity" :max-items="8" />
          </DashboardSection>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wb-stat { display: flex; flex-direction: column; gap: 1px; }
.wb-stat__label { font-size: 0.68rem; color: #8a94a6; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
.wb-stat__value { font-size: 0.8rem; font-weight: 700; color: #1a202c; }
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
</style>
