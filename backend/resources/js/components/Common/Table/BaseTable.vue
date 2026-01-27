<script setup lang="ts" name="BaseTable">
  import { computed } from 'vue'
  import type { Column } from '@/js/types/common/table/column-row'

  /** Props recibidas */
  const props = defineProps<{
    tableId: string
    columns: Column[]
    rows: Array<Record<string, any>>
    emptyMessage?: string
    selectedIds: string[]
  }>()

  /** Emit eventos */
  const emit = defineEmits<{
    (e: 'selection-change', ids: string[]): void
  }>()

  /** Computed para verificar selección total */
  const allSelected = computed(
    () => props.rows.length > 0 && props.selectedIds.length === props.rows.length
  )

  /** Computed para verificar selección parcial */
  const someSelected = computed(
    () => props.selectedIds.length > 0 && props.selectedIds.length < props.rows.length
  )

  /** Alterna selección total */
  function toggleAll(): void {
    const allIds = props.rows.map(r => r.id)
    const newSelection = allSelected.value ? [] : allIds
    emit('selection-change', newSelection)
  }

  /** Alterna selección individual */
  function toggleRow(id: string): void {
    const idx = props.selectedIds.indexOf(id)
    let newSelection: string[]
    if (idx >= 0) {
      newSelection = props.selectedIds.filter(i => i !== id)
    } else {
      newSelection = [...props.selectedIds, id]
    }
    emit('selection-change', newSelection)
  }

  /** Verifica si fila está seleccionada */
  function isSelected(id: string): boolean {
    return props.selectedIds.includes(id)
  }

  /** Obtiene valor seguro en objeto */
  function getNestedValue(obj: Record<string, any>, key: string): any {
    return obj?.[key] ?? ''
  }

  /**
   * Escapa texto para evitar inyección HTML
   * @param text Texto crudo
   * @returns Texto seguro para HTML
   */
  function escapeHtml(text: string): string {
    const map: Record<string, string> = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }
    return String(text).replace(/[&<>"']/g, m => map[m])
  }

  /**
   * Formatea contenido de celda según configuración HTML
   * @param row Fila de datos
   * @param column Columna actual
   * @returns Contenido seguro o con HTML
   */
  function formatCell(row: Record<string, any>, column: Column): string {
    const value = getNestedValue(row, column.key)
    return column.html ? value : escapeHtml(value)
  }
</script>

<template>
  <div class="table-responsive mt-2">
    <table :id="tableId" class="table-striped table-bordered w-100 my-2 table">
      <thead>
        <tr>
          <th class="text-center" style="width: 40px">
            <input
              type="checkbox"
              :checked="allSelected"
              :indeterminate.prop="someSelected"
              @change="toggleAll"
            />
          </th>
          <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in rows"
          :key="row.id"
          :class="{ 'table-primary': isSelected(row.id) }"
          @click="toggleRow(row.id)"
          style="cursor: pointer"
        >
          <td class="text-center">
            <input type="checkbox" :checked="isSelected(row.id)" @click.stop="toggleRow(row.id)" />
          </td>
          <td v-for="column in columns" :key="column.key">
            <slot :name="`cell-${column.key}`" :row="row" :value="getNestedValue(row, column.key)">
              <span v-html="formatCell(row, column)"></span>
            </slot>
          </td>
        </tr>
        <tr v-if="rows.length === 0">
          <td :colspan="columns.length + 1" class="text-muted py-4 text-center">
            <i class="fas fa-inbox fa-2x mb-2"></i>
            <p class="mb-0">{{ emptyMessage ?? 'No hay datos para mostrar' }}</p>
          </td>
        </tr>
      </tbody>
    </table>
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
</style>
