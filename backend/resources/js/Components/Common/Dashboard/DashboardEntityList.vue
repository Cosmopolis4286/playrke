<script setup lang="ts" name="DashboardEntityList">
  import type { StatCardVariant } from '@/js/types/common/dashboard/dashboard'

  export interface EntityItem {
    label:   string
    value:   string | number
    icon?:   string
    variant?: StatCardVariant
  }

  defineProps<{
    items: EntityItem[]
  }>()
</script>

<template>
  <div v-if="items.length" class="del-list">
    <div
      v-for="(entity, i) in items"
      :key="i"
      class="del-item"
    >
      <span v-if="entity.icon" class="del-icon" :class="`text-${entity.variant ?? 'secondary'}`">
        <i :class="entity.icon"></i>
      </span>
      <span class="del-label">{{ entity.label }}</span>
      <span class="del-value badge" :class="`bg-${entity.variant ?? 'secondary'}`">
        {{ entity.value }}
      </span>
    </div>
  </div>
  <div v-else class="del-empty">
    <i class="fas fa-layer-group"></i>
    <p class="mb-0">Sin datos disponibles.</p>
  </div>
</template>

<style scoped>
.del-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.del-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.del-icon {
  font-size: 1.1rem;
  width: 1.5rem;
  text-align: center;
  flex-shrink: 0;
}

.del-label {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
}

.del-value {
  font-size: 0.8rem;
  font-weight: 600;
  min-width: 2rem;
  text-align: center;
}

.del-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: #adb5bd;
  font-size: 0.85rem;
  text-align: center;
}
.del-empty i { font-size: 2rem; }
</style>
