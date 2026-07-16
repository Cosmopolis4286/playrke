<script setup lang="ts">
import type { RkeSummary } from '@js/composables/roles/superadmin/dashboard/useRkeSummary'

defineProps<{
  summary: RkeSummary | null
  loading: boolean
  error: string | null
  formatUsd: (v: number, decimals?: number) => string
  formatDate: (iso: string | null) => string
}>()

defineEmits<{
  refresh: []
}>()
</script>

<template>
  <div class="rke-section">

    <!-- Header -->
    <div class="rke-section__header">
      <div class="rke-section__title">
        <i class="fas fa-percentage rke-section__icon"></i>
        <div>
          <h2 class="rke-section__h2">Comisiones RKE por sala</h2>
          <p class="rke-section__sub">Cobrado en cada venta de cartón — acreditado a la billetera de plataforma</p>
        </div>
      </div>
      <button class="rke-section__refresh" :class="{ 'rke-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <!-- KPIs -->
    <div v-if="summary" class="rke-kpis">
      <div class="rke-kpi rke-kpi--rke">
        <div class="rke-kpi__icon"><i class="fas fa-coins"></i></div>
        <div>
          <p class="rke-kpi__label">Total RKE cobrado</p>
          <p class="rke-kpi__value">$ {{ formatUsd(summary.totals.total_rke) }}</p>
        </div>
      </div>
      <div class="rke-kpi rke-kpi--sales">
        <div class="rke-kpi__icon"><i class="fas fa-shopping-cart"></i></div>
        <div>
          <p class="rke-kpi__label">Total ventas</p>
          <p class="rke-kpi__value">$ {{ formatUsd(summary.totals.total_sales) }}</p>
        </div>
      </div>
      <div class="rke-kpi rke-kpi--rooms">
        <div class="rke-kpi__icon"><i class="fas fa-door-open"></i></div>
        <div>
          <p class="rke-kpi__label">Salas con ventas</p>
          <p class="rke-kpi__value">{{ summary.totals.room_count }}</p>
        </div>
      </div>
    </div>

    <div v-if="loading && !summary" class="rke-state rke-state--loading">
      <span class="rke-spinner"></span><span>Cargando comisiones...</span>
    </div>
    <div v-else-if="error" class="rke-state rke-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="summary && summary.rooms.length" class="rke-table-wrap">
      <table class="rke-table">
        <thead>
          <tr>
            <th>Sala</th>
            <th>Anfitrión</th>
            <th class="rke-table__num">Total vendido</th>
            <th class="rke-table__num">RKE cobrado</th>
            <th class="rke-table__num">% RKE</th>
            <th>Última venta</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="room in summary.rooms" :key="room.room_id">
            <td><span class="rke-table__code">{{ room.room_name ?? ('…' + room.room_id.slice(-6)) }}</span></td>
            <td>{{ room.creator_name }}</td>
            <td class="rke-table__num">$ {{ formatUsd(room.total_sales) }}</td>
            <td class="rke-table__num rke-table__rke">$ {{ formatUsd(room.total_rke) }}</td>
            <td class="rke-table__num"><span class="rke-badge">{{ room.rke_pct }}%</span></td>
            <td class="rke-table__date">{{ formatDate(room.last_sale_at) }}</td>
            <td>
              <span class="rke-status" :class="{
                'rke-status--active':   room.status === 'ACTIVE',
                'rke-status--progress': room.status === 'IN_PROGRESS',
                'rke-status--finished': room.status === 'FINISHED',
                'rke-status--other':    !['ACTIVE','IN_PROGRESS','FINISHED'].includes(room.status ?? ''),
              }">
                {{ room.status === 'ACTIVE' ? 'Activa' : room.status === 'IN_PROGRESS' ? 'En juego' : room.status === 'FINISHED' ? 'Finalizada' : (room.status ?? '—') }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="summary && !summary.rooms.length" class="rke-state rke-state--empty">
      <i class="fas fa-inbox"></i> Sin ventas registradas todavía.
    </div>

  </div>
</template>

<style scoped>
.rke-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.rke-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.rke-section__title {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.rke-section__icon {
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

.rke-section__h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 2px;
}

.rke-section__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0;
}

.rke-section__refresh {
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
.rke-section__refresh:hover:not(:disabled) { background: rgba(102,126,234,.15); border-color: #667eea; }
.rke-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.rke-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* KPIs */
.rke-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

@media (max-width: 600px) {
  .rke-kpis { grid-template-columns: 1fr; }
}

.rke-kpi {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border-radius: 10px;
}

.rke-kpi--rke   { background: rgba(124,58,237,.1); }
.rke-kpi--sales { background: rgba(16,185,129,.1); }
.rke-kpi--rooms { background: rgba(37,99,235,.1); }

.rke-kpi__icon {
  width: 38px;
  height: 38px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.rke-kpi--rke   .rke-kpi__icon { background: rgba(124,58,237,.2); color: #a78bfa; }
.rke-kpi--sales .rke-kpi__icon { background: rgba(16,185,129,.2); color: #34d399; }
.rke-kpi--rooms .rke-kpi__icon { background: rgba(37,99,235,.2); color: #60a5fa; }

.rke-kpi__label {
  font-size: 0.73rem;
  color: var(--ds-text-2);
  margin: 0 0 2px;
  font-weight: 500;
}

.rke-kpi__value {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0;
}

/* Tabla */
.rke-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
}

.rke-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.rke-table thead tr {
  background: var(--ds-surface-2);
  border-bottom: 1px solid var(--ds-border);
}

.rke-table th {
  padding: 0.65rem 0.875rem;
  font-weight: 600;
  color: var(--ds-text-2);
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.rke-table td {
  padding: 0.65rem 0.875rem;
  border-bottom: 1px solid var(--ds-border);
  color: var(--ds-text);
  vertical-align: middle;
}

.rke-table tbody tr:last-child td { border-bottom: none; }
.rke-table tbody tr:hover td { background: var(--ds-surface-2); }

.rke-table__num { text-align: right; }
.rke-table__rke { color: #a78bfa !important; font-weight: 600; }
.rke-table__code { font-family: monospace; font-size: 0.78rem; color: var(--ds-text-2); }
.rke-table__date { color: var(--ds-text-muted); font-size: 0.78rem; white-space: nowrap; }

.rke-badge {
  display: inline-block;
  background: rgba(124,58,237,.15);
  color: #a78bfa;
  font-size: 0.73rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
}

.rke-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 20px;
}
.rke-status--active   { background: rgba(22,163,74,.18);  color: #4ade80; }
.rke-status--progress { background: rgba(37,99,235,.18);  color: #60a5fa; }
.rke-status--finished { background: rgba(147,51,234,.18); color: #c084fc; }
.rke-status--other    { background: var(--ds-surface-2);  color: var(--ds-text-2); }

/* Estado vacío / carga / error */
.rke-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}

.rke-state--error { color: #ef4444; }
.rke-state--empty i { font-size: 1.5rem; color: var(--ds-text-muted); }

.rke-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
</style>
