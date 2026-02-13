<script setup lang="ts" name="TableWithFilter">
  import { computed, toRef } from 'vue'

  import CardHeader from '../Card/CardHeader.vue'
  import TableFilter from './TableFilter.vue'
  import TableActionButtons from './TableActionButtons.vue'
  import BaseTable from './BaseTable.vue'
  import TableFooterInfo from './TableFooterInfo.vue'

  import { useTableController } from '@/js/composables/common/table/useTableController'
  import type { Column } from '@/js/types/common/table/column-row'

  /** Opciones para filtros */
  interface FilterOption {
    label: string
    value: string
  }

  /** Definición de filtro */
  interface Filter {
    key: string
    label: string
    options: FilterOption[]
  }

  type TableRow = {
    id: string
    [key: string]: any
  }

  /** Props */
  const props = defineProps<{
    tableId: string
    title: string
    columns: Column[]
    rows: TableRow[]
    filters?: Filter[]
    searchPlaceholder?: string
    debounceDelay?: number
    emptyMessage?: string
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
  }>()

  /** Emits */
  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
    (e: 'selection-change', ids: string[]): void
  }>()

  /** Defaults */
  const searchPlaceholder = props.searchPlaceholder ?? 'Buscar en la tabla...'
  const debounceDelay = props.debounceDelay ?? 300
  const emptyMessage = props.emptyMessage ?? 'No se encontraron registros'
  const showCreateButton = props.showCreateButton ?? true
  const showEditButton = props.showEditButton ?? true
  const showDeleteButton = props.showDeleteButton ?? true

  /** 👉 Controller */
  const { handleFilter, selectedIds, filteredRows } = useTableController({
    columns: toRef(props, 'columns'),
    rows: toRef(props, 'rows'),
    onSelectionChange: ids => emit('selection-change', ids.map(String))
  })

  const stringSelectedIds = computed(() => selectedIds.value.map(String))

  /** Cambio de selección desde BaseTable */
  function handleSelectionChange(ids: string[]): void {
    selectedIds.value = ids
    emit('selection-change', ids)
  }
</script>

<template>
  <div class="card">
    <CardHeader :title="title" />
    <div class="card-body">
      <TableFilter
        :search-placeholder="searchPlaceholder"
        :filters="filters"
        :debounce-delay="debounceDelay"
        @filter="handleFilter"
      />

      <TableActionButtons
        class="mt-3"
        :selected-ids="stringSelectedIds"
        :show-create-button="showCreateButton"
        :show-edit-button="showEditButton"
        :show-delete-button="showDeleteButton"
        @create="$emit('create')"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      >
        <template #action-buttons="{ selectedIds }">
          <slot name="action-buttons" :selectedIds="selectedIds" />
        </template>
      </TableActionButtons>

      <BaseTable
        :table-id="tableId"
        :columns="columns"
        :rows="filteredRows"
        :empty-message="emptyMessage"
        selectable
        :selected-ids="stringSelectedIds"
        @selection-change="handleSelectionChange"
      >
        <template
          v-for="column in columns"
          :key="column.key"
          v-slot:[`cell-${column.key}`]="{ row, value }"
        >
          <slot :name="`cell-${column.key}`" :row="row" :value="value" />
        </template>
      </BaseTable>

      <TableFooterInfo
        :filtered-count="filteredRows.length"
        :total-count="rows.length"
        :selected-count="selectedIds.length"
      />
    </div>
  </div>
</template>

<style scoped>
  .table tbody tr {
    transition: background-color 0.15s ease-in-out;
  }

  .table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
  }

  .table-primary {
    background-color: rgba(0, 123, 255, 0.1) !important;
  }
</style>
