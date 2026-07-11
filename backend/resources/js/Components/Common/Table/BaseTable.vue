<script setup lang="ts" name="BaseTable">
  import { computed } from 'vue'
  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { TableSortState } from '@/js/composables/common/table/useTableFiltering'

  /** Props recibidas */
  const props = withDefaults(
    defineProps<{
      tableId: string
      columns: Column[]
      rows: Row[]
      emptyMessage?: string
      selectable?: boolean
      selectedIds?: string[]
      sort?: TableSortState
      /**
       * IDs de todos los resultados filtrados (no solo la página visible).
       * Cuando está presente y hay más resultados que filas en la página,
       * el checkbox de cabecera ofrece expandir la selección a "todos los
       * N resultados" en vez de limitarse a la página actual.
       */
      allFilteredIds?: string[] | null
      /** true si el usuario tiene búsqueda/filtros activos (para diferenciar el mensaje de tabla vacía). */
      hasActiveFilters?: boolean
    }>(),
    {
      selectable: false,
      selectedIds: () => [],
      allFilteredIds: null,
      hasActiveFilters: false
    }
  )

  /** Emit eventos */
  const emit = defineEmits<{
    (e: 'selection-change', ids: string[]): void
    (e: 'sort-change', key: string): void
    (e: 'clear-filters'): void
  }>()

  /** Computed para verificar selección total de la página visible */
  const allSelected = computed(
    () => props.rows.length > 0 && props.rows.every(r => props.selectedIds.includes(String(r.id)))
  )

  /** true si ya están seleccionados todos los resultados filtrados (no solo la página) */
  const allFilteredSelected = computed(() => {
    if (!props.allFilteredIds || props.allFilteredIds.length === 0) return allSelected.value
    return props.selectedIds.length === props.allFilteredIds.length
  })

  /** Computed para verificar selección parcial (de la página o del total filtrado) */
  const someSelected = computed(() => props.selectedIds.length > 0 && !allFilteredSelected.value)

  /** Hay más resultados filtrados que filas en la página actual (paginación activa) */
  const hasMoreThanPage = computed(
    () => !!props.allFilteredIds && props.allFilteredIds.length > props.rows.length
  )

  /** Alterna selección de la página visible */
  function toggleAll(): void {
    const pageIds = props.rows.map(r => String(r.id))
    const newSelection = allSelected.value ? [] : pageIds
    emit('selection-change', newSelection)
  }

  /** Expande la selección a todos los resultados filtrados */
  function selectAllFiltered(): void {
    if (props.allFilteredIds) emit('selection-change', [...props.allFilteredIds])
  }

  /** Limpia la selección por completo */
  function clearSelection(): void {
    emit('selection-change', [])
  }

  /** Alterna selección individual */
  function toggleRow(id: string | number): void {
    const key = String(id)
    const idx = props.selectedIds.indexOf(key)
    let newSelection: string[]
    if (idx >= 0) {
      newSelection = props.selectedIds.filter(i => i !== key)
    } else {
      newSelection = [...props.selectedIds, key]
    }
    emit('selection-change', newSelection)
  }

  /** Verifica si fila está seleccionada */
  function isSelected(id: string | number): boolean {
    return props.selectedIds.includes(String(id))
  }

  /** Solicita ordenar por una columna (delega el toggle asc/desc al padre) */
  function requestSort(column: Column): void {
    if (!column.sortable) return
    emit('sort-change', column.key)
  }

  /** Ícono de orden actual para una columna */
  function sortIcon(column: Column): string | null {
    if (!column.sortable || props.sort?.key !== column.key) return null
    return props.sort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
  }

  /** Estado ARIA de orden para lectores de pantalla */
  function ariaSort(column: Column): 'ascending' | 'descending' | 'none' | undefined {
    if (!column.sortable) return undefined
    if (props.sort?.key !== column.key) return 'none'
    return props.sort.direction === 'asc' ? 'ascending' : 'descending'
  }

  /** Activa el ordenamiento también desde teclado (Enter/Espacio) en el header */
  function onSortKeydown(event: KeyboardEvent, column: Column): void {
    if (event.key !== 'Enter' && event.key !== ' ') return
    event.preventDefault()
    requestSort(column)
  }

  /**
   * Label legible para el checkbox de una fila. Usa el primer campo de texto
   * disponible entre las columnas visibles como referencia (ej. "Código"),
   * con el id como respaldo si no hay ninguno utilizable.
   */
  function rowLabel(row: Record<string, any>): string {
    const firstTextColumn = props.columns.find(c => !c.html)
    const value = firstTextColumn ? getNestedValue(row, firstTextColumn.key) : null
    return value ? String(value) : String(row.id)
  }

  /** Obtiene valor seguro en objeto */
  function getNestedValue(obj: Record<string, any>, key: string): any {
    return obj?.[key] ?? ''
  }

  function formatDate(value: string): string {
    const d = new Date(value)
    if (isNaN(d.getTime())) return value

    return d.toLocaleString('es-ES', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  /** Columnas cuyo valor se formatea como fecha automáticamente */
  const DATE_COLUMN_KEYS = new Set(['created_at', 'updated_at'])

  /**
   * Formatea contenido de celda (aplica formato de fecha si corresponde)
   * @param row Fila de datos
   * @param column Columna actual
   * @returns Valor formateado, aún sin escapar
   */
  function formatCell(row: Record<string, any>, column: Column): string {
    let value = getNestedValue(row, column.key)

    if (DATE_COLUMN_KEYS.has(column.key) && typeof value === 'string') {
      value = formatDate(value)
    }

    return String(value)
  }

  /**
   * NOTA: la limpieza de selectedIds cuando una fila deja de existir vive en
   * useFilterableSelection (compara contra el dataset completo tras filtros,
   * no contra la página visible). Un watcher aquí sobre `props.rows` estaría
   * comparando contra la página actual, y borraría la selección de otras
   * páginas cada vez que el usuario simplemente navega — por eso no vive acá.
   */
</script>

<template>
  <div>
    <!-- Banner: selección expandible a todos los resultados filtrados (no solo la página) -->
    <div v-if="selectable && allSelected && hasMoreThanPage && !allFilteredSelected" class="select-all-banner">
      Los {{ rows.length }} elementos de esta página están seleccionados.
      <button type="button" class="btn btn-link btn-sm p-0 align-baseline" @click="selectAllFiltered">
        Seleccionar los {{ allFilteredIds!.length }} resultados
      </button>
    </div>
    <div v-else-if="selectable && allFilteredSelected && hasMoreThanPage" class="select-all-banner">
      Los {{ allFilteredIds!.length }} resultados están seleccionados.
      <button type="button" class="btn btn-link btn-sm p-0 align-baseline" @click="clearSelection">
        Quitar selección
      </button>
    </div>

    <div class="table-responsive mt-2">
      <table :id="tableId" class="table-striped table-bordered w-100 my-2 table">
        <thead>
          <tr>
            <th v-if="selectable" class="text-center" style="width: 40px">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate.prop="someSelected"
                aria-label="Seleccionar todas las filas de esta página"
                @change="toggleAll"
              />
            </th>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="{ 'is-sortable': column.sortable }"
              :tabindex="column.sortable ? 0 : undefined"
              :role="column.sortable ? 'button' : undefined"
              :aria-sort="ariaSort(column)"
              @click="requestSort(column)"
              @keydown="onSortKeydown($event, column)"
            >
              {{ column.label }}
              <i v-if="sortIcon(column)" :class="sortIcon(column)" class="ms-1"></i>
              <i v-else-if="column.sortable" class="fas fa-sort ms-1 text-muted"></i>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rows"
            :key="row.id"
            :class="{ 'table-primary': selectable && isSelected(row.id) }"
            v-bind="selectable ? { onClick: () => toggleRow(row.id), style: 'cursor: pointer' } : {}"
          >
            <td v-if="selectable" class="text-center">
              <input
                type="checkbox"
                :checked="isSelected(row.id)"
                :aria-label="`Seleccionar fila ${rowLabel(row)}`"
                @click.stop="toggleRow(row.id)"
              />
            </td>
            <td v-for="column in columns" :key="column.key" :class="{ 'cell-truncate': column.truncate }">
              <slot :name="`cell-${column.key}`" :row="row" :value="getNestedValue(row, column.key)">
                <span v-if="column.html" v-html="formatCell(row, column)"></span>
                <span v-else :title="column.truncate ? formatCell(row, column) : undefined">{{ formatCell(row, column) }}</span>
              </slot>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="columns.length + (selectable ? 1 : 0)" class="text-muted py-4 text-center">
              <i class="fas fa-inbox fa-2x mb-2"></i>
              <p class="mb-0">
                {{ hasActiveFilters ? 'Ningún registro coincide con los filtros aplicados.' : (emptyMessage ?? 'No hay datos para mostrar') }}
              </p>
              <button
                v-if="hasActiveFilters"
                type="button"
                class="btn btn-link btn-sm"
                @click="emit('clear-filters')"
              >
                Limpiar filtros
              </button>
            </td>
          </tr>
        </tbody>
      </table>
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

  .is-sortable {
    cursor: pointer;
    user-select: none;
  }

  .is-sortable:hover {
    background-color: rgba(0, 0, 0, 0.03);
  }

  .is-sortable:focus-visible,
  input[type='checkbox']:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 1px;
  }

  /* Sticky header: mantiene encabezados visibles al hacer scroll vertical
     dentro de .table-responsive (útil con muchas columnas/filas). */
  .table-responsive {
    max-height: 70vh;
  }

  thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: #fff;
  }

  .cell-truncate {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .select-all-banner {
    background-color: #e7f1ff;
    border: 1px solid #b6d4fe;
    border-radius: 0.25rem;
    padding: 0.5rem 0.85rem;
    font-size: 0.85rem;
    text-align: center;
    margin-top: 0.5rem;
  }

  /* Celda combinada "Código" + fecha/hora de inicio (contenido inyectado vía v-html) */
  :deep(.room-code-cell) {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  :deep(.room-code-cell__code) {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace;
    font-size: 0.85rem;
    font-weight: 600;
  }

  :deep(.room-code-cell__date) {
    font-size: 0.75rem;
    color: #8891a0;
  }

  /* Celda "Premio": una línea por moneda, alineada por código ISO */
  :deep(.room-prize-cell) {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  :deep(.room-prize-cell__line) {
    display: flex;
    align-items: baseline;
    gap: 6px;
  }

  :deep(.room-prize-cell__currency) {
    font-size: 0.7rem;
    font-weight: 700;
    color: #8891a0;
    min-width: 2.4em;
    flex: none;
  }

  :deep(.room-prize-cell__amount) {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    font-size: 0.85rem;
  }

  /* Bote en 0 con cartones ya vendidos: atenuado + marca de alerta (no cambia la cifra) */
  :deep(.room-prize-cell__amount--zero) {
    color: #b45309;
    font-weight: 500;
  }

  :deep(.room-prize-cell__amount--zero)::after {
    content: ' \26A0';
    font-size: 0.7rem;
  }

  /* Sin tasa de cambio activa para convertir el bote a esa moneda */
  :deep(.room-prize-cell__amount--na) {
    color: #b3bac4;
    font-weight: 500;
    cursor: help;
  }

  /* Estado de la sala: chip con punto de color, más discreto que un badge sólido */
  :deep(.room-status-chip) {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.6rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
  }

  :deep(.room-status-chip)::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
  }

  :deep(.room-status-chip--offline)   { background: #eef0f3; color: #8891a0; }
  :deep(.room-status-chip--finished)  { background: #e7f1ff; color: #0d6efd; }
  :deep(.room-status-chip--live)      { background: #e9f7ee; color: #15803d; }
  :deep(.room-status-chip--warning)   { background: #fef6e7; color: #b45309; }
  :deep(.room-status-chip--cancelled) { background: #fdecec; color: #b91c1c; }

  /* Moneda(s): pills discretas (metadata de contexto, no el dato principal de la columna) */
  :deep(.room-currency-pills) {
    display: flex;
    gap: 0.3rem;
    flex-wrap: wrap;
  }

  :deep(.room-currency-pill) {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.16rem 0.42rem;
    border-radius: 4px;
    background: #eef0f3;
    color: #8891a0;
    letter-spacing: 0.02em;
  }

  /* Cartones vendidos + cuota mínima, apilados en vez de concatenados en texto */
  :deep(.room-cards-cell) {
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  :deep(.room-cards-cell__count) {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
  }

  :deep(.room-cards-cell__count--zero) {
    color: #b3bac4;
    font-weight: 400;
  }

  :deep(.room-cards-cell__quota) {
    font-size: 0.7rem;
    color: #b3bac4;
  }

  :deep(.room-cards-cell__quota--met) {
    color: #15803d;
  }

  /* Jugadores: atenuar el 0 para no competir visualmente con salas con actividad real */
  :deep(.room-players-cell) {
    font-variant-numeric: tabular-nums;
  }

  :deep(.room-players-cell--zero) {
    color: #b3bac4;
  }
</style>
