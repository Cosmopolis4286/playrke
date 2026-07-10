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
  color: #1e293b;
  padding-bottom: 4px;
  border-bottom: 1px solid #e9eef5;
}

.info-modal__paragraph { margin: 0; color: #4b5563; }
.info-modal__paragraph--intro { color: #1e293b; font-weight: 500; }
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
</style>
