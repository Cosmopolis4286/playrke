<script setup lang="ts" name="TableWithFilterBridge">
  import { computed, ref } from 'vue'
  import { vTooltip } from 'floating-vue'
  import TableWithFilter from './TableWithFilter.vue'
  import type { Column } from '@/js/types/common/table/column-row'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import { TableFilter } from '@/js/types/common/table/table-filter'

  /** Props recibidas desde el componente padre */
  const props = defineProps<{
    tableId: string
    title: string
    columns: Column[]
    rows: Array<Record<string, any>> | string
    filters?: readonly TableFilter[]
    searchPlaceholder?: string
    debounceDelay?: number
    emptyMessage?: string
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
    extraButtons?: ExtraButton[]
  }>()

  /** IDs seleccionados actualmente en la tabla */
  const selectedIds = ref<string[]>([])

  /** Parsear props que podrían venir como JSON strings */
  const parsedColumns = computed(() =>
    typeof props.columns === 'string' ? JSON.parse(props.columns) : props.columns
  )
  const parsedRows = computed(() =>
    typeof props.rows === 'string' ? JSON.parse(props.rows) : props.rows
  )
  const parsedFilters = computed(() =>
    typeof props.filters === 'string' ? JSON.parse(props.filters) : (props.filters ?? [])
  )

  /**
   * Verifica si debe mostrar el contador para ciertos botones
   */
  const shouldShowCount = (btn: ExtraButton, selected: string[]) =>
    btn.action === 'toggleStatus' && selected.length > 1

  /**
   * Obtiene la razón por la cual el botón está deshabilitado
   */
  function getDisabledReason(btn: ExtraButton, ids: string[]): string {
    if (!btn.disabledReason) return ''
    return typeof btn.disabledReason === 'function'
      ? btn.disabledReason(ids)
      : btn.disabledReason
  }

  /** Requisitos para cantidad mínima de selección */
  type SelectionRequirement = 'any' | 'single' | 'multiple'

  /** Opciones para acciones CRUD */
  interface CrudActionOptions {
    action: string
    selectionRequirement?: SelectionRequirement
    onBeforeEmit?: (ids: string[]) => boolean
    logMessage?: string
    ids?: string[]
  }

  /** Emite evento global con acción y IDs */
  function emitTableCrudEvent(action: string, ids: string[] = selectedIds.value) {
    console.log('evento')
    window.dispatchEvent(
      new CustomEvent('table:crud', {
        detail: { action, encryptIds: ids, tableId: props.tableId }
      })
    )
  }

  /**
   * Maneja acciones CRUD validando selección y emitiendo eventos
   */
  function handleCrudAction({
    action,
    selectionRequirement = 'any',
    onBeforeEmit,
    logMessage,
    ids = selectedIds.value
  }: CrudActionOptions) {
    const count = ids.length

    if (
      (selectionRequirement === 'single' && count !== 1) ||
      (selectionRequirement === 'multiple' && count < 2) ||
      (selectionRequirement === 'any' && count === 0 && action !== 'create')
    ) {
      console.warn(
        `La acción '${action}' requiere selección ${selectionRequirement}, pero hay ${count} seleccionados.`
      )
      return
    }

    if (logMessage) console.log(logMessage, ids)
    if (onBeforeEmit && !onBeforeEmit(ids)) {
      console.log(`Acción '${action}' cancelada por onBeforeEmit.`)
      return
    }

    emitTableCrudEvent(action, ids)
  }

  /** Handlers estándar para botones CRUD */
  const handleCreate = () => emitTableCrudEvent('create')
  const handleEdit = () => handleCrudAction({ action: 'edit', selectionRequirement: 'single' })
  const handleDelete = () => handleCrudAction({ action: 'delete', selectionRequirement: 'any' })
  const handleToggleStatus = () =>
    handleCrudAction({ action: 'toggleStatus', logMessage: 'Cambio de estado para IDs:' })
  const handleResetPassword = () =>
    handleCrudAction({ action: 'resetPassword', logMessage: 'Reset contraseña para IDs:' })

  /**
   * Asigna onClick y disabled a botones extra basados en la acción
   */
  const extraButtonsWithHandlers = computed(() =>
    (props.extraButtons ?? []).map(btn => ({
      ...btn,
      onClick: () => {
        switch (btn.action) {
          case 'toggleStatus':
            handleToggleStatus()
            break
          case 'resetPassword':
            handleResetPassword()
            break
          default:
            console.warn(`No handler para botón '${btn.label}' con acción '${btn.action}'`)
        }
      },
      disabled: (selectedIds: string[]) =>
        btn.disabled
          ? btn.disabled(selectedIds)
          : false
    }))
  )

  /** Filtra botones extra visibles según la propiedad show */
  const visibleExtraButtons = computed(() =>
    extraButtonsWithHandlers.value.filter(btn => {
      if (!btn) return false
      if (btn.show === undefined) return true
      if (typeof btn.show === 'function') return btn.show(selectedIds.value)
      if (btn.show === false) return selectedIds.value.length > 0
      return !!btn.show
    })
  )

  /** Actualiza IDs seleccionados y puede emitir evento global (comentado para evitar ruido) */
  const handleSelectionChange = (ids: string[]): void => {
    selectedIds.value = [...ids]
    // if (import.meta.env.DEV) {
    //   console.log('🔁 table-selection-updated', { tableId: props.tableId, selectedIds: selectedIds.value })
    // }
    // window.dispatchEvent(new CustomEvent('table-selection-updated', {
    //   detail: { tableId: props.tableId, selectedIds: [...selectedIds.value] }
    // }))
  }
</script>

<template>
  <TableWithFilter
    :table-id="props.tableId"
    :title="props.title"
    :columns="parsedColumns"
    :rows="parsedRows"
    :filters="parsedFilters"
    :search-placeholder="props.searchPlaceholder"
    :debounce-delay="props.debounceDelay"
    :empty-message="props.emptyMessage"
    :show-create-button="props.showCreateButton"
    :show-edit-button="props.showEditButton"
    :show-delete-button="props.showDeleteButton"
    @create="handleCreate"
    @edit="handleEdit"
    @delete="handleDelete"
    @selection-change="handleSelectionChange"
  >
    <!-- Reenvío de slots recibidos -->
    <template v-for="(_, name) in $slots" #[name]="slotData" :key="name">
      <slot :name="name" v-bind="slotData" />
    </template>

    <!-- Botones extra a la derecha -->
    <template #action-buttons="{ selectedIds }">
      <button
        v-for="(btn, i) in visibleExtraButtons"
        :key="i"
        v-tooltip="{
          content: getDisabledReason(btn, selectedIds),
          disabled: !btn.disabled?.(selectedIds) || !getDisabledReason(btn, selectedIds)
        }"
        :class="['btn', 'btn-sm', btn.class || 'btn-secondary']"
        :disabled="btn.disabled ? btn.disabled(selectedIds) : false"
        @click="() => btn.onClick()"
        type="button"
      >
        <i v-if="btn.icon" :class="btn.icon"></i>
        {{ btn.label }}
        <span v-if="shouldShowCount(btn, selectedIds)"> ({{ selectedIds.length }}) </span>
      </button>
    </template>
  </TableWithFilter>
</template>
