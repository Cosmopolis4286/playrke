<script setup lang="ts">
  import { computed } from 'vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRulesDefault from '@/js/Components/Games/Bingo/Config/Rules/Default.vue'

  const props = defineProps<{
    modelValue: boolean
    rules?: string | null
    image?: string | null
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()

  // BUG-03: sanitizar HTML antes de v-html. Usa DOMParser nativo (sin dependencias).
  // Elimina scripts, iframes, event handlers y hrefs javascript:.
  // El contenido lo generan solo admins, pero una XSS stored sería crítica.
  const sanitizedRules = computed(() => {
    if (!props.rules) return null
    const doc = new DOMParser().parseFromString(props.rules, 'text/html')
    doc.querySelectorAll('script,style,iframe,object,embed,form').forEach(el => el.remove())
    doc.querySelectorAll('*').forEach(el => {
      for (const attr of Array.from(el.attributes)) {
        if (
          attr.name.startsWith('on') ||
          (attr.name === 'href' && attr.value.trimStart().toLowerCase().startsWith('javascript'))
        ) {
          el.removeAttribute(attr.name)
        }
      }
    })
    return doc.body.innerHTML
  })
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Reglamento del Bingo"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Imagen -->
    <div v-if="image" class="d-flex justify-content-center mb-4">
      <img
        :src="`/storage/game/bingo/${image}`"
        alt="Imagen del reglamento"
        class="img-fluid rounded"
        style="max-height: 200px; object-fit: contain"
      />
    </div>

    <!-- Reglamento personalizado como HTML (sanitizado) -->
    <div v-if="sanitizedRules" class="bingo-rules-content" v-html="sanitizedRules" />

    <!-- Reglamento por defecto como componente -->
    <div v-else class="bingo-rules-content">
      <BingoRulesDefault />
    </div>

    <template #footer>
      <button class="btn btn-sm btn-secondary" @click="emit('update:modelValue', false)">
        Cerrar
      </button>
    </template>
  </Modal>
</template>

<style scoped>
  .bingo-rules-content {
    font-size: 0.9rem;
    line-height: 1.7;
    color: #343a40;
  }

  .bingo-rules-content :deep(h1),
  .bingo-rules-content :deep(h2),
  .bingo-rules-content :deep(h3) {
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
  }

  .bingo-rules-content :deep(p) {
    margin-bottom: 0.75rem;
  }

  .bingo-rules-content :deep(ul),
  .bingo-rules-content :deep(ol) {
    padding-left: 1.5rem;
    margin-bottom: 0.75rem;
  }
</style>
