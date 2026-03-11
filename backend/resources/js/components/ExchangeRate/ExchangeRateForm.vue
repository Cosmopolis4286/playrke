<script setup lang="ts" name="ExchangeRateForm">
  import { computed } from 'vue'
  import { useExchangeRateForm } from '@/js/composables/exchange-rate/useExchangeRateForm'
  import type { Currency } from '@/js/types/currency'
  import type { ExchangeRate, AdminExchangeRate, UsedCurrency } from '@/js/types/exchange-rate'
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
    modelValue: Partial<ExchangeRate> | Partial<AdminExchangeRate>
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
   * - Solo FIAT y Activas
   * - En create: excluye usadas
   * - En edit: permite la moneda actual
   */
  const availableCurrencies = computed(() => {
    console.log('availableCurrencies Debug:', {
      allCurrencies: props.currencies,
      isEditMode: isEditMode.value,
      formCurrencyId: form.value.currency_id,
      modelValue: props.modelValue
    })
    
    const activeFiatCurrencies = props.currencies.filter(c => 
      c.type === 'FIAT' && c.status === 'ACTIVE'
    )
    
    console.log('Active FIAT currencies:', activeFiatCurrencies)

    // En modo edición, mostrar solo la moneda actual
    if (isEditMode.value) {
      // Try to find by iso_code first (for regular ExchangeRate)
      let currentCurrency = activeFiatCurrencies.find(c => c.iso_code === form.value.currency_id)
      
      // If not found by iso_code, try by id (for AdminExchangeRate)
      if (!currentCurrency) {
        currentCurrency = activeFiatCurrencies.find(c => c.id === form.value.currency_id)
      }
      
      // If still not found, try to find by checking the modelValue currency data
      if (!currentCurrency && props.modelValue && 'currency' in props.modelValue && props.modelValue.currency) {
        const modelCurrency = props.modelValue as Partial<AdminExchangeRate>
        currentCurrency = activeFiatCurrencies.find(c => 
          c.iso_code === modelCurrency.currency?.iso_code || 
          c.id === modelCurrency.currency?.id
        )
      }
      
      // If still not found, try to match by the first few digits of the ID (data inconsistency workaround)
      if (!currentCurrency && props.modelValue?.currency_id) {
        const targetId = String(props.modelValue.currency_id)
        currentCurrency = activeFiatCurrencies.find(c => 
          c.id.startsWith(targetId.substring(0, 10)) // Match first 10 characters
        )
      }
      
      // If we found a currency but the form has the wrong currency_id, update it
      if (currentCurrency && form.value.currency_id !== currentCurrency.iso_code) {
        console.log('Updating form currency_id from', form.value.currency_id, 'to', currentCurrency.iso_code)
        form.value.currency_id = currentCurrency.iso_code
      }
      
      console.log('Current currency found:', currentCurrency)
      return currentCurrency ? [currentCurrency] : []
    }

    // En modo creación, excluir monedas ya usadas
    if (!props.usedCurrencies?.length) {
      return activeFiatCurrencies
    }

    const usedIsoCodes = new Set(props.usedCurrencies.map(c => c.iso_code))
    return activeFiatCurrencies.filter(c => !usedIsoCodes.has(c.iso_code))
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
      <!-- Divisa: select para todos los usuarios con monedas FIAT activas filtradas -->
      <FormSelect
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
