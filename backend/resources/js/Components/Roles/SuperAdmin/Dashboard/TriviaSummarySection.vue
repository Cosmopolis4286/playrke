<script setup lang="ts">
import type { TriviaSummary } from '@js/composables/roles/superadmin/dashboard/useTriviaSummary'

defineProps<{
  summary: TriviaSummary | null
  loading: boolean
  error: string | null
  formatUsd: (v: number, decimals?: number) => string
}>()

defineEmits<{
  refresh: []
}>()

const prizeTypeLabel: Record<string, string> = {
  fixed: 'Fijo',
  accumulated: 'Acumulado',
}

const statusLabel: Record<string, string> = {
  waiting: 'Esperando',
  playing: 'En juego',
  closed: 'Cerrada',
  cancelled: 'Cancelada',
}
</script>

<template>
  <div class="trivia-section">

    <div class="trivia-section__header">
      <div class="trivia-section__title">
        <i class="fas fa-brain trivia-section__icon"></i>
        <div>
          <h2 class="trivia-section__h2">Actividad Trivia</h2>
          <p class="trivia-section__sub">Salas activas, participantes y comisión acumulada</p>
        </div>
      </div>
      <button class="trivia-section__refresh" :class="{ 'trivia-section__refresh--spin': loading }" :disabled="loading" @click="$emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <div v-if="summary" class="trivia-kpis">
      <div class="trivia-kpi trivia-kpi--rooms">
        <div class="trivia-kpi__icon"><i class="fas fa-door-open"></i></div>
        <div>
          <p class="trivia-kpi__label">Salas activas</p>
          <p class="trivia-kpi__value">{{ summary.totals.active_rooms }}</p>
        </div>
      </div>
      <div class="trivia-kpi trivia-kpi--participants">
        <div class="trivia-kpi__icon"><i class="fas fa-users"></i></div>
        <div>
          <p class="trivia-kpi__label">Participantes</p>
          <p class="trivia-kpi__value">{{ summary.totals.total_participants }}</p>
        </div>
      </div>
      <div class="trivia-kpi trivia-kpi--commission">
        <div class="trivia-kpi__icon"><i class="fas fa-coins"></i></div>
        <div>
          <p class="trivia-kpi__label">Comisión acumulada</p>
          <p class="trivia-kpi__value">$ {{ formatUsd(summary.totals.total_commission) }}</p>
        </div>
      </div>
    </div>

    <div v-if="loading && !summary" class="trivia-state trivia-state--loading">
      <span class="trivia-spinner"></span><span>Cargando actividad de Trivia...</span>
    </div>
    <div v-else-if="error" class="trivia-state trivia-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="summary && summary.rooms.length" class="trivia-table-wrap">
      <table class="trivia-table">
        <thead>
          <tr>
            <th>Sala</th>
            <th class="trivia-table__num">Participantes</th>
            <th class="trivia-table__num">Comisión</th>
            <th>Premio</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="room in summary.rooms" :key="room.room_id">
            <td><span class="trivia-table__code">{{ room.title ?? ('…' + room.room_id.slice(-6)) }}</span></td>
            <td class="trivia-table__num">{{ room.participant_count }}</td>
            <td class="trivia-table__num trivia-table__commission">$ {{ formatUsd(room.commission_amount) }} <small>({{ room.commission_percent }}%)</small></td>
            <td>
              <span v-if="room.prize_type" class="trivia-badge">
                {{ prizeTypeLabel[room.prize_type] ?? room.prize_type }} · $ {{ formatUsd(room.prize_amount) }}
              </span>
              <span v-else class="text-muted">—</span>
            </td>
            <td>
              <span class="trivia-status" :class="{
                'trivia-status--waiting':  room.status === 'waiting',
                'trivia-status--playing':  room.status === 'playing',
                'trivia-status--other':    !['waiting','playing'].includes(room.status),
              }">
                {{ statusLabel[room.status] ?? room.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="summary && !summary.rooms.length" class="trivia-state trivia-state--empty">
      <i class="fas fa-inbox"></i> Sin salas de Trivia registradas todavía.
    </div>

  </div>
</template>

<style scoped>
.trivia-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.trivia-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.trivia-section__title {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.trivia-section__icon {
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

.trivia-section__h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 2px;
}

.trivia-section__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0;
}

.trivia-section__refresh {
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
.trivia-section__refresh:hover:not(:disabled) { background: rgba(102,126,234,.15); border-color: #667eea; }
.trivia-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.trivia-section__refresh--spin i { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.trivia-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

@media (max-width: 600px) {
  .trivia-kpis { grid-template-columns: 1fr; }
}

.trivia-kpi {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border-radius: 10px;
}

.trivia-kpi--rooms        { background: rgba(37,99,235,.1); }
.trivia-kpi--participants { background: rgba(16,185,129,.1); }
.trivia-kpi--commission   { background: rgba(124,58,237,.1); }

.trivia-kpi__icon {
  width: 38px;
  height: 38px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.trivia-kpi--rooms        .trivia-kpi__icon { background: rgba(37,99,235,.2);  color: #60a5fa; }
.trivia-kpi--participants .trivia-kpi__icon { background: rgba(16,185,129,.2); color: #34d399; }
.trivia-kpi--commission   .trivia-kpi__icon { background: rgba(124,58,237,.2); color: #a78bfa; }

.trivia-kpi__label {
  font-size: 0.73rem;
  color: var(--ds-text-2);
  margin: 0 0 2px;
  font-weight: 500;
}

.trivia-kpi__value {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0;
}

.trivia-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
}

.trivia-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.trivia-table thead tr {
  background: var(--ds-surface-2);
  border-bottom: 1px solid var(--ds-border);
}

.trivia-table th {
  padding: 0.65rem 0.875rem;
  font-weight: 600;
  color: var(--ds-text-2);
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.trivia-table td {
  padding: 0.65rem 0.875rem;
  border-bottom: 1px solid var(--ds-border);
  color: var(--ds-text);
  vertical-align: middle;
}

.trivia-table tbody tr:last-child td { border-bottom: none; }
.trivia-table tbody tr:hover td { background: var(--ds-surface-2); }

.trivia-table__num { text-align: right; }
.trivia-table__commission { color: #a78bfa !important; font-weight: 600; }
.trivia-table__commission small { color: var(--ds-text-muted); font-weight: 400; }
.trivia-table__code { font-family: monospace; font-size: 0.78rem; color: var(--ds-text-2); }

.trivia-badge {
  display: inline-block;
  background: rgba(124,58,237,.15);
  color: #a78bfa;
  font-size: 0.73rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}

.trivia-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 20px;
}
.trivia-status--waiting { background: rgba(37,99,235,.18);  color: #60a5fa; }
.trivia-status--playing { background: rgba(22,163,74,.18);  color: #4ade80; }
.trivia-status--other    { background: var(--ds-surface-2);  color: var(--ds-text-2); }

.trivia-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}

.trivia-state--error { color: #ef4444; }
.trivia-state--empty i { font-size: 1.5rem; color: var(--ds-text-muted); }

.trivia-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
</style>
