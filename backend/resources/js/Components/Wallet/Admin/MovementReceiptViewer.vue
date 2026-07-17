<script setup lang="ts">
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  detail: Movement
  isReceiptImage: boolean
  receiptZoom: boolean
}>()

const emit = defineEmits<{
  'update:receiptZoom': [value: boolean]
}>()
</script>

<template>
  <!-- ── Comprobante (DEPOSIT) ───────────────────────────────────── -->
  <template v-if="detail.type === 'DEPOSIT'">
    <div class="mv-detail__section">
      <p class="mv-detail__label">Comprobante / Baucher</p>
      <div v-if="detail.receipt_url" class="mv-receipt">
        <!-- Imagen -->
        <template v-if="isReceiptImage">
          <div
            class="mv-receipt__img-wrap"
            :class="{ 'mv-receipt__img-wrap--zoomed': receiptZoom }"
          >
            <img
              :src="detail.receipt_url"
              alt="Comprobante"
              class="mv-receipt__img"
              @click="emit('update:receiptZoom', !receiptZoom)"
              title="Clic para ampliar"
            />
            <div class="mv-receipt__toolbar">
              <button class="mv-receipt__btn" @click="emit('update:receiptZoom', !receiptZoom)" type="button" :title="receiptZoom ? 'Reducir' : 'Ampliar'">
                <i :class="receiptZoom ? 'fas fa-compress-alt' : 'fas fa-expand-alt'"></i>
              </button>
              <a class="mv-receipt__btn" :href="detail.receipt_url" target="_blank" title="Abrir en nueva pestaña">
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
          </div>
        </template>
        <!-- PDF u otro -->
        <template v-else>
          <div class="mv-receipt__file">
            <i class="fas fa-file-pdf mv-receipt__file-icon"></i>
            <div>
              <p class="mv-receipt__file-name">{{ detail.receipt_path?.split('/').pop() }}</p>
              <a :href="detail.receipt_url" target="_blank" class="mv-receipt__file-link">
                <i class="fas fa-external-link-alt me-1"></i> Abrir archivo
              </a>
            </div>
          </div>
        </template>
      </div>
      <p v-else class="mv-detail__value text-muted">Sin comprobante adjunto</p>
    </div>

    <!-- Referencia del usuario -->
    <div v-if="detail.reference_id" class="mv-detail__section mt-2">
      <p class="mv-detail__label">Número de referencia</p>
      <p class="mv-detail__value mv-detail__value--mono">{{ detail.reference_id }}</p>
    </div>

    <div class="mv-detail__divider" />
  </template>
</template>

<style scoped>
.mv-detail__divider {
  height: 1px;
  background: var(--ds-border);
  margin: 0.75rem 0;
}

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

/* Comprobante */
.mv-receipt { margin-top: 6px; }

.mv-receipt__img-wrap {
  position: relative;
  display: block;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  transition: border-color 0.2s;
}
.mv-receipt__img-wrap:hover { border-color: #667eea; }

.mv-receipt__img {
  display: block;
  width: 100%;
  max-height: 260px;
  object-fit: contain;
  cursor: zoom-in;
  background: var(--ds-surface-2);
  transition: max-height 0.3s;
}
.mv-receipt__img-wrap--zoomed .mv-receipt__img {
  max-height: 520px;
  cursor: zoom-out;
}

.mv-receipt__toolbar {
  display: flex;
  justify-content: flex-end;
  gap: 4px;
  padding: 4px 6px;
  background: rgba(0,0,0,0.15);
  border-top: 1px solid var(--ds-border);
}
.mv-receipt__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  color: var(--ds-text-2);
  font-size: 0.75rem;
  cursor: pointer;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
}
.mv-receipt__btn:hover { background: #667eea; color: #fff; border-color: #667eea; }

.mv-receipt__file {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.9rem;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 8px;
}
.mv-receipt__file-icon { font-size: 1.8rem; color: #fb7185; }
.mv-receipt__file-name { font-size: 0.8rem; font-weight: 500; color: var(--ds-text); margin: 0; }
.mv-receipt__file-link { font-size: 0.75rem; color: #667eea; text-decoration: none; }
.mv-receipt__file-link:hover { text-decoration: underline; }

.mt-2 { margin-top: 0.5rem; }
.me-1 { margin-right: 0.25rem; }
</style>
