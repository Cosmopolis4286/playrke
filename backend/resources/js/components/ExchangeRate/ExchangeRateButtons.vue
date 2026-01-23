<script setup lang="ts">
  import { computed, ref } from 'vue'
  import type { Currency } from '@/js/types/currency'
  import { UsedCurrency } from '@/js/types/exchange-rate'

  const props = defineProps<{
    currencies: Currency[]
    usedCurrencies?: UsedCurrency[]
    hasPrivilegedRole: boolean
    selectedCurrency: Array<string>
  }>()

  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'historical'): void
    (e: 'update'): Promise<void> | void
  }>()

  const currencies = props.currencies
  const usedCurrencies = props.usedCurrencies ?? []

  /**
   * Loading exclusivo del botón "Actualizar"
   */
  const updating = ref(false)

  /**
   * Indica si es posible registrar una nueva tasa de cambio
   */
  const canCreate = computed(() => {
    if (props.hasPrivilegedRole) return true

    return currencies.length > 0 && usedCurrencies.length < currencies.length
  })

  /**
   * Maneja click de actualización con loading local
   */
  async function onUpdate() {
    updating.value = true
    try {
      await emit('update')
    } finally {
      updating.value = false
    }
  }
</script>

<template>
  <div>
    <button v-if="canCreate" @click="$emit('create')" class="btn btn-sm btn-success" type="button">
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

    <button
      :disabled="updating"
      @click="onUpdate"
      class="btn btn-sm btn-secondary ml-2"
      type="button"
    >
      <i class="fa-solid fa-rotate"></i> Actualizar
      <span v-if="updating" class="spinner-border spinner-border-sm ml-1" aria-hidden="true"></span>
    </button>
  </div>
</template>
