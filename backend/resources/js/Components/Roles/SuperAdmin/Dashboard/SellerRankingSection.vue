<script setup lang="ts">
import type { SellerRanking } from '@js/composables/roles/superadmin/dashboard/useSellerRanking'

defineProps<{
  ranking: SellerRanking | null
  loading: boolean
  error: string | null
}>()

defineEmits<{
  refresh: []
}>()

function formatUsd(v: number): string {
  return v.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const medalIcon: Record<number, string> = {
  1: '🥇',
  2: '🥈',
  3: '🥉',
}
</script>

<template>
  <div class="sr-section">

    <div class="sr-section__header">
      <div class="sr-section__title">
        <i class="fas fa-ranking-star sr-section__icon"></i>
        <div>
          <h2 class="sr-section__h2">Ranking de vendedores</h2>
          <p class="sr-section__sub">Comisión generada este mes, meta ${{ ranking ? formatUsd(ranking.goal) : '—' }}</p>
        </div>
      </div>
      <button class="sr-section__refresh" :class="{ 'sr-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div v-if="loading && !ranking" class="sr-state sr-state--loading">
      <span class="sr-spinner"></span><span>Cargando ranking...</span>
    </div>
    <div v-else-if="error" class="sr-state sr-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="ranking && ranking.rows.length" class="sr-table-wrap">
      <table class="sr-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Vendedor</th>
            <th class="sr-table__num">Cartones</th>
            <th class="sr-table__num">Ventas</th>
            <th class="sr-table__num">Comisión</th>
            <th>Meta</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in ranking.rows" :key="row.seller_user_id">
            <td class="sr-table__rank">{{ medalIcon[row.rank] ?? row.rank }}</td>
            <td>{{ row.seller_name }}</td>
            <td class="sr-table__num">{{ row.cards_sold }}</td>
            <td class="sr-table__num">$ {{ formatUsd(row.sale_amount) }}</td>
            <td class="sr-table__num sr-table__commission">$ {{ formatUsd(row.commission_amount) }}</td>
            <td>
              <span class="sr-goal-badge" :class="{ 'sr-goal-badge--reached': row.goal_reached }">
                {{ row.goal_reached ? '✓ Alcanzada' : 'En progreso' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="sr-state sr-state--empty">
      <i class="fas fa-inbox"></i> Sin ventas de vendedores registradas este mes.
    </div>

  </div>
</template>

<style scoped>
.sr-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.sr-section__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
.sr-section__title { display: flex; align-items: center; gap: 0.875rem; }

.sr-section__icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}

.sr-section__h2 { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); margin: 0 0 2px; }
.sr-section__sub { font-size: 0.78rem; color: var(--ds-text-2); margin: 0; }

.sr-section__refresh {
  display: flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: #f59e0b;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.sr-section__refresh:hover:not(:disabled) { background: rgba(245,158,11,.15); border-color: #f59e0b; }
.sr-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.sr-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.sr-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--ds-border); }
.sr-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.sr-table thead tr { background: var(--ds-surface-2); border-bottom: 1px solid var(--ds-border); }
.sr-table th {
  padding: 0.65rem 0.875rem; font-weight: 600; color: var(--ds-text-2); text-align: left;
  font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.sr-table td { padding: 0.65rem 0.875rem; border-bottom: 1px solid var(--ds-border); color: var(--ds-text); vertical-align: middle; }
.sr-table tbody tr:last-child td { border-bottom: none; }
.sr-table tbody tr:hover td { background: var(--ds-surface-2); }
.sr-table__num { text-align: right; }
.sr-table__rank { font-size: 1rem; text-align: center; width: 40px; }
.sr-table__commission { color: #f59e0b !important; font-weight: 700; }

.sr-goal-badge {
  display: inline-block; background: var(--ds-surface-2); color: var(--ds-text-2);
  font-size: 0.72rem; font-weight: 600; padding: 2px 9px; border-radius: 20px; white-space: nowrap;
}
.sr-goal-badge--reached { background: rgba(16,185,129,.15); color: #34d399; }

.sr-state { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 2rem; color: var(--ds-text-2); font-size: 0.875rem; }
.sr-state--error { color: #ef4444; }
.sr-state--empty i { font-size: 1.3rem; color: var(--ds-text-muted); }

.sr-spinner {
  width: 20px; height: 20px; border: 3px solid var(--ds-border-2); border-top-color: #f59e0b;
  border-radius: 50%; animation: spin 0.8s linear infinite; flex-shrink: 0;
}
</style>
