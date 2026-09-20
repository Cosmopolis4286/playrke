<script setup lang="ts">
import { computed } from 'vue'
import type { Movement } from '@js/types/wallet/admin-movement'

const props = defineProps<{
  detail: Movement
  formatAmount: (mov: Movement) => string
  formatDate: (iso: string) => string
}>()

const emit = defineEmits<{
  'view-movement': [id: string]
  'view-user-movements': [search: string]
}>()

const signals = computed(() => props.detail.fraud_signals ?? null)

const depositVelocityLevel = computed(() => {
  const s = signals.value
  if (!s || s.deposits_today === null || s.deposits_today_max === null) return null
  const ratio = s.deposits_today / s.deposits_today_max
  return ratio >= 1 ? 'critical' : ratio >= 0.7 ? 'warning' : 'normal'
})

const withdrawalVelocityLevel = computed(() => {
  const s = signals.value
  if (!s || s.withdrawals_today === null || s.withdrawals_today_max === null) return null
  const ratio = s.withdrawals_today / s.withdrawals_today_max
  return ratio >= 1 ? 'critical' : ratio >= 0.7 ? 'warning' : 'normal'
})

function viewUserMovements() {
  if (props.detail.user?.email) emit('view-user-movements', props.detail.user.email)
}
</script>

<template>
  <div v-if="signals" class="mv-fraud">
    <p class="mv-detail__label mb-2">Señales de riesgo</p>

    <!-- Comprobante repetido -->
    <div v-if="signals.duplicate_receipt_movement_id" class="mv-fraud__alert mv-fraud__alert--critical">
      <i class="fas fa-triangle-exclamation me-2"></i>
      Este comprobante ya fue usado en otro movimiento.
      <button type="button" class="mv-fraud__link" @click="emit('view-movement', signals.duplicate_receipt_movement_id)">
        Ver movimiento #{{ signals.duplicate_receipt_movement_id.slice(-8) }}
      </button>
    </div>

    <!-- Velocidad de depósitos -->
    <div
      v-if="depositVelocityLevel && depositVelocityLevel !== 'normal'"
      class="mv-fraud__alert"
      :class="`mv-fraud__alert--${depositVelocityLevel}`"
    >
      <i class="fas fa-gauge-high me-2"></i>
      {{ signals.deposits_today }}º depósito de hoy (límite {{ signals.deposits_today_max }}/día).
    </div>

    <!-- Velocidad de retiros -->
    <div
      v-if="withdrawalVelocityLevel && withdrawalVelocityLevel !== 'normal'"
      class="mv-fraud__alert"
      :class="`mv-fraud__alert--${withdrawalVelocityLevel}`"
    >
      <i class="fas fa-gauge-high me-2"></i>
      {{ signals.withdrawals_today }}º retiro de hoy (límite {{ signals.withdrawals_today_max }}/día).
    </div>

    <!-- Referencia duplicada -->
    <div v-if="signals.duplicate_reference" class="mv-fraud__alert mv-fraud__alert--warning">
      <i class="fas fa-copy me-2"></i>
      Esta referencia ya fue usada en otro movimiento de este usuario en las últimas 24h.
    </div>

    <!-- Tasa de rechazo histórica -->
    <div v-if="signals.rejection_rate !== null && signals.rejection_rate > 20" class="mv-fraud__alert mv-fraud__alert--warning">
      <i class="fas fa-chart-line me-2"></i>
      {{ signals.rejection_rate }}% de sus movimientos anteriores fueron rechazados.
    </div>

    <!-- Historial reciente -->
    <div v-if="signals.recent_movements.length" class="mv-fraud__history">
      <div class="mv-fraud__history-header">
        <p class="mv-detail__label mb-1">Últimos movimientos de este usuario</p>
        <button type="button" class="mv-fraud__link" @click="viewUserMovements">
          Ver todos <i class="fas fa-arrow-right ms-1"></i>
        </button>
      </div>
      <table class="mv-fraud__table">
        <tbody>
          <tr v-for="rm in signals.recent_movements" :key="rm.id">
            <td>{{ rm.type }}</td>
            <td>{{ rm.status }}</td>
            <td class="text-end">{{ formatAmount({ ...rm, currency: detail.currency } as Movement) }}</td>
            <td class="text-end mv-fraud__date">{{ formatDate(rm.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="mv-fraud__empty">Sin movimientos previos de este usuario.</p>
  </div>
</template>

<style scoped>
.mv-fraud { margin-top: 0.75rem; }

.mv-fraud__alert {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  font-size: 0.8rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  border: 1px solid transparent;
}
.mv-fraud__alert--normal   { background: var(--ds-surface-2); color: var(--ds-text-2); }
.mv-fraud__alert--warning  { background: rgba(217,119,6,.14);  border-color: rgba(217,119,6,.4);  color: #fbbf24; }
.mv-fraud__alert--critical { background: rgba(220,38,38,.16);  border-color: rgba(220,38,38,.45);  color: #f87171; }

.mv-fraud__link {
  background: none;
  border: none;
  color: inherit;
  text-decoration: underline;
  font-size: inherit;
  font-weight: 700;
  padding: 0;
  cursor: pointer;
}

.mv-fraud__history { margin-top: 0.6rem; }

.mv-fraud__history-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}
.mv-fraud__history-header .mv-fraud__link { font-size: 0.75rem; white-space: nowrap; }
.ms-1 { margin-left: 0.25rem; }

.mv-fraud__table {
  width: 100%;
  font-size: 0.78rem;
  color: var(--ds-text-2);
  border-collapse: collapse;
}
.mv-fraud__table td {
  padding: 4px 6px;
  border-bottom: 1px solid var(--ds-border);
  white-space: nowrap;
}
.mv-fraud__date { color: var(--ds-text-muted); }

.mv-fraud__empty {
  font-size: 0.78rem;
  color: var(--ds-text-muted);
  margin: 0.4rem 0 0;
}

.mv-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.me-2 { margin-right: 0.5rem; }
.text-end { text-align: right; }
</style>
