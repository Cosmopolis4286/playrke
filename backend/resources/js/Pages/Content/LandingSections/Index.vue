<script setup lang="ts">
import { ref } from 'vue'
import PageHeader from '@js/Components/Common/PageHeader/PageHeader.vue'
import LandingSectionList from '@js/Components/Content/LandingSections/LandingSectionList.vue'
import LandingSectionEditDrawer from '@js/Components/Content/LandingSections/LandingSectionEditDrawer.vue'
import { useLandingSections } from '@js/composables/content/useLandingSections'
import type { LandingSectionAdmin, LandingSectionKey } from '@js/types/content/landing-section'

const props = defineProps<{
  sections: LandingSectionAdmin[]
}>()

const { sections, savingKey, reordering, updateContent, toggleStatus, persistOrder } = useLandingSections(props.sections)

const showEditDrawer = ref(false)
const editingSection = ref<LandingSectionAdmin | null>(null)

function openEditor(section: LandingSectionAdmin) {
  editingSection.value = section
  showEditDrawer.value = true
}

async function handleSave(key: LandingSectionKey, content: Record<string, unknown>) {
  await updateContent(key, content)
}
</script>

<template>
  <PageHeader
    icon="fa-window-maximize"
    title="Contenido de la Landing"
    subtitle="Activa, desactiva, reordena y edita los textos de las secciones de la página principal pública."
    :breadcrumb-items="[{ label: 'Inicio', route: 'dashboard' }, { label: 'Contenido de la Landing' }]"
  />

  <div class="card">
    <div class="card-body">
      <LandingSectionList
        :sections="sections"
        :saving-key="savingKey"
        :reordering="reordering"
        @toggle="(s) => toggleStatus(s.section_key)"
        @edit="openEditor"
        @reorder="persistOrder"
      />
    </div>
  </div>

  <LandingSectionEditDrawer v-model="showEditDrawer" :section="editingSection" @save="handleSave" />
</template>
