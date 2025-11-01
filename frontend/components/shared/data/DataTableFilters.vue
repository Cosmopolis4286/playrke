<template>
  <div
    v-if="showFilters && filters && filters.length > 0"
    class="data-table__filters"
  >
    <div class="flex flex-wrap items-center gap-3">
      <div
        v-for="filter in filters"
        :key="filter.key"
        class="flex items-center gap-2"
      >
        <label class="text-sm font-medium text-gray-700">
          {{ filter.label }}:
        </label>

        <select
          v-if="filter.type === 'select'"
          :value="activeFilters[filter.key]"
          class="@apply text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          @change="handleFilterChange(filter.key, ($event.target as HTMLSelectElement).value)"
        >
          <option value="">
            Todos
          </option>
          <option
            v-for="option in filter.options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>

        <div
          v-else-if="filter.type === 'dateRange'"
          class="flex items-center gap-2"
        >
          <input
            :value="activeFilters[`${filter.key}_from`]"
            type="date"
            class="@apply text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            @input="handleFilterChange(`${filter.key}_from`, ($event.target as HTMLInputElement).value)"
          >
          <span class="text-gray-500">-</span>
          <input
            :value="activeFilters[`${filter.key}_to`]"
            type="date"
            class="@apply text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            @input="handleFilterChange(`${filter.key}_to`, ($event.target as HTMLInputElement).value)"
          >
        </div>
      </div>

      <BaseButton
        v-if="hasActiveFilters"
        variant="outline"
        size="sm"
        @click="emit('clear')"
      >
        Limpiar filtros
      </BaseButton>
    </div>
  </div>
</template>

<script setup lang="ts">
interface TableFilter {
  key: string
  label: string
  type: 'select' | 'dateRange' | 'number' | 'text'
  options?: Array<{ label: string, value: unknown }>
}

interface Props {
  filters?: TableFilter[]
  activeFilters?: Record<string, any>
  showFilters?: boolean
  hasActiveFilters?: boolean
}

withDefaults(defineProps<Props>(), {
  filters: () => [],
  activeFilters: () => ({}),
  showFilters: true,
  hasActiveFilters: false,
})

const emit = defineEmits<{
  'update:filter': [key: string, value: any]
  'clear': []
}>()

const handleFilterChange = (key: string, value: any) => {
  emit('update:filter', key, value)
}
</script>
