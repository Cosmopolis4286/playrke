<script setup lang="ts">
import { computed } from 'vue'
import type { SellerPendingAction } from '@/js/types/roles/seller/dashboard/dashboard-seller'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

const props = defineProps<{
  items: SellerPendingAction[]
}>()

// Solo se listan las colas con algo pendiente; sin nada → estado vacío.
const active = computed(() => props.items.filter(i => i.count > 0))
</script>

<template>
  <div v-if="active.length" class="spa-list">
    <a
      v-for="item in active"
      :key="item.key"
      :href="item.href"
      class="spa-item"
      :class="`spa-item--${item.variant}`"
    >
      <span class="spa-item__icon"><i :class="item.icon"></i></span>
      <span class="spa-item__body">
        <span class="spa-item__label">{{ item.label }}</span>
        <span class="spa-item__hint">{{ item.hint }}</span>
      </span>
      <span class="spa-item__figures">
        <span class="spa-item__count">{{ item.count }}</span>
        <span v-if="item.amount" class="spa-item__amount">{{ item.amount }}</span>
      </span>
    </a>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-circle-check"
    title="Todo al día"
    description="No tienes pagos en revisión, devoluciones ni premios por entregar."
    compact
  />
</template>

<style scoped>
.spa-list { display: flex; flex-direction: column; gap: 0.5rem; }

.spa-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.65rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  border-left: 3px solid transparent;
  text-decoration: none; color: inherit;
  transition: box-shadow 0.15s;
}
.spa-item:hover { box-shadow: var(--ds-card-hover-shadow); text-decoration: none; }
.spa-item--info    { border-left-color: #3b82f6; }
.spa-item--danger  { border-left-color: #ef4444; }
.spa-item--warning { border-left-color: #f59e0b; }

.spa-item__icon {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: var(--ds-surface); color: var(--ds-text-2); font-size: 0.85rem;
}
.spa-item--info .spa-item__icon    { color: #3b82f6; }
.spa-item--danger .spa-item__icon  { color: #ef4444; }
.spa-item--warning .spa-item__icon { color: #f59e0b; }

.spa-item__body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.spa-item__label { font-size: 0.85rem; font-weight: 700; color: var(--ds-text); }
.spa-item__hint { font-size: 0.72rem; color: var(--ds-text-muted); }

.spa-item__figures { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; }
.spa-item__count { font-size: 1.05rem; font-weight: 800; color: var(--ds-text); line-height: 1.1; }
.spa-item__amount { font-size: 0.72rem; color: var(--ds-text-2); white-space: nowrap; }
</style>
