<script setup lang="ts" name="TableWithFilter">
  import { ref, computed } from 'vue'
  import TableFilter from './TableFilter.vue'
  import CardHeader from '../Card/CardHeader.vue'
  import type { Column } from '@/js/types/common/table/column'

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
    rows: Array<Record<string, any>>
    filters?: Filter[]
    searchPlaceholder?: string
    debounceDelay?: number
    emptyMessage?: string
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
  }>()

  /** Valores por defecto para props opcionales */
  const searchPlaceholder = props.searchPlaceholder ?? 'Buscar en la tabla...'
  const debounceDelay = props.debounceDelay ?? 300
  const emptyMessage = props.emptyMessage ?? 'No se encontraron registros'
  const showCreateButton = props.showCreateButton ?? true
  const showEditButton = props.showEditButton ?? true
  const showDeleteButton = props.showDeleteButton ?? true

  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
    (e: 'selection-change', ids: string[]): void
  }>()

  /** IDs seleccionados en la tabla */
  const selectedIds = ref<string[]>([])

  /** Estado actual de filtros y búsqueda */
  const currentFilters = ref<{ search: string; filters: Record<string, string> }>({
    search: '',
    filters: {}
  })

  /**
   * Obtiene valor seguro de propiedad en objeto, evita errores si no existe.
   * @param obj Objeto de datos
   * @param key Clave para extraer
   * @returns Valor o cadena vacía
   */
  function getNestedValue(obj: Record<string, any>, key: string): any {
    return obj?.[key] ?? ''
  }

  /**
   * Escapa texto para evitar inyección HTML
   * @param text Texto crudo
   * @returns Texto seguro para HTML
   */
  function escapeHtml(text: string): string {
    const map: Record<string, string> = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }
    return String(text).replace(/[&<>"']/g, m => map[m])
  }

  /**
   * Formatea contenido de celda según configuración HTML
   * @param row Fila de datos
   * @param column Columna actual
   * @returns Contenido seguro o con HTML
   */
  function formatCell(row: Record<string, any>, column: Column): string {
    const value = getNestedValue(row, column.key)
    return column.html ? value : escapeHtml(value)
  }

  /** Filtra filas según búsqueda y filtros activos */
  const filteredRows = computed(() => {
    let rows = props.rows

    // Filtro de búsqueda global (cualquier columna)
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
    Object.entries(currentFilters.value.filters).forEach(([key, val]) => {
      if (val) {
        rows = rows.filter(row => String(getNestedValue(row, key)) === val)
      }
    })

    return rows
  })

  /** Indica si todas las filas visibles están seleccionadas */
  const allSelected = computed(
    () => filteredRows.value.length > 0 && selectedIds.value.length === filteredRows.value.length
  )

  /** Indica si hay selección parcial */
  const someSelected = computed(
    () => selectedIds.value.length > 0 && selectedIds.value.length < filteredRows.value.length
  )

  /** Cambia filtro y limpia selección inválida */
  function handleFilter(filterData: { search: string; filters: Record<string, string> }): void {
    currentFilters.value = filterData
    selectedIds.value = selectedIds.value.filter(id =>
      filteredRows.value.some(row => row.id === id)
    )
    emitSelectionChange()
  }

  /** Alterna selección de fila */
  function toggleRow(id: string): void {
    const idx = selectedIds.value.indexOf(id)
    idx >= 0 ? selectedIds.value.splice(idx, 1) : selectedIds.value.push(id)
    emitSelectionChange()
  }

  /** Alterna selección total */
  function toggleAll(): void {
    selectedIds.value = allSelected.value ? [] : filteredRows.value.map(r => r.id)
    emitSelectionChange()
  }

  /** Indica si fila está seleccionada */
  function isSelected(id: string): boolean {
    return selectedIds.value.includes(id)
  }

  /** Emite evento con IDs seleccionados */
  function emitSelectionChange(): void {
    emit('selection-change', [...selectedIds.value])
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

      <div class="mb-3" v-if="selectedIds.length || showCreateButton">
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
          {{ selectedIds.length > 1 ? ` (${selectedIds.length})` : '' }}
        </button>

        <slot name="action-buttons" :selectedIds="selectedIds"></slot>
      </div>

      <div class="table-responsive mt-2">
        <table :id="tableId" class="table-striped table-bordered w-100 my-2 table">
          <thead>
            <tr>
              <th class="text-center" style="width: 40px">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                  @change="toggleAll"
                />
              </th>
              <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in filteredRows"
              :key="row.id"
              :class="{ 'table-primary': isSelected(row.id) }"
              @click="toggleRow(row.id)"
              style="cursor: pointer"
            >
              <td class="text-center">
                <input
                  type="checkbox"
                  :checked="isSelected(row.id)"
                  @click.stop="toggleRow(row.id)"
                />
              </td>
              <td v-for="column in columns" :key="column.key">
                <slot
                  :name="`cell-${column.key}`"
                  :row="row"
                  :value="getNestedValue(row, column.key)"
                >
                  <span v-html="formatCell(row, column)"></span>
                </slot>
              </td>
            </tr>
            <tr v-if="filteredRows.length === 0">
              <td :colspan="columns.length + 1" class="text-muted py-4 text-center">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">{{ emptyMessage }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

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
