<script setup lang="ts">
import type { GameHistoryDetail } from '@/js/types/games/bingo/history/my-games'
import { formatLocalOnly } from '@js/composables/games/bingo/history/useMyGamesFormatters'

defineProps<{
  detail: GameHistoryDetail
}>()
</script>

<template>
  <div class="result-summary mb-3">
    <div class="result-summary__item">
      <div class="result-summary__label"><i class="fas fa-coins"></i>Gastado</div>
      <div class="result-summary__value">{{ formatLocalOnly(detail.total_spent_local, detail.currency_symbol, detail.decimal_places, detail.is_usd) }}</div>
    </div>
    <div class="result-summary__sep"><i class="fas fa-minus"></i></div>
    <div class="result-summary__item">
      <div class="result-summary__label"><i class="fas fa-trophy"></i>Ganado</div>
      <div class="result-summary__value result-summary__value--win">{{ formatLocalOnly(detail.total_local, detail.currency_symbol, detail.decimal_places, detail.is_usd) }}</div>
    </div>
    <div class="result-summary__sep"><i class="fas fa-equals"></i></div>
    <div class="result-summary__item">
      <div class="result-summary__label">Neto</div>
      <div
        class="result-summary__value"
        :class="detail.net_result_local >= 0 ? 'result-summary__value--win' : 'result-summary__value--loss'"
      >
        {{ detail.net_result_local >= 0 ? '+' : '' }}{{ formatLocalOnly(detail.net_result_local, detail.currency_symbol, detail.decimal_places, detail.is_usd) }}
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Resumen financiero: gasto → ganado → neto, el feedback más accionable
 * del modal (¿me convino jugar?) — antes no existía en absoluto. ── */
.result-summary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  padding: 12px 18px;
  flex-wrap: wrap;
}
.result-summary__item { text-align: center; }
.result-summary__label {
  font-size: 10px; text-transform: uppercase; letter-spacing: .4px;
  color: var(--ds-text-muted); margin-bottom: 2px;
  display: flex; align-items: center; justify-content: center; gap: 4px;
}
.result-summary__label i { font-size: 9px; }
.result-summary__value { font-size: 16px; font-weight: 700; color: var(--ds-text); }
.result-summary__value--win  { color: #10b981; }
.result-summary__value--loss { color: #ef4444; }
.result-summary__sep { color: var(--ds-text-muted); font-size: 11px; opacity: .6; }
</style>
