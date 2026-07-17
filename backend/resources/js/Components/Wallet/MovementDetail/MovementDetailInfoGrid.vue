<script setup lang="ts">
import type { Wallet, WalletMovement } from '@js/types/wallet'
import { formatAmount, formatOperationDate } from '@js/composables/wallet/useWalletOperationFormatters'

defineProps<{
  movement: WalletMovement
  activeWallet: Wallet | null
}>()
</script>

<template>
  <div class="mv-detail__grid">
    <div class="mv-detail__cell mv-detail__cell--full">
      <span class="mv-detail__cell-label">ID de transacción</span>
      <span class="mv-detail__cell-value mv-detail__cell-value--mono">{{ movement.id }}</span>
    </div>
    <div class="mv-detail__cell">
      <span class="mv-detail__cell-label">Saldo anterior</span>
      <span class="mv-detail__cell-value">
        {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(movement.balance_before, activeWallet?.currency.decimal_places ?? 2) }}
      </span>
    </div>
    <div class="mv-detail__cell">
      <span class="mv-detail__cell-label">Saldo posterior</span>
      <span class="mv-detail__cell-value mv-detail__cell-value--highlight">
        {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(movement.balance_after, activeWallet?.currency.decimal_places ?? 2) }}
      </span>
    </div>
    <div class="mv-detail__cell">
      <span class="mv-detail__cell-label">Fecha</span>
      <span class="mv-detail__cell-value">{{ formatOperationDate(movement.created_at) }}</span>
      <span class="mv-detail__cell-sub">{{ new Date(movement.created_at).toLocaleString('es-VE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</span>
    </div>
    <div v-if="movement.description" class="mv-detail__cell mv-detail__cell--full">
      <span class="mv-detail__cell-label">Descripción</span>
      <span class="mv-detail__cell-value">{{ movement.description }}</span>
    </div>
    <div v-if="movement.admin_notes" class="mv-detail__cell mv-detail__cell--full">
      <span class="mv-detail__cell-label">Nota del equipo</span>
      <span class="mv-detail__cell-value">{{ movement.admin_notes }}</span>
    </div>
  </div>
</template>

<style scoped>
.mv-detail__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem 1rem;
  margin-bottom: 1.2rem;
}
.mv-detail__cell { display: flex; flex-direction: column; gap: 0.15rem; }
.mv-detail__cell--full { grid-column: 1 / -1; }
.mv-detail__cell-label {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 500;
}
.mv-detail__cell-value { font-size: 0.9rem; color: var(--ds-text-2); font-weight: 600; }
.mv-detail__cell-value--highlight { color: #667eea; }
.mv-detail__cell-sub { font-size: 0.72rem; color: var(--ds-text-muted); margin-top: 1px; }
.mv-detail__cell-value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--ds-text-2);
  word-break: break-all;
}
</style>
