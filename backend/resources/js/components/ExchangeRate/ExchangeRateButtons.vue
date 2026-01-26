<script setup lang="ts">
  import { computed } from 'vue'
  import type { UsedCurrency } from '@/js/types/exchange-rate'
  import type { Currency } from '@/js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    usedCurrencies?: UsedCurrency[]
    hasPrivilegedRole: boolean
    selectedCurrency: Array<string>

    /**
     * Indica si la acción "Actualizar tasas" está en progreso
     * (controlado por el componente padre)
     */
    updatingExchangeRates: boolean
  }>()

  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'historical'): void
    (e: 'updateExchangeRates'): void
  }>()

  const currencies = props.currencies ?? []

  // Monedas en uso
  const usedCurrencies = computed(() => props.usedCurrencies ?? [])

  /**
   * Indica si es posible registrar una nueva tasa de cambio
   */
  const canCreate = computed(() => {
    if (props.hasPrivilegedRole) return true

    return currencies.length > 0 && usedCurrencies.value.length < currencies.length
  })

  /**
   * Maneja el click del botón "Actualizar".
   * No ejecuta lógica async, solo notifica al padre.
   */
  function onUpdateExchangeRates(): void {
    if (props.updatingExchangeRates) return
    emit('updateExchangeRates')
  }
</script>

<template>
  <div>
    <button v-if="canCreate" @click="$emit('create')" class="btn btn-sm btn-success" type="button">
      <i class="fas fa-plus-square"></i> Registrar
    </button>

    <button
      :disabled="!selectedCurrency.length"
      @click="$emit('historical')"
      class="btn btn-sm btn-primary ml-2"
      type="button"
    >
      <i class="fa-solid fa-money-bill-trend-up"></i> Histórico
    </button>

    <button
      :disabled="props.updatingExchangeRates"
      @click="onUpdateExchangeRates"
      class="btn btn-sm btn-secondary ml-2"
      type="button"
    >
      <i class="fa-solid fa-rotate"></i> Actualizar
      <span
        v-if="props.updatingExchangeRates"
        class="spinner-border spinner-border-sm ml-1"
        aria-hidden="true"
      ></span>
    </button>
  </div>
</template>
