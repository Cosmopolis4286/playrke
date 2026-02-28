<script setup lang="ts" name="RoomCurrencySection">
  import { computed } from 'vue'
  import MultiCurrencyPrice, {
    type CurrencyOption,
    type MultiCurrencyValue
  } from '@/js/Components/Currency/MultiCurrencyPrice.vue'

  interface RoomCurrencyForm {
    currencyConfig: MultiCurrencyValue
  }

  const props = defineProps<{
    modelValue: RoomCurrencyForm
    errors?: Record<string, string | undefined>
    availableCurrencies: CurrencyOption[]
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: RoomCurrencyForm): void
  }>()

  /**
   * Bridge v-model hacia MultiCurrencyPrice
   */
  const currencyConfig = computed({
    get: () => props.modelValue.currencyConfig,
    set: (value: MultiCurrencyValue) => {
      emit('update:modelValue', {
        ...props.modelValue,
        currencyConfig: value
      })
    }
  })
</script>

<template>
  <div class="room-currency-section mt-4">
    <h5 class="mb-3">Configuración de Monedas</h5>

    <MultiCurrencyPrice
      v-model="currencyConfig"
      :currencies="availableCurrencies"
      :errors="errors"
      label="Precios por moneda"
    />
  </div>
</template>
