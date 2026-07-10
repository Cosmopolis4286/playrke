<script setup lang="ts" name="TableWithFilter">
  import { computed, ref, toRef } from 'vue'

  import CardHeader from '../Card/CardHeader.vue'
  import TableFilter from './TableFilter.vue'
  import TableActionButtons from './TableActionButtons.vue'
  import BaseTable from './BaseTable.vue'
  import TableFooterInfo from './TableFooterInfo.vue'
  import TablePagination from './TablePagination.vue'

  import { useTableController, ServerQuery } from '@/js/composables/common/table/useTableController'
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
    errorMessage?: string | null
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
    disableEdit?: boolean
    disableEditReason?: string
    disableDelete?: boolean
    disableDeleteReason?: string
    loading?: boolean
    /** Activa paginación client-side con este tamaño de página */
    pageSize?: number
    /**
     * Modo server-side: `rows` ya viene filtrado/ordenado/paginado por el backend.
     * Requiere `serverTotalPages` y maneja filtros/orden/página vía `@query-change`.
     */
    serverSide?: boolean
    serverTotalPages?: number
  }>()

  /**
   * Eventos emitidos al componente padre.
   */
  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
    (e: 'selection-change', ids: string[]): void
    (e: 'query-change', query: ServerQuery): void
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
   * Maneja filtrado, búsqueda, orden, selección y paginación.
   */
  const {
    handleFilter,
    currentFilters,
    selectedIds,
    filteredRows,
    paginatedRows,
    currentPage,
    totalPages,
    setPage,
    currentSort,
    setSort
  } = useTableController({
    columns: toRef(props, 'columns'),
    rows: toRef(props, 'rows'),
    pageSize: props.pageSize,
    serverSide: props.serverSide,
    onSelectionChange: ids => emit('selection-change', ids.map(String)),
    onQueryChange: query => emit('query-change', query)
  })

  /** Referencia al TableFilter para poder limpiar filtros desde el botón de tabla vacía. */
  const tableFilterRef = ref<{ clearAllFilters: () => void } | null>(null)

  /** true si hay búsqueda o algún filtro por columna activo. */
  const hasActiveFilters = computed(
    () =>
      currentFilters.value.search.trim() !== '' ||
      Object.values(currentFilters.value.filters).some(v => v !== '')
  )

  function handleClearFilters(): void {
    tableFilterRef.value?.clearAllFilters()
  }

  /**
   * Filas realmente renderizadas: paginadas si hay `pageSize` o modo server-side,
   * filtradas completas en caso contrario (comportamiento previo).
   */
  const visibleRows = computed(() => (props.pageSize || props.serverSide ? paginatedRows.value : filteredRows.value))

  /**
   * IDs de todos los resultados filtrados (no solo la página visible), para
   * ofrecer "seleccionar los N resultados" en vez de solo la página actual.
   * Solo tiene sentido en client-side: en server-side el backend pagina y el
   * cliente nunca tiene el dataset completo para poder seleccionarlo todo.
   */
  const allFilteredIds = computed(() =>
    props.serverSide ? null : filteredRows.value.map(r => String(r.id))
  )

  /**
   * Total de páginas a mostrar en el paginador.
   */
  const displayTotalPages = computed(() => (props.serverSide ? (props.serverTotalPages ?? 1) : totalPages.value))

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
  <div class="card" style="position: relative;">
    <!-- Overlay de carga -->
    <div
      v-if="props.loading"
      style="position:absolute;inset:0;z-index:10;background:rgba(255,255,255,0.6);display:flex;align-items:center;justify-content:center;border-radius:inherit;"
    >
      <span class="spinner-border text-primary" role="status" aria-hidden="true" style="width:2rem;height:2rem;"></span>
    </div>

    <!-- Header -->
    <CardHeader :title="title" :subtitle="subtitle" />

    <div class="card-body">
      <!-- Filtros y búsqueda -->
      <TableFilter
        ref="tableFilterRef"
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
        :disable-edit="props.disableEdit ?? false"
        :disable-edit-reason="props.disableEditReason ?? ''"
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

      <!-- Error al cargar datos -->
      <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center mt-3 mb-0" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ errorMessage }}
      </div>

      <!-- Tabla -->
      <BaseTable
        v-else
        :table-id="tableId"
        :columns="columns"
        :rows="visibleRows"
        :empty-message="emptyMessage"
        :sort="currentSort"
        selectable
        :selected-ids="stringSelectedIds"
        :all-filtered-ids="allFilteredIds"
        :has-active-filters="hasActiveFilters"
        @selection-change="handleSelectionChange"
        @sort-change="setSort"
        @clear-filters="handleClearFilters"
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

      <!-- Footer informativo + paginación -->
      <div v-if="!errorMessage" class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
        <TableFooterInfo
          :filtered-count="filteredRows.length"
          :total-count="rows.length"
          :selected-count="selectedIds.length"
        />

        <TablePagination
          v-if="pageSize || serverSide"
          :current-page="currentPage"
          :total-pages="displayTotalPages"
          :disabled="loading"
          @update:current-page="setPage"
        />
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

