<script setup lang="ts" name="ExchangeRateForm">
  import { ref, watch, computed, toRefs } from 'vue'
  import type { Currency } from '@/js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    roleUser: string
    modelValue: {
      currency?: string
      exchange_rate?: number | null
    }
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: { currency?: string; exchange_rate?: number | null }): void
  }>()

  // Se extraen refs reactivas de modelValue
  const { modelValue } = toRefs(props)

  // Errores de validación
  const errors = ref<{ currency?: string; exchange_rate?: string }>({})

  const maskOptions = {
    mask: Number,
    scale: 2, // 2 decimales
    thousandsSeparator: '',
    padFractionalZeros: false,
    normalizeZeros: true,
    radix: '.',
    mapToRadix: [','] // aceptar coma como decimal también si quieres
  }

  // Validar campos
  const validate = () => {
    errors.value.currency = ''
    errors.value.exchange_rate = ''

    if (!modelValue.value.currency || modelValue.value.currency.trim() === '') {
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

  // Computed que indica si formulario es válido
  const isValid = computed(() => {
    return !errors.value.currency && !errors.value.exchange_rate
  })

  // Cuando cambie el formulario, emitir para sincronizar
  watch(
    modelValue,
    newVal => {
      emit('update:modelValue', newVal)
    },
    { deep: true }
  )

  // Validar cambios en props.modelValue automáticamente
  watch(
    () => [modelValue.value.currency, modelValue.value.exchange_rate],
    () => validate(),
    { immediate: true }
  )

  // Función submit para emitir si válido
  const submit = (): boolean => {
    validate()
    if (isValid.value) {
      emit('submit')
      return true
    }
    return false
  }

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
        v-if="roleUser === 'company' || roleUser === 'customer'"
        type="text"
        class="form-control"
        v-model="modelValue.currency"
        required
        @blur="validate"
      />

      <select v-else class="form-control" v-model="modelValue.currency" required @blur="validate">
        <option disabled value="">Seleccione una moneda</option>
        <option v-for="currency in currencies" :key="currency.id" :value="currency.iso_code">
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
    </div>
  </form>
</template>
