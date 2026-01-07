<template>
    <TableWithFilter :table-id="tableId" :title="title" :columns="parsedColumns" :rows="parsedRows"
        :filters="parsedFilters" :search-placeholder="searchPlaceholder" :debounce-delay="debounceDelay"
        :empty-message="emptyMessage" :show-create-button="showCreateButton" :show-edit-button="showEditButton"
        :show-delete-button="showDeleteButton" @create="handleCreate" @edit="handleEdit" @delete="handleDelete"
        @selection-change="handleSelectionChange">
        <!-- Forward all slots -->
        <template v-for="(_, name) in $slots" #[name]="slotData">
            <slot :name="name" v-bind="slotData" />
        </template>
    </TableWithFilter>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import TableWithFilter from './TableWithFilter.vue'

/**
 * Props recibidas desde Blade / sistema legacy
 */
const props = defineProps({
    tableId: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    columns: {
        type: [Array, String],
        required: true
    },
    rows: {
        type: [Array, String],
        required: true
    },
    filters: {
        type: [Array, String],
        default: () => []
    },
    searchPlaceholder: {
        type: String,
        default: 'Buscar en la tabla...'
    },
    debounceDelay: {
        type: Number,
        default: 300
    },
    emptyMessage: {
        type: String,
        default: 'No se encontraron registros'
    },
    showCreateButton: {
        type: Boolean,
        default: true
    },
    showEditButton: {
        type: Boolean,
        default: true
    },
    showDeleteButton: {
        type: Boolean,
        default: true
    },
    moduleId: {
        type: String,
        default: ''
    },
    context: {
        type: String,
        default: ''
    }
})

/**
 * Estado local de selección
 * Vue es la fuente de verdad del ID seleccionado
 */
const selectedIds = ref<string[]>([])

/**
 * Parseo seguro de props serializadas
 */
const parsedColumns = computed(() =>
    typeof props.columns === 'string'
        ? JSON.parse(props.columns)
        : props.columns
)

const parsedRows = computed(() =>
    typeof props.rows === 'string'
        ? JSON.parse(props.rows)
        : props.rows
)

const parsedFilters = computed(() =>
    typeof props.filters === 'string'
        ? JSON.parse(props.filters)
        : props.filters
)

/**
 * Acción: Crear
 */
const handleCreate = () => {
    window.dispatchEvent(new CustomEvent('table:crud', {
        detail: {
            action: 'create',
            moduleId: props.moduleId,
            tableId: props.tableId,
            context: props.context
        }
    }))
}

/**
 * Acción: Editar
 * ⚠️ El ID se resuelve AQUÍ, no en el CRUD
 */
const handleEdit = () => {
    if (selectedIds.value.length !== 1) return

    window.dispatchEvent(new CustomEvent('table:crud', {
        detail: {
            action: 'edit',
            moduleId: props.moduleId,
            tableId: props.tableId,
            encryptId: selectedIds.value[0],
            context: props.context
        }
    }))
}

/**
 * Acción: Eliminar
 */
const handleDelete = () => {
    if (selectedIds.value.length === 0) return

    window.dispatchEvent(new CustomEvent('table:crud', {
        detail: {
            action: 'delete',
            moduleId: props.moduleId,
            tableId: props.tableId,
            encryptId: selectedIds.value.length === 1
                ? selectedIds.value[0]
                : '',
            context: props.context
        }
    }))
}

/**
 * Cambio de selección desde la tabla
 * Evento informativo para Alpine / otros módulos
 */
const handleSelectionChange = (ids: string[]) => {
    selectedIds.value = [...ids]

    if (import.meta.env.DEV) {
        console.log('🔁 table-selection-updated', {
            tableId: props.tableId,
            selectedIds: selectedIds.value
        })
    }

    window.dispatchEvent(new CustomEvent('table-selection-updated', {
        detail: {
            tableId: props.tableId,
            selectedIds: [...selectedIds.value],
            context: props.context
        }
    }))
}
</script>
