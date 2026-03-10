<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import ExchangeRateTable from '@js/Components/ExchangeRate/ExchangeRateTable.vue'
  import ExchangeRateForm from '@/js/Components/ExchangeRate/ExchangeRateForm.vue'
  import TableActionButtons from '@/js/Components/Common/Table/TableActionButtons.vue'
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
   * Determina si el usuario tiene privilegios (company, customer, admin)
   */
  const hasPrivilegedRole = computed(() => ['company', 'customer', 'admin'].includes(props.roleUser))

  /**
   * Indica si es posible registrar una nueva tasa de cambio
   */
  const canCreate = computed(() => {
    if (hasPrivilegedRole.value) return true
    return currencies.length > 0 && usedCurrencies.value.length < currencies.length
  })

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
   * IDs seleccionados en la tabla
   */
  const selectedIds = ref<string[]>([])

  /**
   * Propiedades y estado local
   */
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
        // Handle both ExchangeRate and AdminExchangeRate types
        const currencyId = rate.currency_id
        
        // For AdminExchangeRate, use the embedded currency object
        // For regular ExchangeRate, try to find by ID or ISO code
        let currencyData = rate.currency
        
        if (!currencyData) {
          // Try to find by ID first (for regular ExchangeRate)
          currencyData = currencies.find(c => c.id === currencyId)
          
          // If not found by ID, try by ISO code
          if (!currencyData) {
            currencyData = currencies.find(c => c.iso_code === currencyId)
          }
        }

        return {
          id: currencyId,
          name: currencyData?.name ?? 'Desconocido',
          description: currencyData?.description ?? 'Desconocido',
          iso_code: currencyData?.iso_code ?? 'Desconocido',
          type: currencyData?.type,
          country: currencyData?.country
            ? {
                id: String(currencyData.country.id),
                name: currencyData.country.name,
                iso_code: currencyData.country.iso_code_3 || currencyData.country.iso_code_2
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
  async function handleEdit(id: string): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Alterna el status de las tasas seleccionadas
   */
  async function handleChangeStatus(ids: string[]): Promise<void> {
    for (const id of ids) {
      await toggleStatus(id)
    }
    selectedIds.value = []
  }

  /**
   * Elimina las tasas seleccionadas
   */
  async function handleDelete(ids: string[]): Promise<void> {
    for (const id of ids) {
      await remove(id)
    }
    selectedIds.value = []
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
   * Navega a la vista histórica de la tasa seleccionada
   */
  function goHistorical(): void {
    if (!selectedIds.value.length) return

    const selected = exchangeRates.value.find(er => String(er.id) === selectedIds.value[0])
    if (!selected?.currency_id) return

    const params = props.tokenId ? `?tokenId=${props.tokenId}` : ''
    window.location.href = `/exchange-rate/historical/${selected.currency_id}${params}`
  }
</script>
<template>
  <div class="card">
    <CardHeader title="Tasas de Cambio" subtitle="Valores en base a Un Dólar (1 USD)" />

    <div class="card-body">
      <TableActionButtons
        :selected-ids="selectedIds"
        :show-create-button="canCreate"
        show-edit-button
        show-delete-button
        @create="handleCreate"
        @edit="handleEdit"
        @delete="handleDelete"
      >
        <template #action-buttons="{ selectedIds: ids }">
          <!-- Cambiar Status (solo con selección) -->
          <button
            v-if="ids.length > 0"
            class="btn btn-sm btn-secondary"
            title="Cambiar Status"
            @click="handleChangeStatus(ids)"
          >
            <i class="fa-solid fa-repeat"></i>
          </button>

          <!-- Histórico (requiere exactamente 1 seleccionado) -->
          <button
            v-if="ids.length === 1"
            class="btn btn-sm btn-primary"
            type="button"
            title="Ver Histórico"
            @click="goHistorical"
          >
            <i class="fa-solid fa-money-bill-trend-up"></i> Histórico
          </button>

          <!-- Actualizar tasas -->
          <button
            class="btn btn-sm btn-secondary"
            type="button"
            :disabled="updatingExchangeRates"
            title="Actualizar Tasas"
            @click="handleUpdateExchangeRates"
          >
            <i class="fa-solid fa-rotate"></i> Actualizar
            <span
              v-if="updatingExchangeRates"
              class="spinner-border spinner-border-sm ml-1"
              aria-hidden="true"
            ></span>
          </button>
        </template>
      </TableActionButtons>

      <ExchangeRateTable
        :usedCurrencies="usedCurrencies"
        :exchange-rates="exchangeRates"
        :has-privileged-role="hasPrivilegedRole"
        :selected-ids="selectedIds"
        @selection-change="ids => (selectedIds = ids)"
      />

      <TableFooterInfo
        :filtered-count="exchangeRates.length"
        :total-count="exchangeRates.length"
        :selected-count="selectedIds.length"
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
    />
  </Modal>
</template>
