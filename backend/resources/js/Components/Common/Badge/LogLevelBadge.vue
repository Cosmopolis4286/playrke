<template>
  <span class="badge log-level-badge" :class="getLevelBadgeClass(level)">
    <i :class="getLevelIcon(level)" class="me-1"></i>{{ formatLevelName(level) }}
  </span>
</template>

<script setup lang="ts">
import { formatLevelName, getLevelBadgeClass, getLevelIcon } from '@/js/utils/logs/logLevelDisplay'

defineProps<{
  level: string
}>()
</script>

<style scoped>
.log-level-badge { font-size: 0.72rem; padding: 0.3rem 0.6rem; }

.badge-red       { background-color: rgba(220,38,38,.18);  color: #f87171; }
.badge-yellow    { background-color: rgba(217,119,6,.18);  color: #fbbf24; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }
</style>
