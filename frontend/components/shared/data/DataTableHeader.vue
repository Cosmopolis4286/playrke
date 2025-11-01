<template>
  <div class="data-table__header">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div v-if="title || description">
          <h3 class="@apply text-lg font-semibold text-gray-900">
            {{ title }}
          </h3>
          <p
            v-if="description"
            class="@apply text-sm text-gray-600 mt-1"
          >
            {{ description }}
          </p>
        </div>

        <div
          v-if="showRecordCount"
          class="@apply text-sm text-gray-500"
        >
          {{ recordCountText }}
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div
          v-if="searchable"
          class="relative"
        >
          <Icon
            name="search"
            class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"
          />
          <input
            :model-value="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="@apply pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            @input="emit('update:search', ($event.target as HTMLInputElement).value)"
          >
        </div>

        <BaseDropdown
          v-if="showColumnToggle"
          placement="bottom-end"
        >
          <template #trigger>
            <BaseButton
              variant="outline"
              size="sm"
            >
              <Icon
                name="AdjustmentsHorizontalIcon"
                class="w-4 h-4 mr-2"
              />
              Columnas
            </BaseButton>
          </template>
          <template #content>
            <div class="p-3 space-y-2">
              <label
                v-for="column in columns"
                :key="column.key"
                class="flex items-center"
              >
                <input
                  :model-value="visibleColumns"
                  type="checkbox"
                  :value="column.key"
                  class="@apply mr-2 h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                  @change="emit('update:visible-columns', ($event.target as HTMLInputElement).checked ? [...visibleColumns, column.key] : visibleColumns.filter(k => k !== column.key))"
                >
                <span class="text-sm">{{ column.title }}</span>
              </label>
            </div>
          </template>
        </BaseDropdown>

        <slot
          name="actions"
          :selected="selectedRows"
          :data="filteredData"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface TableColumn {
  key: string
  title: string
}

interface Props {
  title?: string
  description?: string
  searchable?: boolean
  searchPlaceholder?: string
  searchQuery?: string
  showRecordCount?: boolean
  showColumnToggle?: boolean
  recordCountText?: string
  columns?: TableColumn[]
  visibleColumns?: string[]
  selectedRows?: unknown[]
  filteredData?: unknown[]
}

withDefaults(defineProps<Props>(), {
  searchPlaceholder: 'Buscar...',
  searchQuery: '',
  recordCountText: '',
  columns: () => [],
  visibleColumns: () => [],
  selectedRows: () => [],
  filteredData: () => [],
})

const emit = defineEmits<{
  'update:search': [value: string]
  'update:visible-columns': [value: string[]]
}>()
</script>
