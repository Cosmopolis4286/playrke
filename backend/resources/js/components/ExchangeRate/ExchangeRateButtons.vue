<script setup lang="ts">
  import { computed } from 'vue'
  import type { Currency } from '@/js/types/currency'
  import { UsedCurrency } from '@/js/types/exchange-rate'

  const props = defineProps<{
    currencies: Currency[]
    usedCurrencies?: UsedCurrency[]
    roleUser: string
    selectedCurrency: Array<string>
  }>()

  const currencies = props.currencies
  const usedCurrencies = props.usedCurrencies ?? []

  /**
   * Indica si un usuario externo con token
   */
  const extUser = computed(() => props.roleUser === 'company' || props.roleUser === 'customer')

  /**
   * Indica si es posible registrar una nueva tasa de cambio
   */
  const canCreate = computed(() => {
    if (extUser.value) return true

    const totalCurrencies = currencies.length
    const usedCount = usedCurrencies.length

    return totalCurrencies > 0 && usedCount < totalCurrencies
  })
</script>

<template>
  <div class="row">
    <div class="col-12">
      <button
        v-if="canCreate"
        @click="$emit('create')"
        class="btn btn-sm btn-success"
        type="button"
      >
        <i class="fas fa-plus-square"></i> Añadir
      </button>

      <button
        :disabled="!selectedCurrency"
        @click="$emit('historical')"
        class="btn btn-sm btn-primary ml-2"
        type="button"
      >
        <i class="fa-solid fa-money-bill-trend-up"></i> Histórico
      </button>

      <button @click="$emit('update')" class="btn btn-sm btn-secondary ml-2" type="button">
        <i class="fa-solid fa-rotate"></i> Actualizar
      </button>
    </div>
  </div>
</template>
