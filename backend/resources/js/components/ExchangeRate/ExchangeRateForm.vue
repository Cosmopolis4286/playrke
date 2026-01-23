<script setup lang="ts" name="ExchangeRateForm">
  /* =======================
   * Imports
   * ======================= */
  import { ref, watch, computed, toRefs } from 'vue'
  import type { Currency } from '@/js/types/currency'
  import type { UsedCurrency } from '@/js/types/exchange-rate'

  /* =======================
   * Props
   * ======================= */
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

  /* =======================
   * Emits
   * ======================= */
  const emit = defineEmits<{
    (e: 'submit'): void
    (
      e: 'update:modelValue',
      value: {
        currency?: string
        amount?: number | null
      }
    ): void
  }>()

  /* =======================
   * State / Refs
   * ======================= */
  const { modelValue } = toRefs(props)

  const errors = ref<{
    currency?: string
    amount?: string
  }>({})

  /* =======================
   * Computed
   * ======================= */
  const availableCurrencies = computed<Currency[]>(() => {
    if (!props.usedCurrencies?.length) {
      return props.currencies.filter(currency => currency.type === 'FIAT')
    }

    const usedIsoCodes = new Set(props.usedCurrencies.map(currency => currency.iso_code))

    return props.currencies.filter(
      currency => currency.type === 'FIAT' && !usedIsoCodes.has(currency.iso_code)
    )
  })

  const isValid = computed(() => {
    return !errors.value.currency && !errors.value.amount
  })

  /* =======================
   * Constants
   * ======================= */
  const maskOptions = {
    mask: Number,
    scale: 2,
    thousandsSeparator: '',
    padFractionalZeros: false,
    normalizeZeros: true,
    radix: '.',
    mapToRadix: [',']
  }

  /* =======================
   * Methods
   * ======================= */
  const validate = (): void => {
    errors.value.currency = ''
    errors.value.amount = ''

    if (!modelValue.value.currency?.trim()) {
      errors.value.currency = 'La divisa es requerida.'
    }

    if (
      modelValue.value.amount === null ||
      modelValue.value.amount === undefined ||
      modelValue.value.amount <= 0
    ) {
      errors.value.amount = 'El cambio debe ser mayor a cero.'
    }
  }

  const submit = (): boolean => {
    validate()

    if (!isValid.value) return false

    emit('submit')
    return true
  }

  /* =======================
   * Watchers
   * ======================= */
  watch(modelValue, newValue => emit('update:modelValue', newValue), { deep: true })

  watch(() => [modelValue.value.currency, modelValue.value.amount], validate, {
    immediate: true
  })

  /* =======================
   * Expose
   * ======================= */
  defineExpose({
    submit,
    get isValid() {
      return isValid.value
    }
  })
</script>

<template>
  <form @submit.prevent="submit" novalidate>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label>Divisa*</label>

        <input
          v-if="hasPrivilegedRole"
          type="text"
          class="form-control"
          v-model="modelValue.currency"
          required
          @blur="validate"
        />

        <select v-else class="form-control" v-model="modelValue.currency" required @blur="validate">
          <option disabled value="">Seleccione una moneda</option>
          <option
            v-for="currency in availableCurrencies"
            :key="currency.id"
            :value="currency.iso_code"
          >
            {{ currency.name }} ({{ currency.symbol }})
          </option>
        </select>
        <small v-if="errors.currency" class="text-danger mt-1">{{ errors.currency }}</small>
      </div>

      <div class="form-group col-md-6">
        <label>Cambio*</label>

        <input
          type="text"
          class="form-control"
          v-model="modelValue.amount"
          v-mask="maskOptions"
          placeholder="0.00"
          @blur="validate"
          required
          inputmode="decimal"
        />

        <small v-if="errors.amount" class="text-danger mt-1">
          {{ errors.amount }}
        </small>
      </div>
    </div>

    <div class="form-group">
      <div class="custom-control custom-switch">
        <input
          type="checkbox"
          class="custom-control-input"
          v-model="modelValue.status"
          id="custom-switch-status"
        />
        <label class="custom-control-label" for="custom-switch-status">
          Activar Tasa de Cambio
        </label>
      </div>
    </div>

    <div class="alert alert-primary mb-0" role="alert">
      El tipo de cambio ingresado representa el valor de la moneda seleccionada equivalente a
      <strong>1 USD</strong>.
    </div>
  </form>
</template>
