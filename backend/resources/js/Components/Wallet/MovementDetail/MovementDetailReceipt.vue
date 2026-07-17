<script setup lang="ts">
import type { WalletMovement } from '@js/types/wallet'

defineProps<{
  movement: WalletMovement
}>()
</script>

<template>
  <div v-if="movement.type === 'DEPOSIT' && movement.receipt_url" class="mv-detail__receipt">
    <p class="mv-detail__cell-label">Comprobante adjunto</p>
    <template v-if="/\.(jpg|jpeg|png|webp|gif)$/i.test(movement.receipt_path ?? '')">
      <img :src="movement.receipt_url" alt="Comprobante" class="mv-detail__receipt-img" />
    </template>
    <template v-else>
      <a :href="movement.receipt_url" target="_blank" rel="noopener" class="mv-detail__receipt-link">
        <i class="fas fa-file-pdf me-1"></i>
        {{ movement.receipt_path?.split('/').pop() ?? 'Ver comprobante' }}
      </a>
    </template>
    <p v-if="movement.reference_id" class="mv-detail__ref">
      Ref: <span class="mv-detail__ref-value">{{ movement.reference_id }}</span>
    </p>
  </div>
</template>

<style scoped>
.mv-detail__cell-label {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 500;
}

.mv-detail__receipt {
  padding: 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.mv-detail__receipt-img {
  display: block;
  max-width: 100%;
  max-height: 200px;
  object-fit: contain;
  border-radius: 6px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface);
}
.mv-detail__receipt-link {
  font-size: 0.82rem;
  color: #667eea;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.mv-detail__receipt-link:hover { text-decoration: underline; }
.mv-detail__ref { font-size: 0.75rem; color: var(--ds-text-2); margin: 0; }
.mv-detail__ref-value { font-family: ui-monospace, monospace; color: var(--ds-text-2); }
</style>
