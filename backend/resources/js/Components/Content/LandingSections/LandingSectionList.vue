<script setup lang="ts">
import { ref, watch } from 'vue'
import draggable from 'vuedraggable'
import LandingSectionRow from './LandingSectionRow.vue'
import type { LandingSectionAdmin, LandingSectionKey } from '@js/types/content/landing-section'

const props = defineProps<{
  sections: LandingSectionAdmin[]
  savingKey: LandingSectionKey | null
  reordering: boolean
}>()

const emit = defineEmits<{
  toggle: [section: LandingSectionAdmin]
  edit: [section: LandingSectionAdmin]
  reorder: [orderedKeys: LandingSectionKey[]]
}>()

const localSections = ref<LandingSectionAdmin[]>([...props.sections])

watch(
  () => props.sections,
  (sections) => {
    localSections.value = [...sections]
  }
)

function onDragEnd() {
  emit('reorder', localSections.value.map((s) => s.section_key))
}
</script>

<template>
  <draggable
    v-model="localSections"
    item-key="section_key"
    handle=".drag-handle"
    :disabled="reordering"
    @end="onDragEnd"
  >
    <template #item="{ element }: { element: LandingSectionAdmin }">
      <LandingSectionRow
        :section="element"
        :saving="savingKey === element.section_key"
        @toggle="emit('toggle', $event)"
        @edit="emit('edit', $event)"
      />
    </template>
  </draggable>
</template>
