<script setup lang="ts">
  import { ref, computed } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ExchangeRateButtons from '@js/Components/ExchangeRate/ExchangeRateButtons.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import ExchangeRateForm from '@/js/Components/ExchangeRate/ExchangeRateForm.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useExchangeRate } from '@/js/composables/exchange-rate/useExchangeRate'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  import type { ExchangeRatePageProps, UsedCurrency } from '@js/types/exchange-rate'

  /**
   * Props Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Determina si el usuario tiene privilegios
   */
  const hasPrivilegedRole = computed(
    () => props.roleUser === 'company' || props.roleUser === 'customer'
  )

  /**
   * Composable de negocio (no UI)
   */
  const { exchangeRateData, create, edit, changeStatus, remove, updateExchangeRates, submit } =
    useExchangeRate(props.tokenId)

  /**
   * Composable de UI (modal + formulario)
   */
  const {
    showModalForm,
    modalTitle,
    formMode,
    formRef: exchangeRateFormRef,
    handleSubmit
  } = useModalForm(exchangeRateData, submit)

  /**
   * Estado local de selección
   */
  const selectedCurrency = ref<string[]>([])
  const currencies = props.currencies
  const exchangeRates = props.exchangeRates

  /**
   * Monedas usadas en las tasas (derivado)
   */
  const usedCurrencies = computed<UsedCurrency[]>(() =>
    exchangeRates.map(rate => {
      const currency = currencies.find(c => c.id === rate.currency_id)

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
  )

  /**
   * Abre el modal en modo creación
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abre el modal en modo edición
   *
   * @param id Identificador de la tasa
   */
  async function handleEdit(id: string | number): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Navega al histórico de tasas
   */
  function goHistorical(): void {
    if (!selectedCurrency.value.length) return

    window.location.href =
      `/exchange-rate/historical/${selectedCurrency.value}` + `?tokenId=${props.tokenId}`
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
        @create="handleCreate"
        @historical="goHistorical"
        @update="updateExchangeRates"
      />

      <ExchangeRateTable
        :usedCurrencies="usedCurrencies"
        :exchange-rates="exchangeRates"
        :has-privileged-role="hasPrivilegedRole"
        @edit="handleEdit"
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
    :on-submit="handleSubmit"
    :confirm-disabled="!(exchangeRateFormRef?.isValid ?? false)"
  >
    <ExchangeRateForm
      ref="exchangeRateFormRef"
      v-model="exchangeRateData"
      :currencies="currencies"
      :usedCurrencies="usedCurrencies"
      :has-privileged-role="hasPrivilegedRole"
      :mode="formMode"
    />
  </Modal>
</template>
