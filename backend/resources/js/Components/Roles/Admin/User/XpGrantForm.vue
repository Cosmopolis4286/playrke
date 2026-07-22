<script setup lang="ts" name="XpGrantForm">
  import { computed } from 'vue'

  /* ==========================================================================
   * Types
   * ========================================================================== */

  export interface XpGrantData {
    amount: string
    reason: string
  }

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    modelValue: XpGrantData
    userName: string
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: XpGrantData): void
  }>()

  /* ==========================================================================
   * Form logic
   * ========================================================================== */

  const form = computed<XpGrantData>({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value)
  })

  const quickAmounts = [25, 50, 100, 250, 500, 1000]

  function selectQuickAmount(value: number) {
    form.value = { ...form.value, amount: value.toString() }
  }

  const parsedAmount = computed(() => parseInt(form.value.amount, 10))
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
  <form id="xp-grant-form" novalidate @submit.prevent="submit">
    <div class="form-group">
      <label class="form-label">Usuario</label>
      <input type="text" class="form-control" :value="userName" readonly />
    </div>

    <div class="form-group">
      <label for="xp-amount" class="form-label">Cantidad de XP</label>
      <input
        id="xp-amount"
        type="number"
        class="form-control"
        v-model="form.amount"
        placeholder="0"
        step="1"
        min="1"
        autofocus
      />
    </div>

    <div class="form-group">
      <label class="form-label">Cantidades rápidas</label>
      <div class="quick-amounts">
        <button
          v-for="quickAmount in quickAmounts"
          :key="quickAmount"
          type="button"
          class="quick-amount-btn"
          :class="{ 'quick-amount-btn--active': form.amount === quickAmount.toString() }"
          @click="selectQuickAmount(quickAmount)"
        >
          {{ quickAmount }} XP
        </button>
      </div>
    </div>

    <div class="form-group">
      <label for="xp-reason" class="form-label">Motivo (opcional)</label>
      <textarea
        id="xp-reason"
        class="form-control"
        v-model="form.reason"
        rows="2"
        maxlength="255"
        placeholder="Ej. compensación por incidente, premio de evento..."
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
