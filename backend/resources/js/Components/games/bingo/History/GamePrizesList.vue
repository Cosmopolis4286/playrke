<script setup lang="ts">
import type { GameHistoryDetail } from '@/js/types/games/bingo/history/my-games'
import { prizeStatusCls, formatPrizeAmount, formatTotalAmount } from '@js/composables/games/bingo/history/useMyGamesFormatters'

defineProps<{
  detail: GameHistoryDetail
}>()
</script>

<template>
  <div v-if="detail.prizes.length > 0" class="mb-3">
    <h6 class="section-heading">
      <i class="fas fa-star"></i>Premios obtenidos
    </h6>
    <div class="prize-list">
      <div
        v-for="(prize, i) in detail.prizes"
        :key="i"
        class="prize-item"
      >
        <div class="prize-item__left">
          <span class="badge bg-info text-dark me-2">{{ prize.type }}</span>
          <span :class="prizeStatusCls(prize.status)">{{ prize.status_label }}</span>
        </div>
        <div class="prize-item__right">
          <span v-if="prize.usd_amount > 0" class="prize-item__amount">
            {{ formatPrizeAmount(prize) }}
          </span>
          <span v-if="!prize.is_usd && prize.usd_amount > 0" class="prize-item__amount-usd">
            ({{ prize.usd_amount.toFixed(2) }} USD)
          </span>
        </div>
      </div>
      <!-- Total -->
      <div v-if="detail.total_usd > 0" class="prize-total">
        <span>Total ganado</span>
        <div class="text-end">
          <span class="prize-total__amount">{{ formatTotalAmount(detail) }}</span>
          <span v-if="!detail.is_usd" class="prize-total__usd">({{ detail.total_usd.toFixed(2) }} USD)</span>
        </div>
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

.prize-list {
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  overflow: hidden;
}
.prize-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid var(--ds-border);
  background: var(--ds-surface);
}
.prize-item:last-child { border-bottom: none; }
.prize-item__amount     { font-weight: 700; color: #10b981; margin-right: 6px; }
.prize-item__amount-usd { font-size: 11px; color: var(--ds-text-muted); }
.prize-total {
  display: flex;
  justify-content: space-between;
  padding: 10px 14px;
  background: rgba(16,185,129,.08);
  border-top: 2px solid rgba(16,185,129,.25);
  font-size: 14px;
  color: var(--ds-text-2);
}
.prize-total__amount { font-weight: 800; color: #10b981; font-size: 15px; }
.prize-total__usd    { display: block; font-size: 10px; color: var(--ds-text-muted); text-align: right; }
</style>
