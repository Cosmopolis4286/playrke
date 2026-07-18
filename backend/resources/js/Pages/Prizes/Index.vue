<script setup lang="ts">
  /**
   * Página → Premios (Prizes/Index)
   */

  import { watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import PrizeForm from '@/js/Components/Prizes/PrizeForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { usePrize } from '@/js/composables/prizes/usePrize'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { PrizePageProps } from '@js/types/prizes/prize-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<PrizePageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    prizeData,
    kitItemObjects,
    prizes,
    canCreateKit,
    setInitialPrizes,
    create,
    edit,
    toggleStatusBulk,
    removeBulk,
    submit
  } = usePrize()

  setInitialPrizes(props.prizes.rows, props.can_create_kit)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: prizeFormRef,
    handleSubmit
  } = useModalForm(prizeData, submit, {
    defaultTitle: 'Premio'
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      prizeFormRef.value?.reset?.()
      create()
    }
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
      await removeBulk(ids)
    },

    toggleStatus: async ids => {
      if (!ids) return
      await toggleStatusBulk(ids)
    }
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters: readonly TableFilter[] = [
    {
      key: 'reward_type',
      label: 'Tipo',
      placeholder: 'Filtrar por tipo',
      options: [
        { value: 'PRODUCT', label: 'Producto' },
        { value: 'SERVICE', label: 'Servicio' }
      ]
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' }
      ]
    },
    {
      key: 'kit',
      label: 'Kit',
      placeholder: 'Filtrar por kit',
      options: [
        { value: 'Y', label: 'Sí' },
        { value: 'N', label: 'No' }
      ]
    }
  ]

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
  <div class="ds-page">
    <div class="ds-inner">
      <TableWithFilterBridge
        tableId="prizes-table"
        title="Premios"
        :columns="props.prizes.columns"
        :rows="prizes"
        :filters="filters"
        search-placeholder="Buscar por nombre, código..."
        showCreateButton
        showEditButton
        showDeleteButton
        :extra-buttons="extraButtons"
      />
    </div>
  </div>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(prizeFormRef?.isValid ?? false)"
  >
    <PrizeForm
      ref="prizeFormRef"
      v-model="prizeData"
      v-model:kit-item-objects="kitItemObjects"
      :can-create-kit="canCreateKit"
    />
  </Modal>
</template>
