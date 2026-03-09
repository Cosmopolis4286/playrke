<script setup lang="ts" name="TableActionButtons">
  /**
   * @deprecated Usa Common/Button/ActionButtons.vue directamente.
   * Este wrapper se mantiene para compatibilidad con los consumidores actuales.
   */
  import ActionButtons from '@/js/Components/Common/Button/ActionButtons.vue'
  import type { PropType } from 'vue'

  defineProps({
    selectedIds:         { type: Array as PropType<string[]>, required: true },
    showCreateButton:    { type: Boolean, default: true },
    showEditButton:      { type: Boolean, default: true },
    showDeleteButton:    { type: Boolean, default: true },
    disableDelete:       { type: Boolean, default: false },
    disableDeleteReason: { type: String,  default: '' }
  })

  const emit = defineEmits<{
    (e: 'create'): void
    (e: 'edit', id: string): void
    (e: 'delete', ids: string[]): void
  }>()
</script>

<template>
  <ActionButtons
    :selected-ids="selectedIds"
    :show-create="showCreateButton"
    :show-edit="showEditButton"
    :show-delete="showDeleteButton"
    :disable-delete="disableDelete"
    :disable-delete-reason="disableDeleteReason"
    @create="emit('create')"
    @edit="(id) => emit('edit', id)"
    @delete="(ids) => emit('delete', ids)"
  >
    <template #actions="slotProps">
      <slot name="action-buttons" :selectedIds="slotProps.selectedIds" />
    </template>
  </ActionButtons>
</template>
