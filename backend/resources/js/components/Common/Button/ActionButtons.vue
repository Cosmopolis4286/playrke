<script setup lang="ts" name="ActionButtons">
  import type { PropType } from 'vue'

  const props = defineProps({
    /** IDs seleccionados */
    selectedIds: {
      type: Array as PropType<string[]>,
      default: () => []
    },

    /** Muestra el botón Crear */
    showCreate: {
      type: Boolean,
      default: true
    },

    /** Muestra el botón Editar */
    showEdit: {
      type: Boolean,
      default: true
    },

    /** Muestra el botón Eliminar */
    showDelete: {
      type: Boolean,
      default: true
    },

    /** Muestra el botón Cambiar estado */
    showToggleStatus: {
      type: Boolean,
      default: false
    },

    /** Texto del botón Crear */
    createLabel: {
      type: String,
      default: 'Registrar'
    },

    /** Tamaño de los botones (btn-sm, btn-md) */
    size: {
      type: String as PropType<'sm' | 'md'>,
      default: 'sm'
    }
  })

  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
    (e: 'toggle-status', ids: string[]): void
  }>()

  const hasSelection    = () => props.selectedIds.length > 0
  const hasSingleSelect = () => props.selectedIds.length === 1
  const btnSize         = () => props.size === 'sm' ? 'btn-sm' : ''
</script>

<template>
  <div
    v-if="hasSelection() || showCreate"
    class="action-buttons"
  >
    <!-- Crear -->
    <button
      v-if="showCreate"
      :class="['btn', 'btn-success', btnSize()]"
      @click="emit('create')"
    >
      <i class="fas fa-plus me-1" />
      {{ createLabel }}
    </button>

    <!-- Editar (solo con 1 seleccionado) -->
    <button
      v-if="hasSingleSelect() && showEdit"
      :class="['btn', 'btn-primary', btnSize()]"
      :title="`Editar`"
      @click="emit('edit', selectedIds[0])"
    >
      <i class="fas fa-edit" />
    </button>

    <!-- Cambiar estado -->
    <button
      v-if="hasSelection() && showToggleStatus"
      :class="['btn', 'btn-warning', btnSize()]"
      :title="selectedIds.length > 1 ? `Cambiar estado (${selectedIds.length})` : 'Cambiar estado'"
      @click="emit('toggle-status', selectedIds)"
    >
      <i class="fas fa-toggle-on" />
      <span v-if="selectedIds.length > 1" class="ms-1">({{ selectedIds.length }})</span>
    </button>

    <!-- Eliminar -->
    <button
      v-if="hasSelection() && showDelete"
      :class="['btn', 'btn-danger', btnSize()]"
      :title="selectedIds.length > 1 ? `Eliminar (${selectedIds.length})` : 'Eliminar'"
      @click="emit('delete', selectedIds)"
    >
      <i class="fas fa-trash" />
      <span v-if="selectedIds.length > 1" class="ms-1">({{ selectedIds.length }})</span>
    </button>

    <!-- Acciones personalizadas -->
    <slot name="actions" :selectedIds="selectedIds" />
  </div>
</template>

<style scoped>
.action-buttons {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.375rem;
}
</style>
