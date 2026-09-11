<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'
import HeroSectionForm from './forms/HeroSectionForm.vue'
import SalasSectionForm from './forms/SalasSectionForm.vue'
import VerificarSectionForm from './forms/VerificarSectionForm.vue'
import InvitaSectionForm from './forms/InvitaSectionForm.vue'
import GanadoresSectionForm from './forms/GanadoresSectionForm.vue'
import CtaFinalSectionForm from './forms/CtaFinalSectionForm.vue'
import FooterSectionForm from './forms/FooterSectionForm.vue'
import type { LandingSectionAdmin, LandingSectionKey } from '@js/types/content/landing-section'

const props = defineProps<{
  modelValue: boolean
  section: LandingSectionAdmin | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [key: LandingSectionKey, content: Record<string, unknown>]
}>()

const FORM_COMPONENTS: Partial<Record<LandingSectionKey, unknown>> = {
  hero: HeroSectionForm,
  salas: SalasSectionForm,
  verificar: VerificarSectionForm,
  invita: InvitaSectionForm,
  ganadores: GanadoresSectionForm,
  cta_final: CtaFinalSectionForm,
  footer: FooterSectionForm,
}

const localContent = reactive<Record<string, unknown>>({})

watch(
  () => props.section,
  (section) => {
    Object.keys(localContent).forEach((key) => delete localContent[key])
    // JSON.parse/stringify en vez de structuredClone: section.content puede
    // ser un Proxy reactivo de Vue (section viene de un ref del padre) y
    // structuredClone no sabe clonar Proxies — el contenido es siempre JSON
    // plano (strings/arrays/objetos), así que este roundtrip es seguro.
    if (section) Object.assign(localContent, JSON.parse(JSON.stringify(section.content)))
  },
  { immediate: true }
)

const formComponent = computed(() => (props.section ? FORM_COMPONENTS[props.section.section_key] : null))

async function handleSubmit(): Promise<boolean> {
  if (!props.section) return false
  emit('save', props.section.section_key, JSON.parse(JSON.stringify(localContent)))
  return true
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    :title="section ? `Editar sección: ${section.label}` : 'Editar sección'"
    icon="fa-pen"
    size="lg"
    show-confirm
    confirm-text="Guardar"
    :confirm-disabled="!formComponent"
    :on-submit="handleSubmit"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <component :is="formComponent" v-if="formComponent" v-model="localContent" />
    <p v-else class="text-muted mb-0">Esta sección no tiene contenido editable.</p>
  </Modal>
</template>

<style>
/* Sin scope a propósito: estas clases las usan los 7 formularios hijos
   (forms/*SectionForm.vue) renderizados vía <component :is>, y `scoped`
   no comparte reglas entre componentes distintos. */
.lsf-field {
  margin-bottom: 14px;
}
.lsf-field label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 4px;
  color: var(--ds-text-muted, #6c757d);
}
</style>
