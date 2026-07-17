<script setup lang="ts">
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  detail: Movement
  formatAmount: (mov: Movement) => string
}>()
</script>

<template>
  <!-- ── Montos ──────────────────────────────────────────────────── -->
  <div class="mv-detail__row mv-detail__row--3">
    <div class="mv-detail__section">
      <p class="mv-detail__label">Monto</p>
      <p
        class="mv-detail__amount"
        :class="['DEPOSIT','PRIZE'].includes(detail.type) ? 'mv-amount--positive' : 'mv-amount--negative'"
      >
        {{ formatAmount(detail) }}
      </p>
    </div>
    <div class="mv-detail__section">
      <p class="mv-detail__label">Moneda</p>
      <p class="mv-detail__value">
        {{ detail.currency ? `${detail.currency.symbol} ${detail.currency.iso_code}` : '—' }}
      </p>
    </div>
    <div class="mv-detail__section">
      <p class="mv-detail__label">Saldo antes</p>
      <p class="mv-detail__value mv-detail__value--mono">
        {{ detail.currency?.symbol }}
        {{ detail.balance_before.toLocaleString('es-VE', { minimumFractionDigits: detail.currency?.decimal_places ?? 2 }) }}
      </p>
    </div>
  </div>

  <!-- Saldo después solo si ya fue procesado -->
  <div v-if="detail.status !== 'PENDING'" class="mv-detail__section mt-2">
    <p class="mv-detail__label">Saldo después</p>
    <p class="mv-detail__value mv-detail__value--mono">
      {{ detail.currency?.symbol }}
      {{ detail.balance_after.toLocaleString('es-VE', { minimumFractionDigits: detail.currency?.decimal_places ?? 2 }) }}
    </p>
  </div>

  <div class="mv-detail__divider" />
</template>

<style scoped>
.mv-detail__divider {
  height: 1px;
  background: var(--ds-border);
  margin: 0.75rem 0;
}

.mv-detail__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.mv-detail__row--3 { grid-template-columns: 1fr 1fr 1fr; }

.mv-detail__section { display: flex; flex-direction: column; gap: 2px; }

.mv-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

.mv-detail__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ds-text);
  margin: 0;
}
.mv-detail__value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.82rem;
}

.mv-detail__amount {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0;
}
.mv-amount--positive { color: #4ade80; }
.mv-amount--negative { color: #f87171; }

.mt-2 { margin-top: 0.5rem; }
</style>
