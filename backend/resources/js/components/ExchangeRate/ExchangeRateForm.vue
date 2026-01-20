<script setup lang="ts" name="ExchangeRateForm">
  import type { Currency } from '@/js/types/currency'

  const props = defineProps<{
    currencies: Currency[]
    roleUser: string
    modelValue: {
      currency?: string
      exchange_rate?: number | null
    }
  }>()
</script>

<template>
  <div class="form-group">
    <label>Divisa*</label>

    <!-- Company / Customer -->
    <input
      v-if="roleUser === 'company' || roleUser === 'customer'"
      type="text"
      class="form-control"
      v-model="modelValue.currency"
      required
    />

    <!-- Otros roles -->
    <select v-else class="form-control" v-model="modelValue.currency" required>
      <option disabled value="">Seleccione una moneda</option>
      <option v-for="currency in currencies" :key="currency.id" :value="currency.iso_code">
        {{ currency.name }} ({{ currency.symbol }})
      </option>
    </select>
  </div>

  <div class="form-group">
    <label>Cambio*</label>
    <input
      type="number"
      step="0.01"
      min="0.01"
      class="form-control"
      v-model.number="modelValue.exchange_rate"
      required
    />
  </div>
</template>
