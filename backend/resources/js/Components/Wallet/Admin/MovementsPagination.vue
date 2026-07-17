<script setup lang="ts">
import type { MovementsMeta } from '@js/types/wallet/admin-movement'

defineProps<{
  meta: MovementsMeta | null
  currentPage: number
  totalPages: number
  loading: boolean
}>()

defineEmits<{
  'page-change': [page: number]
}>()
</script>

<template>
  <div v-if="meta && totalPages > 1" class="mv-pagination">
    <span class="mv-pagination__info">
      Página {{ currentPage }} de {{ totalPages }}
      <span class="text-muted">· {{ meta.total }} movimientos</span>
    </span>
    <div class="mv-pagination__btns">
      <button
        class="btn btn-sm btn-outline-secondary"
        :disabled="currentPage <= 1 || loading"
        @click="$emit('page-change', currentPage - 1)"
      >
        <i class="fas fa-chevron-left"></i>
      </button>
      <button
        v-for="p in totalPages"
        :key="p"
        class="btn btn-sm"
        :class="p === currentPage ? 'btn-primary' : 'btn-outline-secondary'"
        :disabled="loading"
        @click="$emit('page-change', p)"
      >
        {{ p }}
      </button>
      <button
        class="btn btn-sm btn-outline-secondary"
        :disabled="currentPage >= totalPages || loading"
        @click="$emit('page-change', currentPage + 1)"
      >
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<style scoped>
.mv-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-top: 1px solid var(--ds-border);
  margin-top: 0.5rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.mv-pagination__info { font-size: 0.78rem; color: var(--ds-text-2); }
.mv-pagination__btns { display: flex; align-items: center; gap: 3px; }
.mv-pagination__btns .btn { min-width: 32px; }
</style>
