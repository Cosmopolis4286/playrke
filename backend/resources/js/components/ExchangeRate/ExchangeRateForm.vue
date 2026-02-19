<script setup lang="ts" name="ExchangeRateForm">
  import { computed } from 'vue'
  import { useExchangeRateForm } from '@/js/composables/exchange-rate/useExchangeRateForm'
  import type { Currency } from '@/js/types/currency'
  import type { ExchangeRate, UsedCurrency } from '@/js/types/exchange-rate'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'

  /**
   * Props
   */
  const props = defineProps<{
    usedCurrencies?: UsedCurrency[]
    currencies: Currency[]
    hasPrivilegedRole: boolean
    modelValue: Partial<ExchangeRate>
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: any): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, maskOptions, isEditMode, isValid, validate } = useExchangeRateForm(
    props.modelValue,
    emit
  )

  /* =======================
   * Computed
   * ======================= */

  /**
   * Monedas disponibles:
   * - Solo FIAT
   * - En create: excluye usadas
   * - En edit: permite la moneda actual
   */
  const availableCurrencies = computed(() => {
    const fiatCurrencies = props.currencies.filter(c => c.type === 'FIAT')

    if (isEditMode.value || !props.usedCurrencies?.length) {
      return fiatCurrencies
    }

    const usedIsoCodes = new Set(props.usedCurrencies.map(c => c.iso_code))
    return fiatCurrencies.filter(c => !usedIsoCodes.has(c.iso_code))
  })

  /* =======================
   * Methods
   * ======================= */

  /**
   * Submit interno del formulario
   */
  const submit = (): boolean => {
    validate()
    if (!isValid.value) return false

    emit('submit')
    return true
  }

  /**
   * API pública para el modal
   */
  defineExpose({
    submit,
    isValid
  })
</script>
<template>
  <form @submit.prevent="submit" novalidate>
    <div class="row">
      <!-- Divisa: input libre para privilegiados, select para el resto -->
      <FormInput
        v-if="hasPrivilegedRole"
        name="currency_id"
        label="Divisa"
        col="col-md-6"
        :required="true"
        :disabled="isEditMode"
        :error="errors.currency_id"
        :modelValue="form.currency_id"
        @update:modelValue="form.currency_id = $event"
        @blur="validate"
      />
      <FormSelect
        v-else
        name="currency_id"
        label="Divisa"
        col="col-md-6"
        :required="true"
        :disabled="isEditMode"
        :error="errors.currency_id"
        :options="availableCurrencies"
        option-value="iso_code"
        option-label="description"
        placeholder="Seleccione una moneda"
        :modelValue="form.currency_id"
        @update:modelValue="form.currency_id = $event"
      />

      <!-- Valor de cambio -->
      <FormInput
        name="amount"
        label="Valor de Cambio"
        col="col-md-6"
        :required="true"
        placeholder="0.00"
        :mask="maskOptions"
        :error="errors.amount"
        :modelValue="form.amount"
        @update:modelValue="form.amount = $event"
        @blur="validate"
      />

      <!-- Switch estado -->
      <FormSwitch
        name="exchange-rate-status"
        label="Activar Tasa de Cambio"
        col="col-12"
        :modelValue="form.status"
        @update:modelValue="form.status = $event"
      />

      <div class="col-12">
        <div class="alert alert-primary mb-0">
          El tipo de cambio ingresado representa el valor de la moneda seleccionada equivalente a
          <strong>1 USD</strong>.
        </div>
      </div>
    </div>
  </form>
</template>
