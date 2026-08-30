<script setup lang="ts">
import type { PlayerActivitySummary } from '@js/composables/roles/superadmin/dashboard/usePlayerActivitySummary'

defineProps<{
  summary: PlayerActivitySummary | null
  loading: boolean
  error: string | null
}>()

defineEmits<{
  refresh: []
}>()
</script>

<template>
  <div class="activity-section">

    <div class="activity-section__header">
      <div class="activity-section__title">
        <i class="fas fa-user-clock activity-section__icon"></i>
        <div>
          <h2 class="activity-section__h2">Actividad de jugadores</h2>
          <p class="activity-section__sub">Jugadores activos por ventana y clientes recurrentes por vendedor</p>
        </div>
      </div>
      <button class="activity-section__refresh" :class="{ 'activity-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div v-if="loading && !summary" class="activity-state activity-state--loading">
      <span class="activity-spinner"></span><span>Cargando actividad...</span>
    </div>
    <div v-else-if="error" class="activity-state activity-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <template v-else-if="summary">

      <div class="activity-kpis">
        <div class="activity-kpi activity-kpi--daily">
          <div class="activity-kpi__icon"><i class="fas fa-calendar-day"></i></div>
          <div>
            <p class="activity-kpi__label">Activos hoy</p>
            <p class="activity-kpi__value">{{ summary.active_players.daily }}</p>
          </div>
        </div>
        <div class="activity-kpi activity-kpi--weekly">
          <div class="activity-kpi__icon"><i class="fas fa-calendar-week"></i></div>
          <div>
            <p class="activity-kpi__label">Activos esta semana</p>
            <p class="activity-kpi__value">{{ summary.active_players.weekly }}</p>
          </div>
        </div>
        <div class="activity-kpi activity-kpi--monthly">
          <div class="activity-kpi__icon"><i class="fas fa-calendar-alt"></i></div>
          <div>
            <p class="activity-kpi__label">Activos este mes</p>
            <p class="activity-kpi__value">{{ summary.active_players.monthly }}</p>
          </div>
        </div>
        <div class="activity-kpi activity-kpi--conversion">
          <div class="activity-kpi__icon"><i class="fas fa-user-plus"></i></div>
          <div>
            <p class="activity-kpi__label">Invitados → registrados</p>
            <p class="activity-kpi__value">
              {{ summary.external_player_conversion.converted }}/{{ summary.external_player_conversion.total_external_players }}
              <small>({{ summary.external_player_conversion.conversion_rate }}%)</small>
            </p>
          </div>
        </div>
      </div>

      <div class="activity-block">
        <h3 class="activity-block__title">Clientes recurrentes por vendedor</h3>
        <div v-if="summary.recurring_by_seller.length" class="activity-table-wrap">
          <table class="activity-table">
            <thead>
              <tr>
                <th>Vendedor</th>
                <th class="activity-table__num">Compradores</th>
                <th class="activity-table__num">Recurrentes</th>
                <th class="activity-table__num">% Recurrencia</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summary.recurring_by_seller" :key="row.seller_user_id">
                <td>{{ row.seller_name }}</td>
                <td class="activity-table__num">{{ row.total_buyers }}</td>
                <td class="activity-table__num">{{ row.recurring_buyers }}</td>
                <td class="activity-table__num">
                  <span class="activity-rate-badge" :class="{ 'activity-rate-badge--good': row.recurring_rate >= 30 }">
                    {{ row.recurring_rate }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="activity-state activity-state--empty">
          <i class="fas fa-inbox"></i> Sin ventas de vendedores registradas todavía.
        </div>
      </div>

    </template>

  </div>
</template>

<style scoped>
.activity-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.activity-section__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
.activity-section__title { display: flex; align-items: center; gap: 0.875rem; }

.activity-section__icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #f7971e, #ffd200);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}

.activity-section__h2 { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); margin: 0 0 2px; }
.activity-section__sub { font-size: 0.78rem; color: var(--ds-text-2); margin: 0; }

.activity-section__refresh {
  display: flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: #f7971e;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.activity-section__refresh:hover:not(:disabled) { background: rgba(247,151,30,.15); border-color: #f7971e; }
.activity-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.activity-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.activity-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
@media (max-width: 800px) { .activity-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .activity-kpis { grid-template-columns: 1fr; } }

.activity-kpi { display: flex; align-items: center; gap: 0.875rem; padding: 0.875rem 1rem; border-radius: 10px; }
.activity-kpi--daily      { background: rgba(37,99,235,.1); }
.activity-kpi--weekly     { background: rgba(16,185,129,.1); }
.activity-kpi--monthly    { background: rgba(124,58,237,.1); }
.activity-kpi--conversion { background: rgba(247,151,30,.1); }

.activity-kpi__icon {
  width: 38px; height: 38px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; flex-shrink: 0;
}
.activity-kpi--daily      .activity-kpi__icon { background: rgba(37,99,235,.2);  color: #60a5fa; }
.activity-kpi--weekly     .activity-kpi__icon { background: rgba(16,185,129,.2); color: #34d399; }
.activity-kpi--monthly    .activity-kpi__icon { background: rgba(124,58,237,.2); color: #a78bfa; }
.activity-kpi--conversion .activity-kpi__icon { background: rgba(247,151,30,.2); color: #fbbf24; }
.activity-kpi--conversion .activity-kpi__value small { font-size: 0.68rem; font-weight: 500; color: var(--ds-text-2); }

.activity-kpi__label { font-size: 0.73rem; color: var(--ds-text-2); margin: 0 0 2px; font-weight: 500; }
.activity-kpi__value { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); margin: 0; }

.activity-block__title {
  font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
  color: var(--ds-text-2); margin: 0 0 0.75rem;
}

.activity-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--ds-border); }
.activity-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.activity-table thead tr { background: var(--ds-surface-2); border-bottom: 1px solid var(--ds-border); }
.activity-table th {
  padding: 0.65rem 0.875rem; font-weight: 600; color: var(--ds-text-2); text-align: left;
  font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.activity-table td { padding: 0.65rem 0.875rem; border-bottom: 1px solid var(--ds-border); color: var(--ds-text); vertical-align: middle; }
.activity-table tbody tr:last-child td { border-bottom: none; }
.activity-table tbody tr:hover td { background: var(--ds-surface-2); }
.activity-table__num { text-align: right; }

.activity-rate-badge {
  display: inline-block; background: var(--ds-surface-2); color: var(--ds-text-2);
  font-size: 0.73rem; font-weight: 700; padding: 2px 8px; border-radius: 20px;
}
.activity-rate-badge--good { background: rgba(16,185,129,.15); color: #34d399; }

.activity-state { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 2rem; color: var(--ds-text-2); font-size: 0.875rem; }
.activity-state--error { color: #ef4444; }
.activity-state--empty i { font-size: 1.3rem; color: var(--ds-text-muted); }

.activity-spinner {
  width: 20px; height: 20px; border: 3px solid var(--ds-border-2); border-top-color: #f7971e;
  border-radius: 50%; animation: spin 0.8s linear infinite; flex-shrink: 0;
}
</style>
