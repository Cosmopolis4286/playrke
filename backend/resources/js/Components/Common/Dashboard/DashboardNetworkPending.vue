<script setup lang="ts">
import { computed } from 'vue'
import type { NetworkPayments, PrizesSummary } from '@/js/types/common/dashboard/dashboard'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

const props = defineProps<{
  payments?: NetworkPayments
  prizes?:   PrizesSummary
  /**
   * Cola de revisión (solo el comercio revisa). Sin ella, el bloque de
   * pagos es informativo: la agencia ve cuánto espera su red.
   */
  reviewHref?: string
}>()

const pending  = computed(() => props.payments?.pending_count ?? 0)
const rejected = computed(() => props.payments?.rejected_count ?? 0)
const prizes   = computed(() => props.prizes?.count ?? 0)
const isEmpty  = computed(() => pending.value === 0 && rejected.value === 0 && prizes.value === 0)

// Una cola de más de 2 h ya hace esperar al comprador: se resalta.
const isStale = computed(() => {
  const at = props.payments?.oldest_pending_at
  return !!at && Date.now() - new Date(at).getTime() > 2 * 3_600_000
})

function sinceLabel(iso: string | null | undefined): string | null {
  if (!iso) return null
  const minutes = Math.floor((Date.now() - new Date(iso).getTime()) / 60_000)
  if (minutes < 60) return `hace ${Math.max(minutes, 1)} min`
  if (minutes < 1440) return `hace ${Math.floor(minutes / 60)} h`
  return `hace ${Math.floor(minutes / 1440)} d`
}
</script>

<template>
  <div v-if="!isEmpty" class="np-list">
    <component
      :is="reviewHref ? 'a' : 'div'"
      v-if="pending > 0"
      :href="reviewHref"
      class="np-item np-item--info"
      :class="{ 'np-item--stale': isStale, 'np-item--link': reviewHref }"
    >
      <span class="np-item__icon"><i class="fas fa-hourglass-half"></i></span>
      <span class="np-item__body">
        <span class="np-item__label">{{ reviewHref ? 'Pagos por revisar' : 'Pagos de tu red en revisión' }}</span>
        <span class="np-item__hint">
          <template v-if="reviewHref">Los compradores no ven sus cartones hasta que apruebes.</template>
          <template v-else>Los revisa tu comercializadora.</template>
          <template v-if="sinceLabel(payments?.oldest_pending_at)"> El más antiguo {{ sinceLabel(payments?.oldest_pending_at) }}.</template>
        </span>
      </span>
      <span class="np-item__figures">
        <span class="np-item__count">{{ pending }}</span>
        <span v-if="payments?.pending_amount" class="np-item__amount">{{ payments.pending_amount }}</span>
      </span>
    </component>

    <div v-if="rejected > 0" class="np-item np-item--danger">
      <span class="np-item__icon"><i class="fas fa-rotate-left"></i></span>
      <span class="np-item__body">
        <span class="np-item__label">Pagos rechazados (7 días)</span>
        <span class="np-item__hint">Cada vendedor debe devolverle el dinero a su comprador.</span>
      </span>
      <span class="np-item__figures"><span class="np-item__count">{{ rejected }}</span></span>
    </div>

    <a v-if="prizes > 0" href="/bingo/winners" class="np-item np-item--warning np-item--link">
      <span class="np-item__icon"><i class="fas fa-gift"></i></span>
      <span class="np-item__body">
        <span class="np-item__label">Premios por entregar</span>
        <span class="np-item__hint">Ganados con cartones de tu red; el comprador espera su pago.</span>
      </span>
      <span class="np-item__figures">
        <span class="np-item__count">{{ prizes }}</span>
        <span v-if="props.prizes?.amount" class="np-item__amount">{{ props.prizes.amount }}</span>
      </span>
    </a>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-circle-check"
    title="Todo al día"
    description="No hay pagos en revisión, rechazos recientes ni premios por entregar en tu red."
    compact
  />
</template>

<style scoped>
.np-list { display: flex; flex-direction: column; gap: 0.5rem; }

.np-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.65rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  border-left: 3px solid transparent;
  text-decoration: none; color: inherit;
  transition: box-shadow 0.15s;
}
.np-item--link:hover { box-shadow: var(--ds-card-hover-shadow); text-decoration: none; }
.np-item--info    { border-left-color: #3b82f6; }
.np-item--danger  { border-left-color: #ef4444; }
.np-item--warning { border-left-color: #f59e0b; }
.np-item--stale   { border-left-color: #dc2626; }

.np-item__icon {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: var(--ds-surface); font-size: 0.85rem;
}
.np-item--info .np-item__icon    { color: #3b82f6; }
.np-item--danger .np-item__icon  { color: #ef4444; }
.np-item--warning .np-item__icon { color: #f59e0b; }
.np-item--stale .np-item__icon   { color: #dc2626; }

.np-item__body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.np-item__label { font-size: 0.85rem; font-weight: 700; color: var(--ds-text); }
.np-item__hint { font-size: 0.72rem; color: var(--ds-text-muted); }

.np-item__figures { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; }
.np-item__count { font-size: 1.05rem; font-weight: 800; color: var(--ds-text); line-height: 1.1; }
.np-item__amount { font-size: 0.72rem; color: var(--ds-text-2); white-space: nowrap; }
</style>
