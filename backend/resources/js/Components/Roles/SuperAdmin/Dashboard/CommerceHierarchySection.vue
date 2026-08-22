<script setup lang="ts">
import type { CommerceHierarchy } from '@js/composables/roles/superadmin/dashboard/useCommerceHierarchy'

defineProps<{
  hierarchy: CommerceHierarchy | null
  loading: boolean
  error: string | null
}>()

defineEmits<{
  refresh: []
}>()
</script>

<template>
  <div class="ch-section">

    <div class="ch-section__header">
      <div class="ch-section__title">
        <i class="fas fa-sitemap ch-section__icon"></i>
        <div>
          <h2 class="ch-section__h2">Comercios y su red</h2>
          <p class="ch-section__sub">Vendedores directos y a través de agencias/sucursales</p>
        </div>
      </div>
      <button class="ch-section__refresh" :class="{ 'ch-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div v-if="hierarchy" class="ch-kpis">
      <div class="ch-kpi">
        <p class="ch-kpi__label">Comercios</p>
        <p class="ch-kpi__value">{{ hierarchy.totals.commerce_count }}</p>
      </div>
      <div class="ch-kpi">
        <p class="ch-kpi__label">Agencias/Sucursales</p>
        <p class="ch-kpi__value">{{ hierarchy.totals.total_subsidiaries }}</p>
      </div>
      <div class="ch-kpi">
        <p class="ch-kpi__label">Vendedores en total</p>
        <p class="ch-kpi__value">{{ hierarchy.totals.total_sellers }}</p>
      </div>
    </div>

    <div v-if="loading && !hierarchy" class="ch-state ch-state--loading">
      <span class="ch-spinner"></span><span>Cargando jerarquía...</span>
    </div>
    <div v-else-if="error" class="ch-state ch-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="hierarchy && hierarchy.commerces.length" class="ch-table-wrap">
      <table class="ch-table">
        <thead>
          <tr>
            <th>Comercio</th>
            <th class="ch-table__num">Vendedores directos</th>
            <th class="ch-table__num">Agencias</th>
            <th class="ch-table__num">Vendedores en agencias</th>
            <th class="ch-table__num">Total vendedores</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="commerce in hierarchy.commerces" :key="commerce.commerce_id">
            <td><span class="ch-table__name">{{ commerce.commerce_name }}</span></td>
            <td class="ch-table__num">{{ commerce.direct_sellers }}</td>
            <td class="ch-table__num">{{ commerce.subsidiaries_count }}</td>
            <td class="ch-table__num">{{ commerce.subsidiary_sellers }}</td>
            <td class="ch-table__num"><strong>{{ commerce.total_sellers }}</strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="hierarchy && !hierarchy.commerces.length" class="ch-state ch-state--empty">
      <i class="fas fa-inbox"></i> Sin comercios registrados todavía.
    </div>

  </div>
</template>

<style scoped>
.ch-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.ch-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.ch-section__title {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.ch-section__icon {
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

.ch-section__h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 2px;
}

.ch-section__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0;
}

.ch-section__refresh {
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
.ch-section__refresh:hover:not(:disabled) { background: rgba(102,126,234,.15); border-color: #667eea; }
.ch-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.ch-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.ch-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

@media (max-width: 600px) {
  .ch-kpis { grid-template-columns: 1fr; }
}

.ch-kpi {
  padding: 0.875rem 1rem;
  border-radius: 10px;
  background: var(--ds-surface-2);
}

.ch-kpi__label {
  font-size: 0.73rem;
  color: var(--ds-text-2);
  margin: 0 0 2px;
  font-weight: 500;
}

.ch-kpi__value {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0;
}

.ch-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
}

.ch-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.ch-table thead tr {
  background: var(--ds-surface-2);
  border-bottom: 1px solid var(--ds-border);
}

.ch-table th {
  padding: 0.65rem 0.875rem;
  font-weight: 600;
  color: var(--ds-text-2);
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.ch-table td {
  padding: 0.65rem 0.875rem;
  border-bottom: 1px solid var(--ds-border);
  color: var(--ds-text);
  vertical-align: middle;
}

.ch-table tbody tr:last-child td { border-bottom: none; }
.ch-table tbody tr:hover td { background: var(--ds-surface-2); }

.ch-table__num { text-align: right; }
.ch-table__name { font-weight: 600; }

.ch-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}

.ch-state--error { color: #ef4444; }
.ch-state--empty i { font-size: 1.5rem; color: var(--ds-text-muted); }

.ch-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
</style>
