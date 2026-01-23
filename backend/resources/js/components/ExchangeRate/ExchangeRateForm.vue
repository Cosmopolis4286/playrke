<script setup lang="ts" name="ExchangeRateForm">
  import { computed } from 'vue'
  import { useExchangeRateForm } from '@/js/composables/exchange-rate/useExchangeRateForm'
  import type { Currency } from '@/js/types/currency'
  import type { UsedCurrency } from '@/js/types/exchange-rate'

  /**
   * Props
   */
  const props = defineProps<{
    usedCurrencies?: UsedCurrency[]
    currencies: Currency[]
    hasPrivilegedRole: boolean
    modelValue: {
      currency?: string
      amount?: number
      status?: string
    }
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
      <div class="form-group col-md-6">
        <label>Divisa*</label>
        <!-- Input libre solo para roles privilegiados -->
        <input
          v-if="hasPrivilegedRole"
          type="text"
          class="form-control"
          v-model="form.currency"
          :disabled="isEditMode"
          @blur="validate"
        />
        <!-- Select normal -->
        <select
          v-else
          class="form-control"
          v-model="form.currency"
          :disabled="isEditMode"
          @blur="validate"
        >
          <option disabled value="">Seleccione una moneda</option>
          <option
            v-for="currency in availableCurrencies"
            :key="currency.id"
            :value="currency.iso_code"
          >
            {{ currency.name }} ({{ currency.symbol }})
          </option>
        </select>

        <small v-if="errors.currency" class="text-danger">
          {{ errors.currency }}
        </small>
      </div>

      <div class="form-group col-md-6">
        <label>Cambio*</label>
        <input
          type="text"
          class="form-control"
          v-model="form.amount"
          v-mask="maskOptions"
          placeholder="0.00"
          inputmode="decimal"
          @blur="validate"
        />
        <small v-if="errors.amount" class="text-danger">
          {{ errors.amount }}
        </small>
      </div>

      <div class="form-group col-12">
        <div class="custom-control custom-switch">
          <input
            id="exchange-rate-status"
            type="checkbox"
            class="custom-control-input"
            v-model="form.status"
          />
          <label class="custom-control-label" for="exchange-rate-status">
            Activar Tasa de Cambio
          </label>
        </div>
      </div>
      <div class="alert alert-primary mb-0">
        El tipo de cambio ingresado representa el valor de la moneda seleccionada equivalente a
        <strong>1 USD</strong>.
      </div>
    </div>
  </form>
</template>
