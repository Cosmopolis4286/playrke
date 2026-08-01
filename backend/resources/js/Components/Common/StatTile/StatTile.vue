<template>
  <div class="stat-tile" :class="`stat-tile--${variant}`">
    <div class="stat-tile-icon">
      <i class="fas" :class="icon"></i>
    </div>
    <div class="stat-tile-body">
      <span class="stat-tile-value">{{ value }}</span>
      <span class="stat-tile-label">{{ label }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  label: string
  value: string | number
  icon: string
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info'
}>(), {
  variant: 'default',
})
</script>

<style scoped>
.stat-tile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  min-width: 0;
}

.stat-tile-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
}

.stat-tile--success .stat-tile-icon { background: rgba(5,150,105,.15); color: #34d399; }
.stat-tile--warning .stat-tile-icon { background: rgba(217,119,6,.15); color: #fbbf24; }
.stat-tile--danger  .stat-tile-icon { background: rgba(220,38,38,.15); color: #f87171; }
.stat-tile--info    .stat-tile-icon { background: rgba(37,99,235,.15); color: #60a5fa; }

.stat-tile-body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-tile-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--ds-text);
  line-height: 1.2;
}

.stat-tile-label {
  font-size: 0.75rem;
  color: var(--ds-text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
