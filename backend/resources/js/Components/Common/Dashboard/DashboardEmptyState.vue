<script setup lang="ts" name="DashboardEmptyState">
  withDefaults(defineProps<{
    icon?:        string
    title:        string
    /** Texto secundario opcional — explica por qué está vacío o qué esperar. */
    description?: string
    /** Reduce el padding vertical para paneles más chicos (ej. dentro de un widget anidado). */
    compact?:     boolean
  }>(), {
    icon: 'fas fa-inbox',
  })
</script>

<template>
  <div class="des" :class="{ 'des--compact': compact }">
    <i :class="icon" class="des__icon"></i>
    <p class="des__title mb-0">{{ title }}</p>
    <p v-if="description" class="des__desc mb-0">{{ description }}</p>
  </div>
</template>

<style scoped>
.des {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 2.5rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.85rem;
  text-align: center;
}
.des--compact {
  padding: 1.25rem 1rem;
}

.des__icon {
  font-size: 2rem;
  opacity: 0.7;
  margin-bottom: 0.25rem;
}

.des__title {
  font-weight: 600;
  color: var(--ds-text-2);
}

.des__desc {
  color: var(--ds-text-muted);
  font-size: 0.8rem;
}
</style>
