<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import WalletLimitForm from '@/js/Components/WalletLimit/WalletLimitForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useWalletLimit } from '@/js/composables/walletLimit/useWalletLimit'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { WalletLimitPageProps } from '@js/types/walletLimit'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<WalletLimitPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    walletLimitData,
    walletLimits,
    setInitialWalletLimits,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = useWalletLimit()

  setInitialWalletLimits(props.walletLimits)

  // ==========================================================================
  // Filas de tabla: aplanan currency para mostrar el ISO code directamente
  // ==========================================================================

  const rows = computed(() =>
    walletLimits.value.map(limit => ({
      ...limit,
      currency_label: limit.currency ? limit.currency.iso_code : 'Todas'
    }))
  )

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: walletLimitFormRef,
    handleSubmit
  } = useModalForm(walletLimitData, submit, {
    defaultTitle: 'Límites de Billetera'
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
    { key: 'currency_label',       label: 'Moneda' },
    { key: 'kyc_level',            label: 'Nivel KYC' },
    { key: 'min_deposit',          label: 'Dep. mín.' },
    { key: 'max_deposit',          label: 'Dep. máx.' },
    { key: 'max_per_transaction',  label: 'Retiro máx./tx' },
    { key: 'max_monthly',          label: 'Retiro máx./mes' },
    { key: 'is_active',            label: 'Estado' }
  ]

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'kyc_level',
      label: 'Nivel KYC',
      placeholder: 'Filtrar por nivel',
      options: [
        { value: '0', label: 'Nivel 0 — Sin verificar' },
        { value: '1', label: 'Nivel 1 — Básico' },
        { value: '2', label: 'Nivel 2 — Completo' }
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

  function kycLabel(level: number): string {
    return level === 0 ? 'Nivel 0' : level === 1 ? 'Nivel 1' : 'Nivel 2'
  }
</script>

<template>
  <TableWithFilterBridge
    table-id="wallet-limit-table"
    title="Límites de Billetera"
    subtitle="Montos mínimos y máximos de depósito y retiro, por moneda y nivel de verificación KYC"
    :columns="columns"
    :rows="rows"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por moneda..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay límites de billetera configurados"
  >
    <!-- Moneda -->
    <template #cell-currency_label="{ row }">
      <span class="badge" :class="row.currency_id ? 'badge-info' : 'badge-secondary'">
        {{ row.currency_label }}
      </span>
    </template>

    <!-- Nivel KYC -->
    <template #cell-kyc_level="{ row }">
      {{ kycLabel(row.kyc_level) }}
    </template>

    <!-- Depósito mínimo/máximo -->
    <template #cell-min_deposit="{ row }">{{ row.min_deposit }}</template>
    <template #cell-max_deposit="{ row }">{{ row.max_deposit }}</template>

    <!-- Retiro máximo por transacción / mes -->
    <template #cell-max_per_transaction="{ row }">{{ row.max_per_transaction }}</template>
    <template #cell-max_monthly="{ row }">{{ row.max_monthly }}</template>

    <!-- Estado -->
    <template #cell-is_active="{ row }">
      <span :class="['badge', row.is_active ? 'badge-success' : 'badge-secondary']">
        {{ row.is_active ? 'Activo' : 'Inactivo' }}
      </span>
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(walletLimitFormRef?.isValid ?? false)"
  >
    <WalletLimitForm
      ref="walletLimitFormRef"
      v-model="walletLimitData"
      :currencies="props.currencies"
    />
  </Modal>
</template>
