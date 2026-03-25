<script setup lang="ts">
  /**
   * Página → Bingo - Patrones (Bingo/Pattern/Index)
   */

  import { ref, computed, watch } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoPatternForm from '@/js/Components/Games/Bingo/Pattern/BingoPatternForm.vue'

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoPattern } from '@/js/composables/games/bingo/pattern/useBingoPattern'

  import type { BingoPatternPageProps } from '@/js/types/games/bingo/pattern/bingo-patterns-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter } from '@js/types/common/table/table-filter'

  const props = defineProps<BingoPatternPageProps>()

  const isAdmin = computed(() => props.bingoPatterns.roleUser === 'admin')

  // En modo visualización el modal es solo lectura
  const isViewMode = ref(false)

  // IDs actualmente seleccionados en la tabla
  const selectedIds = ref<string[]>([])

  /** true si todos los IDs seleccionados son patrones de sistema (default_pattern='Y') */
  function allSelectedAreSystem(ids: string[]): boolean {
    return ids.length > 0 && ids.every(id =>
      bingoPatterns.value.find(p => p.id === id)?.default_pattern === 'Y'
    )
  }

  /** true si todos los IDs seleccionados son patrones propios (default_pattern='N') */
  function allSelectedAreOwn(ids: string[]): boolean {
    return ids.length > 0 && ids.every(id =>
      bingoPatterns.value.find(p => p.id === id)?.default_pattern === 'N'
    )
  }

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

  const {
    showModalForm,
    formMode,
    formRef: bingoPatternFormRef,
    handleSubmit
  } = useModalForm(bingoPatternData, submit, {
    defaultTitle: 'Patrón de Bingo'
  })

  const modalTitle = computed(() => {
    if (isViewMode.value) return 'Visualizar Patrón de Bingo'
    return formMode.value === 'create' ? 'Registrar Patrón de Bingo' : 'Editar Patrón de Bingo'
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      bingoPatternFormRef.value?.reset?.()
      isViewMode.value = false
      create()
    }
  })

  async function openEdit(ids: string[], viewOnly: boolean) {
    if (!ids || ids.length !== 1) return
    const ok = await edit(ids[0])
    if (ok) {
      isViewMode.value = viewOnly
      showModalForm.value = true
    }
  }

  useCrudEventListener({
    create: () => {
      create()
      isViewMode.value = false
      showModalForm.value = true
    },
    // Editar: admin siempre edita; no-admin solo edita sus propios (default_pattern='N')
    edit: async ids => {
      const isSystem = allSelectedAreSystem(ids ?? [])
      await openEdit(ids ?? [], !isAdmin.value && isSystem)
    },
    // Visualizar: acción exclusiva de no-admin para patrones de sistema
    view: async ids => {
      await openEdit(ids ?? [], true)
    },
    // Eliminar y ToggleStatus: solo permitido sobre patrones propios (default_pattern='N')
    delete: async ids => {
      if (!ids || (!isAdmin.value && !allSelectedAreOwn(ids))) return
      await removeBulk(ids)
    },
    toggleStatus: async ids => {
      if (!ids || (!isAdmin.value && !allSelectedAreOwn(ids))) return
      await toggleStatusBulk(ids)
    }
  })

  const filters: readonly TableFilter[] = [
    {
      key: 'type_pattern',
      label: 'Tipo',
      placeholder: 'Filtrar por tipo',
      options: [
        { value: 'UNIQUE',   label: 'Único' },
        { value: 'MULTIPLE', label: 'Múltiple' },
      ]
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE',   label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' },
      ]
    },
    {
      key: 'default_pattern',
      label: 'Por defecto',
      placeholder: 'Filtrar',
      options: [
        { value: 'Y', label: 'Sí' },
        { value: 'N', label: 'No' },
      ]
    }
  ]

  /**
   * true si la selección tiene algún patrón de sistema (default_pattern='Y').
   * En ese caso, no-admin no puede editar ni eliminar.
   */
  const selectionHasSystemPattern = computed(() =>
    !isAdmin.value &&
    selectedIds.value.some(id => bingoPatterns.value.find(p => p.id === id)?.default_pattern === 'Y')
  )

  // Para no-admin: ocultar Editar/Eliminar cuando la selección incluye patrones de sistema
  const hideStandardButtons = computed(() => selectionHasSystemPattern.value)

  const extraButtons = computed<ExtraButton[]>(() => [
    // ToggleStatus: admin siempre; no-admin solo si TODOS son propios (default_pattern='N')
    {
      icon:   'fas fa-exchange-alt',
      class:  'btn-secondary',
      show:   (ids: string[]) => ids.length > 0 && (isAdmin.value || allSelectedAreOwn(ids)),
      action: 'toggleStatus',
    },
    // Visualizar: solo no-admin, solo 1 seleccionado, solo patrón de sistema
    {
      label:  'Visualizar',
      icon:   'fas fa-eye',
      class:  'btn-info',
      show:   (ids: string[]) => !isAdmin.value && ids.length === 1 && allSelectedAreSystem(ids),
      action: 'view',
    },
  ])
</script>

<template>
  <TableWithFilterBridge
    tableId="bingo-patterns-table"
    title="Patrones de Bingo"
    :columns="props.bingoPatterns.columns"
    :rows="bingoPatterns"
    :filters="filters"
    search-placeholder="Buscar por nombre..."
    showCreateButton
    :showEditButton="!hideStandardButtons"
    :showDeleteButton="!hideStandardButtons"
    :extra-buttons="extraButtons"
    @selection-change="ids => selectedIds = ids"
  />

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    :show-confirm="!isViewMode"
    :on-submit="handleSubmit"
    :confirm-disabled="!(bingoPatternFormRef?.isValid ?? false)"
  >
    <BingoPatternForm
      ref="bingoPatternFormRef"
      v-model="bingoPatternData"
      :readonly="isViewMode"
      :is-admin="isAdmin"
    />
  </Modal>
</template>
