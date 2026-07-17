<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'
import { PRIZE_STATUS_LABELS, type WinnerEntry } from '@js/composables/games/bingo/room/useWinnerCardViewer'

const props = defineProps<{
  winners: WinnerEntry[]
  loadingCard: string | null
  currencyIso: (currencyId?: string | number | null) => string
  winnerUsername: (w: WinnerEntry) => string
}>()

defineEmits<{
  'view-card': [winner: WinnerEntry]
}>()

/**
 * true si el premio se pagó sin conversión real: cuando no hay tasa de cambio
 * activa al momento del sorteo, BingoDrawService::convertToLocal() devuelve
 * el monto USD tal cual como si fuera moneda local (usd_amount === local_amount).
 * En ese caso el "≈ $X USD" sería engañoso (no es una conversión, es el mismo
 * número sin convertir) — se muestra una advertencia en su lugar.
 */
function isUnconvertedPrize(w: WinnerEntry): boolean {
  if (props.currencyIso(w.currency_id) === 'USD') return false
  if (!w.usd_amount || w.usd_amount <= 0) return false
  return Math.abs(w.usd_amount - w.local_amount) < 0.005
}
</script>

<template>
  <div v-if="winners.length" class="details-section">
    <h6 class="section-title">Ganadores ({{ winners.length }})</h6>
    <div class="winners-list">
      <div v-for="w in winners" :key="w.id" class="winner-item">
        <div class="winner-main">
          <span class="winner-badge"><i class="fas fa-trophy" /></span>
          <div class="winner-info">
            <span class="winner-name">
              {{ winnerUsername(w) }}
              <span class="winner-id">#{{ w.user_id }}</span>
            </span>
            <span class="winner-meta">
              <span v-if="w.round_number">Ronda {{ w.round_number }}</span>
              <span v-if="w.winning_pattern"> · {{ w.winning_pattern }}</span>
            </span>
          </div>
          <div class="winner-prize">
            <span class="winner-amount">
              {{ currencyIso(w.currency_id) }} {{ fmt.localAmount(w.local_amount) }}
            </span>
            <span v-if="isUnconvertedPrize(w)" class="winner-amount-warning" title="No había tasa de cambio activa al momento del sorteo: el monto no se convirtió de USD a la moneda local">
              <i class="fas fa-exclamation-triangle"></i> Sin tasa activa, no convertido
            </span>
            <span v-else class="winner-amount-usd">≈ ${{ w.usd_amount.toFixed(2) }} USD</span>
            <span class="winner-status" :class="`winner-status--${w.prize_status}`">{{ PRIZE_STATUS_LABELS[w.prize_status] ?? w.prize_status }}</span>
          </div>
          <button
            class="winner-card-btn"
            :class="{ 'winner-card-btn--loading': loadingCard === w.id }"
            title="Ver cartón ganador"
            @click="$emit('view-card', w)"
          >
            <i v-if="loadingCard === w.id" class="fas fa-spinner fa-spin" />
            <i v-else class="fas fa-id-card" />
          </button>
        </div>
        <div v-if="w.serial_number" class="winner-serial">{{ w.serial_number }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.details-section {
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e9ecef;
}
.details-section:last-child { border-bottom: none; margin-bottom: 0; }

.section-title {
  color: #495057;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 0.65rem;
  padding-bottom: 0.4rem;
  border-bottom: 2px solid #007bff;
  display: inline-block;
}

.winners-list { display: flex; flex-direction: column; gap: 0.4rem; }

.winner-item {
  background: #f8f9fa;
  border-radius: 0.375rem;
  border-left: 3px solid #ffc107;
  padding: 0.45rem 0.6rem;
}

.winner-main { display: flex; align-items: center; gap: 0.6rem; }

.winner-badge {
  font-size: 0.85rem; color: #ffc107; flex-shrink: 0;
  width: 1.5rem; text-align: center;
}

.winner-info { flex: 1; min-width: 0; }
.winner-name { display: block; font-size: 0.82rem; font-weight: 600; color: #212529; }
.winner-id { font-size: 0.68rem; font-weight: 400; color: #adb5bd; margin-left: 4px; }
.winner-meta { font-size: 0.7rem; color: #6c757d; }

.winner-prize { text-align: right; flex-shrink: 0; }
.winner-amount { display: block; font-size: 0.85rem; font-weight: 700; color: #28a745; white-space: nowrap; }
.winner-amount-usd { display: block; font-size: 0.68rem; color: #6c757d; }
.winner-amount-warning {
  display: block; font-size: 0.65rem; font-weight: 600; color: #b45309;
  white-space: nowrap;
}
.winner-status {
  font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
  padding: 1px 5px; border-radius: 3px;
}
.winner-status--waiting    { background: #fff3cd; color: #856404; }
.winner-status--processing { background: #cfe2ff; color: #084298; }
.winner-status--claimed    { background: #d1e7dd; color: #0f5132; }
.winner-status--cancelled  { background: #f8d7da; color: #842029; }
.winner-status--suspended  { background: #e2e3e5; color: #41464b; }

.winner-card-btn {
  flex-shrink: 0; width: 28px; height: 28px; border-radius: 6px;
  border: 1px solid #dee2e6; background: #fff;
  color: #6c757d; font-size: 0.8rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.14s;
}
.winner-card-btn:hover { border-color: #007bff; color: #007bff; background: #e7f1ff; }
.winner-card-btn--loading { opacity: 0.6; pointer-events: none; }

.winner-serial { font-size: 0.65rem; color: #adb5bd; margin-top: 2px; font-family: monospace; }
</style>
