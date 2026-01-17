<script setup lang="ts">
const props = defineProps<{
  currencies: { type: Array<T>; required: true };
  isExternalUser: boolean;
  selectedCurrency: { type: [String, Number, null]; default: null };
}>();
</script>

<template>
  <div class="mb-2">
    <button
      v-if="currencies.length || creator.type === 'customer'"
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
