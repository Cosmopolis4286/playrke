<script setup lang="ts">
import type { WalletOperation } from '@js/types/wallet'

defineProps<{
  step: number
  operation: WalletOperation
}>()
</script>

<template>
  <div class="op-steps">
    <div class="op-steps__track">
      <div
        class="op-steps__item"
        :class="{ 'op-steps__item--active': step >= 1, 'op-steps__item--done': step > 1 }"
      >
        <div class="op-steps__circle">
          <i v-if="step > 1" class="fas fa-check"></i>
          <span v-else>1</span>
        </div>
        <span class="op-steps__label">Monto</span>
      </div>
      <div class="op-steps__line" :class="{ 'op-steps__line--done': step > 1 }"></div>
      <div
        class="op-steps__item"
        :class="{ 'op-steps__item--active': step >= 2 }"
      >
        <div class="op-steps__circle">
          <span>2</span>
        </div>
        <span class="op-steps__label">
          {{ operation === 'deposit' ? 'Comprobante' : 'Destino' }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.op-steps {
  margin-bottom: 1.25rem;
}

.op-steps__track {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
}

.op-steps__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.op-steps__circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid var(--ds-border);
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 700;
  transition: all 0.2s;
}

.op-steps__item--active .op-steps__circle {
  border-color: #667eea;
  background: #667eea;
  color: #fff;
}

.op-steps__item--done .op-steps__circle {
  border-color: #22c55e;
  background: #22c55e;
  color: #fff;
}

.op-steps__label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--ds-text-muted);
}

.op-steps__item--active .op-steps__label,
.op-steps__item--done .op-steps__label {
  color: var(--ds-text-2);
}

.op-steps__line {
  flex: 1;
  height: 2px;
  background: var(--ds-border);
  margin: 0 8px;
  margin-bottom: 18px;
  transition: background 0.2s;
}

.op-steps__line--done { background: #22c55e; }
</style>
