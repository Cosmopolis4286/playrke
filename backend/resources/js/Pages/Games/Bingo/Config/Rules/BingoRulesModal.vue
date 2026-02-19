<script setup lang="ts">
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRulesDefault from '@/js/Components/Games/Bingo/Config/Rules/Default.vue'

  const props = defineProps<{
    modelValue: boolean
    rules?: string | null
    image?: string | null
  }>()

  /**
   * Define el reglamento
   */
  const regulation = computed(() => props.rules ?? BingoRulesDefault.vue)

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()
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

    <!-- Reglamento interpretado como HTML -->
    <div v-if="rules" class="bingo-rules-content" v-html="regulation" />

    <div v-else class="text-muted py-4 text-center">
      <i class="fa-regular fa-file-lines fa-2x d-block mb-2 opacity-50" />
      <small>No hay reglamento cargado.</small>
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
