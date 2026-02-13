<script setup lang="ts" name="TableActionButtons">
  import type { PropType } from 'vue'

  /**
   * Props del componente TableActionButtons
   */
  const props = defineProps({
    /**
     * IDs seleccionados en la tabla
     */
    selectedIds: {
      type: Array as PropType<string[]>,
      required: true
    },

    /**
     * Muestra el botón Crear
     */
    showCreateButton: {
      type: Boolean,
      default: true
    },

    /**
     * Muestra el botón Editar
     */
    showEditButton: {
      type: Boolean,
      default: true
    },

    /**
     * Muestra el botón Eliminar
     */
    showDeleteButton: {
      type: Boolean,
      default: true
    }
  })

  /**
   * Eventos emitidos por el componente
   */
  const emit = defineEmits<{
    /**
     * Evento para crear un nuevo registro
     */
    (e: 'create'): void

    /**
     * Evento para editar un registro
     * @param id ID seleccionado
     */
    (e: 'edit', id: string): void

    /**
     * Evento para eliminar uno o varios registros
     * @param ids IDs seleccionados
     */
    (e: 'delete', ids: string[]): void
  }>()
</script>

<template>
  <div
    v-if="selectedIds.length || showCreateButton"
    class="table-action-buttons"
  >
    <!-- Crear -->
    <button v-if="showCreateButton" class="btn btn-sm btn-success" @click="emit('create')">
      <i class="fas fa-plus"></i> Registar
    </button>

    <!-- Editar (solo si hay un registro seleccionado) -->
    <button
      v-if="selectedIds.length === 1 && showEditButton"
      class="btn btn-sm btn-primary"
      @click="emit('edit', selectedIds[0])"
    >
      <i class="fas fa-edit"></i>
    </button>

    <!-- Eliminar (uno o varios) -->
    <button
      v-if="selectedIds.length > 0 && showDeleteButton"
      class="btn btn-sm btn-danger"
      @click="emit('delete', selectedIds)"
    >
      <i class="fas fa-trash"></i>
      <span v-if="selectedIds.length > 1"> ({{ selectedIds.length }}) </span>
    </button>

    <!-- Slot para acciones personalizadas -->
    <slot name="action-buttons" :selectedIds="selectedIds" />
  </div>
</template>

<style scoped>
  .table-action-buttons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.375rem;
  }
</style>
