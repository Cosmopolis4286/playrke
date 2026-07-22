<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { statusLabel, statusClass } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

defineProps<{
  room: BingoRoom
}>()
</script>

<template>
  <div class="details-header mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge badge-lg" :class="statusClass(room.status)">{{ statusLabel(room.status) }}</span>
      <span class="text-muted small">ID: <strong>{{ room.id }}</strong></span>
      <span v-if="room.config?.in_live === 'Y'" class="badge badge-success">En Vivo</span>
      <span v-if="room.paid_prizes === 'Y'" class="badge badge-info">Premios Pagados</span>
      <span v-if="room.is_closed" class="badge badge-danger">Sala Cerrada</span>
    </div>
  </div>
</template>

<style scoped>
.details-header {
  padding: 0.5rem 0.5rem 0.75rem;
  border-bottom: 1px solid var(--ds-border);
}

.badge {
  padding: 0.3rem 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 0.25rem;
}
.badge-lg { font-size: 0.8rem; padding: 0.4rem 0.85rem; }

.badge-success   { background-color: #28a745; color: white; }
.badge-primary   { background-color: #007bff; color: white; }
.badge-info      { background-color: #17a2b8; color: white; }
.badge-warning   { background-color: #ffc107; color: #212529; }
.badge-danger    { background-color: #dc3545; color: white; }
.badge-secondary { background-color: #6c757d; color: white; }
.badge-dark      { background-color: #343a40; color: white; }
.badge-light     { background-color: var(--ds-surface-2); color: var(--ds-text); border: 1px solid var(--ds-border); }

.gap-2 { gap: 0.5rem; }
</style>
