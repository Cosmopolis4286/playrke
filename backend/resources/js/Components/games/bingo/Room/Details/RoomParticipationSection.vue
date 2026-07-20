<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

defineProps<{
  room: BingoRoom
}>()
</script>

<template>
  <div class="details-section">
    <h6 class="section-title">Participación</h6>
    <div class="details-grid">
      <div class="detail-item highlight">
        <span class="detail-label">Jugadores</span>
        <span class="detail-value font-weight-bold text-primary">{{ fmt.number(room.total_players) }}</span>
      </div>
      <div class="detail-item highlight">
        <span class="detail-label">Cartones Vendidos</span>
        <span class="detail-value font-weight-bold text-info">{{ fmt.number(room.total_cards) }}</span>
      </div>
      <div v-if="room.config?.min_cards_to_start" class="detail-item">
        <span class="detail-label">Mínimo Requerido</span>
        <span class="detail-value">
          {{ fmt.number(room.config?.min_cards_to_start) }}
          <span class="ml-1" :class="(room.total_cards ?? 0) >= (room.config?.min_cards_to_start ?? 0) ? 'text-success' : 'text-warning'">
            <i :class="(room.total_cards ?? 0) >= (room.config?.min_cards_to_start ?? 0) ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
            {{ (room.total_cards ?? 0) >= (room.config?.min_cards_to_start ?? 0) ? 'Alcanzado' : 'Pendiente' }}
          </span>
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.details-section {
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--ds-border);
}

.section-title {
  color: var(--ds-text-2);
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 0.65rem;
  padding-bottom: 0.4rem;
  border-bottom: 2px solid #007bff;
  display: inline-block;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 0.6rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  padding: 0.45rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 0.375rem;
  border-left: 3px solid transparent;
}
.detail-item.highlight { background: #e8f4fd; border-left-color: #007bff; }
.detail-item.highlight .detail-label { color: #0b3d6b; }

.detail-label {
  font-size: 0.7rem;
  color: var(--ds-text-2);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 0.2rem;
}

.detail-value { font-size: 0.875rem; color: var(--ds-text); }

.font-weight-bold { font-weight: 600 !important; }
.text-primary { color: #007bff !important; }
.text-info    { color: #17a2b8 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.ml-1 { margin-left: 0.25rem; }
</style>
