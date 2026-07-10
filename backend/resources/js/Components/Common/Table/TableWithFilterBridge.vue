<script setup lang="ts" name="TableWithFilterBridge">
  import { computed, ref } from 'vue'
  import { vTooltip } from 'floating-vue'
  import TableWithFilter from './TableWithFilter.vue'

  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  /**
   * Props recibidas desde el componente padre.
   */
  const props = defineProps<{
    tableId: string
    title: string
    subtitle?: string
    columns: Column[]
    rows: Row[]
    filters?: readonly TableFilterInterface[]
    searchPlaceholder?: string
    debounceDelay?: number
    emptyMessage?: string
    errorMessage?: string | null
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
    disableEdit?: boolean | ((selectedIds: string[]) => boolean)
    disableEditReason?: string | ((selectedIds: string[]) => string)
    disableDelete?: boolean | ((selectedIds: string[]) => boolean)
    disableDeleteReason?: string | ((selectedIds: string[]) => string)
    extraButtons?: ExtraButton[]
    loading?: boolean
    /** Activa paginación client-side con este tamaño de página */
    pageSize?: number
    /** Modo server-side: ver TableWithFilter.vue */
    serverSide?: boolean
    serverTotalPages?: number
  }>()

  /**
   * Eventos emitidos al componente padre.
   */
  const emit = defineEmits<{
    (e: 'selection-change', ids: string[]): void
    (e: 'query-change', query: ServerQuery): void
  }>()

  /**
   * IDs actualmente seleccionados en la tabla.
   */
  const selectedIds = ref<string[]>([])

  /**
   * Determina si un botón debe mostrar contador de selección.
   */
  const shouldShowCount = (btn: ExtraButton, selected: string[]) =>
    btn.action === 'toggleStatus' && selected.length > 1

  /**
   * Obtiene el mensaje de deshabilitado de un botón.
   */
  function getDisabledReason(btn: ExtraButton, ids: string[]): string {
    if (!btn.disabledReason) return ''
    return typeof btn.disabledReason === 'function' ? btn.disabledReason(ids) : btn.disabledReason
  }

  /**
   * Requisitos de selección para acciones CRUD.
   */
  type SelectionRequirement = 'any' | 'single' | 'multiple'

  /**
   * Emite evento global CRUD utilizado por el sistema.
   */
  function emitTableCrudEvent(action: string, ids: string[] = selectedIds.value) {
    window.dispatchEvent(
      new CustomEvent('table:crud', {
        detail: { action, encryptIds: ids, tableId: props.tableId }
      })
    )
  }

  /**
   * Ejecuta una acción CRUD validando requisitos de selección.
   */
  function runCrudAction(
    action: string,
    requirement: SelectionRequirement = 'any',
    ids: string[] = selectedIds.value
  ) {
    const count = ids.length

    if (
      (requirement === 'single' && count !== 1) ||
      (requirement === 'multiple' && count < 2) ||
      (requirement === 'any' && count === 0 && action !== 'create')
    ) {
      console.warn(`Acción '${action}' inválida para ${count} elementos seleccionados.`)
      return
    }

    emitTableCrudEvent(action, ids)
  }

  /**
   * Handlers estándar para acciones CRUD.
   */
  const actionHandlers: Record<string, () => void> = {
    create: () => runCrudAction('create'),
    edit: () => runCrudAction('edit', 'single'),
    delete: () => runCrudAction('delete'),
    view: () => runCrudAction('view', 'single'),
    copy: () => runCrudAction('copy', 'single'),
    toggleStatus: () => runCrudAction('toggleStatus'),
    resetPassword: () => runCrudAction('resetPassword')
  }

  /**
   * Tipo interno para botones con handlers resueltos.
   */
  type ExtraButtonResolved = ExtraButton & {
    onClick: () => void
    disabled: (ids: string[]) => boolean
  }

  /**
   * Botones extra con handlers y disabled resuelto.
   */
  const extraButtonsWithHandlers = computed<ExtraButtonResolved[]>(() =>
    (props.extraButtons ?? []).map(btn => ({
      ...btn,

      /**
       * Handler de click del botón.
       */
      onClick: () => {
        if (btn.onClick) {
          btn.onClick(selectedIds.value)
          return
        }

        actionHandlers[btn.action]?.()
      },

      /**
       * Resolver estado disabled.
       */
      disabled: (ids: string[]) => btn.disabled?.(ids) ?? false
    }))
  )

  /**
   * Filtra los botones visibles según la propiedad `show`.
   */
  const visibleExtraButtons = computed(() =>
    extraButtonsWithHandlers.value.filter(btn => {
      if (typeof btn.show === 'function') return btn.show(selectedIds.value)
      if (btn.show === false) return selectedIds.value.length > 0
      return btn.show ?? true
    })
  )

  /**
   * Actualiza selección y la re-emite al padre.
   */
  function handleSelectionChange(ids: string[]) {
    selectedIds.value = [...ids]
    emit('selection-change', [...ids])
  }

   /**
   * Resolver estado de deshabilitado para botón edit.
   */
  const resolvedDisableEdit = computed(() =>
    typeof props.disableEdit === 'function'
      ? props.disableEdit(selectedIds.value)
      : (props.disableEdit ?? false)
  )

  /**
   * Resolver razón de deshabilitado para edit.
   */
  const resolvedDisableEditReason = computed(() =>
    typeof props.disableEditReason === 'function'
      ? props.disableEditReason(selectedIds.value)
      : (props.disableEditReason ?? '')
  )

  /**
   * Resolver estado de deshabilitado para botón delete.
   */
  const resolvedDisableDelete = computed(() =>
    typeof props.disableDelete === 'function'
      ? props.disableDelete(selectedIds.value)
      : (props.disableDelete ?? false)
  )

  /**
   * Resolver razón de deshabilitado para delete.
   */
  const resolvedDisableDeleteReason = computed(() =>
    typeof props.disableDeleteReason === 'function'
      ? props.disableDeleteReason(selectedIds.value)
      : (props.disableDeleteReason ?? '')
  )
</script>

<template>
  <TableWithFilter
    :table-id="props.tableId"
    :title="props.title"
    :subtitle="props.subtitle"
    :columns="props.columns"
    :rows="props.rows"
    :filters="props.filters ? [...props.filters] : undefined"
    :search-placeholder="props.searchPlaceholder"
    :debounce-delay="props.debounceDelay"
    :empty-message="props.emptyMessage"
    :error-message="props.errorMessage"
    :show-create-button="props.showCreateButton"
    :show-edit-button="props.showEditButton"
    :show-delete-button="props.showDeleteButton"
    :disable-edit="resolvedDisableEdit"
    :disable-edit-reason="resolvedDisableEditReason"
    :disable-delete="resolvedDisableDelete"
    :disable-delete-reason="resolvedDisableDeleteReason"
    :loading="props.loading"
    :page-size="props.pageSize"
    :server-side="props.serverSide"
    :server-total-pages="props.serverTotalPages"
    @create="actionHandlers.create"
    @edit="actionHandlers.edit"
    @delete="actionHandlers.delete"
    @selection-change="handleSelectionChange"
    @query-change="emit('query-change', $event)"
  >
    <!-- Reenvío automático de slots -->
    <template v-for="(_, name) in $slots" #[name]="slotData" :key="name">
      <slot :name="name" v-bind="slotData" />
    </template>

    <!-- Botones extra -->
    <template #action-buttons="{ selectedIds }">
      <span v-if="visibleExtraButtons.length" class="table-extra-buttons__divider" aria-hidden="true" />
      <button
        v-for="(btn, i) in visibleExtraButtons"
        :key="i"
        v-tooltip="btn.disabled(selectedIds) && getDisabledReason(btn, selectedIds) ? getDisabledReason(btn, selectedIds) : btn.title"
        :class="['btn', 'btn-sm', btn.class || 'btn-secondary']"
        :disabled="btn.disabled(selectedIds)"
        @click="btn.onClick"
        type="button"
      >
        <i v-if="btn.icon" :class="btn.icon"></i>
        {{ btn.label }}

        <span v-if="shouldShowCount(btn, selectedIds)"> ({{ selectedIds.length }}) </span>
      </button>
    </template>
  </TableWithFilter>
</template>

<style scoped>
.table-extra-buttons__divider {
  width: 1px;
  align-self: stretch;
  min-height: 1.5rem;
  background: var(--bs-border-color, #dee2e6);
  margin: 0 0.25rem;
}
</style>
