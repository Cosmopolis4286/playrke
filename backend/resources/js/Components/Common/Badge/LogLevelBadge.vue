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

/* emergency/alert/critical: más urgente que un 'error' común (sistema caído
   o fallo de alto impacto), fondo sólido en vez del tono translúcido del
   resto para que destaque a simple vista en la tabla. */
.badge-critical  { background-color: #dc2626; color: #fff; font-weight: 700; }
</style>
