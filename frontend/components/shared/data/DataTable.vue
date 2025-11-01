<template>
  <div
    class="data-table"
    :class="tableClasses"
  >
    <DataTableHeader
      v-if="showHeader"
      :title="title"
      :description="description"
      :searchable="searchable"
      :search-placeholder="searchPlaceholder"
      :search-query="searchQuery"
      :show-record-count="showRecordCount"
      :show-column-toggle="showColumnToggle"
      :record-count-text="recordCountText"
      :columns="columns"
      :visible-columns="visibleColumns"
      :selected-rows="selectedData"
      :filtered-data="filteredData"
      @update:search="searchQuery = $event"
      @update:visible-columns="visibleColumns = $event"
    >
      <template #actions="{ selected, data }">
        <slot
          name="actions"
          :selected="selected"
          :data="data"
        />
      </template>
    </DataTableHeader>

    <DataTableFilters
      :filters="filters"
      :active-filters="activeFilters"
      :show-filters="showFilters"
      :has-active-filters="hasActiveFilters"
      @update:filter="handleFilterUpdate"
      @clear="clearFilters"
    />

    <div
      class="data-table__container"
      :class="containerClasses"
    >
      <DataTableBody
        :paginated-data="paginatedData"
        :visible-columns="visibleColumnsList"
        :selectable="selectable"
        :loading="loading"
        :loading-text="loadingText"
        :empty-text="emptyText"
        :select-all="selectAll"
        :selected-rows="selectedRows"
        :has-row-actions="!!$slots.rowActions"
        :total-columns="totalColumns"
        :get-row-key="getRowKey"
        :get-row-classes="getRowClasses"
        :get-column-classes="getColumnClasses"
        :get-column-header-classes="getColumnHeaderClasses"
        :get-sort-icon-classes="getSortIconClasses"
        :get-cell-value="getCellValue"
        :format-cell-value="formatCellValue"
        @sort="handleSort"
        @row-click="handleRowClick"
        @select-row="toggleSelection"
        @update:select-all="selectAll = $event"
      >
        <template
          v-for="(_, name) in $slots"
          #[name]="slotData"
        >
          <slot
            :name="name"
            v-bind="slotData"
          />
        </template>
      </DataTableBody>
    </div>

    <DataTableFooter
      v-if="showFooter"
      :selectable="selectable"
      :selected-rows="selectedData"
      :paginated="paginated"
      :current-page="currentPage"
      :filtered-data-length="filteredData.length"
      :items-per-page="itemsPerPage"
      :show-size-changer="showSizeChanger"
      :page-sizes="pageSizes"
      @page-change="emit('update:currentPage', $event)"
      @page-size-change="handlePageSizeChange"
    />
  </div>
</template>

<script setup lang="ts">
import { useDataTableFiltering } from '~/composables/shared/data/useDataTableFiltering'
import { useDataTableSorting } from '~/composables/shared/data/useDataTableSorting'
import { useDataTablePagination } from '~/composables/shared/data/useDataTablePagination'
import { useDataTableSelection } from '~/composables/shared/data/useDataTableSelection'

interface TableColumn {
  key: string
  title: string
  sortable?: boolean
  searchable?: boolean
  width?: string
  minWidth?: string
  align?: 'left' | 'center' | 'right'
  fixed?: 'left' | 'right'
  component?: string
  componentProps?: Record<string, any>
  formatter?: (value: unknown, row: unknown) => string
  render?: (value: unknown, row: unknown, column: TableColumn) => any
}

interface TableFilter {
  key: string
  label: string
  type: 'select' | 'dateRange' | 'number' | 'text'
  options?: Array<{ label: string, value: unknown }>
}

interface SortState {
  column: string | null
  direction: 'asc' | 'desc' | null
}

interface Props {
  data?: unknown[]
  columns?: TableColumn[]
  rowKey?: string
  title?: string
  description?: string
  loading?: boolean
  loadingText?: string
  emptyText?: string
  searchable?: boolean
  searchPlaceholder?: string
  sortable?: boolean
  filterable?: boolean
  selectable?: boolean
  paginated?: boolean
  itemsPerPage?: number
  pageSizes?: number[]
  showSizeChanger?: boolean
  striped?: boolean
  bordered?: boolean
  hoverable?: boolean
  compact?: boolean
  responsive?: boolean
  showHeader?: boolean
  showFooter?: boolean
  showRecordCount?: boolean
  showColumnToggle?: boolean
  filters?: TableFilter[]
  showFilters?: boolean
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'card' | 'minimal'
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  columns: () => [],
  rowKey: 'id',
  loadingText: 'Cargando datos...',
  emptyText: 'No se encontraron datos',
  searchPlaceholder: 'Buscar...',
  itemsPerPage: 10,
  pageSizes: () => [5, 10, 25, 50, 100],
  showSizeChanger: true,
  striped: true,
  bordered: true,
  hoverable: true,
  responsive: true,
  showHeader: true,
  showFooter: true,
  showRecordCount: true,
  showColumnToggle: true,
  showFilters: true,
  size: 'md',
  variant: 'default',
})

const emit = defineEmits<{
  'row-click': [row: unknown, index: number]
  'row-select': [selectedRows: unknown[]]
  'sort-change': [sort: SortState]
  'filter-change': [filters: Record<string, any>]
  'update:currentPage': [page: number]
  'page-change': [page: number, pageSize: number]
}>()

const slots = useSlots()
const dataRef = toRef(props, 'data')
const columnsRef = toRef(props, 'columns')
const searchableRef = toRef(props, 'searchable')
const paginatedRef = toRef(props, 'paginated')
const itemsPerPageRef = toRef(props, 'itemsPerPage')
const rowKeyRef = toRef(props, 'rowKey')

const { searchQuery, activeFilters, filteredData, hasActiveFilters, clearFilters, getCellValue }
  = useDataTableFiltering(dataRef, columnsRef, searchableRef)

const { sortState, sortedData, handleSort: sortColumn, getSortIconClasses }
  = useDataTableSorting(filteredData, columnsRef, getCellValue)

const { currentPage, paginatedData, handlePageSizeChange: changePageSize, resetPage }
  = useDataTablePagination(sortedData, paginatedRef, itemsPerPageRef)

const { selectedRows, selectAll, getRowKey, getRowClasses }
  = useDataTableSelection(paginatedData, rowKeyRef)

const visibleColumns = ref<string[]>(props.columns.map(col => col.key))

const tableClasses = computed(() => [
  'data-table',
  `data-table--${props.variant}`,
  `data-table--${props.size}`,
  {
    'data-table--striped': props.striped,
    'data-table--bordered': props.bordered,
    'data-table--hoverable': props.hoverable,
    'data-table--compact': props.compact,
    'data-table--responsive': props.responsive,
  },
])

const containerClasses = computed(() => {
  const classes = ['overflow-x-auto']
  if (props.variant === 'card') { classes.push('@apply border border-gray-200 rounded-lg') }
  if (props.variant === 'default' && props.bordered) { classes.push('@apply border border-gray-200 rounded-md') }
  return classes
})

const visibleColumnsList = computed(() => props.columns.filter(column => visibleColumns.value.includes(column.key)))

const totalColumns = computed(() => {
  let count = visibleColumnsList.value.length
  if (props.selectable) { count++ }
  if (slots.rowActions) { count++ }
  return count
})

const recordCountText = computed(() => {
  if (props.loading) { return 'Cargando...' }
  const total = props.data.length
  const filtered = filteredData.value.length
  const label = total === 1 ? 'registro' : 'registros'
  return total === filtered ? `${total} ${label}` : `${filtered} de ${total} ${label}`
})

const selectedData = computed(() => props.data.filter((row, index) => selectedRows.value.includes(getRowKey(row, index))))

const formatCellValue = (value: unknown, column: TableColumn): string => {
  if (column.formatter) { return column.formatter(value, column) }
  if (value === null || value === undefined) { return '-' }
  if (typeof value === 'boolean') { return value ? 'Sí' : 'No' }
  if (value instanceof Date) { return value.toLocaleDateString() }
  return String(value)
}

const getColumnClasses = (column: TableColumn) => [
  `text-${column.align || 'left'}`,
  {
    'sticky left-0 bg-white z-10': column.fixed === 'left',
    'sticky right-0 bg-white z-10': column.fixed === 'right',
  },
]

const getColumnHeaderClasses = (column: TableColumn) => [
  getColumnClasses(column),
  {
    'cursor-pointer hover:bg-gray-50': column.sortable,
    'data-table__th--sorted': sortState.value.column === column.key,
  },
]

const handleSort = (column: TableColumn) => {
  const newSort = sortColumn(column)
  emit('sort-change', newSort)
}

const handleRowClick = (row: unknown, index: number) => {
  emit('row-click', row, index)
}

const handleFilterUpdate = (key: string, value: any) => {
  activeFilters.value[key] = value
  currentPage.value = 1
  emit('filter-change', { ...activeFilters.value })
}

const handlePageSizeChange = (newSize: number) => {
  const result = changePageSize(newSize)
  emit('page-change', result.page, result.pageSize)
}

const toggleSelection = (key: string) => {
  const index = selectedRows.value.indexOf(key)
  if (index === -1) { selectedRows.value.push(key) } else { selectedRows.value.splice(index, 1) }
}

watch(selectedRows, () => emit('row-select', selectedData.value), { deep: true })
watch(activeFilters, () => { resetPage(); emit('filter-change', { ...activeFilters.value }) }, { deep: true })
watch(() => currentPage.value, newPage => emit('page-change', newPage, props.itemsPerPage))
watch(() => props.data, () => resetPage(), { deep: true })
</script>

<style scoped>
.data-table {
  @apply w-full;
}

.data-table--card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.data-table--minimal {
  @apply bg-transparent;
}

.data-table__header {
  @apply p-4 border-b border-gray-200;
}

.data-table__filters {
  @apply px-4 py-3 bg-gray-50 border-b border-gray-200;
}

.data-table__container {
  @apply relative;
}

.data-table__table {
  @apply w-full divide-y divide-gray-200;
}

.data-table__th {
  @apply px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50;
}

.data-table--sm .data-table__th {
  @apply px-3 py-2 text-xs;
}

.data-table--lg .data-table__th {
  @apply px-6 py-4 text-sm;
}

.data-table__th--select {
  @apply w-12;
}

.data-table__th--actions {
  @apply w-20 text-right;
}

.data-table__th--sorted {
  @apply bg-primary-50 text-primary-900;
}

.data-table__td {
  @apply px-4 py-3 text-sm text-gray-900 whitespace-nowrap;
}

.data-table--sm .data-table__td {
  @apply px-3 py-2 text-xs;
}

.data-table--lg .data-table__td {
  @apply px-6 py-4 text-base;
}

.data-table__td--select {
  @apply w-12;
}

.data-table__td--actions {
  @apply w-20 text-right;
}

.data-table__row {
  @apply bg-white;
}

.data-table--striped .data-table__row--even {
  @apply bg-gray-50;
}

.data-table--hoverable .data-table__row:hover {
  @apply bg-gray-100;
}

.data-table__row--selected {
  @apply bg-primary-50;
}

.data-table--bordered .data-table__table {
  @apply border border-gray-200;
}

.data-table--bordered .data-table__th,
.data-table--bordered .data-table__td {
  @apply border-b border-gray-200;
}

.data-table--compact .data-table__th {
  @apply px-2 py-1;
}

.data-table--compact .data-table__td {
  @apply px-2 py-1;
}

.data-table__footer {
  @apply p-4 border-t border-gray-200 bg-gray-50;
}

@media (max-width: 768px) {
  .data-table--responsive .data-table__table {
    @apply min-w-full;
  }

  .data-table--responsive .data-table__th,
  .data-table--responsive .data-table__td {
    @apply px-2 py-1 text-xs;
  }
}

.data-table__container::-webkit-scrollbar {
  height: 6px;
}

.data-table__container::-webkit-scrollbar-track {
  @apply bg-gray-100;
}

.data-table__container::-webkit-scrollbar-thumb {
  @apply bg-gray-400 rounded;
}
</style>
