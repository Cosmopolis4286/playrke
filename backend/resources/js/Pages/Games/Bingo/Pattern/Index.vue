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

  import type { BingoPatternPageProps } from '@/js/types/games/bingo/pattern/bingo-pattern-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter } from '@js/types/common/table/table-filter'

  const props = defineProps<BingoPatternPageProps>()

  const isAdmin = computed(() => props.bingoPatterns.roleUser === 'admin')

  // En modo visualización (no admin editando), el modal es solo lectura
  const isViewMode = ref(false)

  /**
   * Dado un conjunto de IDs seleccionados, determina si TODOS son patrones de sistema
   * (default_pattern = 'Y'). Para no-admin esto bloquea edición/borrado/toggleStatus.
   */
  function allSelectedAreSystem(ids: string[]): boolean {
    return ids.every(id => {
      const p = bingoPatterns.value.find(p => p.id === id)
      return p?.default_pattern === 'Y'
    })
  }

  /**
   * Para no-admin: si algún patrón seleccionado es de sistema, bloquear la acción
   * destructiva y devolver true (bloqueado).
   */
  function isBlockedForNonAdmin(ids: string[]): boolean {
    if (isAdmin.value) return false
    return allSelectedAreSystem(ids)
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

  useCrudEventListener({
    create: () => {
      create()
      isViewMode.value = false
      showModalForm.value = true
    },
    edit: async ids => {
      if (!ids || ids.length !== 1) return
      const ok = await edit(ids[0])
      if (ok) {
        // No-admin viendo un patrón de sistema → solo visualizar
        const isSystemPattern = bingoPatterns.value.find(p => p.id === ids[0])?.default_pattern === 'Y'
        isViewMode.value = !isAdmin.value && isSystemPattern
        showModalForm.value = true
      }
    },
    delete: async ids => {
      if (!ids || isBlockedForNonAdmin(ids)) return
      await removeBulk(ids)
    },
    toggleStatus: async ids => {
      if (!ids || isBlockedForNonAdmin(ids)) return
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

  // IDs actualmente seleccionados en la tabla (sincronizados via @selection-change)
  const selectedIds = ref<string[]>([])

  /**
   * Para no-admin: ocultar Editar/Eliminar/ToggleStatus cuando TODOS los
   * seleccionados son patrones de sistema (default_pattern='Y').
   * El admin siempre ve todos los botones.
   */
  const hideDestructiveButtons = computed(() =>
    !isAdmin.value && selectedIds.value.length > 0 && allSelectedAreSystem(selectedIds.value)
  )

  const extraButtons = computed<ExtraButton[]>(() => [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      // Ocultar para no-admin si selección es solo patrones de sistema
      show: (ids: string[]) => ids.length > 0 && (isAdmin.value || !allSelectedAreSystem(ids)),
      action: 'toggleStatus'
    }
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
    :showEditButton="!hideDestructiveButtons"
    :showDeleteButton="isAdmin && !hideDestructiveButtons"
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
