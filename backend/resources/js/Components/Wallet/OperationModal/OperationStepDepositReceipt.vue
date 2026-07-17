<script setup lang="ts">
import { computed } from 'vue'
import type { Wallet } from '@js/types/wallet'
import { formatAmount } from '@js/composables/wallet/useWalletOperationFormatters'

const props = defineProps<{
  activeWallet: Wallet | null
  parsedAmount: number
  receipt: File | null
  reference: string
  notes: string
  errorMsg: string | null
}>()

const emit = defineEmits<{
  (e: 'update:receipt', v: File | null): void
  (e: 'update:reference', v: string): void
  (e: 'update:notes', v: string): void
}>()

const receiptPreviewUrl = computed(() => {
  if (!props.receipt) return null
  if (!props.receipt.type.startsWith('image/')) return null
  return URL.createObjectURL(props.receipt)
})

function onReceiptChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  emit('update:receipt', file)
}
</script>

<template>
  <!-- Resumen del monto -->
  <div class="op-summary">
    <i class="fas fa-arrow-down op-summary__icon op-summary__icon--deposit"></i>
    <div>
      <p class="op-summary__label">Depositando</p>
      <p class="op-summary__amount">{{ activeWallet?.currency.symbol }} {{ formatAmount(parsedAmount, activeWallet?.currency.decimal_places ?? 2) }}</p>
    </div>
  </div>

  <div class="op-field">
    <label class="op-field__label">
      Comprobante de pago <span class="op-required">*</span>
    </label>
    <label class="op-file-drop" :class="{ 'op-file-drop--selected': receipt }">
      <input type="file" accept="image/jpeg,image/png,application/pdf" class="op-file-input" @change="onReceiptChange" />
      <template v-if="receipt">
        <i class="fas fa-file-check op-file-drop__icon op-file-drop__icon--ok"></i>
        <p class="op-file-drop__name">{{ receipt.name }}</p>
        <p class="op-file-drop__hint">Haz clic para cambiar</p>
      </template>
      <template v-else>
        <i class="fas fa-cloud-upload-alt op-file-drop__icon"></i>
        <p class="op-file-drop__name">Selecciona o arrastra el comprobante</p>
        <p class="op-file-drop__hint">JPG, PNG o PDF · máx. 5 MB</p>
      </template>
    </label>

    <!-- Previsualización de imagen -->
    <div v-if="receiptPreviewUrl" class="op-receipt-preview">
      <img :src="receiptPreviewUrl" alt="Vista previa del comprobante" class="op-receipt-preview__img" />
    </div>
  </div>

  <div class="op-field">
    <label class="op-field__label">Número de referencia <span class="op-optional">(opcional)</span></label>
    <input
      :value="reference"
      type="text"
      class="op-field__input op-input--deposit"
      placeholder="Ej: 00123456"
      maxlength="100"
      @input="emit('update:reference', ($event.target as HTMLInputElement).value)"
    />
  </div>

  <div class="op-field">
    <label class="op-field__label">Notas <span class="op-optional">(opcional)</span></label>
    <textarea
      :value="notes"
      class="op-field__textarea"
      rows="2"
      placeholder="Información adicional para el administrador..."
      maxlength="500"
      @input="emit('update:notes', ($event.target as HTMLTextAreaElement).value)"
    ></textarea>
  </div>

  <Transition name="msg-fade">
    <div v-if="errorMsg" class="op-msg op-msg--error">
      <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
      <div><p class="op-msg__title">Error</p><p>{{ errorMsg }}</p></div>
    </div>
  </Transition>
</template>

<style scoped>
.op-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 0.875rem 1rem;
  margin-bottom: 1.25rem;
}

.op-summary__icon {
  font-size: 1.25rem;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.op-summary__icon--deposit  { background: rgba(34, 197, 94, .14); color: #16a34a; }

.op-summary__label  { font-size: 0.72rem; color: var(--ds-text-muted); margin: 0; }
.op-summary__amount { font-size: 1.2rem; font-weight: 800; color: var(--ds-text); margin: 0; }

/* ── File drop ── */
.op-file-drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: 2px dashed #c7d2fe;
  border-radius: 12px;
  padding: 1.5rem 1rem;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  background: var(--ds-surface-2);
  text-align: center;
}

.op-file-drop:hover       { border-color: #667eea; background: rgba(102, 126, 234, .1); }
.op-file-drop--selected   { border-color: #22c55e; border-style: solid; background: rgba(34, 197, 94, .12); }

.op-file-input { display: none; }

.op-file-drop__icon {
  font-size: 1.75rem;
  color: #a5b4fc;
}

.op-file-drop__icon--ok { color: #22c55e; }

.op-file-drop__name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--ds-text-2);
  margin: 0;
  word-break: break-all;
}

.op-file-drop__hint {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}

/* ── Receipt preview ── */
.op-receipt-preview {
  margin-top: 10px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid rgba(34, 197, 94, .35);
  background: rgba(34, 197, 94, .1);
  text-align: center;
}
.op-receipt-preview__img {
  display: block;
  max-width: 100%;
  max-height: 220px;
  object-fit: contain;
  margin: 0 auto;
}

/* ── Field / textarea ── */
.op-field { margin-bottom: 1.25rem; }

.op-field__label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin-bottom: 8px;
}

.op-field__input {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 1rem;
  font-weight: 600;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}
.op-input--deposit:focus { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.15); }

.op-field__textarea {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 0.875rem;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s;
  resize: vertical;
  box-sizing: border-box;
  font-family: inherit;
}
.op-field__textarea:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }

/* ── Labels auxiliares ── */
.op-required { color: #e11d48; font-weight: 700; }
.op-optional { color: var(--ds-text-muted); font-weight: 400; font-size: 0.75em; }

/* ── Mensajes ── */
.op-msg {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: 12px;
  font-size: 0.82rem;
  margin-bottom: 0.75rem;
}
.op-msg--error { background: rgba(220, 38, 38, .14); border: 1px solid rgba(220, 38, 38, .35); color: #f87171; }
.op-msg__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.op-msg--error .op-msg__icon { background: rgba(220, 38, 38, .2); }
.op-msg__title { font-weight: 700; margin: 0 0 2px; }

/* ── Transición mensajes ── */
.msg-fade-enter-active, .msg-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.msg-fade-enter-from,   .msg-fade-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
