<script setup lang="ts" name="AdminCommerceForm">
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
      exchange_rate?: number | null
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
        exchange_rate?: number | null
      }
    ): void
  }>()

  /* =======================
   * State / Refs
   * ======================= */
  const { modelValue } = toRefs(props)

  const errors = ref<{
    currency?: string
    exchange_rate?: string
  }>({})

  /* =======================
   * Computed
   * ======================= */
  const availableCurrencies = computed<Currency[]>(() => {
    if (!props.usedCurrencies?.length) {
      return props.currencies
    }

    const usedIsoCodes = new Set(props.usedCurrencies.map(currency => currency.iso_code))

    return props.currencies.filter(currency => !usedIsoCodes.has(currency.iso_code))
  })

  const isValid = computed(() => {
    return !errors.value.currency && !errors.value.exchange_rate
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
    errors.value.exchange_rate = ''

    if (!modelValue.value.currency?.trim()) {
      errors.value.currency = 'La divisa es requerida.'
    }

    if (
      modelValue.value.exchange_rate === null ||
      modelValue.value.exchange_rate === undefined ||
      modelValue.value.exchange_rate <= 0
    ) {
      errors.value.exchange_rate = 'El cambio debe ser un número mayor a cero.'
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

  watch(() => [modelValue.value.currency, modelValue.value.exchange_rate], validate, {
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
    <div class="form-group">
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

    <div class="form-group">
      <label>Cambio*</label>

      <input
        type="text"
        class="form-control"
        v-model="modelValue.exchange_rate"
        v-mask="maskOptions"
        placeholder="0.00"
        @blur="validate"
        required
        inputmode="decimal"
      />

      <small v-if="errors.exchange_rate" class="text-danger mt-1">
        {{ errors.exchange_rate }}
      </small>

      <div class="alert alert-primary mb-n2 mt-2" role="alert">
        El tipo de cambio ingresado representa el valor de la moneda seleccionada equivalente a
        <strong>1 USD</strong>.
      </div>
    </div>
  </form>
</template>
