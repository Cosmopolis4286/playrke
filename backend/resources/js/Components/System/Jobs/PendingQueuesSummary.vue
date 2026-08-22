<script setup lang="ts">
import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
import type { PendingQueueSummary } from '@/js/types/system/jobs'

defineProps<{
  queues: PendingQueueSummary[]
  refreshing?: boolean
}>()

const queueLabels: Record<string, string> = {
  'default':        'Cola general',
  'bingo-draw':     'Sorteos de bingo',
  'bingo-refunds':  'Reembolsos de bingo',
}

function labelFor(queue: string): string {
  return queueLabels[queue] ?? queue
}
</script>

<template>
  <div class="pqs-grid" :class="{ 'pqs-grid--refreshing': refreshing }">
    <StatTile
      v-for="q in queues"
      :key="q.queue"
      :label="labelFor(q.queue)"
      :value="q.pending"
      icon="fa-layer-group"
      :variant="q.pending > 20 ? 'warning' : 'default'"
    />
  </div>
</template>

<style scoped>
.pqs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
  transition: opacity 0.15s;
}
.pqs-grid--refreshing { opacity: 0.6; }
</style>
