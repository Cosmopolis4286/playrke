<script setup lang="ts" name="TableWithFilter">
  import { ref, computed } from 'vue'
  import TableFilter from './TableFilter.vue'
  import CardHeader from '../Card/CardHeader.vue'
  import BaseTable from './BaseTable.vue'
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

  /** Props del componente */
  const props = defineProps<{
    tableId: string
    title: string
    columns: Column[]
    rows: Record<string, any>[]
    filters?: Filter[]
    searchPlaceholder?: string
    debounceDelay?: number
    emptyMessage?: string
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
  }>()

  /** Emit events */
  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
    (e: 'selection-change', ids: string[]): void
  }>()

  /** Opciones por defecto para props opcionales */
  const searchPlaceholder = props.searchPlaceholder ?? 'Buscar en la tabla...'
  const debounceDelay = props.debounceDelay ?? 300
  const emptyMessage = props.emptyMessage ?? 'No se encontraron registros'
  const showCreateButton = props.showCreateButton ?? true
  const showEditButton = props.showEditButton ?? true
  const showDeleteButton = props.showDeleteButton ?? true

  /** Estado reactivo para búsqueda y filtros */
  const currentFilters = ref<{ search: string; filters: Record<string, string> }>({
    search: '',
    filters: {}
  })

  /** IDs seleccionados */
  const selectedIds = ref<string[]>([])

  /**
   * Obtiene valor seguro en objeto (evita errores si no existe)
   * @param obj Objeto
   * @param key Clave a obtener
   */
  function getNestedValue(obj: Record<string, any>, key: string): any {
    return obj?.[key] ?? ''
  }

  /** Filtra las filas según búsqueda y filtros activos */
  const filteredRows = computed(() => {
    let rows = props.rows

    // Filtro global de búsqueda
    if (currentFilters.value.search) {
      const searchLower = currentFilters.value.search.toLowerCase()
      rows = rows.filter(row =>
        props.columns.some(col => {
          const val = getNestedValue(row, col.key)
          return String(val).toLowerCase().includes(searchLower)
        })
      )
    }

    // Filtros por columna
    for (const [key, val] of Object.entries(currentFilters.value.filters)) {
      if (val) {
        rows = rows.filter(row => String(getNestedValue(row, key)) === val)
      }
    }

    return rows
  })

  /**
   * Actualiza filtros y limpia selección inválida
   * @param filterData Datos de búsqueda y filtros
   */
  function handleFilter(filterData: { search: string; filters: Record<string, string> }): void {
    currentFilters.value = filterData
    selectedIds.value = selectedIds.value.filter(id =>
      filteredRows.value.some(row => row.id === id)
    )
    emitSelectionChange()
  }

  /** Emite evento con IDs seleccionados */
  function emitSelectionChange(): void {
    emit('selection-change', [...selectedIds.value])
  }

  /** Maneja evento de cambio de selección desde BaseTable */
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

      <div class="mt-3" v-if="selectedIds.length || showCreateButton">
        <button v-if="showCreateButton" class="btn btn-sm btn-success" @click="$emit('create')">
          <i class="fas fa-plus"></i> Crear
        </button>

        <button
          v-if="selectedIds.length === 1 && showEditButton"
          class="btn btn-sm btn-primary ml-2"
          @click="$emit('edit', selectedIds[0])"
        >
          <i class="fas fa-edit"></i>
        </button>

        <button
          v-if="selectedIds.length > 0 && showDeleteButton"
          class="btn btn-sm btn-danger ml-2"
          @click="$emit('delete', selectedIds)"
        >
          <i class="fas fa-trash"></i>
          <span v-if="selectedIds.length > 1">({{ selectedIds.length }})</span>
        </button>

        <slot name="action-buttons" :selectedIds="selectedIds" />
      </div>

      <BaseTable
        :table-id="tableId"
        :columns="columns"
        :rows="filteredRows"
        :empty-message="emptyMessage"
        :selected-ids="selectedIds"
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

      <div
        v-if="filteredRows.length"
        class="d-flex justify-content-between align-items-center mt-2"
      >
        <small class="text-muted">
          Mostrando {{ filteredRows.length }} de {{ rows.length }} registros
          <span v-if="selectedIds.length"> ({{ selectedIds.length }} seleccionados)</span>
        </small>
      </div>
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
