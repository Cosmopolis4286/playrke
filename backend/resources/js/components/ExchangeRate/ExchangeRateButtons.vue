<script setup lang="ts">
  import { computed } from 'vue'
  import type { Currency } from '@/js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    roleUser: string
    selectedCurrency: Array<string>
  }>()

  /**
   * Indica si un usuario externo con token
   */
  const extUser = computed(() => props.roleUser === 'company' || props.roleUser === 'customer')
</script>

<template>
  <div class="mb-2">
    <button
      v-if="currencies.length || extUser"
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
</template>
