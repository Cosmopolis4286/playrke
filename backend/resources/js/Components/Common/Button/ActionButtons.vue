<script setup lang="ts" name="ActionButtons">
  import { type PropType } from 'vue'
  import { vTooltip } from 'floating-vue'

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

    /** Deshabilita el botón Editar */
    disableEdit: {
      type: Boolean,
      default: false
    },

    /** Razón por la que el botón Editar está deshabilitado */
    disableEditReason: {
      type: String,
      default: ''
    },

    /** Deshabilita el botón Eliminar */
    disableDelete: {
      type: Boolean,
      default: false
    },

    /** Razón por la que el botón Eliminar está deshabilitado */
    disableDeleteReason: {
      type: String,
      default: ''
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
    },

    /**
     * true si el slot #actions va a renderizar al menos un botón real.
     * Necesario como prop explícita (no inferible desde `useSlots()`):
     * intermediarios como TableActionButtons.vue siempre DECLARAN
     * `<template #actions>` para reenviar su propio slot #action-buttons,
     * así que la función del slot existe aunque el consumidor final no haya
     * puesto ningún botón ahí — inspeccionar slots.actions() daría un falso
     * positivo constante. El separador antes de este slot depende de esta
     * prop, no de la mera presencia del slot.
     */
    hasExtraActions: {
      type: Boolean,
      default: false
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
  <div class="action-buttons">
    <!-- Crear: acción primaria, siempre visible sin depender de selección -->
    <button
      v-if="showCreate"
      :class="['btn', 'btn-success', btnSize()]"
      @click="emit('create')"
    >
      <i class="fas fa-plus me-1" />
      {{ createLabel }}
    </button>

    <!-- Separador + contador: solo aparecen cuando hay algo seleccionado,
         para distinguir visualmente "crear" de las acciones sobre la selección -->
    <template v-if="hasSelection()">
      <span class="action-buttons__divider" aria-hidden="true" />
      <span class="action-buttons__count">
        {{ selectedIds.length }} seleccionado{{ selectedIds.length > 1 ? 's' : '' }}
      </span>
    </template>

    <!-- Editar (solo con 1 seleccionado) -->
    <button
      v-if="hasSingleSelect() && showEdit"
      v-tooltip="disableEdit && disableEditReason ? disableEditReason : 'Editar'"
      :class="['btn', 'btn-primary', btnSize()]"
      :disabled="disableEdit"
      @click="emit('edit', selectedIds[0])"
    >
      <i class="fas fa-edit" />
    </button>

    <!-- Cambiar estado -->
    <button
      v-if="hasSelection() && showToggleStatus"
      v-tooltip="selectedIds.length > 1 ? `Cambiar estado (${selectedIds.length})` : 'Cambiar estado'"
      :class="['btn', 'btn-warning', btnSize()]"
      @click="emit('toggle-status', selectedIds)"
    >
      <i class="fas fa-toggle-on" />
      <span v-if="selectedIds.length > 1" class="ms-1">({{ selectedIds.length }})</span>
    </button>

    <!-- Eliminar -->
    <button
      v-if="hasSelection() && showDelete"
      v-tooltip="disableDelete && disableDeleteReason ? disableDeleteReason : (selectedIds.length > 1 ? `Eliminar (${selectedIds.length})` : 'Eliminar')"
      :class="['btn', 'btn-danger', btnSize()]"
      :disabled="disableDelete"
      @click="emit('delete', selectedIds)"
    >
      <i class="fas fa-trash" />
      <span v-if="selectedIds.length > 1" class="ms-1">({{ selectedIds.length }})</span>
    </button>

    <!-- Separador antes de las acciones personalizadas (ej. "Exportar") —
         visible siempre que el slot tenga contenido, no solo cuando hay
         selección (ese es el separador de arriba, entre Crear y las
         acciones CRUD sobre la fila seleccionada; son conceptos distintos). -->
    <span v-if="hasExtraActions" class="action-buttons__divider" aria-hidden="true" />

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

.action-buttons__divider {
  width: 1px;
  align-self: stretch;
  min-height: 1.5rem;
  background: var(--bs-border-color, #dee2e6);
  margin: 0 0.25rem;
}

.action-buttons__count {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--bs-secondary, #6c757d);
  white-space: nowrap;
  margin-right: 0.125rem;
}
</style>
