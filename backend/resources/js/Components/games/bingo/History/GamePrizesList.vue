<script setup lang="ts">
import { computed } from 'vue'
import type { GameHistoryDetail, GameHistoryPrize } from '@/js/types/games/bingo/history/my-games'
import { formatPrizeAmount, formatTotalAmount } from '@js/composables/games/bingo/history/useMyGamesFormatters'

const props = defineProps<{
  detail: GameHistoryDetail
}>()

/** Estado semántico propio del premio — evita depender de badges Bootstrap crudos (bg-info, bg-warning, …). */
const PRIZE_STATE: Record<string, { cls: string; icon: string }> = {
  waiting:    { cls: 'prize-status--waiting',    icon: 'fa-clock' },
  processing: { cls: 'prize-status--processing', icon: 'fa-rotate' },
  claimed:    { cls: 'prize-status--claimed',    icon: 'fa-check-circle' },
  cancelled:  { cls: 'prize-status--cancelled',  icon: 'fa-circle-xmark' },
  suspended:  { cls: 'prize-status--suspended',  icon: 'fa-pause-circle' },
}

function prizeState(status: string) {
  return PRIZE_STATE[status] ?? { cls: 'prize-status--waiting', icon: 'fa-clock' }
}

const sortedPrizes = computed<GameHistoryPrize[]>(() =>
  [...props.detail.prizes].sort((a, b) => b.usd_amount - a.usd_amount)
)
</script>

<template>
  <div v-if="detail.prizes.length > 0" class="mb-3">
    <h6 class="section-heading">
      <i class="fas fa-star"></i>Premios obtenidos
    </h6>
    <div class="prize-list">
      <div
        v-for="(prize, i) in sortedPrizes"
        :key="i"
        class="prize-item"
        :style="{ '--pd': `${i * 45}ms` }"
      >
        <div class="prize-item__left">
          <span class="prize-type">{{ prize.type }}</span>
          <span class="prize-status" :class="prizeState(prize.status).cls">
            <i class="fas" :class="prizeState(prize.status).icon" />
            {{ prize.status_label }}
          </span>
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
        <span><i class="fas fa-sack-dollar" /> Total ganado</span>
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
  border-radius: 10px;
  overflow: hidden;
}
.prize-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 14px;
  border-bottom: 1px solid var(--ds-border);
  background: var(--ds-surface);
  animation: prizeItemIn .35s cubic-bezier(.16,1,.3,1) both;
  animation-delay: var(--pd, 0ms);
}
@keyframes prizeItemIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
  .prize-item { animation: none; }
}
.prize-item:last-child { border-bottom: none; }

.prize-item__left { display: flex; align-items: center; gap: 8px; min-width: 0; flex-wrap: wrap; }
.prize-type {
  font-size: 11px; font-weight: 700; letter-spacing: .2px;
  color: var(--ds-text-2);
  background: rgba(127,127,127,.1);
  border: 1px solid var(--ds-border);
  border-radius: 6px;
  padding: 2px 8px;
  white-space: nowrap;
}

/* ── Estado del premio: semántica propia, sin depender de badges Bootstrap crudos ── */
.prize-status {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700;
  padding: 2px 9px; border-radius: 20px;
  white-space: nowrap;
}
.prize-status i { font-size: 9px; }
.prize-status--claimed    { color: #10b981; background: rgba(16,185,129,.1);  border: 1px solid rgba(16,185,129,.25); }
.prize-status--waiting    { color: #f59e0b; background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.25); }
.prize-status--processing { color: #3b82f6; background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.25); }
.prize-status--processing i { animation: prizeSpin 1.6s linear infinite; }
@keyframes prizeSpin { to { transform: rotate(360deg); } }
.prize-status--cancelled  { color: #ef4444; background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.25); }
.prize-status--suspended  { color: var(--ds-text-muted); background: rgba(127,127,127,.1); border: 1px solid var(--ds-border); }

.prize-item__right { text-align: right; flex-shrink: 0; }
.prize-item__amount     { font-weight: 700; color: #10b981; margin-right: 6px; font-variant-numeric: tabular-nums; }
.prize-item__amount-usd { font-size: 11px; color: var(--ds-text-muted); font-variant-numeric: tabular-nums; }

.prize-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 14px;
  background: rgba(16,185,129,.08);
  border-top: 2px solid rgba(16,185,129,.25);
  font-size: 13px;
  font-weight: 600;
  color: var(--ds-text-2);
}
.prize-total i { color: #10b981; font-size: 12px; margin-right: 4px; }
.prize-total__amount { font-weight: 800; color: #10b981; font-size: 16px; font-variant-numeric: tabular-nums; }
.prize-total__usd    { display: block; font-size: 10px; color: var(--ds-text-muted); text-align: right; font-variant-numeric: tabular-nums; }
</style>
