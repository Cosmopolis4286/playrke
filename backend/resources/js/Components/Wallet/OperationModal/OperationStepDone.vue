<script setup lang="ts">
import type { WalletOperation } from '@js/types/wallet'

defineProps<{
  operation: WalletOperation
  successMsg: string | null
}>()
</script>

<template>
  <div class="op-done">
    <div class="op-done__icon">
      <i class="fas fa-check"></i>
    </div>
    <h3 class="op-done__title">¡Solicitud enviada!</h3>
    <p class="op-done__text">{{ successMsg }}</p>
    <div class="op-done__detail">
      <i class="fas fa-info-circle"></i>
      {{ operation === 'deposit'
        ? 'Un administrador revisará tu comprobante y acreditará el monto a tu billetera.'
        : 'Un administrador procesará la transferencia a tu cuenta registrada.' }}
    </div>
  </div>
</template>

<style scoped>
.op-done {
  text-align: center;
  padding: 1rem 0 0.5rem;
}

.op-done__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  margin: 0 auto 1rem;
  box-shadow: 0 4px 16px rgba(34,197,94,0.3);
}

.op-done__title {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--ds-text);
  margin: 0 0 0.5rem;
}

.op-done__text {
  color: var(--ds-text-muted);
  font-size: 0.875rem;
  margin: 0 0 1rem;
}

.op-done__detail {
  background: rgba(59, 130, 246, .12);
  border: 1px solid rgba(59, 130, 246, .35);
  border-radius: 12px;
  padding: 0.875rem 1rem;
  font-size: 0.78rem;
  color: #38bdf8;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  text-align: left;
}

.op-done__detail i { margin-top: 2px; flex-shrink: 0; }
</style>
