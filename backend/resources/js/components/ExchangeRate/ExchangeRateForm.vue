<script setup lang="ts">
  import type { Currency } from '@/js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    selectedCurrency: Array<string>
    roleUser: string
    tokenId?: string
  }>()
</script>

<template>
  <div class="form-group">
    <label for="currency">Divisa*</label>
    <input
      v-if="roleUser === 'company' || roleUser === 'customer'"
      type="text"
      id="currency"
      class="form-control"
      name="currency"
    />
    <select v-else v-model="selectedCurrency" id="currency" class="form-control" name="currency">
      <option disabled value="">Seleccione una moneda</option>
      <!-- <option v-for="(label, key) in source" :key="key" :value="key">{{ label }}</option> -->
    </select>
  </div>
  <div class="form-group">
    <label for="exchange-rate">Cambio*</label>
    <input
      type="number"
      step="0.01"
      min="0.01"
      id="exchange-rate"
      class="form-control"
      name="exchange_rate"
    />
  </div>
  <div
    id="alert-exchange-rate"
    class="alert alert-danger fade show hide-elem text-center"
    role="alert"
  >
    Un Dólar (USD) es igual a:
    <h6 id="text-exchange-rate"></h6>
  </div>
</template>
