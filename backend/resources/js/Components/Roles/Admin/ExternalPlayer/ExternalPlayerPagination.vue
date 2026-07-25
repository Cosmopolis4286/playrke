<template>
  <div v-if="lastPage > 1" class="ep-pagination">
    <span class="ep-pagination-info">{{ total }} resultado{{ total === 1 ? '' : 's' }}</span>
    <div class="ep-pagination-controls">
      <button class="btn btn-sm btn-outline-secondary" :disabled="currentPage <= 1" @click="$emit('change', currentPage - 1)">
        <i class="fas fa-chevron-left"></i>
      </button>
      <span class="ep-pagination-page">{{ currentPage }} / {{ lastPage }}</span>
      <button class="btn btn-sm btn-outline-secondary" :disabled="currentPage >= lastPage" @click="$emit('change', currentPage + 1)">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  currentPage: number
  lastPage: number
  total: number
}>()

defineEmits<{
  (e: 'change', page: number): void
}>()
</script>

<style scoped>
.ep-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--ds-border);
}
.ep-pagination-info { font-size: 0.78rem; color: var(--ds-text-muted); }
.ep-pagination-controls { display: flex; align-items: center; gap: 0.5rem; }
.ep-pagination-page { font-size: 0.8rem; color: var(--ds-text-2); font-weight: 600; min-width: 3.5rem; text-align: center; }
</style>
