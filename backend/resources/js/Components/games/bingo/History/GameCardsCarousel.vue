<script setup lang="ts">
import BingoCardDisplay from '@js/Components/games/bingo/Card/BingoCardDisplay.vue'
import { isRoomOngoing } from '@js/composables/games/bingo/history/useMyGamesFormatters'
import type { GameHistoryDetail, GameHistoryPlayerCard } from '@/js/types/games/bingo/history/my-games'

const props = defineProps<{
  detail: GameHistoryDetail
  activeCardTab: 'all' | 'winners'
  displayCards: GameHistoryPlayerCard[]
  cardIndex: number
  activeCard: GameHistoryPlayerCard | null
}>()

const emit = defineEmits<{
  'update:activeCardTab': [tab: 'all' | 'winners']
  'update:cardIndex': [index: number]
  prev: []
  next: []
}>()
</script>

<template>
  <div v-if="detail.player_cards.length > 0">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="section-heading mb-0">
        <i class="fas fa-th"></i>Mis cartones
        <span class="badge bg-light text-dark border ms-2">{{ detail.player_cards.length }}</span>
      </h6>
      <div v-if="detail.player_cards.some(c => c.is_winner)" class="btn-group btn-group-sm">
        <button
          class="btn"
          :class="activeCardTab === 'winners' ? 'btn-warning text-dark' : 'btn-outline-secondary'"
          @click="emit('update:activeCardTab', 'winners')"
        >
          <i class="fas fa-trophy me-1"></i>Ganadores
        </button>
        <button
          class="btn"
          :class="activeCardTab === 'all' ? 'btn-secondary' : 'btn-outline-secondary'"
          @click="emit('update:activeCardTab', 'all')"
        >
          Todos
        </button>
      </div>
    </div>

    <div v-if="activeCard" class="card-carousel">
      <button
        v-if="displayCards.length > 1"
        class="card-carousel__nav card-carousel__nav--prev"
        aria-label="Cartón anterior"
        @click="emit('prev')"
      ><i class="fas fa-chevron-left"></i></button>

      <div class="card-carousel__stage">
        <BingoCardDisplay
          :key="activeCard.id"
          :cardMatrix="activeCard.card_matrix"
          :variant="detail.game_variant"
          :serial="activeCard.serial"
          :isWinner="activeCard.is_winner"
        />
      </div>

      <button
        v-if="displayCards.length > 1"
        class="card-carousel__nav card-carousel__nav--next"
        aria-label="Cartón siguiente"
        @click="emit('next')"
      ><i class="fas fa-chevron-right"></i></button>
    </div>

    <div v-if="displayCards.length > 1" class="card-carousel__dots">
      <button
        v-for="(card, i) in displayCards"
        :key="card.id"
        class="card-carousel__dot"
        :class="{ 'card-carousel__dot--active': i === cardIndex, 'card-carousel__dot--winner': card.is_winner }"
        :aria-label="`Ver cartón ${i + 1}`"
        @click="emit('update:cardIndex', i)"
      ></button>
    </div>

    <div v-if="!activeCard" class="text-center text-muted small py-3">
      Sin cartones ganadores en esta partida.
    </div>
  </div>

  <div v-else-if="isRoomOngoing(detail.room_status)" class="alert alert-primary py-2">
    <i class="fas fa-hourglass-half me-2"></i>La partida sigue en curso — aún no hay resultado final.
  </div>
  <div v-else-if="!detail.won" class="alert alert-secondary py-2">
    <i class="fas fa-info-circle me-2"></i>No obtuviste premios en esta partida.
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

/* ── Carrusel de cartones ─────────────────────────────────
 * Se muestra un cartón a la vez (más legible que una grilla apretada
 * cuando el jugador compró varios), con flechas + puntos indicadores;
 * el punto del cartón ganador se resalta en dorado para ubicarlo rápido. ── */
.card-carousel {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.card-carousel__stage {
  width: 100%;
  max-width: 280px;
  display: flex;
  justify-content: center;
}
.card-carousel__nav {
  flex-shrink: 0;
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border-2);
  color: var(--ds-text-2);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px;
  cursor: pointer;
  transition: background .2s, border-color .2s, color .2s, transform .15s;
}
.card-carousel__nav:hover {
  background: rgba(102,126,234,.15);
  border-color: #667eea;
  color: #667eea;
  transform: scale(1.05);
}
.card-carousel__dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 12px;
}
.card-carousel__dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--ds-border-2);
  border: none;
  cursor: pointer;
  padding: 0;
  transition: background .2s, transform .15s;
}
.card-carousel__dot:hover { transform: scale(1.2); }
.card-carousel__dot--active { background: #667eea; transform: scale(1.3); }
.card-carousel__dot--winner { background: #f59e0b; }
.card-carousel__dot--winner.card-carousel__dot--active { background: #f59e0b; }
</style>
