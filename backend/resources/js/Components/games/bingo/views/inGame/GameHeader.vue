<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import HeaderPrizeCountdown from './HeaderPrizeCountdown.vue'
import TopCardsChips        from './TopCardsChips.vue'
import HeaderStatsRow       from './HeaderStatsRow.vue'
import HeaderDevRibbon      from './HeaderDevRibbon.vue'

interface TopCard {
  id:      string
  serial:  string
  missing: number
}

interface Props {
  prizePoolType: string
  currencySym: string
  prizeFormatted: string
  prizeFlash: boolean
  hasTime: boolean
  cd: { time: { days: number; hours: number; minutes: number; seconds: number } } | null
  isFinished: boolean
  livePlayers: number
  totalWinners: number
  playersFlash: boolean
  cardCount: number
  rounds?: number
  round?: number
  bingoPattern?: string
  muted: boolean
  isLocal: boolean
  devResetting: boolean
  /** Hasta 3 cartones activos más cerca de completar (serial + fichas faltantes). */
  topCards?: TopCard[]
}

withDefaults(defineProps<Props>(), {
  rounds: 0,
  round: 1,
  bingoPattern: '',
  topCards: () => [],
})

const emit = defineEmits<{
  toggleMute: []
  openPlayers: []
  devReset: []
  focusCard: [id: string]
}>()
</script>

<template>
  <div class="gh-root">
    <!-- Accent line top -->
    <div class="gh-accent" />

    <!-- ══ Main row ══ -->
    <div class="gh-main">

      <HeaderPrizeCountdown
        :prize-pool-type="prizePoolType"
        :currency-sym="currencySym"
        :prize-formatted="prizeFormatted"
        :prize-flash="prizeFlash"
        :has-time="hasTime"
        :cd="cd"
        :is-finished="isFinished"
      />

      <TopCardsChips
        :top-cards="topCards"
        :is-finished="isFinished"
        @focus-card="(id) => emit('focusCard', id)"
      />

      <!-- Actions -->
      <div class="gh-actions">
        <Link v-if="isFinished" href="/bingo/game-rooms" class="gh-btn gh-btn--rooms">
          <i class="fas fa-door-open" /> Volver
        </Link>
        <button
          class="gh-btn gh-btn--icon"
          :class="{ 'gh-btn--muted': muted }"
          :title="muted ? 'Activar sonido' : 'Silenciar'"
          :aria-label="muted ? 'Activar sonido' : 'Silenciar'"
          :aria-pressed="muted"
          @click="emit('toggleMute')"
        >
          <i :class="muted ? 'fas fa-volume-mute' : 'fas fa-volume-up'" />
        </button>
      </div>

    </div>

    <!-- ── separator ── -->
    <div class="gh-sep" />

    <!-- ══ Stats row ══ -->
    <HeaderStatsRow
      :players-flash="playersFlash"
      :live-players="livePlayers"
      :total-winners="totalWinners"
      :card-count="cardCount"
      :rounds="rounds"
      :round="round"
      :bingo-pattern="bingoPattern"
      @open-players="emit('openPlayers')"
    />

    <!-- ══ DEV ribbon ══ -->
    <HeaderDevRibbon
      v-if="isLocal"
      :dev-resetting="devResetting"
      @dev-reset="emit('devReset')"
    />
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   ROOT
═══════════════════════════════════════════ */
.gh-root {
  display: flex; flex-direction: column;
  background: linear-gradient(160deg, rgba(10,16,44,0.97) 0%, rgba(5,9,28,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.14);
  border-radius: 16px; overflow: hidden;
  backdrop-filter: blur(20px);
  box-shadow: 0 6px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
}

/* Accent line */
.gh-accent {
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(80,140,255,0.55) 18%,
    rgba(42,245,152,0.75) 50%,
    rgba(123,79,255,0.55) 82%,
    transparent 100%);
}

/* ═══════════════════════════════════════════
   MAIN ROW
═══════════════════════════════════════════ */
.gh-main {
  display: flex; align-items: center;
  padding: 10px 18px; gap: 0; min-height: 60px;
}

/* Actions */
.gh-actions { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.gh-btn {
  display: inline-flex; align-items: center; gap: 5px;
  border-radius: 20px; font-size: 11px; font-weight: 700;
  cursor: pointer; transition: all 0.16s ease; border: 1px solid; text-decoration: none;
}
.gh-btn--rooms {
  padding: 6px 14px;
  background: rgba(100,140,255,0.09); border-color: rgba(100,140,255,0.22); color: #7c9dff;
}
.gh-btn--rooms:hover { background: rgba(100,140,255,0.18); color: #b0c8ff; box-shadow: 0 3px 12px rgba(100,140,255,0.18); }
.gh-btn--icon {
  width: 34px; height: 34px; padding: 0; justify-content: center; border-radius: 50%;
  background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.09); color: #6080b0; font-size: 13px;
}
.gh-btn--icon:hover { background: rgba(255,255,255,0.09); color: #a0c0ff; }
.gh-btn--muted { color: #2a3460; border-color: rgba(255,255,255,0.05); }
/* Feedback táctil tipo app nativa — el :hover no existe en touch, así que el
   :active es la única señal de "toqué esto" en móvil. */
.gh-btn:active { transform: scale(0.94); }

/* ═══════════════════════════════════════════
   SEPARATOR
═══════════════════════════════════════════ */
.gh-sep { height: 1px; background: rgba(80,120,255,0.08); }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 768px) {
  .gh-main { padding: 10px 14px; }
}
@media (max-width: 560px) {
  .gh-main { padding: 10px 12px; min-height: auto; gap: 0; flex-wrap: wrap; }
}
@media (max-width: 380px) {
  .gh-main { padding: 8px 10px; }
}
</style>
