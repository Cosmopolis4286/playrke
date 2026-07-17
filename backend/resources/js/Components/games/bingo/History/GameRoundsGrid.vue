<script setup lang="ts">
import type { GameHistoryDetail } from '@/js/types/games/bingo/history/my-games'
import { roundStatusLabel, roundStatusCls } from '@js/composables/games/bingo/history/useMyGamesFormatters'

defineProps<{
  detail: GameHistoryDetail
}>()
</script>

<template>
  <div v-if="detail.rounds.length > 0" class="mb-3">
    <h6 class="section-heading">
      <i class="fas fa-list-ol"></i>Rondas
      <span class="section-heading__hint">{{ detail.rounds_finished }}/{{ detail.rounds_total }} finalizadas</span>
    </h6>
    <div class="rounds-grid">
      <div
        v-for="round in detail.rounds"
        :key="round.round"
        class="round-item"
      >
        <div class="round-item__number">#{{ round.round }}</div>
        <div class="round-item__info">{{ round.prize_type }}</div>
        <span :class="roundStatusCls(round.status)" class="round-item__status">
          {{ roundStatusLabel(round.status) }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.section-heading {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--ds-text-2);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 7px;
}
.section-heading i { color: #f59e0b; font-size: 12px; }
.section-heading__hint {
  font-weight: 500; text-transform: none; letter-spacing: 0;
  color: var(--ds-text-muted); font-size: 11px; margin-left: 2px;
}

.rounds-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.round-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  padding: 8px 12px;
  min-width: 160px;
}
.round-item__number { font-weight: 700; font-size: 13px; color: var(--ds-text); }
.round-item__info   { flex: 1; font-size: 12px; color: var(--ds-text-muted); }
.round-item__status { flex-shrink: 0; font-size: 10px; }
</style>
