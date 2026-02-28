<script setup lang="ts" name="MultiCurrencyPrice">
  import { reactive, watch, computed } from 'vue'

  export interface CurrencyOption {
    code: string
    symbol: string
    decimals: number
  }

  export interface MultiCurrencyValue {
    prices: Record<string, number>
    mainCurrency: string
  }

  const props = defineProps<{
    modelValue: MultiCurrencyValue
    currencies: CurrencyOption[]
    errors?: Record<string, string | undefined>
    label?: string
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: MultiCurrencyValue): void
  }>()

  /**
   * Estado local
   */
  const state = reactive<MultiCurrencyValue>({
    prices: { ...props.modelValue.prices },
    mainCurrency: props.modelValue.mainCurrency
  })

  /**
   * Sync hacia padre
   */
  watch(
    state,
    () => {
      emit('update:modelValue', { ...state })
    },
    { deep: true }
  )

  /**
   * Monedas activas (solo las que tienen precio definido)
   */
  const activeCurrencies = computed(() =>
    props.currencies.filter(c => state.prices[c.code] !== undefined)
  )

  /**
   * Agregar moneda
   */
  function addCurrency(currency: CurrencyOption) {
    if (state.prices[currency.code] !== undefined) return
    state.prices[currency.code] = 0
  }

  /**
   * Eliminar moneda
   */
  function removeCurrency(code: string) {
    delete state.prices[code]
    if (state.mainCurrency === code) {
      state.mainCurrency = ''
    }
  }

  const mainError = computed(() => props.errors?.mainCurrency)
</script>

<template>
  <div class="multi-currency-price">
    <h6 v-if="label" class="mb-3">{{ label }}</h6>

    <!-- Selector para agregar monedas -->
    <div class="mb-3">
      <label class="form-label">Agregar moneda</label>
      <div class="d-flex flex-wrap gap-2">
        <button
          v-for="currency in currencies"
          :key="currency.code"
          type="button"
          class="btn btn-sm"
          :class="[
            state.prices[currency.code] !== undefined ? 'btn-primary' : 'btn-outline-secondary'
          ]"
          @click="addCurrency(currency)"
        >
          {{ currency.code }}
        </button>
      </div>
    </div>

    <!-- Tabla de precios -->
    <div v-if="activeCurrencies.length" class="table-responsive">
      <table class="table-sm table align-middle">
        <thead>
          <tr>
            <th>Moneda</th>
            <th>Precio</th>
            <th>Principal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="currency in activeCurrencies" :key="currency.code">
            <td>
              <strong>{{ currency.code }}</strong>
            </td>

            <td style="width: 200px">
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  {{ currency.symbol }}
                </span>
                <input
                  type="number"
                  min="0"
                  :step="1 / Math.pow(10, currency.decimals)"
                  class="form-control"
                  v-model.number="state.prices[currency.code]"
                />
              </div>
            </td>

            <td>
              <input type="radio" :value="currency.code" v-model="state.mainCurrency" />
            </td>

            <td>
              <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                @click="removeCurrency(currency.code)"
              >
                ×
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="mainError" class="text-danger small">
        {{ mainError }}
      </div>
    </div>
  </div>
</template>
