<template>
    <TableWithFilter :title="title" :columns="parsedColumns" :rows="parsedRows" :filters="parsedFilters"
        :search-placeholder="searchPlaceholder" :debounce-delay="debounceDelay" :empty-message="emptyMessage"
        :show-create-button="showCreateButton" :show-edit-button="showEditButton" :show-delete-button="showDeleteButton"
        @create="handleCreate" @edit="handleEdit" @delete="handleDelete" @selection-change="handleSelectionChange">
        <!-- Forward all slots -->
        <template v-for="(_, name) in $slots" #[name]="slotData">
            <slot :name="name" v-bind="slotData" />
        </template>
    </TableWithFilter>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import TableWithFilter from './TableWithFilter.vue'

const props = defineProps({
    // Datos serializados desde Blade
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
    // Callbacks de integración con Blade/Alpine
    moduleId: {
        type: String,
        default: ''
    },
    context: {
        type: String,
        default: ''
    }
})

// Parse JSON strings if needed
const parsedColumns = computed(() => {
    return typeof props.columns === 'string'
        ? JSON.parse(props.columns)
        : props.columns
})

const parsedRows = computed(() => {
    return typeof props.rows === 'string'
        ? JSON.parse(props.rows)
        : props.rows
})

const parsedFilters = computed(() => {
    return typeof props.filters === 'string'
        ? JSON.parse(props.filters)
        : props.filters
})

// Handlers que se integran con el sistema existente
const handleCreate = () => {
    console.log('dispatch create event');
    window.dispatchEvent(
        new CustomEvent('table:crud', {
            detail: {
                action: 'create',
                moduleId: props.moduleId,
                context: props.context
            }
        })
    )
}

const handleEdit = (id: string) => {
    window.dispatchEvent(new CustomEvent('table:crud', {
        detail: {
            action: 'edit',
            id,
            moduleId: props.moduleId,
            context: props.context
        }
    }))
}

const handleDelete = (ids: Array<string>) => {
    window.dispatchEvent(new CustomEvent('table:delete', {
        detail: { ids, moduleId: props.moduleId, context: props.context }
    }))
}

const handleSelectionChange = (selectedIds: Array<string>) => {
    window.dispatchEvent(new CustomEvent('table:selection-change', {
        detail: { selectedIds, moduleId: props.moduleId, context: props.context }
    }))
}
</script>
