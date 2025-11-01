<template>
  <div class="data-table__footer">
    <div class="flex items-center justify-between">
      <div
        v-if="selectable && selectedRows.length > 0"
        class="text-sm text-gray-600"
      >
        {{ selectedRows.length }} {{ selectedRows.length === 1 ? 'elemento seleccionado' : 'elementos seleccionados' }}
      </div>

      <Pagination
        v-if="paginated"
        :current-page="currentPage"
        :total-items="filteredDataLength"
        :items-per-page="itemsPerPage"
        :show-size-changer="showSizeChanger"
        :page-sizes="pageSizes"
        @update:current-page="emit('page-change', $event)"
        @update:items-per-page="emit('page-size-change', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  selectable?: boolean
  selectedRows?: unknown[]
  paginated?: boolean
  currentPage?: number
  filteredDataLength?: number
  itemsPerPage?: number
  showSizeChanger?: boolean
  pageSizes?: number[]
}

withDefaults(defineProps<Props>(), {
  selectedRows: () => [],
  currentPage: 1,
  filteredDataLength: 0,
  itemsPerPage: 10,
  showSizeChanger: true,
  pageSizes: () => [5, 10, 25, 50, 100],
})

const emit = defineEmits<{
  'page-change': [page: number]
  'page-size-change': [size: number]
}>()
</script>
