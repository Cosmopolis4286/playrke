<script setup lang="ts">
  import { ref } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import ExchangeRateButtons from '@js/Components/ExchangeRate/ExchangeRateButtons.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import { useExchangeRate } from '@js/composables/exchangeRate/useExchangeRate'

  /**
   * Tipos locales
   */
  interface Creator {
    type: string
    id: string | number
  }

  interface ExchangeRatePageProps {
    title: string
    subtitle?: string
    btnCardHeader?: Record<string, unknown>
    currencies: unknown[]
    creator: Creator
    exchangeRates: unknown[]
    isLogged: boolean
  }

  /**
   * Props tipadas correctamente para Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Composable
   */
  const { loading, create, edit, changeStatus, remove, updateExchangeRates } = useExchangeRate(
    props.creator
  )

  /**
   * Estado local
   */
  const selectedCurrency = ref<string | number | null>(null)

  /**
   * Navega al histórico
   */
  function goHistorical(): void {
    if (!selectedCurrency.value) return

    const url = `/exchange-rate/historical/${selectedCurrency.value}?creatorType=${props.creator.type}&creatorId=${props.creator.id}`
    window.location.href = url
  }
</script>

<template>
      <TableWithFilterBridge

    />
</template>
