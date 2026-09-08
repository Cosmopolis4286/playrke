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
      /** IDs de filas cuyo checkbox debe deshabilitarse (ej. cuentas protegidas). */
      disabledRowIds?: string[]
      /** Tooltip mostrado en filas deshabilitadas. */
      disabledRowReason?: string
      /**
       * Alineación vertical del contenido de las celdas de datos (no afecta
       * el header). 'top' es el comportamiento previo — útil cuando la
       * mayoría de columnas tiene una sola línea de texto y una celda con
       * contenido más alto (ej. bloques de 2 líneas) no debe "tirar" del
       * resto hacia arriba de forma desalineada.
       */
      rowVerticalAlign?: 'top' | 'middle' | 'bottom'
    }>(),
    {
      selectable: false,
      selectedIds: () => [],
      allFilteredIds: null,
      hasActiveFilters: false,
      disabledRowIds: () => [],
      disabledRowReason: 'Esta fila no puede modificarse',
      rowVerticalAlign: 'top'
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

  /** Verifica si el checkbox de la fila debe estar deshabilitado */
  function isRowDisabled(id: string | number): boolean {
    return props.disabledRowIds.includes(String(id))
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

  /** Clase Bootstrap para ocultar la columna en pantallas menores al breakpoint indicado. */
  function hideBelowClass(column: Column): string | null {
    return column.hideBelow ? `d-none d-${column.hideBelow}-table-cell` : null
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
              :class="[{ 'is-sortable': column.sortable }, hideBelowClass(column)]"
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
        <tbody :class="`row-valign-${rowVerticalAlign}`">
          <tr
            v-for="row in rows"
            :key="row.id"
            :class="{ 'table-primary': selectable && isSelected(row.id), 'table-row--disabled': isRowDisabled(row.id) }"
            v-bind="selectable && !isRowDisabled(row.id) ? { onClick: () => toggleRow(row.id), style: 'cursor: pointer' } : {}"
          >
            <td v-if="selectable" class="text-center">
              <input
                type="checkbox"
                :checked="isSelected(row.id)"
                :disabled="isRowDisabled(row.id)"
                :title="isRowDisabled(row.id) ? disabledRowReason : undefined"
                :aria-label="`Seleccionar fila ${rowLabel(row)}`"
                @click.stop="toggleRow(row.id)"
              />
            </td>
            <td v-for="column in columns" :key="column.key" :class="[{ 'cell-truncate': column.truncate }, hideBelowClass(column)]">
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

  .table-row--disabled {
    opacity: 0.6;
  }

  /* Alineación vertical del contenido de celda — ver prop rowVerticalAlign */
  .row-valign-top td { vertical-align: top; }
  .row-valign-middle td { vertical-align: middle; }
  .row-valign-bottom td { vertical-align: bottom; }

  .table-row--disabled td:first-child {
    cursor: not-allowed;
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

  /* Sin max-height/overflow-y propio: cualquier ancestro con scroll interno
     recorta popovers position:absolute que se abren fuera de sus límites
     (ver .room-status-popover más abajo — debe poder salir de la tabla sin
     cortarse ni generar scrollbars). El scroll vertical pasa a ser el de la
     página; el sticky header sigue funcionando porque position:sticky no
     requiere un contenedor con overflow explícito, solo se fija respecto al
     scroll ancestro más cercano (aquí, la página). overflow-x se mantiene
     para permitir scroll horizontal en pantallas angostas con muchas columnas. */
  .table-responsive {
    overflow-x: auto;
    overflow-y: visible;
  }

  thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: var(--ds-surface);
  }

  .cell-truncate {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .select-all-banner {
    background-color: rgba(102,126,234,.12);
    border: 1px solid rgba(102,126,234,.3);
    border-radius: 0.25rem;
    padding: 0.5rem 0.85rem;
    font-size: 0.85rem;
    text-align: center;
    margin-top: 0.5rem;
    color: var(--ds-text);
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
    color: var(--ds-text-muted);
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
    color: var(--ds-text-muted);
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
    color: var(--ds-text-muted);
    font-weight: 500;
    cursor: help;
  }

  /* Total aproximado en USD cuando la sala maneja 2+ monedas */
  :deep(.room-prize-cell__total) {
    margin-top: 2px;
    padding-top: 3px;
    border-top: 1px dashed var(--ds-border);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--ds-text-muted);
    font-variant-numeric: tabular-nums;
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

  :deep(.room-status-chip--offline)   { background: var(--ds-surface-2); color: var(--ds-text-muted); }
  :deep(.room-status-chip--finished)  { background: rgba(13,110,253,.14); color: #4c8dff; }
  :deep(.room-status-chip--live)      { background: rgba(21,128,61,.16); color: #34d399; }
  :deep(.room-status-chip--warning)   { background: rgba(180,83,9,.16);  color: #f59e0b; }
  :deep(.room-status-chip--cancelled) { background: rgba(185,28,28,.16); color: #ef4444; }

  /* Wrapper del chip de estado + ícono de info (columna "Estado") */
  :deep(.room-status-chip-wrap) {
    display: flex;
    align-items: center;
    gap: 0.35rem;
  }

  /* Ícono de info: abre un popover CSS-only (:hover/:focus) con fecha de
     creación/actualización y último editor — ver .room-status-popover abajo. */
  :deep(.room-status-chip__info) {
    position: relative;
    display: inline-flex;
    color: var(--ds-text-muted);
    font-size: 0.72rem;
    cursor: help;
    outline: none;
  }

  :deep(.room-status-chip__info:hover),
  :deep(.room-status-chip__info:focus-visible) {
    color: var(--ds-text);
  }

  /* Panel del popover: oculto por defecto, revelado con hover/focus del ícono padre.
     Se abre hacia ABAJO del ícono (no hacia arriba) para no quedar tapado por la
     barra de acciones de la tabla cuando la fila está cerca del borde superior.
     .table-responsive ya no tiene overflow-y/max-height propio (ver arriba), así
     que el popover puede salir libremente de los límites de la tabla sin
     recortarse ni generar scrollbars falsos. z-index alto para quedar por encima
     de modales/headers sticky de otras secciones de la página. */
  :deep(.room-status-popover) {
    display: none;
    opacity: 0;
    position: absolute;
    z-index: 1080;
    top: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%) translateY(-4px);
    min-width: 220px;
    padding: 0.6rem 0.75rem;
    border-radius: 0.5rem;
    background: var(--ds-surface);
    border: 1px solid var(--ds-border);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
    transition: opacity 0.12s ease, transform 0.12s ease;
    cursor: default;
    white-space: normal;
  }

  :deep(.room-status-chip__info:hover .room-status-popover),
  :deep(.room-status-chip__info:focus-visible .room-status-popover) {
    display: block;
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }

  /* Flecha apuntando hacia arriba, hacia el ícono */
  :deep(.room-status-popover)::after {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-bottom-color: var(--ds-surface);
  }

  :deep(.room-status-popover__row) {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.72rem;
    padding: 0.15rem 0;
    white-space: nowrap;
  }

  :deep(.room-status-popover__row + .room-status-popover__row) {
    border-top: 1px solid var(--ds-border);
  }

  :deep(.room-status-popover__label) {
    color: var(--ds-text-muted);
    font-weight: 500;
  }

  :deep(.room-status-popover__value) {
    color: var(--ds-text);
    font-weight: 600;
    text-align: right;
  }

  /* Moneda(s): pills discretas (metadata de contexto, no el dato principal de la columna) */
  :deep(.room-currency-pills) {
    display: flex;
    gap: 0.3rem;
    flex-wrap: wrap;
  }

  :deep(.room-currency-pill) {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.16rem 0.42rem;
    border-radius: 4px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    letter-spacing: 0.02em;
  }

  /* Cartones vendidos en esa moneda — dato numérico, contraste más marcado que el iso_code */
  :deep(.room-currency-pill__count) {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    color: var(--ds-text);
    background: var(--ds-surface);
    border-radius: 3px;
    padding: 0 0.3rem;
  }

  /* Comercializadora dueña de la sala — solo visible para admin/superadmin (ver
     BingoRoomContextService::resolveSummaryGameRooms), mismo estilo pill discreta
     que .room-currency-pill para consistencia visual entre columnas de metadata. */
  :deep(.room-commerce-pill) {
    display: inline-flex;
    align-items: center;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.16rem 0.42rem;
    border-radius: 4px;
    background: var(--ds-surface-2);
    color: var(--ds-text-muted);
    letter-spacing: 0.02em;
  }

  :deep(.room-commerce-pill--unknown) {
    opacity: 0.6;
  }

  /* Sala de sistema (creador es admin/superadmin, sin comercio asociado) —
     distinta de --unknown: aquí SÍ se sabe quién la creó, solo que no
     pertenece a la jerarquía comercial. */
  :deep(.room-commerce-pill--admin) {
    background: rgba(13, 110, 253, 0.14);
    color: #4c8dff;
  }

  /* Cartones vendidos + cuota mínima, apilados en vez de concatenados en texto */
  :deep(.room-cards-cell) {
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  :deep(.room-cards-cell__count) {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    font-size: 0.82rem;
  }

  :deep(.room-cards-cell__count) i {
    font-size: 0.7rem;
    color: var(--ds-text-muted);
  }

  :deep(.room-cards-cell__count--zero) {
    color: var(--ds-text-muted);
    font-weight: 400;
  }

  :deep(.room-cards-cell__count--zero) i {
    color: var(--ds-text-muted);
  }

  :deep(.room-cards-cell__quota) {
    font-size: 0.7rem;
    color: var(--ds-text-muted);
  }

  :deep(.room-cards-cell__quota--met) {
    color: #15803d;
  }

  /* Fila número + cuota, encima de la barra de progreso */
  :deep(.room-cards-cell__row) {
    display: flex;
    align-items: baseline;
    gap: 3px;
  }

  /* Cartones personalizados vendidos, dentro de la fila de conteo */
  :deep(.room-cards-cell__custom) {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--ds-text-muted);
    background: rgba(123,79,255,.14);
    border-radius: 4px;
    padding: 0.05rem 0.35rem;
    margin-left: 0.25rem;
  }

  :deep(.room-cards-cell__custom) i {
    font-size: 0.6rem;
    color: var(--ds-text-muted);
  }

  /* Barra de progreso hacia la cuota mínima de cartones */
  :deep(.room-cards-cell__bar) {
    width: 100%;
    min-width: 60px;
    height: 4px;
    border-radius: 999px;
    background: var(--ds-surface-2);
    overflow: hidden;
  }

  :deep(.room-cards-cell__bar-fill) {
    height: 100%;
    border-radius: 999px;
    background: #0d6efd;
    transition: width 0.2s ease;
  }

  :deep(.room-cards-cell__bar-fill--met) {
    background: #15803d;
  }

  /* Jugadores + ganadores, diferenciados por icono */
  :deep(.room-players-cell) {
    display: flex;
    gap: 0.6rem;
  }

  :deep(.room-players-cell__stat) {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-variant-numeric: tabular-nums;
    font-size: 0.82rem;
  }

  :deep(.room-players-cell__stat) i {
    font-size: 0.7rem;
    color: var(--ds-text-muted);
  }

  :deep(.room-players-cell__stat--zero) {
    color: var(--ds-text-muted);
  }

  :deep(.room-players-cell__stat--zero) i {
    color: var(--ds-text-muted);
  }

  /* Cupo máximo de jugadores (max_players) — mismo criterio visual que
     room-cards-cell__quota, en rojo al llegar al límite en vez de verde
     (llenarse no es "meta alcanzada", es "ya no se pueden vender más cartones"). */
  :deep(.room-players-cell__quota) {
    font-size: 0.7rem;
    color: var(--ds-text-muted);
    align-self: center;
  }

  :deep(.room-players-cell__quota--full) {
    color: #dc2626;
    font-weight: 600;
  }

  :deep(.room-players-cell__stat--full) {
    color: #dc2626;
  }

  :deep(.room-players-cell__stat--full) i {
    color: #dc2626;
  }
</style>
