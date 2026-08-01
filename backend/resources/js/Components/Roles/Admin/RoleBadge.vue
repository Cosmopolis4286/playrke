<template>
  <span class="badge role-badge" :class="getRoleBadgeClass(name)">
    {{ formatRoleName(name) }}
  </span>
</template>

<script setup lang="ts">
import { formatRoleName, getRoleBadgeClass } from '@/js/utils/roles/roleDisplay'

defineProps<{
  name: string
}>()
</script>

<style scoped>
.role-badge { font-size: 0.72rem; padding: 0.3rem 0.6rem; }

.badge-purple    { background-color: rgba(124,58,237,.18); color: #a78bfa; }
.badge-blue      { background-color: rgba(37,99,235,.18);  color: #60a5fa; }
.badge-green     { background-color: rgba(5,150,105,.18);  color: #34d399; }
.badge-teal      { background-color: rgba(13,148,136,.18); color: #2dd4bf; }
.badge-yellow    { background-color: rgba(217,119,6,.18);  color: #fbbf24; }
.badge-orange    { background-color: rgba(234,88,12,.18);  color: #fb923c; }
.badge-pink      { background-color: rgba(219,39,119,.18); color: #f472b6; }
.badge-info      { background-color: rgba(5,81,96,.25);    color: #22d3ee; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }
</style>
