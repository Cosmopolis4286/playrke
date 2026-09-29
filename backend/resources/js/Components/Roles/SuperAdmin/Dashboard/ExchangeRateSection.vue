<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import type { ExchangeRateSummary } from '@js/composables/roles/superadmin/dashboard/useExchangeRateSummary'
import ExchangeRateTrendChart from '@/js/Components/Common/ExchangeRate/ExchangeRateTrendChart.vue'

defineProps<{
  summary: ExchangeRateSummary | null
  loading: boolean
  error: string | null
  formatDate: (iso: string | null) => string
}>()

defineEmits<{
  refresh: []
}>()

/**
 * Navegación Inertia (no window.location.href, ver auditoría del dominio
 * exchange-rate: Index.vue::goHistorical() rompía la SPA) hacia el
 * histórico completo de la moneda — el sparkline compacto de acá es solo
 * un vistazo, el detalle vive en /exchange-rate/historical/{currencyId}.
 */
function goToHistorical(currencyId: string): void {
  router.visit(`/exchange-rate/historical/${currencyId}`)
}
</script>

<template>
  <div class="er-section">

    <div class="er-section__header">
      <div class="er-section__title">
        <i class="fas fa-exchange-alt er-section__icon"></i>
        <div>
          <h2 class="er-section__h2">Tasas de cambio</h2>
          <p class="er-section__sub">Tasas oficiales activas por moneda</p>
        </div>
      </div>
      <button class="er-section__refresh" :class="{ 'er-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div v-if="loading && !summary" class="er-state er-state--loading">
      <span class="er-spinner"></span><span>Cargando tasas...</span>
    </div>
    <div v-else-if="error" class="er-state er-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="summary && summary.rates.length" class="er-list">
      <button
        v-for="rate in summary.rates"
        :key="rate.currency_id"
        type="button"
        class="er-item"
        :title="`Ver histórico completo de ${rate.currency_code}`"
        @click="goToHistorical(rate.currency_id)"
      >
        <div class="er-item__row">
          <span class="er-item__code">{{ rate.currency_code }}</span>
          <span class="er-item__value">{{ rate.symbol }} {{ rate.amount.toFixed(4) }}</span>
          <span v-if="rate.percentage" class="er-item__pct" :class="{ 'er-item__pct--up': rate.percentage > 0, 'er-item__pct--down': rate.percentage < 0 }">
            {{ rate.percentage > 0 ? '+' : '' }}{{ rate.percentage }}%
          </span>
          <span class="er-item__spark">
            <ExchangeRateTrendChart :data="rate.history" :height="36" compact />
          </span>
        </div>
        <div class="er-item__meta">
          <span class="er-item__source" :class="{ 'er-item__source--system': rate.source === 'system' }">
            <i :class="rate.source === 'system' ? 'fas fa-robot' : 'fas fa-user'"></i>
            {{ rate.source === 'system' ? 'Automático' : rate.user_name }}
          </span>
          <span class="er-item__date">{{ formatDate(rate.updated_at) }}</span>
        </div>
      </button>
    </div>

    <div v-else-if="summary && !summary.rates.length" class="er-state er-state--empty">
      <i class="fas fa-inbox"></i> Sin tasas de cambio oficiales configuradas.
    </div>

  </div>
</template>

<style scoped>
.er-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.er-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.er-section__title {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.er-section__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.er-section__h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 2px;
}

.er-section__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0;
}

.er-section__refresh {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: #667eea;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.er-section__refresh:hover:not(:disabled) { background: rgba(102,126,234,.15); border-color: #667eea; }
.er-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.er-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.er-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.er-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.6rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
  font-size: 0.85rem;
  /* Reset de estilos de <button> — es clickeable (abre el histórico), ver
     goToHistorical(). */
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  color: inherit;
  font-family: inherit;
  transition: background 0.15s;
}
.er-item:hover { background: var(--ds-border-2); }

.er-item__row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.er-item__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-left: 0.05rem;
}

.er-item__source {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
}
.er-item__source i { font-size: 0.68rem; }
/* 'Automático' (cronjob) en violeta acento — visualmente distinto de un
   nombre de usuario real, que se queda con el color muted por defecto. */
.er-item__source--system { color: #a78bfa; font-weight: 600; }

.er-item__spark {
  width: 90px;
  flex-shrink: 0;
}

.er-item__code {
  font-weight: 700;
  color: var(--ds-text);
  min-width: 4rem;
}

.er-item__value {
  flex: 1;
  font-variant-numeric: tabular-nums;
  color: var(--ds-text);
}

.er-item__pct {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  background: var(--ds-surface);
}
.er-item__pct--up   { color: #4ade80; }
.er-item__pct--down { color: #f87171; }

.er-item__date {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  white-space: nowrap;
}

.er-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}

.er-state--error { color: #ef4444; }
.er-state--empty i { font-size: 1.5rem; color: var(--ds-text-muted); }

.er-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
</style>
