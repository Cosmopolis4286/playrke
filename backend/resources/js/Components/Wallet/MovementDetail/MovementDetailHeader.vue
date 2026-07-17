<script setup lang="ts">
import type { Wallet, WalletMovement } from '@js/types/wallet'
import type { MovementMetaEntry } from '@js/composables/wallet/useMovementMeta'
import { formatAmount } from '@js/composables/wallet/useWalletOperationFormatters'

defineProps<{
  movement: WalletMovement
  meta: MovementMetaEntry
  activeWallet: Wallet | null
}>()
</script>

<template>
  <div class="mv-detail__header">
    <div class="mv-detail__icon" :class="meta.colorClass" :style="meta.colorStyle">
      <i :class="meta.icon + ' fa-lg'"></i>
    </div>
    <div class="mv-detail__header-info">
      <p class="mv-detail__type">{{ meta.label }}</p>
      <p
        class="mv-detail__amount"
        :class="meta.sign === '+' ? 'amount--positive' : 'amount--negative'"
      >
        {{ meta.sign }}{{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(movement.amount, activeWallet?.currency.decimal_places ?? 2) }}
      </p>
    </div>
    <span
      class="badge-status mv-detail__badge"
      :class="{
        'badge-status--pending':   movement.status === 'PENDING',
        'badge-status--completed': movement.status === 'COMPLETED',
        'badge-status--rejected':  movement.status === 'REJECTED',
        'badge-status--cancelled': movement.status === 'CANCELLED',
      }"
    >
      <i :class="{
        'fas fa-clock':  movement.status === 'PENDING',
        'fas fa-check':  movement.status === 'COMPLETED',
        'fas fa-times':  movement.status === 'REJECTED',
        'fas fa-ban':    movement.status === 'CANCELLED',
      }"></i>
      {{
        movement.status === 'PENDING'   ? 'Pendiente'  :
        movement.status === 'COMPLETED' ? 'Completado' :
        movement.status === 'REJECTED'  ? 'Rechazado'  : 'Cancelado'
      }}
    </span>
  </div>
</template>

<style scoped>
.amount--positive { color: #16a34a; }
.amount--negative { color: #e11d48; }

.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}
.badge-status--pending   { color: #d97706; background: rgba(245, 158, 11, .16); }
.badge-status--completed { color: #16a34a; background: rgba(34, 197, 94, .16); }
.badge-status--rejected  { color: #dc2626; background: rgba(220, 38, 38, .16); }
.badge-status--cancelled { color: var(--ds-text-2); background: var(--ds-surface-2); }

.mv-detail__header {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  background: var(--ds-surface-2);
  border-radius: 12px;
  padding: 1rem 1.1rem;
  margin-bottom: 1rem;
}
.mv-detail__icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.mv-detail__header-info { flex: 1; min-width: 0; }
.mv-detail__type {
  font-size: 0.78rem;
  color: var(--ds-text-muted);
  margin: 0 0 0.15rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.mv-detail__amount {
  font-size: 1.35rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.2;
}
.mv-detail__badge { margin-left: auto; flex-shrink: 0; align-self: flex-start; }
</style>
