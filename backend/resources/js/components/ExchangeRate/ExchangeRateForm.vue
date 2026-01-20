<script setup lang="ts" name="ExchangeRateForm">
  import { ref, watch } from 'vue'
  import type { Currency } from '@/js/types/currency'

  /**
   * Props que recibe el componente ExchangeRateForm
   */
  const props = defineProps<{
    currencies: Currency[]
    selectedCurrency: Array<string>
    roleUser: string
    tokenId?: string
  }>()

  /**
   * Ref local para manejar el valor de la moneda (solo para roles company/customer)
   */
  const localCurrency = ref('')

  // Ref local para el valor del tipo de cambio
  const exchangeRate = ref<number | null>(null)

  /**
   * Watch para sincronizar localCurrency con selectedCurrency cuando roleUser es company o customer
   */
  watch(
    () => localCurrency.value,
    val => {
      if (props.roleUser === 'company' || props.roleUser === 'customer') {
        props.selectedCurrency[0] = val
      }
    }
  )

  /**
   * Watch para sincronizar selectedCurrency con localCurrency cuando cambie la prop (por ejemplo al editar)
   */
  watch(
    () => props.selectedCurrency[0],
    val => {
      if (props.roleUser === 'company' || props.roleUser === 'customer') {
        localCurrency.value = val || ''
      }
    },
    { immediate: true }
  )
</script>

<template>
  <div class="form-group">
    <label for="currency">Divisa*</label>

    <!-- Input para company/customer con v-model a localCurrency -->
    <input
      v-if="roleUser === 'company' || roleUser === 'customer'"
      type="text"
      id="currency"
      class="form-control"
      name="currency"
      v-model="localCurrency"
      placeholder="Ingrese la moneda"
      required
    />

    <!-- Select para otros roles con binding a selectedCurrency -->
    <select
      v-else
      id="currency"
      class="form-control"
      name="currency"
      v-model="selectedCurrency[0]"
      required
    >
      <option disabled value="">Seleccione una moneda</option>
      <option v-for="currency in currencies" :key="currency.id" :value="currency.iso_code">
        {{ currency.name }} ({{ currency.symbol }})
      </option>
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
      v-model.number="exchangeRate"
      required
      placeholder="Ingrese el valor de cambio"
    />
  </div>

  <div
    id="alert-exchange-rate"
    class="alert alert-danger fade show hide-elem text-center"
    role="alert"
  >
    Un Dólar (USD) es igual a:
    <h6 id="text-exchange-rate">{{ exchangeRate ?? '-' }}</h6>
  </div>
</template>
