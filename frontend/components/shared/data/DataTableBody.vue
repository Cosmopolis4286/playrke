<template>
  <table class="data-table__table">
    <thead>
      <tr>
        <th
          v-if="selectable"
          class="data-table__th data-table__th--select"
        >
          <input
            :model-value="selectAll"
            type="checkbox"
            class="@apply h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
            @change="emit('update:select-all', ($event.target as HTMLInputElement).checked)"
          >
        </th>

        <th
          v-for="column in visibleColumns"
          :key="column.key"
          class="data-table__th"
          :class="getColumnHeaderClasses(column)"
          @click="emit('sort', column)"
        >
          <div class="flex items-center justify-between">
            <span>{{ column.title }}</span>

            <div
              v-if="column.sortable"
              class="flex flex-col ml-2"
            >
              <Icon
                name="ChevronUpIcon"
                class="w-3 h-3"
                :class="getSortIconClasses(column.key, 'asc')"
              />
              <Icon
                name="ChevronDownIcon"
                class="w-3 h-3 -mt-1"
                :class="getSortIconClasses(column.key, 'desc')"
              />
            </div>
          </div>
        </th>

        <th
          v-if="hasRowActions"
          class="data-table__th data-table__th--actions"
        >
          Acciones
        </th>
      </tr>
    </thead>

    <tbody>
      <tr v-if="loading">
        <td
          :colspan="totalColumns"
          class="data-table__td text-center py-8"
        >
          <LoadingSpinner size="md" />
          <p class="text-sm text-gray-600 mt-2">
            {{ loadingText }}
          </p>
        </td>
      </tr>

      <tr v-else-if="paginatedData.length === 0">
        <td
          :colspan="totalColumns"
          class="data-table__td text-center py-8"
        >
          <slot name="empty">
            <div class="text-center">
              <Icon
                name="InboxIcon"
                class="w-12 h-12 text-gray-400 mx-auto mb-3"
              />
              <p class="text-sm text-gray-600">
                {{ emptyText }}
              </p>
            </div>
          </slot>
        </td>
      </tr>

      <tr
        v-for="(row, index) in paginatedData"
        v-else
        :key="getRowKey(row, index)"
        class="data-table__row"
        :class="getRowClasses(row, index)"
        @click="emit('row-click', row, index)"
      >
        <td
          v-if="selectable"
          class="data-table__td data-table__td--select"
        >
          <input
            :model-value="selectedRows"
            type="checkbox"
            :value="getRowKey(row, index)"
            class="@apply h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
            @change="emit('select-row', getRowKey(row, index))"
          >
        </td>

        <td
          v-for="column in visibleColumns"
          :key="column.key"
          class="data-table__td"
          :class="getColumnClasses(column)"
        >
          <slot
            :name="`column.${column.key}`"
            :row="row"
            :value="getCellValue(row, column)"
            :column="column"
            :index="index"
          >
            <component
              :is="column.component"
              v-if="column.component"
              :value="getCellValue(row, column)"
              :row="row"
              v-bind="column.componentProps || {}"
            />
            <template v-else>
              {{ formatCellValue(getCellValue(row, column), column) }}
            </template>
          </slot>
        </td>

        <td
          v-if="hasRowActions"
          class="data-table__td data-table__td--actions"
        >
          <slot
            name="rowActions"
            :row="row"
            :index="index"
          />
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup lang="ts">
interface TableColumn {
  key: string
  title: string
  sortable?: boolean
  align?: 'left' | 'center' | 'right'
  fixed?: 'left' | 'right'
  component?: string
  componentProps?: Record<string, any>
  formatter?: (value: unknown, column: TableColumn) => string
}

interface Props {
  paginatedData: unknown[]
  visibleColumns: TableColumn[]
  selectable?: boolean
  loading?: boolean
  loadingText?: string
  emptyText?: string
  selectAll?: boolean
  selectedRows?: string[]
  hasRowActions?: boolean
  totalColumns?: number
  getRowKey: (row: unknown, index: number) => string
  getRowClasses: (row: unknown, index: number) => Record<string, boolean>
  getColumnClasses: (column: TableColumn) => any
  getColumnHeaderClasses: (column: TableColumn) => any
  getSortIconClasses: (columnKey: string, direction: 'asc' | 'desc') => Record<string, boolean>
  getCellValue: (row: unknown, column: TableColumn) => unknown
  formatCellValue: (value: unknown, column: TableColumn) => string
}

withDefaults(defineProps<Props>(), {
  loadingText: 'Cargando datos...',
  emptyText: 'No se encontraron datos',
  selectedRows: () => [],
  totalColumns: 0,
})

const emit = defineEmits<{
  'sort': [column: TableColumn]
  'row-click': [row: unknown, index: number]
  'select-row': [key: string]
  'update:select-all': [value: boolean]
}>()
</script>
