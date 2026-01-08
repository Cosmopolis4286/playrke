<template>
    <TableWithFilter :table-id="tableId" :title="title" :columns="parsedColumns" :rows="parsedRows"
        :filters="parsedFilters" :search-placeholder="searchPlaceholder" :debounce-delay="debounceDelay"
        :empty-message="emptyMessage" :show-create-button="showCreateButton" :show-edit-button="showEditButton"
        :show-delete-button="showDeleteButton" @create="handleCreate" @edit="handleEdit" @delete="handleDelete"
        @selection-change="handleSelectionChange">
        <!-- Reenvío de todos los slots recibidos -->
        <template v-for="(_, name) in $slots" #[name]="slotData" :key="name">
            <slot :name="name" v-bind="slotData" />
        </template>

        <!-- Botones adicionales a la derecha del eliminar -->
        <template #action-buttons="{ selectedIds }">
            <button v-for="(btn, i) in visibleExtraButtons" :key="i"
                :class="['btn', 'btn-sm', btn.class || 'btn-secondary', 'ml-2']"
                :disabled="btn.disabled ? btn.disabled(selectedIds) : false" @click="() => btn.onClick(selectedIds)"
                type="button">
                <i v-if="btn.icon" :class="btn.icon"></i>
                {{ btn.label }}
            </button>
        </template>
    </TableWithFilter>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import TableWithFilter from './TableWithFilter.vue'

/**
 * Define la estructura de un botón extra que se muestra
 * junto a los botones estándar en la tabla.
 */
interface ExtraButton {
    /** Texto visible en el botón */
    label: string

    /** Clases de ícono FontAwesome u otra librería */
    icon?: string

    /** Clases CSS para el botón (ej: 'btn-info', 'btn-danger') */
    class?: string

    /**
     * Controla si el botón se muestra.
     * Puede ser booleano o función que recibe los IDs seleccionados.
     */
    show?: boolean | ((selectedIds: string[]) => boolean)

    /**
     * Determina si el botón está deshabilitado.
     * Recibe los IDs seleccionados, retorna true para deshabilitar.
     */
    disabled?: (selectedIds: string[]) => boolean

    /**
     * Función que se ejecuta al hacer click en el botón.
     * Recibe los IDs seleccionados.
     */
    onClick: (selectedIds: string[]) => void
}

/**
 * Props recibidas desde Blade o sistema legacy.
 */
const props = defineProps({
    tableId: { type: String, required: true },
    title: { type: String, required: true },
    columns: { type: [Array, String], required: true },
    rows: { type: [Array, String], required: true },
    filters: { type: [Array, String], default: () => [] },
    searchPlaceholder: { type: String, default: 'Buscar en la tabla...' },
    debounceDelay: { type: Number, default: 300 },
    emptyMessage: { type: String, default: 'No se encontraron registros' },
    showCreateButton: { type: Boolean, default: true },
    showEditButton: { type: Boolean, default: true },
    showDeleteButton: { type: Boolean, default: true },
    moduleId: { type: String, default: '' },
    context: { type: String, default: '' },

    /** Botones adicionales a mostrar al lado de acciones estándar */
    extraButtons: {
        type: Array as () => ExtraButton[],
        default: () => []
    }
})

/** Estado reactivo para los IDs seleccionados */
const selectedIds = ref<string[]>([])

/** Computed para parsear columnas, filas y filtros si vienen como JSON string */
const parsedColumns = computed(() =>
    typeof props.columns === 'string' ? JSON.parse(props.columns) : props.columns
)
const parsedRows = computed(() =>
    typeof props.rows === 'string' ? JSON.parse(props.rows) : props.rows
)
const parsedFilters = computed(() =>
    typeof props.filters === 'string' ? JSON.parse(props.filters) : props.filters
)

/**
 * Computed que devuelve solo los botones que deben mostrarse,
 * evaluando la propiedad `show` anticipadamente.
 * Si `show` es booleano false, se muestra solo si hay al menos una selección.
 * Además, se asegura que `onClick` sea una función para evitar errores.
 */
const visibleExtraButtons = computed(() => {
  return props.extraButtons
    .filter(btn => {
      if (!btn) return false

      if (btn.show === undefined) {
        return true
      }

      if (typeof btn.show === 'function') {
        return btn.show(selectedIds.value)
      }

      if (btn.show === false) {
        return selectedIds.value.length > 0
      }

      return !!btn.show
    })
    .map(btn => ({
      ...btn,
      onClick: typeof btn.onClick === 'function' ? btn.onClick : () => {
        console.warn(`ExtraButton '${btn.label}' no tiene función onClick válida.`)
      }
    }))
})

/**
 * Emite evento global para crear un nuevo registro.
 */
const handleCreate = (): void => {
    window.dispatchEvent(
        new CustomEvent('table:crud', {
            detail: {
                action: 'create',
                moduleId: props.moduleId,
                tableId: props.tableId,
                context: props.context
            }
        })
    )
}

/**
 * Emite evento global para editar el registro seleccionado.
 * Solo si hay exactamente un registro seleccionado.
 */
const handleEdit = (): void => {
    if (selectedIds.value.length !== 1) return

    window.dispatchEvent(
        new CustomEvent('table:crud', {
            detail: {
                action: 'edit',
                moduleId: props.moduleId,
                tableId: props.tableId,
                encryptId: selectedIds.value[0],
                context: props.context
            }
        })
    )
}

/**
 * Emite evento global para eliminar registros seleccionados.
 * Si hay uno solo, se envía su ID, si hay varios, se envía cadena vacía.
 */
const handleDelete = (): void => {
    if (selectedIds.value.length === 0) return

    window.dispatchEvent(
        new CustomEvent('table:crud', {
            detail: {
                action: 'delete',
                moduleId: props.moduleId,
                tableId: props.tableId,
                encryptId: selectedIds.value.length === 1 ? selectedIds.value[0] : '',
                context: props.context
            }
        })
    )
}

/**
 * Actualiza el estado local cuando cambia la selección
 * y emite evento global para otros subsistemas.
 * @param ids IDs seleccionados actualmente
 */
const handleSelectionChange = (ids: string[]): void => {
    selectedIds.value = [...ids]

    if (import.meta.env.DEV) {
        console.log('🔁 table-selection-updated', {
            tableId: props.tableId,
            selectedIds: selectedIds.value
        })
    }

    window.dispatchEvent(
        new CustomEvent('table-selection-updated', {
            detail: {
                tableId: props.tableId,
                selectedIds: [...selectedIds.value],
                context: props.context
            }
        })
    )
}
</script>
