<template>
  <div class="card">
    <!-- Card Header -->
    <div class="card-header">
      <div class="d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{{ title }}</h5>
      </div>
    </div>

    <!-- Card Body -->
    <div class="card-body">
      <!-- Filtros -->
      <TableFilter
        :search-placeholder="searchPlaceholder"
        :filters="filters"
        :debounce-delay="debounceDelay"
        @filter="handleFilter"
      />

      <!-- Botones de acciones -->
      <div class="mb-3" v-if="selectedIds.length > 0 || showCreateButton">
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

        <!-- Slot para botones personalizados -->
        <slot name="action-buttons" :selectedIds="selectedIds"></slot>
      </div>

      <!-- Tabla -->
      <div class="table-responsive mt-2">
        <table :id="tableId" class="table-striped table-bordered w-100 my-2 table">
          <thead>
            <tr>
              <th class="text-center" style="width: 40px">
                <input
                  type="checkbox"
                  @change="toggleAll"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                />
              </th>
              <th v-for="column in columns" :key="column.key">
                {{ column.label }}
              </th>
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
                <!-- Si hay un slot personalizado para la columna -->
                <slot
                  :name="`cell-${column.key}`"
                  :row="row"
                  :value="getNestedValue(row, column.key)"
                >
                  <!-- Renderizado por defecto -->
                  <span v-html="formatCell(row, column)"></span>
                </slot>
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="filteredRows.length === 0">
              <td :colspan="columns.length + 1" class="text-muted py-4 text-center">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">{{ emptyMessage }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Información de resultados -->
      <div
        class="d-flex justify-content-between align-items-center mt-2"
        v-if="filteredRows.length > 0"
      >
        <small class="text-muted">
          Mostrando {{ filteredRows.length }} de {{ rows.length }} registros
          <span v-if="selectedIds.length > 0">({{ selectedIds.length }} seleccionados)</span>
        </small>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref, computed } from 'vue'
  import TableFilter from './TableFilter.vue'

  interface FilterOption {
    label: string
    value: string // importante que sea solo string si el componente TableFilter espera value:string
  }

  interface Filter {
    key: string
    label: string
    options: FilterOption[]
  }

  const props = defineProps<{
    tableId: string
    title: string
    columns: Array<{ key: string; label: string; sortable?: boolean; html?: boolean }>
    rows: Array<Record<string, any>>
    filters?: Filter[]
    searchPlaceholder?: string
    debounceDelay?: number
    emptyMessage?: string
    showCreateButton?: boolean
    showEditButton?: boolean
    showDeleteButton?: boolean
  }>()

  // Valores por defecto para props opcionales
  const searchPlaceholder = props.searchPlaceholder ?? 'Buscar en la tabla...'
  const debounceDelay = props.debounceDelay ?? 300
  const emptyMessage = props.emptyMessage ?? 'No se encontraron registros'
  const showCreateButton = props.showCreateButton ?? true
  const showEditButton = props.showEditButton ?? true
  const showDeleteButton = props.showDeleteButton ?? true

  const emit = defineEmits(['create', 'edit', 'delete', 'selection-change'])

  // Estado reactivo
  const selectedIds = ref([])
  const currentFilters = ref({
    search: '',
    filters: {}
  })

  // Computed
  const filteredRows = computed(() => {
    let result = [...props.rows]

    // Aplicar búsqueda
    if (currentFilters.value.search) {
      const searchLower = currentFilters.value.search.toLowerCase()
      result = result.filter(row => {
        return props.columns.some(column => {
          const value = getNestedValue(row, column.key)
          return String(value).toLowerCase().includes(searchLower)
        })
      })
    }

    // Aplicar filtros
    Object.entries(currentFilters.value.filters).forEach(([key, value]) => {
      if (value) {
        result = result.filter(row => {
          const rowValue = getNestedValue(row, key)
          return String(rowValue) === String(value)
        })
      }
    })

    return result
  })

  const allSelected = computed(() => {
    return filteredRows.value.length > 0 && selectedIds.value.length === filteredRows.value.length
  })

  const someSelected = computed(() => {
    return selectedIds.value.length > 0 && selectedIds.value.length < filteredRows.value.length
  })

  // Métodos
  const handleFilter = filterData => {
    currentFilters.value = filterData
    // Limpiar selección si los resultados cambian
    selectedIds.value = selectedIds.value.filter(id =>
      filteredRows.value.some(row => row.id === id)
    )
    emitSelectionChange()
  }

  const toggleRow = id => {
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    } else {
      selectedIds.value.push(id)
    }
    emitSelectionChange()
  }

  const toggleAll = () => {
    if (allSelected.value) {
      selectedIds.value = []
    } else {
      selectedIds.value = filteredRows.value.map(row => row.id)
    }
    emitSelectionChange()
  }

  const isSelected = id => {
    return selectedIds.value.includes(id)
  }

  const emitSelectionChange = () => {
    emit('selection-change', selectedIds.value)
  }

  // Helper para obtener valores anidados (ej: 'user.name')
  const getNestedValue = (obj, path) => {
    return path.split('.').reduce((current, key) => current?.[key], obj) ?? ''
  }

  // Formatear celda
  const formatCell = (row, column) => {
    const value = getNestedValue(row, column.key)

    // Si la columna permite HTML, retornar directamente
    if (column.html) {
      return value
    }

    // Escapar HTML por defecto
    return escapeHtml(value)
  }

  const escapeHtml = text => {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }
    return String(text).replace(/[&<>"']/g, m => map[m])
  }
</script>

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
