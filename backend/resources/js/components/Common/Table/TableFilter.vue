<template>
  <div class="table-filter-container mb-3">
    <div class="row">
      <!-- Search Input -->
      <div class="col-md-6 col-lg-4 mb-2">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="fas fa-search"></i>
            </span>
          </div>
          <input
            type="text"
            class="form-control"
            :placeholder="searchPlaceholder"
            v-model="searchQuery"
            @input="handleSearch"
          />
          <div class="input-group-append" v-if="searchQuery">
            <button class="btn btn-outline-secondary" type="button" @click="clearSearch">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Dynamic Filters -->
      <div v-for="filter in filters" :key="filter.key" class="col-md-6 col-lg-4 mb-2">
        <select
          class="form-control"
          v-model="activeFilters[filter.key]"
          @change="handleFilterChange"
        >
          <option value="">
            {{ filter.placeholder || `Todos los ${filter.label}` }}
          </option>
          <option v-for="option in filter.options" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>

      <!-- Clear All Filters Button -->
      <div class="col-md-12 col-lg-4 d-flex align-items-end mb-2" v-if="hasActiveFilters">
        <button class="btn btn-sm btn-outline-danger w-100" @click="clearAllFilters">
          <i class="fas fa-filter-circle-xmark"></i>
          Limpiar Filtros
        </button>
      </div>
    </div>

    <!-- Active Filters Pills -->
    <div class="active-filters mt-2" v-if="hasActiveFilters">
      <span class="badge badge-secondary mr-1" v-if="searchQuery">
        <i class="fas fa-search mr-1"></i>
        "{{ searchQuery }}"
        <i class="fas fa-times ml-1" style="cursor: pointer" @click="clearSearch"></i>
      </span>
      <span v-for="(value, key) in activeFilters" :key="key" class="badge badge-info mr-1">
        {{ getFilterLabel(key) }}: {{ getOptionLabel(key, value) }}
        <i class="fas fa-times ml-1" style="cursor: pointer" @click="clearFilter(key)"></i>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  interface FilterOption {
    value: string
    label: string
  }

  interface Filter {
    key: string
    label: string
    placeholder?: string
    options: FilterOption[]
  }

  // Definición solo tipo
  const props = defineProps<{
    searchPlaceholder?: string
    filters?: Filter[]
    debounceDelay?: number
  }>()

  // Valores por defecto
  const searchPlaceholder = props.searchPlaceholder ?? 'Buscar...'
  const filters = props.filters ?? []
  const debounceDelay = props.debounceDelay ?? 300

  const emit = defineEmits(['search', 'filter'])

  // Estado reactivo
  const searchQuery = ref('')
  const activeFilters = ref<Record<string, string>>({})
  let debounceTimeout = null

  // Computed
  const hasActiveFilters = computed(() => {
    return searchQuery.value || Object.values(activeFilters.value).some(v => v)
  })

  // Métodos
  const handleSearch = () => {
    // Debounce para evitar múltiples llamadas mientras se escribe
    clearTimeout(debounceTimeout)
    debounceTimeout = setTimeout(() => {
      emitFilterChange()
    }, debounceDelay)
  }

  const handleFilterChange = () => {
    emitFilterChange()
  }

  const emitFilterChange = () => {
    emit('filter', {
      search: searchQuery.value,
      filters: activeFilters.value
    })
  }

  const clearSearch = () => {
    searchQuery.value = ''
    emitFilterChange()
  }

  const clearFilter = key => {
    activeFilters.value[key] = ''
    emitFilterChange()
  }

  const clearAllFilters = () => {
    searchQuery.value = ''
    activeFilters.value = {}
    emitFilterChange()
  }

  const getFilterLabel = (key: string) => {
    const filter = props.filters.find(f => f.key === key)
    return filter ? filter.label : key
  }

  const getOptionLabel = (filterKey: string, value: string) => {
    const filter = props.filters.find(f => f.key === filterKey)
    if (!filter) return value

    const option = filter.options.find(o => o.value === value)
    return option ? option.label : value
  }

  // Inicializar activeFilters con todas las keys de filters
  watch(
    () => props.filters,
    newFilters => {
      newFilters.forEach(filter => {
        if (!(filter.key in activeFilters.value)) {
          activeFilters.value[filter.key] = ''
        }
      })
    },
    { immediate: true }
  )
</script>

<style scoped>
  .table-filter-container {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
  }

  .active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }

  .badge {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
  }

  .input-group-text {
    background-color: white;
    border-right: 0;
  }

  .input-group .form-control {
    border-left: 0;
  }

  .input-group .form-control:focus {
    border-left: 0;
    box-shadow: none;
  }

  .input-group-prepend .input-group-text {
    border-right: 1px solid #ced4da;
  }
</style>
