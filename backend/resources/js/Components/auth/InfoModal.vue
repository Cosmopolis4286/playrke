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
    id="info-modal"
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

<style>
/* SIN scoped a propósito: Modal.vue usa <Teleport to="body">, así que su
   DOM real queda fuera del árbol de InfoModal.vue — un bloque `scoped` (o
   :deep() dentro de uno) nunca alcanza contenido teletransportado, porque
   el atributo data-v-* que acota el scope pertenece a InfoModal y no
   aparece en el HTML que Modal.vue realmente renderiza (confirmado
   inspeccionando el DOM). El id="info-modal" (prop de Modal.vue aplicada
   a su div raíz) da un selector único, sin afectar los otros 50+ usos de
   Modal.vue en el dashboard.
   Tema claro a propósito (más legible para un documento largo que el
   oscuro del resto del sitio) — forzado con colores fijos en vez de
   var(--ds-*): esas variables son :root oscuro por defecto y solo
   [data-theme="light"] las sobreescribe, así que dependiendo de ellas
   este modal se vería oscuro dentro de la Landing (que fuerza
   data-theme="dark" en <html>) o en Auth con el tema oscuro por defecto. */
#info-modal .modal-content { background: #ffffff; }
#info-modal .modal-header  { background: #f4f5fb; border-bottom-color: #d8dde8; }
#info-modal .modal-title   { color: #1a202c; }
#info-modal .modal-close-btn { color: #8a94a6; }
#info-modal .modal-close-btn:hover { background: #fee2e2; color: #ef4444; }
#info-modal .modal-body {
  color: #1a202c;
  scrollbar-color: #c0c8d8 transparent;
}
#info-modal .modal-body::-webkit-scrollbar-thumb { background: #c0c8d8; }
#info-modal .modal-footer { background: #f4f5fb; border-top-color: #d8dde8; }
#info-modal .modal-btn--ghost {
  background: transparent;
  color: #4a5568;
  border-color: #c0c8d8;
}
#info-modal .modal-btn--ghost:hover { background: #f4f5fb; }
</style>

<style scoped>
.info-modal__body {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  font-size: 0.875rem;
  line-height: 1.75;
  color: #374151;
}

.info-modal__section { display: flex; flex-direction: column; gap: 0.55rem; }

.info-modal__section-heading {
  margin: 0;
  font-size: 0.82rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #1a202c;
  padding-bottom: 4px;
  border-bottom: 1px solid #e9eef5;
}

.info-modal__paragraph { margin: 0; color: #4b5563; }
.info-modal__paragraph--intro { color: #1a202c; font-weight: 500; }
.info-modal__effective-date   { margin: -0.5rem 0 0; font-size: 0.78rem; color: #94a3b8; }

.info-modal__list {
  margin: 0;
  padding-left: 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  color: #4b5563;
}
.info-modal__list li { line-height: 1.65; }
.info-modal__list :deep(a),
.info-modal__paragraph :deep(a) { color: #009efd; }
</style>
