<template>
  <div class="ep-stats">
    <div class="ep-stat">
      <span class="ep-stat-value">{{ statistics.total }}</span>
      <span class="ep-stat-label">Total</span>
    </div>
    <div class="ep-stat ep-stat--active">
      <span class="ep-stat-value">{{ statistics.active }}</span>
      <span class="ep-stat-label">Activos</span>
    </div>
    <div class="ep-stat ep-stat--inactive">
      <span class="ep-stat-value">{{ statistics.inactive }}</span>
      <span class="ep-stat-label">Inactivos</span>
    </div>
    <div class="ep-stat ep-stat--blocked">
      <span class="ep-stat-value">{{ statistics.blocked }}</span>
      <span class="ep-stat-label">Bloqueados</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ExternalPlayerStatistics } from '@/js/types/roles/admin/external-player'

defineProps<{
  statistics: ExternalPlayerStatistics
}>()
</script>

<style scoped>
.ep-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.6rem;
  margin-bottom: 1rem;
}
.ep-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
  padding: 0.75rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface);
}
.ep-stat-value { font-size: 1.3rem; font-weight: 700; color: var(--ds-text); }
.ep-stat-label { font-size: 0.75rem; color: var(--ds-text-muted); font-weight: 600; text-transform: uppercase; }
.ep-stat--active .ep-stat-value { color: #34d399; }
.ep-stat--inactive .ep-stat-value { color: var(--ds-text-muted); }
.ep-stat--blocked .ep-stat-value { color: #ef4444; }
</style>
