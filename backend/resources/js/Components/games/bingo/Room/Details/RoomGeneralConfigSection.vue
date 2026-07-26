<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt, VARIANT_LABELS, resolveOption } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

defineProps<{
  room: BingoRoom
  roomTypeOptions: { value: string; label: string }[]
  connectionTypeOptions: { value: string; label: string }[]
  prizePoolTypeOptions: { value: string; code: string; label: string }[]
  startModeLabel: string
  scheduleInfo: string
}>()
</script>

<template>
  <div class="details-section">
    <h6 class="section-title">Configuración General</h6>
    <div class="details-grid">
      <div class="detail-item">
        <span class="detail-label">Tipo de Sala</span>
        <span class="detail-value">{{ resolveOption(roomTypeOptions, room.config?.room_type_id) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Tipo de Conexión</span>
        <span class="detail-value">{{ resolveOption(connectionTypeOptions, room.config?.game_mode_id) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Variante</span>
        <span class="detail-value">{{ VARIANT_LABELS[room.config?.game_variant ?? 'BINGO_75'] ?? room.config?.game_variant ?? 'Bingo 75' }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Tipo de Bote</span>
        <span class="detail-value">{{ resolveOption(prizePoolTypeOptions, room.config?.prize_pool_type_id) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Modo de Inicio</span>
        <span class="detail-value">{{ startModeLabel }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Programación</span>
        <span class="detail-value">{{ scheduleInfo }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Tiempo por Ficha</span>
        <span class="detail-value">{{ fmt.number(room.config?.time_chip) }} seg</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Rondas</span>
        <span class="detail-value">{{ fmt.number(room.config?.rounds) }}</span>
      </div>
      <div v-if="room.config?.min_cards_to_start" class="detail-item highlight-yellow">
        <span class="detail-label">Min. Cartones para Iniciar</span>
        <span class="detail-value font-weight-bold">{{ fmt.number(room.config?.min_cards_to_start) }}</span>
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
.detail-item.highlight-yellow { background: #fff8e1; border-left-color: #ffc107; }
.detail-item.highlight-yellow .detail-label,
.detail-item.highlight-yellow .detail-value { color: #78350f; }

.detail-label {
  font-size: 0.7rem;
  color: var(--ds-text-2);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 0.2rem;
}

.detail-value { font-size: 0.875rem; color: var(--ds-text); }

.font-weight-bold { font-weight: 600 !important; }
</style>
