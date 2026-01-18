<script setup lang="ts">
  import { ref } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ExchangeRateButtons from '@js/Components/ExchangeRate/ExchangeRateButtons.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import { useExchangeRate } from '@js/composables/exchangeRate/useExchangeRate'
  import type { ExchangeRatePageProps } from '@js/types/exchange-rate'

  const title = 'Tasas de Cambio'
  /**
   * Props tipadas correctamente para Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Composable
   */
  const { loading, create, edit, changeStatus, remove, updateExchangeRates } = useExchangeRate(
    props.tokenId
  )

  /**
   * Estado local
   */
  const selectedCurrency = ref<Array<string>>([''])

  /**
   * Navega al histórico
   */
  function goHistorical(): void {
    if (!selectedCurrency.value) return

    const url = `/exchange-rate/historical/${selectedCurrency.value}?tokenId=${props.tokenId}`
    window.location.href = url
  }
</script>

<template>
  <div class="col-12">
    <div class="card">
      <CardHeader :title="title" />

      <div class="card-body">
        <ExchangeRateButtons
          :currencies="currencies"
          :selectedCurrency="selectedCurrency"
          @create="create"
          @historical="goHistorical"
          @update="updateExchangeRates"
        />

        <ExchangeRateTable
          :exchangeRates="exchangeRates"
          :tokenId="tokenId"
          @edit="edit"
          @change-status="changeStatus"
          @delete="remove"
        />
      </div>
    </div>
  </div>
</template>
