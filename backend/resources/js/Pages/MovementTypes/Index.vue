<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import MovementTypeForm from '@/js/Components/MovementType/MovementTypeForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useMovementType } from '@/js/composables/movementType/useMovementType'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { MovementTypePageProps } from '@js/types/movementType'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<MovementTypePageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    movementTypeData,
    movementTypes,
    setInitialMovementTypes,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = useMovementType(props.tokenId)

  setInitialMovementTypes(props.movementTypes)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: movementTypeFormRef,
    handleSubmit
  } = useModalForm(movementTypeData, submit, {
    defaultTitle: 'Tipo de Movimiento'
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
    { key: 'label',             label: 'Nombre' },
    { key: 'code',              label: 'Código' },
    { key: 'is_credit',         label: 'Movimiento' },
    { key: 'requires_approval', label: 'Aprobación' },
    { key: 'status',            label: 'Estado' },
    { key: 'is_system',         label: 'Origen' }
  ]

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE',   label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' }
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
    table-id="movement-type-table"
    title="Tipos de Movimiento"
    subtitle="Catálogo de tipos de movimiento de billetera"
    :columns="columns"
    :rows="movementTypes"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por nombre o código..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay tipos de movimiento registrados"
  >
    <!-- Movimiento (crédito/débito) -->
    <template #cell-is_credit="{ row }">
      <span :class="['badge', row.is_credit ? 'badge-success' : 'badge-danger']">
        <i :class="row.is_credit ? 'fas fa-plus' : 'fas fa-minus'"></i>
        {{ row.is_credit ? 'Suma saldo' : 'Resta saldo' }}
      </span>
    </template>

    <!-- Requiere aprobación -->
    <template #cell-requires_approval="{ row }">
      <span :class="['badge', row.requires_approval ? 'badge-warning' : 'badge-secondary']">
        {{ row.requires_approval ? 'Sí' : 'No' }}
      </span>
    </template>

    <!-- Estado -->
    <template #cell-status="{ row }">
      <span :class="['badge', row.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary']">
        {{ row.status === 'ACTIVE' ? 'Activo' : 'Inactivo' }}
      </span>
    </template>

    <!-- Origen -->
    <template #cell-is_system="{ row }">
      <span :class="['badge', row.is_system ? 'badge-info' : 'badge-light']">
        {{ row.is_system ? 'Sistema' : 'Personalizado' }}
      </span>
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(movementTypeFormRef?.isValid ?? false)"
  >
    <MovementTypeForm
      ref="movementTypeFormRef"
      v-model="movementTypeData"
    />
  </Modal>
</template>
