<script setup lang="ts">
import type { OperationalAlert } from '@/js/types/common/dashboard/dashboard'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  alerts: OperationalAlert[]
}>()
</script>

<template>
  <ul v-if="alerts.length" class="oa-list">
    <li v-for="alert in alerts" :key="alert.code" class="oa-item" :class="`oa-item--${alert.severity}`">
      <span class="oa-item__icon">
        <i :class="alert.severity === 'critical' ? 'fas fa-circle-exclamation' : 'fas fa-triangle-exclamation'"></i>
      </span>
      <span class="oa-item__label">{{ alert.label }}</span>
      <span class="oa-item__count">{{ alert.count }}</span>
      <a v-if="alert.href" :href="alert.href" class="oa-item__link">
        {{ alert.action_label ?? 'Ver' }} <i class="fas fa-arrow-right"></i>
      </a>
    </li>
  </ul>
  <DashboardEmptyState
    v-else
    icon="fas fa-circle-check"
    title="Nada pendiente"
    description="Sin pagos por revisar, reembolsos fallidos, premios trabados ni movimientos por aprobar."
    compact
  />
</template>

<style scoped>
.oa-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem; }

.oa-item {
  display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
  padding: 0.6rem 0.875rem; border-radius: 10px;
  background: var(--ds-surface-2);
  border-left: 3px solid #f59e0b;
}
.oa-item--critical { border-left-color: #dc2626; }

.oa-item__icon { color: #f59e0b; font-size: 0.95rem; }
.oa-item--critical .oa-item__icon { color: #dc2626; }

.oa-item__label { flex: 1; min-width: 160px; font-size: 0.85rem; font-weight: 600; color: var(--ds-text); }
.oa-item__count {
  font-size: 0.78rem; font-weight: 800; color: var(--ds-text);
  background: var(--ds-surface); padding: 2px 10px; border-radius: 20px;
}
.oa-item__link { font-size: 0.75rem; font-weight: 700; color: #db2777; white-space: nowrap; }
</style>
