<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import CurrencyForm from '@/js/Components/Currency/CurrencyForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useCurrency } from '@/js/composables/currency/useCurrency'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { CurrencyPageProps } from '@js/types/currency'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<CurrencyPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const hasPrivilegedRole = computed(() =>
    ['company', 'customer', 'admin'].includes(props.roleUser)
  )

  const {
    currencyData,
    currencies,
    setInitialCurrencies,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = useCurrency(props.tokenId)

  setInitialCurrencies(props.currencies)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: currencyFormRef,
    handleSubmit
  } = useModalForm(currencyData, submit, {
    defaultTitle: 'Moneda'
  })

  // ==========================================================================
  // CRUD global (eventos tabla)
  // ==========================================================================

  useCrudEventListener({
    create: () => {
      create()
      showModalForm.value = true
    },

    edit: async ids => {
      if (!ids || ids.length !== 1) return
      const ok = await edit(ids[0])
      if (ok) showModalForm.value = true
    },

    delete: async ids => {
      if (!ids) return
      for (const id of ids) await remove(id)
    },

    toggleStatus: async ids => {
      if (!ids) return
      for (const id of ids) await toggleStatus(id)
    }
  })

  // ==========================================================================
  // Columnas
  // ==========================================================================

  const columns: Column[] = [
    { key: 'name',     label: 'Nombre' },
    { key: 'iso_code', label: 'Código ISO' },
    { key: 'symbol',   label: 'Símbolo' },
    { key: 'type',     label: 'Tipo' },
    { key: 'status',   label: 'Estado' },
    { key: 'country',  label: 'País' }
  ]

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'type',
      label: 'Tipo',
      placeholder: 'Filtrar por tipo',
      options: [
        { value: 'FIAT',   label: 'Fiat' },
        { value: 'CRYPTO', label: 'Criptomoneda' },
        { value: 'TOKEN',  label: 'Token' }
      ] as TableFilterOption[]
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE',   label: 'Activa' },
        { value: 'INACTIVE', label: 'Inactiva' }
      ] as TableFilterOption[]
    }
  ])

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    }
  ]
</script>

<template>
  <TableWithFilterBridge
    table-id="currency-table"
    title="Monedas"
    subtitle="Gestión de monedas del sistema"
    :columns="columns"
    :rows="currencies"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por nombre, código, símbolo..."
    :show-create-button="hasPrivilegedRole"
    show-edit-button
    show-delete-button
    empty-message="No hay monedas registradas"
  >
    <!-- Tipo -->
    <template #cell-type="{ row }">
      <span :class="['badge', {
        'badge-primary': row.type === 'FIAT',
        'badge-warning': row.type === 'CRYPTO',
        'badge-info':    row.type === 'TOKEN'
      }]">
        {{ row.type === 'FIAT' ? 'Fiat' : row.type === 'CRYPTO' ? 'Cripto' : 'Token' }}
      </span>
    </template>

    <!-- Estado -->
    <template #cell-status="{ row }">
      <span :class="['badge', row.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary']">
        {{ row.status === 'ACTIVE' ? 'Activa' : 'Inactiva' }}
      </span>
    </template>

    <!-- País -->
    <template #cell-country="{ row }">
      {{ row.country?.name ?? '—' }}
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(currencyFormRef?.isValid ?? false)"
  >
    <CurrencyForm
      ref="currencyFormRef"
      v-model="currencyData"
      :currencies="currencies"
      :countries="props.countries"
    />
  </Modal>
</template>
