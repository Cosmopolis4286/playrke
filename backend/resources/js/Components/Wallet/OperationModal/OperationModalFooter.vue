<script setup lang="ts">
import type { WalletOperation, PendingOperation } from '@js/types/wallet'

defineProps<{
  step: number
  operation: WalletOperation
  loading: boolean
  pendingOperation: PendingOperation | null
  pendingLoading: boolean
  canGoToStep2: boolean
  canSubmitDeposit: boolean
  canSubmitWithdraw: boolean
}>()

defineEmits<{
  'go-to-step1': []
  'go-to-step2': []
  submit: []
  close: []
}>()
</script>

<template>
  <!-- Paso 3: solo cerrar -->
  <template v-if="step === 3">
    <button type="button" class="op-btn op-btn--deposit" style="flex:1" @click="$emit('close')">
      <i class="fas fa-check"></i> Entendido
    </button>
  </template>

  <!-- Paso 2: volver + confirmar -->
  <template v-else-if="step === 2">
    <button type="button" class="op-btn op-btn--cancel" :disabled="loading" @click="$emit('go-to-step1')">
      <i class="fas fa-arrow-left"></i> Volver
    </button>
    <button
      type="button"
      class="op-btn"
      :class="operation === 'deposit' ? 'op-btn--deposit' : 'op-btn--withdraw'"
      :disabled="loading || (operation === 'deposit' ? !canSubmitDeposit : !canSubmitWithdraw)"
      @click="$emit('submit')"
    >
      <span v-if="loading" class="op-btn__loading">
        <span class="op-spinner op-spinner--white"></span> Enviando...
      </span>
      <span v-else>
        <i class="fas fa-paper-plane"></i>
        {{ operation === 'deposit' ? 'Enviar solicitud' : 'Confirmar retiro' }}
      </span>
    </button>
  </template>

  <!-- Paso 1: cancelar + siguiente -->
  <template v-else>
    <button type="button" class="op-btn op-btn--cancel" @click="$emit('close')">
      <i class="fas fa-times"></i> Cerrar
    </button>
    <button
      v-if="!pendingOperation && !pendingLoading"
      type="button"
      class="op-btn"
      :class="operation === 'deposit' ? 'op-btn--deposit' : 'op-btn--withdraw'"
      :disabled="!canGoToStep2"
      @click="$emit('go-to-step2')"
    >
      Siguiente <i class="fas fa-arrow-right"></i>
    </button>
  </template>
</template>

<style scoped>
.op-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 20px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}
.op-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.op-btn--cancel {
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1.5px solid var(--ds-border);
}
.op-btn--cancel:hover:not(:disabled) { background: var(--ds-surface-2); }

.op-btn--deposit {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}
.op-btn--deposit:hover:not(:disabled)  { opacity: 0.9; transform: translateY(-1px); }

.op-btn--withdraw {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
}
.op-btn--withdraw:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }

.op-btn__loading { display: flex; align-items: center; gap: 8px; }

.op-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
