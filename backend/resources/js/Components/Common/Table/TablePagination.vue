<script setup lang="ts" name="TablePagination">
  import { computed } from 'vue'

  const props = defineProps<{
    currentPage: number
    totalPages: number
    disabled?: boolean
    /** Máximo de botones de página numéricos visibles */
    maxButtons?: number
  }>()

  const emit = defineEmits<{
    (e: 'update:currentPage', page: number): void
  }>()

  const maxButtons = computed(() => props.maxButtons ?? 5)

  /**
   * Rango de páginas visibles, centrado en la página actual.
   */
  const visiblePages = computed(() => {
    const total = props.totalPages
    const max = Math.min(maxButtons.value, total)

    let start = Math.max(1, props.currentPage - Math.floor(max / 2))
    const end = Math.min(total, start + max - 1)
    start = Math.max(1, end - max + 1)

    return Array.from({ length: end - start + 1 }, (_, i) => start + i)
  })

  function goTo(page: number): void {
    if (page < 1 || page > props.totalPages || page === props.currentPage) return
    emit('update:currentPage', page)
  }
</script>

<template>
  <nav v-if="totalPages > 1" aria-label="Paginación de tabla">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item" :class="{ disabled: currentPage <= 1 || disabled }">
        <button class="page-link" type="button" @click="goTo(currentPage - 1)" :disabled="disabled">
          <i class="fas fa-chevron-left"></i>
        </button>
      </li>

      <li v-if="visiblePages[0] > 1" class="page-item disabled">
        <span class="page-link">…</span>
      </li>

      <li
        v-for="page in visiblePages"
        :key="page"
        class="page-item"
        :class="{ active: page === currentPage }"
      >
        <button class="page-link" type="button" @click="goTo(page)" :disabled="disabled">
          {{ page }}
        </button>
      </li>

      <li v-if="visiblePages[visiblePages.length - 1] < totalPages" class="page-item disabled">
        <span class="page-link">…</span>
      </li>

      <li class="page-item" :class="{ disabled: currentPage >= totalPages || disabled }">
        <button class="page-link" type="button" @click="goTo(currentPage + 1)" :disabled="disabled">
          <i class="fas fa-chevron-right"></i>
        </button>
      </li>
    </ul>
  </nav>
</template>
