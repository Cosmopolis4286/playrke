<script setup lang="ts">
import type { GameHistoryDetail } from '@/js/types/games/bingo/history/my-games'
import { roomStatusBadge, isRoomOngoing, formatTotalAmount } from '@js/composables/games/bingo/history/useMyGamesFormatters'

defineProps<{
  detail: GameHistoryDetail
}>()
</script>

<template>
  <div
    class="detail-banner mb-3"
    :class="detail.won ? 'detail-banner--winner' : (isRoomOngoing(detail.room_status) ? 'detail-banner--live' : 'detail-banner--normal')"
  >
    <div class="detail-banner__left">
      <i
        :class="detail.won ? 'fas fa-trophy' : (isRoomOngoing(detail.room_status) ? 'fas fa-hourglass-half' : 'fas fa-gamepad')"
        class="detail-banner__icon"
      ></i>
      <div>
        <div class="detail-banner__title">
          {{ detail.won ? '¡Felicidades, ganaste!' : (isRoomOngoing(detail.room_status) ? 'Partida en curso' : 'Partida completada') }}
        </div>
        <div class="detail-banner__sub font-monospace">{{ detail.room_code }}</div>
      </div>
    </div>
    <div class="detail-banner__right">
      <div v-if="detail.total_usd > 0" class="detail-banner__prize">
        +{{ formatTotalAmount(detail) }}
      </div>
      <span :class="roomStatusBadge(detail.room_status).cls">
        {{ roomStatusBadge(detail.room_status).label }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.detail-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  border-radius: 10px;
  padding: 14px 18px;
}
.detail-banner--winner {
  background: linear-gradient(135deg, rgba(255,193,7,.14), rgba(255,193,7,.05));
  border: 2px solid rgba(255,193,7,.45);
}
.detail-banner--live {
  background: linear-gradient(135deg, rgba(13,110,253,.12), rgba(13,110,253,.04));
  border: 1px solid rgba(13,110,253,.3);
}
.detail-banner--normal {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
}
.detail-banner__left  { display: flex; align-items: center; gap: 12px; }
.detail-banner__icon  { font-size: 28px; color: #b08000; }
.detail-banner--live .detail-banner__icon { color: #0d6efd; }
.detail-banner--normal .detail-banner__icon { color: var(--ds-text-2); }
.detail-banner__title { font-weight: 700; font-size: 15px; color: var(--ds-text); }
.detail-banner__sub   { font-size: 11px; color: var(--ds-text-muted); }
.detail-banner__right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.detail-banner__prize { font-size: 20px; font-weight: 800; color: #198754; }
</style>
