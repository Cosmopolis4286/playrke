<script setup lang="ts">
  import type { Currency } from '@/js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    selectedCurrency: Array<string>
    tokenId?: string
  }>()
</script>

<template>
  <div class="mb-2">
    <button
      v-if="currencies.length || tokenId"
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
<div class="form-group">
    <label for="currency">Divisa*</label>
    @if ($creator['type'] === 'customer')
        <input type="text" id="currency" class="form-control" name="currency">
    @else
        <select id="currency" class="form-control" name="currency"></select>
    @endif
</div>
<div class="form-group">
    <label for="exchange-rate">Cambio*</label>
    <input type="number" step="0.01" min="0.01" id="exchange-rate" class="form-control" name="exchange_rate">
    <input type="hidden" id="creator" name="creator" value="{{ json_encode($creator) }}">
</div>
<div id="alert-exchange-rate" class="alert alert-danger fade show text-center hide-elem" role="alert">
    Un Dólar (USD) es igual a: <h6 id="text-exchange-rate"></h6>
</div>
