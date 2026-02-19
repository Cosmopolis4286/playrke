<script setup lang="ts">
  /**
   * Página → Bingo - Patrones (Bingo/Pattern/Index)
   */

  import { watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoPatternForm from '@/js/Components/Games/Bingo/Pattern/BingoPatternForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoPattern } from '@/js/composables/games/bingo/pattern/useBingoPattern'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { BingoPatternPageProps } from '@/js/types/games/bingo/pattern/bingo-pattern-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<BingoPatternPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    bingoPatternData,
    bingoPatterns,
    setInitialBingoPatterns,
    create,
    edit,
    toggleStatusBulk,
    removeBulk,
    submit
  } = useBingoPattern()

  setInitialBingoPatterns(props.bingoPatterns.rows)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: bingoPatternFormRef,
    handleSubmit
  } = useModalForm(bingoPatternData, submit, {
    defaultTitle: 'Patron de Bingo'
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      bingoPatternFormRef.value?.reset?.()
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
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    }
  ]
</script>

<template>
  <TableWithFilterBridge
    tableId="bingo-patterns-table"
    title="Patrones de Bingo"
    :columns="props.bingoPatterns.columns"
    :rows="bingoPatterns"
    :filters="filters"
    search-placeholder="Buscar por nombre, código..."
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
    :confirm-disabled="!(bingoPatternFormRef?.isValid ?? false)"
  >
    <BingoPatternForm ref="bingoPatternFormRef" v-model="bingoPatternData" />
  </Modal>
</template>
