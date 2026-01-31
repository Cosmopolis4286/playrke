<script setup lang="ts">
/**
 * Componente de tabla para mostrar directorios de Pago Móvil (Venezuela).
 *
 * Responsabilidades:
 * - Renderizar lista de directorios
 * - Emitir eventos de edición y eliminación
 * - Formatear datos para visualización
 */
import { computed } from 'vue'
import BaseTable from '../Common/Table/BaseTable.vue'
import type { Directory } from '@/js/types/directory'

/**
 * Props del componente DirectoryTable
 */
const props = defineProps<{
  /** Lista de directorios a mostrar */
  directories: Directory[]
}>()

/**
 * Eventos emitidos por el componente
 */
const emit = defineEmits<{
  (e: 'edit', id: number | string): void
  (e: 'delete', id: number | string): void
}>()

/**
 * Columnas de la tabla
 */
const columns = [
  { key: 'document_info', label: 'Documento', sortable: true },
  { key: 'phone', label: 'Teléfono', sortable: true },
  { key: 'bank', label: 'Banco', sortable: true },
  { key: 'actions', label: 'Acciones', sortable: false }
]

/**
 * Filas transformadas para la tabla
 */
const rows = computed(() => {
  return props.directories.map(directory => ({
    id: directory.id,
    document_info: `${directory.document_type?.abbreviation || ''}-${directory.document}`,
    phone: directory.phone,
    bank: directory.bank?.name || 'N/A',
    _raw: directory
  }))
})

/**
 * Maneja la edición de un directorio
 */
function handleEdit(id: number | string) {
  emit('edit', id)
}

/**
 * Maneja la eliminación de un directorio
 */
function handleDelete(id: number | string) {
  emit('delete', id)
}
</script>

<template>
  <BaseTable
    table-id="directory-table"
    :columns="columns"
    :rows="rows"
    empty-message="No hay directorios registrados"
  >
    <template #cell-actions="{ row }">
      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn btn-primary"
          @click="handleEdit(row.id)"
          title="Editar"
        >
          <i class="fas fa-edit"></i>
        </button>
        <button
          type="button"
          class="btn btn-danger"
          @click="handleDelete(row.id)"
          title="Eliminar"
        >
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </template>
  </BaseTable>
</template>
