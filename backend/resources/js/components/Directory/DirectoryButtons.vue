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
  <div class="mt-2">
    <button v-if="canCreate" @click="$emit('create')" class="btn btn-sm btn-success" type="button">
      <i class="fas fa-plus-square"></i> Registrar
    </button>
  </div>
</template>
