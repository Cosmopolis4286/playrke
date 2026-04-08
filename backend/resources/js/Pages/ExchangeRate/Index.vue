<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'
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
  const hasPrivilegedRole = computed(() =>
    ['company', 'customer', 'admin'].includes(props.roleUser)
  )

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
    submit,
    toggleAutoUpdate,
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
   * Estado para controlar actualización automática via cronjob
   */
  const autoUpdateEnabled = ref(props.autoUpdateEnabled)

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
   * Maneja el cambio del switch de actualización automática
   */
  async function handleAutoUpdateToggle(enabled: boolean): Promise<void> {
    const success = await toggleAutoUpdate(enabled)
    if (!success) {
      // Revertir el estado si hay error
      autoUpdateEnabled.value = !enabled
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
      <!-- Switch de actualización automática -->
      <div class="row">
        <FormSwitch
          name="auto-update"
          class="ml-1 col-12"
          label="¿Actualización automática?"
          v-model="autoUpdateEnabled"
          @update:modelValue="handleAutoUpdateToggle"
        />
      </div>

      <hr class="mt-n2" />

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

          <!-- Consultar Valores -->
          <VDropdown
            :shown="exchangeRates.length === 0 ? undefined : false"
            :triggers="exchangeRates.length === 0 ? ['hover', 'focus'] : []"
            placement="top"
            :autoHide="true"
          >
            <button
              class="btn btn-sm btn-secondary"
              type="button"
              :disabled="updatingExchangeRates || exchangeRates.length === 0"
              @click="handleUpdateExchangeRates"
            >
              <i class="fa-solid fa-search"></i> Consultar
              <span
                v-if="updatingExchangeRates"
                class="spinner-border spinner-border-sm ml-1"
                aria-hidden="true"
              ></span>
            </button>
            <template #popper>
              <div style="padding: 4px 8px; max-width: 220px; font-size: 0.8rem;">
                Registra al menos una tasa de cambio para poder consultar valores.
              </div>
            </template>
          </VDropdown>
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
