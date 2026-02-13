<script setup lang="ts" name="TableFilter">
  import { ref, reactive, computed, watch } from 'vue'
  import type { TableFilter } from '@/js/types/common/table/table-filter'

  /**
   * Props del componente
   */
  const props = defineProps<{
    /** Placeholder del input de búsqueda */
    searchPlaceholder?: string

    /** Definición de filtros de tabla */
    filters?: readonly TableFilter[]

    /** Delay del debounce en ms */
    debounceDelay?: number
  }>()

  /**
   * Evento emitido al cambiar búsqueda o filtros
   */
  const emit = defineEmits<{
    (
      e: 'filter',
      payload: {
        search: string
        filters: Record<string, string>
      }
    ): void
  }>()

  /**
   * Valores derivados con defaults seguros
   */
  const searchPlaceholder = computed(() => props.searchPlaceholder ?? 'Buscar...')
  const filters = computed(() => props.filters ?? [])
  const debounceDelay = computed(() => props.debounceDelay ?? 300)

  /**
   * Estado reactivo
   */
  const searchQuery = ref('')
  const activeFilters = reactive<Record<string, string>>({})

  let debounceTimeout: ReturnType<typeof setTimeout> | null = null

  /**
   * Inicializa claves de filtros dinámicos
   */
  watch(
    filters,
    newFilters => {
      newFilters.forEach(({ key }) => {
        if (!(key in activeFilters)) {
          activeFilters[key] = ''
        }
      })
    },
    { immediate: true }
  )

  /**
   * Entradas activas (para pills)
   */
  const activeFilterEntries = computed(() =>
    Object.entries(activeFilters).filter(([, value]) => value !== '')
  )

  /**
   * Indica si hay búsqueda o filtros activos
   */
  const hasActiveFilters = computed(
    () =>
      searchQuery.value.trim() !== '' || Object.values(activeFilters).some(value => value !== '')
  )

  /**
   * Emite el estado actual de filtros
   */
  function emitChange(): void {
    emit('filter', {
      search: searchQuery.value,
      filters: { ...activeFilters }
    })
  }

  /**
   * Maneja búsqueda con debounce
   */
  function handleSearch(): void {
    if (debounceTimeout) clearTimeout(debounceTimeout)

    debounceTimeout = setTimeout(emitChange, debounceDelay.value)
  }

  /**
   * Limpia búsqueda
   */
  function clearSearch(): void {
    searchQuery.value = ''
    emitChange()
  }

  /**
   * Limpia un filtro individual
   */
  function clearFilter(key: string): void {
    activeFilters[key] = ''
    emitChange()
  }

  /**
   * Limpia todos los filtros
   */
  function clearAllFilters(): void {
    searchQuery.value = ''
    Object.keys(activeFilters).forEach(key => (activeFilters[key] = ''))
    emitChange()
  }

  /**
   * Obtiene label de filtro
   */
  function getFilterLabel(key: string): string {
    return filters.value.find(f => f.key === key)?.label ?? key
  }

  /**
   * Obtiene el label del option seleccionado para un filtro
   */
  function getFilterValueLabel(key: string, value: string): string {
    const filter = filters.value.find(f => f.key === key)
    const option = filter?.options?.find(opt => opt.value === value)
    return option?.label ?? value
  }
</script>

<template>
  <div class="table-filter-container">
    <div class="row">
      <!-- Búsqueda -->
      <div class="col-md-6 col-lg-4 my-1">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="fas fa-search" />
            </span>
          </div>
          <input
            v-model="searchQuery"
            type="text"
            class="form-control"
            :placeholder="searchPlaceholder"
            @input="handleSearch"
          />
          <div class="input-group-append">
            <button
              v-if="searchQuery"
              class="btn btn-outline-secondary"
              type="button"
              @click="clearSearch"
            >
              <i class="fas fa-times" />
            </button>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div v-for="filter in filters" :key="filter.key" class="col-md-6 col-lg-4 my-1">
        <select v-model="activeFilters[filter.key]" class="form-control" @change="emitChange">
          <option value="">
            {{ filter.placeholder ?? `Todos los ${filter.label}` }}
          </option>

          <option v-for="option in filter.options" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Pills -->
    <div class="row">
      <div class="col-12">
        <div v-if="hasActiveFilters" class="active-filters mt-2">
          <!-- Limpiar -->
          <button class="btn btn-sm btn-outline-danger" type="button" @click="clearAllFilters">
            <i class="fas fa-filter-circle-xmark" />
            Limpiar filtros
          </button>
          <!-- Filtro-Buscar -->
          <span v-if="searchQuery" class="badge badge-secondary">
            "{{ searchQuery }}"
            <i class="fas fa-times ml-1" @click="clearSearch" />
          </span>
          <!-- Filtro-Selects -->
          <span v-for="[key, value] in activeFilterEntries" :key="key" class="badge badge-info">
            {{ getFilterLabel(key) }}: {{ getFilterValueLabel(key, value) }}
            <i class="fas fa-times ml-1" style="cursor: pointer" @click="clearFilter(key)" />
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .table-filter-container {
    background-color: #f8f9fa;
    padding: 0.75rem 1rem;
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
</style>
