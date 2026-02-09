<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ExchangeRateButtons from '@js/Components/ExchangeRate/ExchangeRateButtons.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import ExchangeRateForm from '@/js/Components/ExchangeRate/ExchangeRateForm.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useExchangeRate } from '@/js/composables/exchange-rate/useExchangeRate'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  import type { ExchangeRatePageProps, UsedCurrency } from '@js/types/exchange-rate'

  /**
   * Props Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Determina si el usuario tiene privilegios (company o customer)
   */
  const hasPrivilegedRole = computed(() => ['company', 'customer'].includes(props.roleUser))

  /**
   * Composable de negocio para manejo de tasas
   */
  const {
    exchangeRateData,
    exchangeRates,
    setInitialExchangeRates,
    create,
    edit,
    toggleStatus,
    remove,
    updateExchangeRates,
    submit
  } = useExchangeRate(props.tokenId)

  // Inicializar exchangeRates con las props
  setInitialExchangeRates(props.exchangeRates)

  /**
   * Propiedades y estado local
   */
  const selectedCurrency = ref<string[]>([])
  const currencies = props.currencies
  const usedCurrencies = ref<UsedCurrency[]>([])

  /**
   * Loading exclusivo para la acción "Actualizar tasas"
   * Vive en el padre porque aquí vive la operación async
   */
  const updatingExchangeRates = ref(false)

  /**
   * Genera las monedas usadas en las tasas para presentación
   */
  watch(
    exchangeRates,
    newRates => {
      usedCurrencies.value = newRates.map(rate => {
        const currency = currencies.find(c => c.id === rate.currency_id)

        return {
          id: rate.currency_id,
          name: currency?.name ?? 'Desconocido',
          description: currency?.description ?? 'Desconocido',
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
    },
    { immediate: true }
  )

  /**
   * Composable para el modal y formulario
   */
  const {
    showModalForm,
    modalTitle,
    formRef: exchangeRateFormRef,
    handleSubmit
  } = useModalForm(exchangeRateData, submit, {
    defaultTitle: 'Tasa de Cambio'
  })

  /**
   * Abre el modal en modo creación, inicializando datos
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abre el modal en modo edición cargando datos existentes
   *
   * @param id Identificador de la tasa a editar
   */
  async function handleEdit(id: string | number): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Ejecuta la sincronización masiva de tasas de cambio
   * y controla el loading del botón "Actualizar".
   */
  async function handleUpdateExchangeRates(): Promise<void> {
    if (updatingExchangeRates.value) return

    updatingExchangeRates.value = true
    try {
      await updateExchangeRates()
    } finally {
      updatingExchangeRates.value = false
    }
  }

  /**
   * Navega a la vista histórica filtrando por las monedas seleccionadas
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
        :updating-exchange-rates="updatingExchangeRates"
        @create="handleCreate"
        @historical="goHistorical"
        @updateExchangeRates="handleUpdateExchangeRates"
      />

      <ExchangeRateTable
        :usedCurrencies="usedCurrencies"
        :exchange-rates="exchangeRates"
        :has-privileged-role="hasPrivilegedRole"
        @edit="handleEdit"
        @change-status="toggleStatus"
        @delete="remove"
      />

      <TableFooterInfo :filtered-count="exchangeRates.length" :total-count="exchangeRates.length" />
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
    />
  </Modal>
</template>
