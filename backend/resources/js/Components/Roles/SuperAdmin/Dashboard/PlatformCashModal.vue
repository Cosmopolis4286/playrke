<script setup lang="ts">
import type { CashModalState } from '@js/composables/roles/superadmin/dashboard/usePlatformCashModal'
import type { PlatformWallet } from '@js/composables/roles/superadmin/dashboard/usePlatformWallet'

const props = defineProps<{
  modelValue: CashModalState | null
  activeWallet: PlatformWallet | null
  withdrawConfirmed: (m: CashModalState) => boolean
  formatUsd: (v: number, decimals?: number) => string
}>()

const emit = defineEmits<{
  close: []
  submit: []
}>()
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="pw-modal-backdrop" @click.self="emit('close')">
      <div class="pw-modal">
        <div class="pw-modal__header">
          <div class="pw-modal__icon" :class="modelValue.mode === 'deposit' ? 'pw-modal__icon--deposit' : 'pw-modal__icon--withdraw'">
            <i :class="modelValue.mode === 'deposit' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
          </div>
          <div>
            <h3 class="pw-modal__title">{{ modelValue.mode === 'deposit' ? 'Depositar fondos' : 'Retirar fondos' }}</h3>
            <p class="pw-modal__subtitle">{{ activeWallet?.currency_code }} — saldo actual: {{ activeWallet?.currency_symbol }} {{ formatUsd(activeWallet?.balance ?? 0) }}</p>
          </div>
          <button class="pw-modal__close" @click="emit('close')"><i class="fas fa-times"></i></button>
        </div>

        <div class="pw-modal__body">
          <div class="pw-modal__field">
            <label class="pw-modal__label">Monto *</label>
            <div class="pw-modal__input-wrap">
              <span class="pw-modal__prefix">{{ activeWallet?.currency_symbol }}</span>
              <input
                v-model="modelValue.amount"
                type="number"
                min="0.01"
                step="0.01"
                placeholder="0.00"
                class="pw-modal__input"
                @keyup.enter="emit('submit')"
              />
            </div>
          </div>
          <div class="pw-modal__field">
            <label class="pw-modal__label">Descripción (opcional)</label>
            <input
              v-model="modelValue.description"
              type="text"
              maxlength="255"
              :placeholder="modelValue.mode === 'deposit' ? 'Depósito manual' : 'Retiro manual'"
              class="pw-modal__input pw-modal__input--full"
              @keyup.enter="emit('submit')"
            />
          </div>
          <div v-if="modelValue.mode === 'withdraw'" class="pw-modal__field">
            <label class="pw-modal__label">Para confirmar, escribe el monto a retirar *</label>
            <input
              v-model="modelValue.confirmAmount"
              type="number"
              min="0.01"
              step="0.01"
              placeholder="0.00"
              class="pw-modal__input pw-modal__input--full"
              @keyup.enter="emit('submit')"
            />
          </div>
          <div v-if="modelValue.error" class="pw-modal__error">
            <i class="fas fa-exclamation-circle"></i> {{ modelValue.error }}
          </div>
        </div>

        <div class="pw-modal__footer">
          <button class="pw-modal__cancel" :disabled="modelValue.loading" @click="emit('close')">Cancelar</button>
          <button
            class="pw-modal__confirm"
            :class="modelValue.mode === 'deposit' ? 'pw-modal__confirm--deposit' : 'pw-modal__confirm--withdraw'"
            :disabled="modelValue.loading || !withdrawConfirmed(modelValue)"
            @click="emit('submit')"
          >
            <span v-if="modelValue.loading"><span class="pw-modal-spinner"></span> Procesando...</span>
            <span v-else><i :class="modelValue.mode === 'deposit' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i> {{ modelValue.mode === 'deposit' ? 'Confirmar depósito' : 'Confirmar retiro' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* Modal depósito / retiro */
.pw-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.pw-modal {
  background: var(--ds-surface);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18);
  width: 100%;
  max-width: 420px;
  overflow: hidden;
}

.pw-modal__header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--ds-border);
  position: relative;
}

.pw-modal__icon {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.pw-modal__icon--deposit  { background: rgba(22,163,74,.16); color: #4ade80; }
.pw-modal__icon--withdraw { background: rgba(220,38,38,.16); color: #f87171; }

.pw-modal__title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 2px;
}

.pw-modal__subtitle {
  font-size: 0.75rem;
  color: var(--ds-text-2);
  margin: 0;
}

.pw-modal__close {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border-2);
  border-radius: 7px;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--ds-text-2);
  font-size: 0.8rem;
  transition: background 0.12s;
}
.pw-modal__close:hover { background: rgba(220,38,38,.14); color: #f87171; border-color: rgba(220,38,38,.4); }

.pw-modal__body {
  padding: 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.pw-modal__field { display: flex; flex-direction: column; gap: 5px; }

.pw-modal__label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ds-text-2);
}

.pw-modal__input-wrap {
  display: flex;
  align-items: center;
  border: 1px solid var(--ds-border-2);
  border-radius: 9px;
  overflow: hidden;
  transition: border-color 0.15s;
}
.pw-modal__input-wrap:focus-within { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.12); }

.pw-modal__prefix {
  padding: 0 10px;
  background: var(--ds-surface-2);
  border-right: 1px solid var(--ds-border-2);
  color: var(--ds-text-2);
  font-size: 0.85rem;
  font-weight: 600;
  align-self: stretch;
  display: flex;
  align-items: center;
}

.pw-modal__input {
  flex: 1;
  border: none;
  outline: none;
  padding: 9px 12px;
  font-size: 0.9rem;
  color: var(--ds-text);
  background: transparent;
}
.pw-modal__input--full {
  border: 1px solid var(--ds-border-2);
  border-radius: 9px;
  padding: 9px 12px;
  font-size: 0.875rem;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s;
  background: var(--ds-surface);
}
.pw-modal__input--full:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.12); }

.pw-modal__error {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(220,38,38,.14);
  color: #f87171;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 0.8rem;
  font-weight: 500;
}

.pw-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--ds-border);
  background: var(--ds-surface-2);
}

.pw-modal__cancel {
  padding: 8px 20px;
  border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  color: var(--ds-text-2);
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.12s;
}
.pw-modal__cancel:hover:not(:disabled) { background: var(--ds-surface-2); }
.pw-modal__cancel:disabled { opacity: 0.5; cursor: not-allowed; }

.pw-modal__confirm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  border-radius: 8px;
  border: none;
  color: #fff;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s;
}
.pw-modal__confirm:disabled { opacity: 0.6; cursor: not-allowed; }
.pw-modal__confirm--deposit  { background: #16a34a; }
.pw-modal__confirm--deposit:hover:not(:disabled)  { background: #15803d; }
.pw-modal__confirm--withdraw { background: #dc2626; }
.pw-modal__confirm--withdraw:hover:not(:disabled) { background: #b91c1c; }

.pw-modal-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--ds-border-2);
  border-top-color: #fff;
  border-radius: 50%;
  animation: pw-modal-spin 0.8s linear infinite;
}
@keyframes pw-modal-spin { to { transform: rotate(360deg); } }
</style>
