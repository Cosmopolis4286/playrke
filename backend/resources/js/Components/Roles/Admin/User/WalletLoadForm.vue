<script setup lang="ts" name="WalletLoadForm">
  import { computed } from 'vue'

  /* ==========================================================================
   * Types
   * ========================================================================== */

  export interface WalletLoadData {
    amount: string
    reason: string
  }

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    modelValue: WalletLoadData
    userName: string
    userBalance?: number | null
    userCurrency?: string
    userCurrencySymbol?: string
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: WalletLoadData): void
  }>()

  /* ==========================================================================
   * Form logic
   * ========================================================================== */

  const form = computed<WalletLoadData>({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value)
  })

  const quickAmounts = [10, 25, 50, 100, 200, 500]

  function selectQuickAmount(value: number) {
    form.value = { ...form.value, amount: value.toString() }
  }

  const parsedAmount = computed(() => parseFloat(form.value.amount))
  const isValid = computed(() => !!form.value.amount && !isNaN(parsedAmount.value) && parsedAmount.value > 0)

  /* ==========================================================================
   * Submit
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form id="wallet-load-form" novalidate @submit.prevent="submit">
    <div class="form-group">
      <label class="form-label">Usuario</label>
      <div class="user-row">
        <input type="text" class="form-control" :value="userName" readonly />
        <span v-if="userCurrency" class="user-row__currency">({{ userCurrency }})</span>
      </div>
    </div>

    <div class="form-group balance-box">
      <label class="form-label">Saldo actual</label>
      <span class="balance-box__amount">
        {{ userCurrencySymbol || '$' }}{{ (userBalance ?? 0).toFixed(2) }}
      </span>
    </div>

    <div class="form-group">
      <label for="wallet-amount" class="form-label">Monto a cargar</label>
      <div class="amount-input">
        <span class="amount-input__symbol">{{ userCurrencySymbol || '$' }}</span>
        <input
          id="wallet-amount"
          type="number"
          class="form-control"
          v-model="form.amount"
          placeholder="0.00"
          step="0.01"
          min="0.01"
          autofocus
        />
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Montos rápidos</label>
      <div class="quick-amounts">
        <button
          v-for="quickAmount in quickAmounts"
          :key="quickAmount"
          type="button"
          class="quick-amount-btn"
          :class="{ 'quick-amount-btn--active': form.amount === quickAmount.toString() }"
          @click="selectQuickAmount(quickAmount)"
        >
          {{ userCurrencySymbol || '$' }}{{ quickAmount }}
        </button>
      </div>
    </div>

    <div class="form-group">
      <label for="wallet-reason" class="form-label">Motivo (opcional)</label>
      <textarea
        id="wallet-reason"
        class="form-control"
        v-model="form.reason"
        rows="2"
        maxlength="255"
        placeholder="Ej. compensación por incidente, bono de bienvenida..."
      />
    </div>
  </form>
</template>

<style scoped>
.form-group { margin-bottom: 1rem; }

.form-label {
  display: block;
  margin-bottom: 0.4rem;
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--ds-text);
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.9rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.375rem;
  font-size: 0.9rem;
  background: var(--ds-surface);
  color: var(--ds-text);
  font-family: inherit;
  resize: vertical;
}

.form-control:focus {
  border-color: #667eea;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2);
}

.user-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-row__currency {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ds-text-muted);
  white-space: nowrap;
}

.balance-box {
  padding: 0.75rem 1rem;
  border: 1px solid var(--ds-border);
  border-radius: 0.5rem;
  background: var(--ds-surface-2);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.balance-box__amount {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--ds-text);
}

.amount-input {
  position: relative;
}

.amount-input__symbol {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  transform: translateY(-50%);
  font-weight: 700;
  color: var(--ds-text-muted);
  pointer-events: none;
}

.amount-input .form-control {
  padding-left: 2rem;
}

.quick-amounts {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
}

.quick-amount-btn {
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  font-weight: 600;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.5rem;
  background: var(--ds-surface);
  color: var(--ds-text-2);
  cursor: pointer;
  transition: all 0.15s ease;
}

.quick-amount-btn:hover {
  border-color: #818cf8;
  background: rgba(99, 102, 241, 0.08);
}

.quick-amount-btn--active {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border-color: transparent;
}
</style>
