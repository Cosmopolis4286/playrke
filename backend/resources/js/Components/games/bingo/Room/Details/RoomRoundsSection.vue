<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

defineProps<{
  room: BingoRoom
  roundsConfig: NonNullable<BingoRoom['rounds_config']>
  roundPrizeLabel: (round: NonNullable<BingoRoom['rounds_config']>[number]) => string
}>()
</script>

<template>
  <!-- ── Rondas ─────────────────────────────────────── -->
  <div v-if="roundsConfig.length" class="details-section">
    <h6 class="section-title">Configuración de Rondas</h6>
    <div class="rounds-list">
      <div v-for="(round, i) in roundsConfig" :key="i" class="round-item">
        <span class="round-number">R{{ i + 1 }}</span>
        <div class="round-info">
          <span class="detail-label">Premio: <strong>{{ roundPrizeLabel(round) }}</strong></span>
          <span class="detail-label ml-2">Tipo: {{ round.prizeType }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Premio Full House ──────────────────────────── -->
  <div v-if="room.full_house_prize" class="details-section">
    <h6 class="section-title">Premio Full House</h6>
    <div class="details-grid">
      <div class="detail-item highlight">
        <span class="detail-label">Monto Full House</span>
        <span class="detail-value font-weight-bold text-success">{{ fmt.currency(room.full_house_prize) }}</span>
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
.text-success { color: #28a745 !important; }
.ml-2 { margin-left: 0.5rem; }

/* Rondas */
.rounds-list { display: flex; flex-direction: column; gap: 0.4rem; }

.round-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.45rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 0.375rem;
  border-left: 3px solid #6f42c1;
}

.round-number { font-size: 0.75rem; font-weight: 700; color: #6f42c1; min-width: 1.75rem; }

.round-info { display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.8rem; }
</style>
