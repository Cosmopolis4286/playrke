<script setup lang="ts">
/**
 * "Mi Tesorería": desempeño de revisión de movimientos de billetera — el
 * tesorero no vende, su trabajo es aprobar/rechazar depósitos, retiros, etc.
 * (wallet_movements.processed_by) y atender la cola de pendientes.
 */
import { computed } from 'vue'
import RpCard from './RpCard.vue'
import DashboardTrendChart from '@/js/Components/Common/Dashboard/DashboardTrendChart.vue'
import type { TreasurerProfile, TreasuryKpi } from '@/js/types/roles/shared/role-profile-common'
import { formatDate, formatInteger, timeAgo } from '@/js/composables/roles/shared/profile/useRoleProfile'

const props = defineProps<{ treasury: TreasurerProfile['treasury'] }>()

const tiles = computed(() => [
  { key: 'processed', icon: 'fa-list-check', label: 'Procesados', kpi: props.treasury.kpis.processed, highlight: true },
  { key: 'approved', icon: 'fa-circle-check', label: 'Aprobados', kpi: props.treasury.kpis.approved },
  { key: 'rejected', icon: 'fa-circle-xmark', label: 'Rechazados', kpi: props.treasury.kpis.rejected },
])

function trend(kpi: TreasuryKpi) {
  if (kpi.change_pct === null) {
    return kpi.value > 0 ? { cls: 'rp-trend--new', icon: 'fa-star', text: 'Nuevo' } : { cls: 'rp-trend--flat', icon: 'fa-minus', text: 'Sin actividad' }
  }
  if (kpi.change_pct > 0) return { cls: 'rp-trend--up', icon: 'fa-arrow-trend-up', text: `+${kpi.change_pct}%` }
  if (kpi.change_pct < 0) return { cls: 'rp-trend--down', icon: 'fa-arrow-trend-down', text: `${kpi.change_pct}%` }
  return { cls: 'rp-trend--flat', icon: 'fa-equals', text: '0%' }
}

const maxType = computed(() => Math.max(1, ...props.treasury.top_types.map((t) => t.count)))
</script>

<template>
  <RpCard title="Gestión de movimientos" icon="fa-scale-balanced" :subtitle="`${treasury.period_label} · vs. mismo período del mes anterior`">
    <template #actions>
      <a href="/wallet-movements" class="rp-link">Ir a la cola <i class="fas fa-arrow-right"></i></a>
    </template>

    <div class="rp-tr-kpis">
      <article v-for="t in tiles" :key="t.key" :class="['rp-tr-kpi', { 'rp-tr-kpi--highlight': t.highlight }]">
        <span class="rp-tr-kpi__label"><i :class="['fas', t.icon]"></i>{{ t.label }}</span>
        <span class="rp-tr-kpi__value">{{ formatInteger(t.kpi.value) }}</span>
        <span :class="['rp-trend', trend(t.kpi).cls]"><i :class="['fas', trend(t.kpi).icon]"></i>{{ trend(t.kpi).text }}</span>
      </article>
      <article class="rp-tr-kpi">
        <span class="rp-tr-kpi__label"><i class="fas fa-percent"></i>Aprobación</span>
        <span class="rp-tr-kpi__value">{{ treasury.approval_rate === null ? '—' : `${treasury.approval_rate}%` }}</span>
        <span class="rp-tr-kpi__hint">Aprobados / resueltos</span>
      </article>
    </div>

    <div class="rp-tr-queue">
      <div class="rp-tr-queue__item">
        <span class="rp-tr-queue__value">{{ formatInteger(treasury.queue.pending) }}</span>
        <span class="rp-tr-queue__label">Pendientes en total</span>
      </div>
      <div class="rp-tr-queue__item">
        <span class="rp-tr-queue__value">{{ formatInteger(treasury.queue.unassigned) }}</span>
        <span class="rp-tr-queue__label">Sin asignar</span>
      </div>
      <div :class="['rp-tr-queue__item', { 'rp-tr-queue__item--mine': treasury.queue.assigned_me > 0 }]">
        <span class="rp-tr-queue__value">{{ formatInteger(treasury.queue.assigned_me) }}</span>
        <span class="rp-tr-queue__label">Tomados por ti</span>
      </div>
      <div class="rp-tr-queue__item">
        <span class="rp-tr-queue__value rp-tr-queue__value--small">{{ treasury.queue.oldest_at ? timeAgo(treasury.queue.oldest_at) : '—' }}</span>
        <span class="rp-tr-queue__label">Pendiente más antiguo</span>
      </div>
    </div>

    <div class="rp-tr-chart">
      <span class="rp-tr-section-title">Movimientos procesados · últimos 14 días</span>
      <DashboardTrendChart :data="treasury.trend" label="Procesados" />
    </div>

    <div v-if="treasury.top_types.length" class="rp-tr-types">
      <span class="rp-tr-section-title">Lo que más revisas este mes</span>
      <div v-for="t in treasury.top_types" :key="t.type" class="rp-tr-type">
        <div class="rp-tr-type__line">
          <span>{{ t.label }}</span>
          <strong>{{ formatInteger(t.count) }}</strong>
        </div>
        <div class="rp-tr-type__bar"><span :style="{ width: `${(t.count / maxType) * 100}%` }"></span></div>
      </div>
    </div>

    <dl v-if="treasury.lifetime.processed" class="rp-tr-lifetime">
      <div><dt>Procesados históricos</dt><dd>{{ formatInteger(treasury.lifetime.processed) }}</dd></div>
      <div v-if="treasury.lifetime.first_at"><dt>Primer movimiento</dt><dd>{{ formatDate(treasury.lifetime.first_at) }}</dd></div>
      <div v-if="treasury.lifetime.last_at"><dt>Último procesado</dt><dd>{{ timeAgo(treasury.lifetime.last_at) }}</dd></div>
    </dl>
    <p v-else class="rp-empty"><i class="fas fa-seedling"></i>Todavía no has procesado movimientos. Aquí verás tu actividad en cuanto resuelvas el primero.</p>
  </RpCard>
</template>

<style scoped>
.rp-link { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: #7c8cf0; text-decoration: none; }
.rp-link:hover { text-decoration: underline; color: #7c8cf0; }

.rp-tr-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; }
.rp-tr-kpi { display: flex; flex-direction: column; gap: 0.35rem; padding: 0.85rem 0.95rem; border-radius: 12px; background: var(--ds-surface-2); border: 1px solid transparent; min-width: 0; }
.rp-tr-kpi--highlight { background: linear-gradient(135deg, rgba(102, 126, 234, 0.16), rgba(118, 75, 162, 0.14)); border-color: rgba(124, 140, 240, 0.35); }
.rp-tr-kpi__label { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-tr-kpi__label i { font-size: 0.72rem; color: #7c8cf0; }
.rp-tr-kpi__value { font-size: 1.35rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-tr-kpi__hint { font-size: 0.68rem; color: var(--ds-text-muted); }

.rp-trend { align-self: flex-start; display: inline-flex; align-items: center; gap: 0.3rem; padding: 1px 8px; border-radius: 999px; font-size: 0.68rem; font-weight: 700; }
.rp-trend i { font-size: 0.62rem; }
.rp-trend--up { background: rgba(22, 163, 74, 0.15); color: #22c55e; }
.rp-trend--down { background: rgba(225, 29, 72, 0.15); color: #f43f5e; }
.rp-trend--new { background: rgba(102, 126, 234, 0.16); color: #7c8cf0; }
.rp-trend--flat { background: var(--ds-surface); color: var(--ds-text-muted); }

.rp-tr-queue { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.6rem; margin-top: 0.85rem; }
.rp-tr-queue__item { display: flex; flex-direction: column; padding: 0.65rem 0.8rem; border-radius: 10px; border: 1px dashed var(--ds-border-2); }
.rp-tr-queue__item--mine { border-style: solid; border-color: rgba(217, 119, 6, 0.5); background: rgba(217, 119, 6, 0.08); }
.rp-tr-queue__value { font-size: 1.1rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-tr-queue__value--small { font-size: 0.92rem; }
.rp-tr-queue__label { font-size: 0.7rem; color: var(--ds-text-muted); }

.rp-tr-section-title { display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-tr-chart { margin-top: 1rem; }

.rp-tr-types { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.75rem; padding-top: 0.85rem; border-top: 1px dashed var(--ds-border); }
.rp-tr-type__line { display: flex; justify-content: space-between; gap: 0.5rem; font-size: 0.82rem; color: var(--ds-text-2); }
.rp-tr-type__line strong { color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-tr-type__bar { height: 5px; margin-top: 0.25rem; border-radius: 999px; background: var(--ds-surface-2); overflow: hidden; }
.rp-tr-type__bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #667eea, #764ba2); }

.rp-tr-lifetime { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem; margin: 0.85rem 0 0; padding-top: 0.85rem; border-top: 1px dashed var(--ds-border); }
.rp-tr-lifetime dt { font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.rp-tr-lifetime dd { margin: 0.15rem 0 0; font-size: 0.95rem; font-weight: 700; color: var(--ds-text); }

.rp-empty { display: flex; align-items: center; gap: 0.5rem; margin: 0.85rem 0 0; padding: 0.75rem 0.9rem; border-radius: 10px; background: var(--ds-surface-2); font-size: 0.8rem; color: var(--ds-text-2); }
.rp-empty i { color: #22c55e; }

@media (max-width: 1199.98px) {
  .rp-tr-kpis, .rp-tr-queue { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
