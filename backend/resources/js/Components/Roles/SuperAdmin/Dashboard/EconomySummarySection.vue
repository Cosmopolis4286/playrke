<script setup lang="ts">
import type { EconomySummary } from '@js/composables/roles/superadmin/dashboard/useEconomySummary'

defineProps<{
  summary: EconomySummary | null
  loading: boolean
  error: string | null
  formatUsd: (v: number, decimals?: number) => string
}>()

defineEmits<{
  refresh: []
}>()

const gameIcon: Record<string, string> = {
  bingo: 'fa-th',
  trivia: 'fa-brain',
}
</script>

<template>
  <div class="economy-section">

    <div class="economy-section__header">
      <div class="economy-section__title">
        <i class="fas fa-chart-line economy-section__icon"></i>
        <div>
          <h2 class="economy-section__h2">Resumen económico</h2>
          <p class="economy-section__sub">Dinero ingresado, cruzado por juego, moneda y comercializadora</p>
        </div>
      </div>
      <button class="economy-section__refresh" :class="{ 'economy-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div v-if="loading && !summary" class="economy-state economy-state--loading">
      <span class="economy-spinner"></span><span>Cargando resumen económico...</span>
    </div>
    <div v-else-if="error" class="economy-state economy-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <template v-else-if="summary">

      <!-- KPIs totales -->
      <div class="economy-kpis">
        <div class="economy-kpi economy-kpi--income">
          <div class="economy-kpi__icon"><i class="fas fa-arrow-down"></i></div>
          <div>
            <p class="economy-kpi__label">Dinero ingresado</p>
            <p class="economy-kpi__value">$ {{ formatUsd(summary.totals.total_income) }}</p>
          </div>
        </div>
        <div class="economy-kpi economy-kpi--prizes">
          <div class="economy-kpi__icon"><i class="fas fa-trophy"></i></div>
          <div>
            <p class="economy-kpi__label">Premios pagados</p>
            <p class="economy-kpi__value">$ {{ formatUsd(summary.totals.total_prizes_paid) }}</p>
          </div>
        </div>
        <div class="economy-kpi economy-kpi--deposits">
          <div class="economy-kpi__icon"><i class="fas fa-wallet"></i></div>
          <div>
            <p class="economy-kpi__label">Depósitos</p>
            <p class="economy-kpi__value">$ {{ formatUsd(summary.totals.total_deposits) }}</p>
          </div>
        </div>
        <div class="economy-kpi economy-kpi--withdrawals">
          <div class="economy-kpi__icon"><i class="fas fa-arrow-up"></i></div>
          <div>
            <p class="economy-kpi__label">Retiros</p>
            <p class="economy-kpi__value">$ {{ formatUsd(summary.totals.total_withdrawals) }}</p>
          </div>
        </div>
        <div class="economy-kpi economy-kpi--active">
          <div class="economy-kpi__icon"><i class="fas fa-users"></i></div>
          <div>
            <p class="economy-kpi__label">Activos (7 días)</p>
            <p class="economy-kpi__value">{{ summary.totals.active_players_7d }}</p>
          </div>
        </div>
      </div>

      <!-- Por juego -->
      <div class="economy-block">
        <h3 class="economy-block__title">Por juego</h3>
        <div class="economy-game-grid">
          <div v-for="row in summary.by_game" :key="row.game" class="economy-game-card">
            <div class="economy-game-card__header">
              <i class="fas economy-game-card__icon" :class="gameIcon[row.game] ?? 'fa-gamepad'"></i>
              <span>{{ row.game_label }}</span>
            </div>
            <p class="economy-game-card__income">$ {{ formatUsd(row.income) }}</p>
            <p class="economy-game-card__commission">
              <template v-if="row.commission !== null">Comisión: $ {{ formatUsd(row.commission) }}</template>
              <template v-else><span class="text-muted">Comisión no desglosable desde este resumen</span></template>
            </p>
          </div>
        </div>
      </div>

      <!-- Por moneda -->
      <div class="economy-block">
        <h3 class="economy-block__title">Por moneda</h3>
        <div v-if="summary.by_currency.length" class="economy-table-wrap">
          <table class="economy-table">
            <thead>
              <tr>
                <th>Moneda</th>
                <th class="economy-table__num">Bingo</th>
                <th class="economy-table__num">Trivia</th>
                <th class="economy-table__num">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summary.by_currency" :key="row.currency_id">
                <td><span class="economy-currency-badge">{{ row.symbol }} {{ row.iso_code }}</span></td>
                <td class="economy-table__num">{{ formatUsd(row.bingo_income) }}</td>
                <td class="economy-table__num">{{ formatUsd(row.trivia_income) }}</td>
                <td class="economy-table__num economy-table__total">{{ formatUsd(row.bingo_income + row.trivia_income) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="economy-state economy-state--empty">
          <i class="fas fa-inbox"></i> Sin ventas registradas todavía.
        </div>
      </div>

      <!-- Por comercializadora -->
      <div class="economy-block">
        <h3 class="economy-block__title">Por comercializadora (Bingo)</h3>
        <div v-if="summary.by_commerce.length" class="economy-table-wrap">
          <table class="economy-table">
            <thead>
              <tr>
                <th>Comercializadora</th>
                <th class="economy-table__num">Volumen vendido</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summary.by_commerce" :key="row.commerce_id">
                <td>{{ row.commerce_name }}</td>
                <td class="economy-table__num">$ {{ formatUsd(row.income) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="economy-state economy-state--empty">
          <i class="fas fa-inbox"></i> Sin ventas de Bingo registradas todavía.
        </div>
      </div>

    </template>

  </div>
</template>

<style scoped>
.economy-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.economy-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.economy-section__title { display: flex; align-items: center; gap: 0.875rem; }

.economy-section__icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #11998e, #38ef7d);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}

.economy-section__h2 { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); margin: 0 0 2px; }
.economy-section__sub { font-size: 0.78rem; color: var(--ds-text-2); margin: 0; }

.economy-section__refresh {
  display: flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: #11998e;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.economy-section__refresh:hover:not(:disabled) { background: rgba(17,153,142,.15); border-color: #11998e; }
.economy-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.economy-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.economy-kpis {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .economy-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .economy-kpis { grid-template-columns: 1fr; } }

.economy-kpi { display: flex; align-items: center; gap: 0.875rem; padding: 0.875rem 1rem; border-radius: 10px; }
.economy-kpi--income      { background: rgba(37,99,235,.1); }
.economy-kpi--prizes      { background: rgba(245,158,11,.1); }
.economy-kpi--deposits    { background: rgba(16,185,129,.1); }
.economy-kpi--withdrawals { background: rgba(239,68,68,.1); }
.economy-kpi--active      { background: rgba(124,58,237,.1); }

.economy-kpi__icon {
  width: 38px; height: 38px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; flex-shrink: 0;
}
.economy-kpi--income      .economy-kpi__icon { background: rgba(37,99,235,.2);  color: #60a5fa; }
.economy-kpi--prizes      .economy-kpi__icon { background: rgba(245,158,11,.2); color: #fbbf24; }
.economy-kpi--deposits    .economy-kpi__icon { background: rgba(16,185,129,.2); color: #34d399; }
.economy-kpi--withdrawals .economy-kpi__icon { background: rgba(239,68,68,.2);  color: #f87171; }
.economy-kpi--active      .economy-kpi__icon { background: rgba(124,58,237,.2); color: #a78bfa; }

.economy-kpi__label { font-size: 0.7rem; color: var(--ds-text-2); margin: 0 0 2px; font-weight: 500; }
.economy-kpi__value { font-size: 0.98rem; font-weight: 700; color: var(--ds-text); margin: 0; }

.economy-block { margin-bottom: 1.5rem; }
.economy-block:last-child { margin-bottom: 0; }
.economy-block__title {
  font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
  color: var(--ds-text-2); margin: 0 0 0.75rem;
}

.economy-game-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; }
.economy-game-card {
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  padding: 0.875rem 1rem;
  background: var(--ds-surface-2);
}
.economy-game-card__header { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--ds-text); font-size: 0.85rem; margin-bottom: 0.5rem; }
.economy-game-card__icon { color: #11998e; }
.economy-game-card__income { font-size: 1.15rem; font-weight: 700; color: var(--ds-text); margin: 0 0 0.25rem; }
.economy-game-card__commission { font-size: 0.75rem; color: var(--ds-text-2); margin: 0; }

.economy-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--ds-border); }
.economy-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.economy-table thead tr { background: var(--ds-surface-2); border-bottom: 1px solid var(--ds-border); }
.economy-table th {
  padding: 0.65rem 0.875rem; font-weight: 600; color: var(--ds-text-2); text-align: left;
  font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.economy-table td { padding: 0.65rem 0.875rem; border-bottom: 1px solid var(--ds-border); color: var(--ds-text); vertical-align: middle; }
.economy-table tbody tr:last-child td { border-bottom: none; }
.economy-table tbody tr:hover td { background: var(--ds-surface-2); }
.economy-table__num { text-align: right; }
.economy-table__total { font-weight: 700; color: #11998e; }

.economy-currency-badge {
  display: inline-block; background: rgba(17,153,142,.15); color: #34d399;
  font-size: 0.73rem; font-weight: 700; padding: 2px 8px; border-radius: 20px;
}

.economy-state { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 2rem; color: var(--ds-text-2); font-size: 0.875rem; }
.economy-state--error { color: #ef4444; }
.economy-state--empty i { font-size: 1.3rem; color: var(--ds-text-muted); }

.economy-spinner {
  width: 20px; height: 20px; border: 3px solid var(--ds-border-2); border-top-color: #11998e;
  border-radius: 50%; animation: spin 0.8s linear infinite; flex-shrink: 0;
}
</style>
