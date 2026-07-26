<script setup lang="ts">
import type { GameHistoryDetail } from '@/js/types/games/bingo/history/my-games'
import { formatDate, variantLabel } from '@js/composables/games/bingo/history/useMyGamesFormatters'

defineProps<{
  detail: GameHistoryDetail
}>()
</script>

<template>
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Fecha</div>
        <div class="detail-info-card__value detail-info-card__value--sm">{{ formatDate(detail.game_date) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Modalidad</div>
        <div class="detail-info-card__value">{{ variantLabel(detail.game_variant) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Tipo de sala</div>
        <div class="detail-info-card__value">{{ detail.room_type ?? '—' }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Cartones</div>
        <div class="detail-info-card__value detail-info-card__value--accent">{{ detail.cards_purchased }}</div>
      </div>
    </div>
    <div v-if="detail.game_mode" class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Tipo de Conexión</div>
        <div class="detail-info-card__value">{{ detail.game_mode }}</div>
      </div>
    </div>
    <div v-if="detail.prize_pool_type" class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Tipo de bote</div>
        <div class="detail-info-card__value">{{ detail.prize_pool_type }}</div>
      </div>
    </div>
    <div v-if="detail.prize_pool > 0" class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Bote total de la sala</div>
        <div class="detail-info-card__value detail-info-card__value--win">
          <template v-if="detail.is_usd">{{ detail.prize_pool.toFixed(2) }} USD</template>
          <template v-else>{{ detail.currency_symbol }}{{ detail.prize_pool_local.toFixed(detail.decimal_places) }}</template>
        </div>
        <div v-if="!detail.is_usd" class="detail-info-card__sub">({{ detail.prize_pool.toFixed(2) }} USD)</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="detail-info-card">
        <div class="detail-info-card__label">Jugadores en la sala</div>
        <div class="detail-info-card__value"><i class="fas fa-users me-1 detail-info-card__icon"></i>{{ detail.total_players }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail-info-card {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  padding: 10px 12px;
  height: 100%;
}
.detail-info-card__label { font-size: 10px; text-transform: uppercase; color: var(--ds-text-muted); letter-spacing: .4px; margin-bottom: 2px; }
.detail-info-card__value { font-weight: 600; font-size: 13px; color: var(--ds-text); }
.detail-info-card__value--sm     { font-size: 11.5px; }
.detail-info-card__value--accent { color: #667eea; }
.detail-info-card__value--win    { color: #10b981; }
.detail-info-card__icon          { color: var(--ds-text-muted); }
.detail-info-card__sub   { font-size: 10px; color: var(--ds-text-muted); margin-top: 1px; }
</style>
