<script setup lang="ts">
  import { ref, computed } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ExchangeRateButtons from '@js/Components/ExchangeRate/ExchangeRateButtons.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import { useExchangeRate } from '@js/composables/exchangeRate/useExchangeRate'
  import type { ExchangeRatePageProps, UsedCurrency } from '@js/types/exchange-rate'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import ExchangeRateForm from '@/js/Components/ExchangeRate/ExchangeRateForm.vue'

  /**
   * Props tipadas correctamente para Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Indica si es un usuario con privilegios
   */
  const hasPrivilegedRole = computed(
    () => props.roleUser === 'company' || props.roleUser === 'customer'
  )

  /**
   * Composable
   */
  const {
    showModalForm,
    modalTitle,
    exchangeRateData,
    submit,
    create,
    edit,
    changeStatus,
    remove,
    updateExchangeRates
  } = useExchangeRate(props.tokenId)

  /**
   * Estado local
   */
  const selectedCurrency = ref<Array<string>>([''])
  const currencies = props.currencies
  const exchangeRates = props.exchangeRates

  // Arreglo con ids de currencies usados en exchangeRates
  const usedCurrencies: UsedCurrency[] = exchangeRates.map(rate => {
    const currency = currencies.find(c => c.id == rate.currency_id)

    return {
      id: rate.currency_id,
      name: currency?.name ?? 'Desconocido',
      iso_code: currency?.iso_code ?? 'Desconocido',
      type: currency?.type,
      country: currency?.country
        ? {
            id: currency.country.id,
            name: currency.country.name,
            iso_code: currency.country.iso_code_3
          }
        : null
    }
  })

  /**
   * Referencias
   */
  interface ExchangeRateFormExpose {
    submit: () => boolean
    isValid: boolean
  }

  const exchangeRateFormRef = ref<ExchangeRateFormExpose | null>(null)
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
  <div class="card">
    <CardHeader title="Tasas de Cambio" subtitle="Valores en base a Un Dólar (1 USD)" />

    <div class="card-body">
      <ExchangeRateButtons
        :currencies="currencies"
        :usedCurrencies="usedCurrencies"
        :has-privileged-role="hasPrivilegedRole"
        :selected-currency="selectedCurrency"
        @create="create"
        @historical="goHistorical"
        @update="updateExchangeRates"
      />

      <ExchangeRateTable
        :usedCurrencies="usedCurrencies"
        :exchange-rates="exchangeRates"
        :has-privileged-role="hasPrivilegedRole"
        @edit="edit"
        @change-status="changeStatus"
        @delete="remove"
      />
    </div>
  </div>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="submit"
    :confirm-disabled="!(exchangeRateFormRef?.isValid ?? false)"
  >
    <ExchangeRateForm
      ref="exchangeRateFormRef"
      :currencies="currencies"
      :usedCurrencies="usedCurrencies"
      :has-privileged-role="hasPrivilegedRole"
      :model-value="exchangeRateData"
    />
  </Modal>
</template>
