<script setup lang="ts">
  import { computed } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import type { AdminCommercePageProps } from '@js/types/roles/admin/admin-commerce'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter, TableFilterOption } from '@js/types/common/table/table-filter'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminCommerceForm from '@/js/Components/Roles/Admin/Commerce/AdminCommerceForm.vue'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useExchangeRate } from '@/js/composables/roles/admin/commerce/useAdminCommerce'

  /**
   * Props tipadas para Inertia
   */
  const props = defineProps<AdminCommercePageProps>()

  /**
   * Composable de negocio para manejo de tasas
   */
  const {
    exchangeRateData,
    exchangeRates,
    setInitialExchangeRates,
    create,
    edit,
    changeStatus,
    remove,
    updateExchangeRates,
    submit
  } = useExchangeRate(props.tokenId)

  /**
   * Opciones de país normalizadas
   * Se expone como array plano (NO computed en filtros)
   */
  const countryOptions = computed<TableFilterOption[]>(() =>
    props.countries.map(country => ({
      value: country.name,
      label: country.name
    }))
  )

  /**
   * Filtros de tabla
   * ⚠️ options debe ser siempre un array, no un ComputedRef
   */
  const filters = computed<readonly TableFilter[]>(() => [
    {
      key: 'country_name',
      label: 'País',
      placeholder: 'Filtrar por país',
      options: countryOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' },
        { value: 'DELETED', label: 'Eliminado' }
      ]
    }
  ])

  /**
   * Botones extra de acciones
   */
  const extraButtons: ExtraButton[] = [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'changeStatus'
    },
    {
      label: '',
      icon: 'fas fa-key',
      class: 'btn-dark',
      show: false,
      action: 'resetPassword'
    }
  ]

  /**
   * Composable para el modal y formulario
   */
  const {
    showModalForm,
    modalTitle,
    formMode,
    formRef: exchangeRateFormRef,
    handleSubmit
  } = useModalForm(exchangeRateData, submit)
</script>

<template>
  <TableWithFilterBridge
    tableId="commerce-table"
    title="Comercializadoras"
    :columns="columns"
    :rows="commerces"
    :filters="filters"
    search-placeholder="Buscar por nombre, email, teléfono..."
    showCreateButton
    showEditButton
    showDeleteButton
    :extra-buttons="extraButtons"
  />

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(exchangeRateFormRef?.isValid ?? false)"
  >
    <!-- <ExchangeRateForm
      ref="exchangeRateFormRef"
      v-model="exchangeRateData"
      :currencies="currencies"
      :usedCurrencies="usedCurrencies"
      :has-privileged-role="hasPrivilegedRole"
      :mode="formMode"
    /> -->
  </Modal>
</template>
