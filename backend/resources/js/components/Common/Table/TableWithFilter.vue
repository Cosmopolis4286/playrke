<script setup lang="ts" name="TableWithFilter">
  import { computed, toRef } from 'vue'

  import CardHeader from '../Card/CardHeader.vue'
  import TableFilter from './TableFilter.vue'
  import TableActionButtons from './TableActionButtons.vue'
  import BaseTable from './BaseTable.vue'
  import TableFooterInfo from './TableFooterInfo.vue'

  import { useTableController } from '@/js/composables/common/table/useTableController'
  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'

  /**
   * Props del componente.
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
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
    disableDelete?: boolean
    disableDeleteReason?: string
  }>()

  /**
   * Eventos emitidos al componente padre.
   */
  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
    (e: 'selection-change', ids: string[]): void
  }>()

  /**
   * Valores por defecto para props opcionales.
   */
  const searchPlaceholder = props.searchPlaceholder ?? 'Buscar en la tabla...'
  const debounceDelay = props.debounceDelay ?? 300
  const emptyMessage = props.emptyMessage ?? 'No se encontraron registros'

  const showCreateButton = props.showCreateButton ?? true
  const showEditButton = props.showEditButton ?? true
  const showDeleteButton = props.showDeleteButton ?? true

  /**
   * Controlador principal de la tabla
   * Maneja filtrado, búsqueda y selección.
   */
  const { handleFilter, selectedIds, filteredRows } = useTableController({
    columns: toRef(props, 'columns'),
    rows: toRef(props, 'rows'),
    onSelectionChange: ids => emit('selection-change', ids.map(String))
  })

  /**
   * IDs seleccionados convertidos a string.
   */
  const stringSelectedIds = computed(() => selectedIds.value.map(String))

  /**
   * Maneja cambios de selección desde BaseTable.
   */
  function handleSelectionChange(ids: string[]) {
    selectedIds.value = ids
    emit('selection-change', ids)
  }
</script>

<template>
  <div class="card">
    <!-- Header -->
    <CardHeader :title="title" :subtitle="subtitle" />

    <div class="card-body">
      <!-- Filtros y búsqueda -->
      <TableFilter
        :search-placeholder="searchPlaceholder"
        :filters="filters"
        :debounce-delay="debounceDelay"
        @filter="handleFilter"
      />

      <!-- Botones de acciones -->
      <TableActionButtons
        class="mt-3"
        :selected-ids="stringSelectedIds"
        :show-create-button="showCreateButton"
        :show-edit-button="showEditButton"
        :show-delete-button="showDeleteButton"
        :disable-delete="props.disableDelete ?? false"
        :disable-delete-reason="props.disableDeleteReason ?? ''"
        @create="$emit('create')"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      >
        <!-- Slot para acciones extra -->
        <template #action-buttons="{ selectedIds }">
          <slot name="action-buttons" :selectedIds="selectedIds" />
        </template>
      </TableActionButtons>

      <!-- Tabla -->
      <BaseTable
        :table-id="tableId"
        :columns="columns"
        :rows="filteredRows"
        :empty-message="emptyMessage"
        selectable
        :selected-ids="stringSelectedIds"
        @selection-change="handleSelectionChange"
      >
        <!-- Slots dinámicos para celdas -->
        <template
          v-for="column in columns"
          :key="column.key"
          v-slot:[`cell-${column.key}`]="{ row, value }"
        >
          <slot :name="`cell-${column.key}`" :row="row" :value="value" />
        </template>
      </BaseTable>

      <!-- Footer informativo -->
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
