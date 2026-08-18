<script setup lang="ts" name="DashboardEntityList">
  import { computed } from 'vue'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import type { EntityCount } from '@/js/types/common/dashboard/dashboard'

  const props = defineProps<{
    items: EntityCount[]
  }>()

  /** Suma total de todas las entidades — base para la barra de proporción relativa de cada una. */
  const total = computed(() => props.items.reduce((sum, item) => sum + (Number(item.value) || 0), 0))

  function proportion(value: number | string): number {
    if (total.value <= 0) return 0
    return Math.round((Number(value) / total.value) * 100)
  }

  const trendIcon = {
    up:      'fas fa-caret-up',
    down:    'fas fa-caret-down',
    neutral: '',
  } as const
</script>

<template>
  <div v-if="items.length" class="del-list">
    <component
      :is="entity.href ? 'a' : 'div'"
      v-for="(entity, i) in items"
      :key="i"
      :href="entity.href"
      class="del-item"
      :class="{ 'del-item--link': entity.href }"
    >
      <span v-if="entity.icon" class="del-icon" :class="`text-${entity.variant ?? 'secondary'}`">
        <i :class="entity.icon"></i>
      </span>

      <div class="del-main">
        <div class="del-row">
          <span class="del-label">{{ entity.label }}</span>
          <span
            v-if="entity.trend && entity.trend !== 'neutral' && entity.trendValue !== undefined"
            class="del-trend"
            :class="`del-trend--${entity.trend}`"
            :title="entity.trendLabel"
          >
            <i :class="trendIcon[entity.trend]"></i>{{ entity.trendValue }}%
          </span>
          <span class="del-value badge" :class="`bg-${entity.variant ?? 'secondary'}`">
            {{ entity.value }}
          </span>
        </div>

        <div class="del-bar">
          <div
            class="del-bar__fill"
            :class="`bg-${entity.variant ?? 'secondary'}`"
            :style="{ width: proportion(entity.value) + '%' }"
          ></div>
        </div>
      </div>

      <i v-if="entity.href" class="fas fa-chevron-right del-chevron"></i>
    </component>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-layer-group"
    title="Sin entidades registradas todavía"
    description="Los conteos aparecerán aquí en cuanto se creen comercios, vendedores u otros roles."
  />
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
  background: var(--ds-surface-2);
  border-radius: 8px;
  color: inherit;
  text-decoration: none;
}

.del-item--link {
  cursor: pointer;
  transition: background-color 0.15s ease-in-out, transform 0.1s ease-in-out;
}
.del-item--link:hover {
  filter: brightness(1.08);
  transform: translateX(2px);
}

.del-icon {
  font-size: 1.1rem;
  width: 1.5rem;
  text-align: center;
  flex-shrink: 0;
}

.del-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.del-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.del-label {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ds-text-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.del-value {
  font-size: 0.8rem;
  font-weight: 600;
  min-width: 2rem;
  text-align: center;
}

.del-trend {
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
}
.del-trend--up   { color: #10b981; }
.del-trend--down { color: #ef4444; }

.del-bar {
  height: 4px;
  border-radius: 2px;
  background: rgba(127, 127, 127, 0.18);
  overflow: hidden;
}
.del-bar__fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.4s ease-out;
}

.del-chevron {
  font-size: 0.7rem;
  color: var(--ds-text-muted);
  flex-shrink: 0;
}
</style>
