<script setup lang="ts" name="ExchangeRateForm">
  import { computed, ref, watch } from 'vue'
  import { useExchangeRateForm } from '@/js/composables/exchange-rate/useExchangeRateForm'
  import { useExchangeRate } from '@/js/composables/exchange-rate/useExchangeRate'
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
    modelValue: Partial<ExchangeRate> | Partial<AdminExchangeRate>
    bcvAutoUpdateEnabled?: boolean
  }>()

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: any): void
    (e: 'update:bcvAutoUpdateEnabled', value: boolean): void
  }>()

  /**
   * Composable de formulario
   */
  const { form, errors, maskOptions, isEditMode, isValid, validate } = useExchangeRateForm(
    props.modelValue,
    emit
  )

  /**
   * Composable de tasas de cambio (para consulta y auto-actualización BCV)
   */
  const { fetchBcvRate, toggleBcvAutoUpdate, fetchCurrencyAutoUpdateStatus, toggleCurrencyAutoUpdate } = useExchangeRate()

  /**
   * Estado del switch de actualización automática vía BCV (solo VES)
   */
  const bcvAutoUpdate = ref(props.bcvAutoUpdateEnabled ?? false)

  /**
   * Estado del switch de actualización automática genérico (todas las
   * monedas excepto VES, que usa bcvAutoUpdate con la fuente oficial del
   * BCV) — control independiente por moneda, ver toggleCurrencyAutoUpdate.
   */
  const currencyAutoUpdate = ref(false)

  /**
   * Loading exclusivo para la consulta de la tasa BCV
   */
  const loadingBcvRate = ref(false)

  /* =======================
   * Computed
   * ======================= */

  /**
   * Indica si la divisa seleccionada es el Bolívar venezolano (VES)
   */
  const isVes = computed(() => form.value.currency_id === 'VES')

  /**
   * Monedas disponibles:
   * - Solo FIAT y Activas
   * - En create: excluye usadas
   * - En edit: permite la moneda actual
   */
  const availableCurrencies = computed(() => {
    
    const activeFiatCurrencies = props.currencies.filter(c => 
      c.type === 'FIAT' && c.status === 'ACTIVE'
    )
    
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
        form.value.currency_id = currentCurrency.iso_code
      }
      
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
   * Consulta la tasa oficial USD/VES publicada por el BCV
   * y la asigna al campo "Valor de Cambio"
   */
  async function handleFetchBcvRate(): Promise<void> {
    if (loadingBcvRate.value) return

    loadingBcvRate.value = true
    try {
      const rate = await fetchBcvRate()
      if (rate !== null) {
        form.value.amount = rate
        validate()
      }
    } finally {
      loadingBcvRate.value = false
    }
  }

  /**
   * Maneja el cambio del switch de actualización automática vía BCV
   */
  async function handleBcvAutoUpdateToggle(enabled: boolean): Promise<void> {
    const success = await toggleBcvAutoUpdate(enabled)
    if (!success) {
      bcvAutoUpdate.value = !enabled
      return
    }

    emit('update:bcvAutoUpdateEnabled', enabled)
  }

  /**
   * Maneja el cambio del switch de actualización automática genérico
   * (moneda no-VES) — control independiente por moneda, ver
   * toggleCurrencyAutoUpdate.
   */
  async function handleCurrencyAutoUpdateToggle(enabled: boolean): Promise<void> {
    if (!form.value.currency_id) return

    const success = await toggleCurrencyAutoUpdate(form.value.currency_id, enabled)
    if (!success) {
      currencyAutoUpdate.value = !enabled
    }
  }

  /**
   * Carga el estado del auto-update genérico al abrir el modal en modo
   * edición de una moneda no-VES (create empieza siempre en false: recién
   * se está registrando la tasa, no hay nada que auto-actualizar todavía).
   */
  watch(
    () => form.value.currency_id,
    async (isoCode) => {
      if (!isoCode || isoCode === 'VES' || !isEditMode.value) {
        currencyAutoUpdate.value = false
        return
      }

      currencyAutoUpdate.value = await fetchCurrencyAutoUpdateStatus(isoCode)
    },
    { immediate: true }
  )

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
        v-if="isVes"
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
      >
        <template #append>
          <button
            type="button"
            class="btn btn-outline-secondary"
            title="Consultar tasa oficial del BCV"
            :disabled="loadingBcvRate"
            @click="handleFetchBcvRate"
          >
            <span v-if="loadingBcvRate" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            <i v-else class="fa-solid fa-landmark"></i>
            BCV
          </button>
        </template>
      </FormInput>
      <FormInput
        v-else
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

      <!-- Switch actualización automática vía BCV (solo VES) -->
      <FormSwitch
        v-if="isVes"
        name="bcv-auto-update"
        label="¿Actualizar automáticamente con la tasa del BCV?"
        col="col-12"
        v-model="bcvAutoUpdate"
        @update:modelValue="handleBcvAutoUpdateToggle"
      />

      <!-- Switch actualización automática genérico (todas las monedas
           excepto VES, que usa la fuente oficial del BCV arriba) — solo en
           modo edición: no tiene sentido auto-actualizar una tasa que
           todavía no existe. -->
      <FormSwitch
        v-if="!isVes && isEditMode"
        name="currency-auto-update"
        label="¿Actualizar automáticamente?"
        col="col-12"
        v-model="currencyAutoUpdate"
        @update:modelValue="handleCurrencyAutoUpdateToggle"
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
