<script setup lang="ts" name="WalletLimitForm">
  import { computed } from 'vue'
  import { useWalletLimitForm } from '@/js/composables/walletLimit/useWalletLimitForm'
  import type { WalletLimit, WalletLimitCurrency } from '@/js/types/walletLimit'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  /**
   * Props
   */
  const props = defineProps<{
    modelValue: Partial<WalletLimit>
    currencies: WalletLimitCurrency[]
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<WalletLimit>): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, isValid, validate, applyRecommendation, isRecommending } = useWalletLimitForm(props.modelValue, emit)

  const KYC_LEVELS = [
    { value: 0, label: 'Nivel 0 — Sin verificar' },
    { value: 1, label: 'Nivel 1 — Email/teléfono verificados' },
    { value: 2, label: 'Nivel 2 — Identidad verificada (KYC completo)' }
  ]

  const currencyOptions = computed(() => [
    { value: null, label: 'Todas las monedas (fallback global)' },
    ...props.currencies.map(c => ({ value: c.id, label: `${c.iso_code} — ${c.name}` }))
  ])

  /**
   * Submit
   */
  function submit(): boolean {
    validate()
    return isValid.value
  }

  /**
   * API pública para el modal
   */
  defineExpose({ submit, isValid })
</script>

<template>
  <form @submit.prevent novalidate>
    <div class="row">
      <!-- Moneda -->
      <FormSelect
        name="currency_id"
        label="Moneda"
        col="col-md-6"
        :options="currencyOptions"
        option-value="value"
        option-label="label"
        :model-value="form.currency_id"
        @update:model-value="form.currency_id = $event"
      />

      <!-- Nivel KYC -->
      <FormSelect
        name="kyc_level"
        label="Nivel KYC"
        col="col-md-6"
        required
        :options="KYC_LEVELS"
        option-value="value"
        option-label="label"
        :model-value="form.kyc_level"
        :error="errors.kyc_level"
        @update:model-value="form.kyc_level = Number($event) as 0 | 1 | 2"
      />

      <!-- Recomendación según tasa de cambio -->
      <div class="col-12 mb-2">
        <button
          type="button"
          class="btn btn-sm btn-outline-primary"
          :disabled="isRecommending || form.kyc_level === undefined"
          @click="applyRecommendation"
        >
          <i class="fas" :class="isRecommending ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'"></i>
          {{ isRecommending ? 'Calculando...' : 'Recomendar según tasa de cambio actual' }}
        </button>
        <p class="text-muted small mt-1 mb-0">
          Calcula los 7 montos escalando un baseline en USD por la tasa de cambio activa de la moneda seleccionada.
          Puedes ajustarlos manualmente después.
        </p>
      </div>

      <div class="col-12">
        <h6 class="text-muted mt-2 mb-3"><i class="fas fa-arrow-down fa-fw text-success"></i> Depósito</h6>
      </div>

      <!-- Depósito mínimo -->
      <FormInput
        name="min_deposit"
        label="Depósito mínimo"
        type="number"
        col="col-md-6"
        min="0"
        step="0.01"
        :error="errors.min_deposit"
        :modelValue="form.min_deposit ?? 0"
        @update:modelValue="form.min_deposit = Number($event)"
      />

      <!-- Depósito máximo -->
      <FormInput
        name="max_deposit"
        label="Depósito máximo"
        type="number"
        col="col-md-6"
        min="0"
        step="0.01"
        :error="errors.max_deposit"
        :modelValue="form.max_deposit ?? 0"
        @update:modelValue="form.max_deposit = Number($event)"
      />

      <div class="col-12">
        <h6 class="text-muted mt-2 mb-3"><i class="fas fa-arrow-up fa-fw text-primary"></i> Retiro</h6>
      </div>

      <!-- Retiro mínimo por transacción -->
      <FormInput
        name="min_per_transaction"
        label="Retiro mínimo (por transacción)"
        type="number"
        col="col-md-6"
        min="0"
        step="0.01"
        :error="errors.min_per_transaction"
        :modelValue="form.min_per_transaction ?? 0"
        @update:modelValue="form.min_per_transaction = Number($event)"
      />

      <!-- Retiro máximo por transacción -->
      <FormInput
        name="max_per_transaction"
        label="Retiro máximo (por transacción)"
        type="number"
        col="col-md-6"
        min="0"
        step="0.01"
        :error="errors.max_per_transaction"
        :modelValue="form.max_per_transaction ?? 0"
        @update:modelValue="form.max_per_transaction = Number($event)"
      />

      <!-- Límite diario -->
      <FormInput
        name="max_daily"
        label="Límite diario"
        type="number"
        col="col-md-4"
        min="0"
        step="0.01"
        :error="errors.max_daily"
        :modelValue="form.max_daily ?? 0"
        @update:modelValue="form.max_daily = Number($event)"
      />

      <!-- Límite semanal -->
      <FormInput
        name="max_weekly"
        label="Límite semanal"
        type="number"
        col="col-md-4"
        min="0"
        step="0.01"
        :error="errors.max_weekly"
        :modelValue="form.max_weekly ?? 0"
        @update:modelValue="form.max_weekly = Number($event)"
      />

      <!-- Límite mensual -->
      <FormInput
        name="max_monthly"
        label="Límite mensual"
        type="number"
        col="col-md-4"
        min="0"
        step="0.01"
        :error="errors.max_monthly"
        :modelValue="form.max_monthly ?? 0"
        @update:modelValue="form.max_monthly = Number($event)"
      />

      <!-- Activo -->
      <FormSwitch
        name="wallet-limit-status"
        label="Configuración activa"
        col="col-12"
        :modelValue="form.is_active ?? true"
        @update:modelValue="form.is_active = $event"
      />
    </div>
  </form>
</template>
