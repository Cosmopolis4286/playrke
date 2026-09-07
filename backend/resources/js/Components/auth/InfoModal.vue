<script setup lang="ts">
import Modal from '@js/Components/Common/Modal/Modal.vue'
import { termsContent }   from '@js/modules/utils/info/termsContent'
import { aboutContent }   from '@js/modules/utils/info/aboutContent'
import { privacyContent } from '@js/modules/utils/info/privacyContent'

type ModalKey = 'terms' | 'about' | 'privacy'

const props = defineProps<{ modelValue: ModalKey | null }>()
const emit  = defineEmits<{ (e: 'update:modelValue', v: null): void }>()

const close = () => emit('update:modelValue', null)

const config: Record<ModalKey, { title: string; icon: string }> = {
  terms:   { title: termsContent.title,   icon: 'fa-file-contract' },
  about:   { title: aboutContent.title,   icon: 'fa-circle-info'   },
  privacy: { title: privacyContent.title, icon: 'fa-shield-halved' },
}
</script>

<template>
  <Modal
    :model-value="modelValue !== null"
    :title="modelValue ? config[modelValue].title : ''"
    :icon="modelValue ? config[modelValue].icon : undefined"
    size="lg"
    @update:model-value="close"
  >
    <!-- ── TERMS ─────────────────────────────────── -->
    <template v-if="modelValue === 'terms'">
      <div class="info-modal__body">
        <section
          v-for="section in termsContent.sections"
          :key="section.heading"
          class="info-modal__section"
        >
          <h3 class="info-modal__section-heading">{{ section.heading }}</h3>
          <p
            v-for="(p, i) in section.paragraphs"
            :key="i"
            class="info-modal__paragraph"
          >{{ p }}</p>
        </section>
      </div>
    </template>

    <!-- ── ABOUT ─────────────────────────────────── -->
    <template v-else-if="modelValue === 'about'">
      <div class="info-modal__body">
        <p
          v-for="(p, i) in aboutContent.paragraphs"
          :key="i"
          class="info-modal__paragraph"
        >{{ p }}</p>
      </div>
    </template>

    <!-- ── PRIVACY ────────────────────────────────── -->
    <template v-else-if="modelValue === 'privacy'">
      <div class="info-modal__body">
        <p v-for="(p, i) in privacyContent.intro" :key="'intro-' + i" class="info-modal__paragraph info-modal__paragraph--intro">
          {{ p }}
        </p>
        <p class="info-modal__effective-date">{{ privacyContent.effectiveDate }}</p>

        <section
          v-for="section in privacyContent.sections"
          :key="section.title"
          class="info-modal__section"
        >
          <h3 class="info-modal__section-heading">{{ section.title }}</h3>
          <template v-for="(block, i) in section.content" :key="i">
            <p v-if="block.type === 'paragraph'" class="info-modal__paragraph" v-html="block.text" />
            <ul v-else-if="block.type === 'list'" class="info-modal__list">
              <li v-for="(item, j) in block.items" :key="j" v-html="item.text" />
            </ul>
          </template>
        </section>
      </div>
    </template>
  </Modal>
</template>

<style scoped>
/* Este modal es contenido público (Términos/Acerca de/Privacidad), visible
   tanto desde Auth como desde la Landing — debe verse siempre con la
   identidad de marca oscura, sin depender de --ds-* (tema claro/oscuro del
   dashboard, guardado en localStorage: ver head.blade.php). Sin esto, un
   visitante con tema claro guardado veía el modal genérico de Bootstrap
   (blanco) en vez de la paleta mint/violeta del sitio. Modal.vue se
   teletransporta a <body> y sus clases son scoped a sí mismo, así que se
   sobreescriben aquí vía :deep() en vez de tocar el componente compartido
   (usado en 50+ pantallas del dashboard, que sí debe respetar el tema).
   El :deep() se filtra a través de InfoModal (:has con este componente
   no es necesario porque Modal solo se instancia una vez por InfoModal). */
:deep(.modal-content) {
  background: #0c1230;
  box-shadow: 0 4px 6px rgba(0,0,0,0.2), 0 24px 70px rgba(0,0,0,0.55);
}
:deep(.modal-header) {
  background: linear-gradient(180deg, rgba(123,79,255,0.10), transparent);
  border-bottom-color: rgba(255,255,255,0.08);
}
:deep(.modal-header-icon) {
  background: linear-gradient(135deg, rgba(42,245,152,0.16), rgba(0,158,253,0.16));
  color: #2af598;
}
:deep(.modal-title) { color: #eef2ff; font-family: "Orbitron", "Inter", sans-serif; }
:deep(.modal-close-btn) { color: #7a96c8; }
:deep(.modal-close-btn:hover) { background: rgba(255,77,109,0.12); color: #ff4d6d; }
:deep(.modal-body) {
  color: #c7d2f0;
  scrollbar-color: rgba(42,245,152,0.35) transparent;
}
:deep(.modal-body::-webkit-scrollbar-thumb) { background: rgba(42,245,152,0.35); }
:deep(.modal-footer) {
  background: rgba(255,255,255,0.02);
  border-top-color: rgba(255,255,255,0.08);
}
:deep(.modal-btn--ghost) {
  background: transparent;
  color: #c7d2f0;
  border-color: rgba(255,255,255,0.14);
}
:deep(.modal-btn--ghost:hover) { background: rgba(255,255,255,0.06); }

.info-modal__body {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  font-size: 0.875rem;
  line-height: 1.75;
  color: #c7d2f0;
}

.info-modal__section { display: flex; flex-direction: column; gap: 0.55rem; }

.info-modal__section-heading {
  margin: 0;
  font-size: 0.82rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #eef2ff;
  padding-bottom: 4px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.info-modal__paragraph { margin: 0; color: #a8b8db; }
.info-modal__paragraph--intro { color: #eef2ff; font-weight: 500; }
.info-modal__effective-date   { margin: -0.5rem 0 0; font-size: 0.78rem; color: #4a6698; }

.info-modal__list {
  margin: 0;
  padding-left: 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  color: #a8b8db;
}
.info-modal__list li { line-height: 1.65; }
.info-modal__list :deep(a),
.info-modal__paragraph :deep(a) { color: #2af598; }
</style>
