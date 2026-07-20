<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

defineProps<{
  room: BingoRoom
}>()
</script>

<template>
  <div v-if="room.config?.use_serials" class="details-section">
    <h6 class="section-title">Seriales</h6>
    <div class="details-grid">
      <div class="detail-item">
        <span class="detail-label">% Seriales</span>
        <span class="detail-value">{{ fmt.pct(room.config?.serials_pct) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Cantidad de Seriales</span>
        <span class="detail-value">{{ fmt.number(room.config?.num_serials) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Premio Serial</span>
        <span class="detail-value">{{ fmt.currency(room.serial_prize) }}</span>
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

.detail-label {
  font-size: 0.7rem;
  color: var(--ds-text-2);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 0.2rem;
}

.detail-value { font-size: 0.875rem; color: var(--ds-text); }
</style>
