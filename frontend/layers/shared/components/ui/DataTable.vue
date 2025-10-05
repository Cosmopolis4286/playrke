<template>
  <div class="w-full">
    <!-- Header con búsqueda y acciones -->
    <div v-if="showHeader" class="flex justify-between items-center mb-4">
      <div class="flex-1">
        <input
          v-if="searchable"
          v-model="searchTerm"
          type="text"
          :placeholder="searchPlaceholder"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
      </div>
      <div class="flex items-center space-x-2">
        <slot name="actions" />
      </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                column.sortable ? 'cursor-pointer hover:bg-gray-100' : ''
              ]"
              @click="column.sortable ? handleSort(column.key) : null"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <Icon
                  v-if="column.sortable && sortBy === column.key"
                  :name="sortOrder === 'asc' ? 'lucide:arrow-up' : 'lucide:arrow-down'"
                  class="h-4 w-4"
                />
              </div>
            </th>
            <th v-if="hasActions" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading" class="text-center">
            <td :colspan="columns.length + (hasActions ? 1 : 0)" class="px-6 py-12">
              <Icon name="lucide:loader-2" class="h-8 w-8 animate-spin mx-auto text-gray-400" />
              <p class="mt-2 text-gray-500">Cargando datos...</p>
            </td>
          </tr>
          <tr v-else-if="paginatedData.length === 0" class="text-center">
            <td :colspan="columns.length + (hasActions ? 1 : 0)" class="px-6 py-12">
              <Icon name="lucide:inbox" class="h-12 w-12 mx-auto text-gray-400" />
              <p class="mt-2 text-gray-500">No hay datos disponibles</p>
            </td>
          </tr>
          <tr
            v-else
            v-for="(row, index) in paginatedData"
            :key="index"
            class="hover:bg-gray-50 transition"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
            >
              <slot :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column.key)">
                {{ getCellValue(row, column.key) }}
              </slot>
            </td>
            <td v-if="hasActions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <slot name="actions" :row="row" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div v-if="paginated && totalPages > 1" class="flex justify-between items-center mt-4">
      <div class="text-sm text-gray-700">
        Mostrando {{ startIndex + 1 }} - {{ endIndex }} de {{ filteredData.length }} registros
      </div>
      <div class="flex items-center space-x-2">
        <button
          :disabled="currentPage === 1"
          class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          @click="currentPage--"
        >
          Anterior
        </button>
        <div class="flex items-center space-x-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            :class="[
              'px-3 py-1 border rounded-lg',
              currentPage === page
                ? 'bg-blue-600 text-white border-blue-600'
                : 'border-gray-300 hover:bg-gray-50'
            ]"
            @click="currentPage = page"
          >
            {{ page }}
          </button>
        </div>
        <button
          :disabled="currentPage === totalPages"
          class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          @click="currentPage++"
        >
          Siguiente
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Componente DataTable reutilizable
 *
 * Tabla de datos con paginación, búsqueda, ordenamiento y slots personalizables.
 */

interface Column {
  key: string
  label: string
  sortable?: boolean
}

interface Props {
  data: any[]
  columns: Column[]
  loading?: boolean
  searchable?: boolean
  searchPlaceholder?: string
  paginated?: boolean
  perPage?: number
  showHeader?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  searchable: true,
  searchPlaceholder: 'Buscar...',
  paginated: true,
  perPage: 10,
  showHeader: true
})

const slots = useSlots()
const hasActions = computed(() => !!slots.actions)

// Estado
const searchTerm = ref('')
const sortBy = ref<string>('')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)

// Datos filtrados
const filteredData = computed(() => {
  let data = [...props.data]

  // Búsqueda
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    data = data.filter(row => {
      return props.columns.some(column => {
        const value = getCellValue(row, column.key)
        return String(value).toLowerCase().includes(term)
      })
    })
  }

  // Ordenamiento
  if (sortBy.value) {
    data.sort((a, b) => {
      const aVal = getCellValue(a, sortBy.value)
      const bVal = getCellValue(b, sortBy.value)

      if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
      if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
      return 0
    })
  }

  return data
})

// Paginación
const totalPages = computed(() => Math.ceil(filteredData.value.length / props.perPage))
const startIndex = computed(() => (currentPage.value - 1) * props.perPage)
const endIndex = computed(() => Math.min(startIndex.value + props.perPage, filteredData.value.length))

const paginatedData = computed(() => {
  if (!props.paginated) return filteredData.value
  return filteredData.value.slice(startIndex.value, endIndex.value)
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

// Métodos
const getCellValue = (row: any, key: string) => {
  return key.split('.').reduce((obj, k) => obj?.[k], row) ?? ''
}

const handleSort = (key: string) => {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = key
    sortOrder.value = 'asc'
  }
}

// Watchers
watch(searchTerm, () => {
  currentPage.value = 1
})
</script>
