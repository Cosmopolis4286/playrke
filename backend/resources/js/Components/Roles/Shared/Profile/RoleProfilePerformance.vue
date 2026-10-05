<script setup lang="ts">
import { computed } from 'vue'
import RpCard from './RpCard.vue'
import DashboardTrendChart from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
import type { AccountRole, RoleProfileKpi, RoleProfilePerformance } from '@/js/types/roles/shared/role-profile-common'
import { formatAmount, formatDate, formatInteger } from '@/js/composables/roles/shared/profile/useRoleProfile'

type SellingRole = Exclude<AccountRole, 'treasurer'>

const props = defineProps<{
  performance: RoleProfilePerformance
  role: SellingRole
}>()

/**
 * Lo que cobra cada rol: la red comercial, su parte del reparto en cascada
 * (CommissionCalculationService::networkSplit()); el Customer, la comisión de
 * sala de las salas que crea.
 */
const COMMISSION_LABEL: Record<SellingRole, { label: string; hint: string }> = {
  seller:     { label: 'Mi comisión',       hint: 'Tu % sobre cada venta' },
  subsidiary: { label: 'Margen de agencia', hint: 'Tu % menos el de cada vendedor' },
  commerce:   { label: 'Comisión de red',   hint: 'Tu % menos el de agencias y vendedores' },
  customer:   { label: 'Comisión de sala',  hint: 'De las ventas en tus salas' },
}

const salesLabel = computed(() => (props.role === 'customer' ? 'Vendido en tus salas' : 'Monto vendido'))

const symbol = computed(() => props.performance.currency?.symbol ?? '')
const hasSales = computed(() => props.performance.lifetime.lots > 0)

const tiles = computed(() => {
  const k = props.performance.kpis
  return [
    { key: 'sales', icon: 'fa-sack-dollar', label: salesLabel.value, value:`${symbol.value} ${formatAmount(k.sales.value)}`, kpi: k.sales, hint: props.performance.currency?.iso ?? '' },
    { key: 'commission', icon: 'fa-hand-holding-dollar', label: COMMISSION_LABEL[props.role].label, value: `${symbol.value} ${formatAmount(k.commission.value)}`, kpi: k.commission, hint: COMMISSION_LABEL[props.role].hint, highlight: true },
    { key: 'cards', icon: 'fa-ticket', label: 'Cartones', value: formatInteger(k.cards.value), kpi: k.cards, hint: 'Todas las monedas' },
    { key: 'lots', icon: 'fa-receipt', label: 'Ventas', value: formatInteger(k.lots.value), kpi: k.lots, hint: 'Operaciones registradas' },
  ]
})

function trend(kpi: RoleProfileKpi): { cls: string; icon: string; text: string } {
  if (kpi.change_pct === null) {
    return kpi.value > 0
      ? { cls: 'rp-trend--new', icon: 'fa-star', text: 'Nuevo' }
      : { cls: 'rp-trend--flat', icon: 'fa-minus', text: 'Sin actividad' }
  }
  if (kpi.change_pct > 0) return { cls: 'rp-trend--up', icon: 'fa-arrow-trend-up', text: `+${kpi.change_pct}%` }
  if (kpi.change_pct < 0) return { cls: 'rp-trend--down', icon: 'fa-arrow-trend-down', text: `${kpi.change_pct}%` }
  return { cls: 'rp-trend--flat', icon: 'fa-equals', text: '0%' }
}
</script>

<template>
  <RpCard title="Desempeño" icon="fa-chart-line" :subtitle="`${performance.period_label} · vs. mismo período del mes anterior`">
    <div class="rp-kpis">
      <article v-for="tile in tiles" :key="tile.key" :class="['rp-kpi', { 'rp-kpi--highlight': tile.highlight }]">
        <div class="rp-kpi__top">
          <span class="rp-kpi__label"><i :class="['fas', tile.icon]"></i>{{ tile.label }}</span>
        </div>
        <div class="rp-kpi__value">{{ tile.value }}</div>
        <div class="rp-kpi__bottom">
          <span :class="['rp-trend', trend(tile.kpi).cls]">
            <i :class="['fas', trend(tile.kpi).icon]"></i>{{ trend(tile.kpi).text }}
          </span>
          <span class="rp-kpi__hint">{{ tile.hint }}</span>
        </div>
      </article>
    </div>

    <p v-if="performance.other_currencies.length" class="rp-other-currencies">
      <i class="fas fa-coins"></i>
      También vendiste en:
      <span v-for="c in performance.other_currencies" :key="c.iso" class="rp-other-currencies__item">
        {{ c.symbol }} {{ formatAmount(c.sales) }} {{ c.iso }}
        <small>(comisión {{ c.symbol }} {{ formatAmount(c.commission) }})</small>
      </span>
    </p>

    <div class="rp-chart">
      <div class="rp-chart__head">
        <span class="rp-chart__title">Cartones vendidos · últimos 14 días</span>
      </div>
      <DashboardTrendChart :data="performance.trend" label="Cartones vendidos" />
    </div>

    <dl v-if="hasSales" class="rp-lifetime">
      <div>
        <dt>Cartones históricos</dt>
        <dd>{{ formatInteger(performance.lifetime.cards) }}</dd>
      </div>
      <div>
        <dt>Ventas históricas</dt>
        <dd>{{ formatInteger(performance.lifetime.lots) }}</dd>
      </div>
      <div>
        <dt>Comisión acumulada</dt>
        <dd>{{ symbol }} {{ formatAmount(performance.lifetime.commission) }}</dd>
      </div>
      <div v-if="performance.lifetime.first_sale_at">
        <dt>Primera venta</dt>
        <dd>{{ formatDate(performance.lifetime.first_sale_at) }}</dd>
      </div>
    </dl>
    <p v-else class="rp-empty">
      <i class="fas fa-seedling"></i>
      Todavía no hay ventas registradas. Aquí verás tu evolución en cuanto se venda el primer cartón.
    </p>
  </RpCard>
</template>

<style scoped>
.rp-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; }
.rp-kpi {
  display: flex; flex-direction: column; gap: 0.35rem;
  padding: 0.85rem 0.95rem;
  border-radius: 12px;
  background: var(--ds-surface-2);
  border: 1px solid transparent;
  min-width: 0;
}
.rp-kpi--highlight {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.16), rgba(118, 75, 162, 0.14));
  border-color: rgba(124, 140, 240, 0.35);
}
.rp-kpi__label {
  display: inline-flex; align-items: center; gap: 0.4rem;
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  color: var(--ds-text-muted);
}
.rp-kpi__label i { font-size: 0.72rem; color: #7c8cf0; }
.rp-kpi__value {
  font-size: 1.25rem; font-weight: 800; color: var(--ds-text);
  font-variant-numeric: tabular-nums;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rp-kpi__bottom { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem 0.5rem; }
.rp-kpi__hint { font-size: 0.68rem; color: var(--ds-text-muted); }

.rp-trend {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 1px 8px; border-radius: 999px;
  font-size: 0.68rem; font-weight: 700;
}
.rp-trend i { font-size: 0.62rem; }
.rp-trend--up   { background: rgba(22, 163, 74, 0.15); color: #22c55e; }
.rp-trend--down { background: rgba(225, 29, 72, 0.15); color: #f43f5e; }
.rp-trend--new  { background: rgba(102, 126, 234, 0.16); color: #7c8cf0; }
.rp-trend--flat { background: var(--ds-surface); color: var(--ds-text-muted); }

.rp-other-currencies {
  display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem 0.75rem;
  margin: 0.85rem 0 0; font-size: 0.78rem; color: var(--ds-text-2);
}
.rp-other-currencies i { color: var(--ds-text-muted); }
.rp-other-currencies__item { font-weight: 700; color: var(--ds-text); }
.rp-other-currencies__item small { font-weight: 500; color: var(--ds-text-muted); }

.rp-chart { margin-top: 1rem; }
.rp-chart__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem; }
.rp-chart__title { font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }

.rp-lifetime {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem;
  margin: 0.75rem 0 0; padding-top: 0.85rem;
  border-top: 1px dashed var(--ds-border);
}
.rp-lifetime dt { font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-lifetime dd { margin: 0.15rem 0 0; font-size: 0.95rem; font-weight: 700; color: var(--ds-text); font-variant-numeric: tabular-nums; }

.rp-empty {
  display: flex; align-items: center; gap: 0.5rem;
  margin: 0.75rem 0 0; padding: 0.75rem 0.9rem;
  border-radius: 10px; background: var(--ds-surface-2);
  font-size: 0.8rem; color: var(--ds-text-2);
}
.rp-empty i { color: #22c55e; }

@media (max-width: 1199.98px) {
  .rp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
  .rp-kpi { padding: 0.7rem 0.75rem; }
  .rp-kpi__value { font-size: 1.05rem; }
  .rp-kpi__hint { display: none; }
}
@media (max-width: 340px) {
  .rp-kpis { grid-template-columns: 1fr; }
}
</style>
