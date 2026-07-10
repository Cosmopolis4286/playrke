<script setup lang="ts" name="TableActionButtons">
  /**
   * Adaptador de ActionButtons para el módulo de tablas: expone los nombres
   * de prop/slot que usan TableWithFilter y sus consumidores directos
   * (showCreateButton/showEditButton/showDeleteButton, slot #action-buttons)
   * en vez de los nombres cortos de ActionButtons (showCreate/showEdit/showDelete, slot #actions).
   */
  import ActionButtons from '@/js/Components/Common/Button/ActionButtons.vue'
  import type { PropType } from 'vue'

  defineProps({
    selectedIds:         { type: Array as PropType<string[]>, required: true },
    showCreateButton:    { type: Boolean, default: true },
    showEditButton:      { type: Boolean, default: true },
    disableEdit:         { type: Boolean, default: false },
    disableEditReason:   { type: String,  default: '' },
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
    :disable-edit="disableEdit"
    :disable-edit-reason="disableEditReason"
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
