<script lang="ts">
// Module-level: computed once per bundle load, not per component mount
const confettiPieces = Array.from({ length: 40 }, (_, i) => ({
  left:     `${(i * 2.618033) % 100}%`,
  delay:    `${(i * 0.07) % 2}s`,
  duration: `${2.5 + (i % 5) * 0.3}s`,
  color:    ['#7b4fff','#2af598','#ffd700','#ff4466','#00c6ff','#ff8c00'][i % 6],
}))
</script>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { GameEndData } from './types'

interface Props {
  show: boolean
  gameEndData: GameEndData | null
  isMyWin: boolean
  userId?: string | null
  currencySym: string
  totalChips: number
  drawnNumbers: number[]
  chipLetter: (n: number) => string
}

const props = withDefaults(defineProps<Props>(), {
  userId: null,
})

const emit = defineEmits<{ close: [] }>()

// Fix #4: dedup en caso de duplicados por reconexión WebSocket
const uniqueDrawn = computed(() => [...new Set(props.drawnNumbers)])

// Fix #8: toggle para ver todas las fichas sorteadas
const showAllChips = ref(false)
const CHIPS_PREVIEW = 40
const visibleChips  = computed(() => showAllChips.value ? uniqueDrawn.value : uniqueDrawn.value.slice(0, CHIPS_PREVIEW))
const hiddenChips   = computed(() => Math.max(0, uniqueDrawn.value.length - CHIPS_PREVIEW))
</script>

<template>
  <Transition name="gm-fade">
    <div v-if="show" class="gm-overlay" @click.self="emit('close')">

      <div
        class="gm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="gm-title-text"
        :class="{
          'gm-modal--win': gameEndData?.hasWinner || isMyWin,
          'gm-modal--no-winner': gameEndData && !gameEndData.hasWinner,
        }"
      >
        <button class="gm-close" aria-label="Cerrar" @click="emit('close')"><i class="fas fa-times" /></button>

        <!-- Confetti dentro del modal: desaparece cuando el modal hace fade-out -->
        <template v-if="gameEndData?.hasWinner || isMyWin">
          <div
            v-for="(cf, i) in confettiPieces"
            :key="`cf-${i}`"
            class="gm-confetti"
            :style="{ left: cf.left, animationDelay: cf.delay, animationDuration: cf.duration, background: cf.color }"
          />
        </template>

        <div class="gm-header">
          <div v-if="gameEndData?.hasWinner || isMyWin" class="gm-trophy">
            <div class="gm-trophy__ring" />
            <span class="gm-trophy__icon">🏆</span>
          </div>
          <div v-else class="gm-trophy gm-trophy--end">
            <span class="gm-trophy__icon">🎱</span>
          </div>

          <h2 id="gm-title-text" class="gm-title">
            <template v-if="isMyWin">¡BINGO! ¡Ganaste!</template>
            <template v-else-if="gameEndData?.hasWinner">¡Tenemos Ganador!</template>
            <template v-else>Sorteo Finalizado</template>
          </h2>
          <p class="gm-subtitle">
            <template v-if="gameEndData">
              {{ gameEndData.totalDrawn }} fichas sorteadas de {{ totalChips }}
            </template>
          </p>

          <div v-if="gameEndData" class="gm-progress">
            <div class="gm-progress__bar" :style="{ width: `${(gameEndData.totalDrawn / totalChips) * 100}%` }" />
            <span class="gm-progress__label">{{ gameEndData.totalDrawn }}/{{ totalChips }}</span>
          </div>
        </div>

        <div v-if="gameEndData?.winners?.length" class="gm-winners">
          <div class="gm-winners__title">
            <i class="fas fa-star" /> {{ gameEndData.winners.length === 1 ? 'Ganador' : 'Ganadores' }}
          </div>
          <div
            v-for="(w, idx) in gameEndData.winners"
            :key="w.card_id"
            class="gm-winner-card"
            :class="{ 'gm-winner-card--me': String(w.user_id) === String(userId) }"
            :style="{ '--wd': `${idx * 60}ms` }"
          >
            <div class="gm-winner-card__rank">#{{ idx + 1 }}</div>
            <div class="gm-winner-card__info">
              <div class="gm-winner-card__name">
                {{ w.user_name }}
                <span v-if="String(w.user_id) === String(userId)" class="gm-winner-card__you">TÚ</span>
              </div>
              <div class="gm-winner-card__serial">Cartón {{ w.card_serial }}</div>
            </div>
            <div class="gm-winner-card__prize">
              <span class="gm-winner-card__sym">{{ currencySym }}</span>
              {{ w.prize.toFixed(2) }}
            </div>
          </div>
        </div>

        <div v-else-if="gameEndData && !gameEndData.hasWinner" class="gm-no-winner">
          <i class="fas fa-info-circle" />
          Ningún cartón completó el bingo en este sorteo.
        </div>

        <div v-if="uniqueDrawn.length > 0" class="gm-chips-summary">
          <div class="gm-chips-summary__header">
            <span class="gm-chips-summary__label">Fichas sorteadas</span>
            <span class="gm-chips-summary__count">{{ uniqueDrawn.length }}/{{ totalChips }}</span>
          </div>
          <div class="gm-chips-summary__grid" :class="{ 'gm-chips-summary__grid--expanded': showAllChips }">
            <div
              v-for="n in visibleChips"
              :key="`gs-${n}`"
              class="ig-chip ig-chip--sm"
              :class="`ig-chip--${chipLetter(n)}`"
            >
              <span class="ig-chip__letter">{{ chipLetter(n) }}</span>
              <span class="ig-chip__num">{{ n }}</span>
            </div>
          </div>
          <button v-if="!showAllChips && hiddenChips > 0" class="gm-chips-show-all" @click="showAllChips = true">
            <i class="fas fa-chevron-down" /> Ver todas ({{ hiddenChips }} más)
          </button>
        </div>

        <div class="gm-actions">
          <button class="gm-btn gm-btn--primary" @click="emit('close')">
            <i class="fas fa-check" /> Cerrar
          </button>
          <Link href="/bingo/game-rooms" class="gm-btn gm-btn--secondary">
            <i class="fas fa-door-open" /> Buscar otra sala
          </Link>
        </div>
      </div>

    </div>
  </Transition>
</template>

<style scoped>
/* ── Shared overlay / modal shell (non-scoped patterns that need to work here too) ── */
.gm-overlay {
  position: fixed; inset: 0; z-index: 1200;
  background: rgba(4,6,20,0.82); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
  overflow: hidden;
}
.gm-modal {
  background: linear-gradient(160deg, rgba(11,18,48,0.98) 0%, rgba(6,10,30,0.98) 100%);
  border: 1px solid rgba(80,120,255,0.18); border-radius: 22px;
  box-shadow: 0 24px 80px rgba(0,0,0,0.7), 0 1px 0 rgba(255,255,255,0.04) inset;
  width: 100%; max-width: 480px; overflow: hidden; position: relative;
  max-height: 90vh; display: flex; flex-direction: column;
  animation: gmModalIn .45s cubic-bezier(.16,1,.3,1) both;
}
@keyframes gmModalIn {
  from { opacity: 0; transform: translateY(10px) scale(.98); }
  to   { opacity: 1; transform: none; }
}
.gm-modal--win    { border-color: rgba(255,200,0,0.35); box-shadow: 0 24px 80px rgba(0,0,0,0.7), 0 0 60px rgba(255,200,0,0.08), 0 1px 0 rgba(255,255,255,0.04) inset; }
.gm-modal--no-winner { border-color: rgba(80,120,255,0.18); }

.gm-close {
  position: absolute; top: 14px; right: 14px;
  width: 30px; height: 30px; border-radius: 50%;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
  color: #5a7acc; font-size: 12px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s, color 0.2s; z-index: 2;
}
.gm-close:hover { background: rgba(255,255,255,0.1); color: #a0c0ff; }

/* ── Header ── */
.gm-header { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 36px 24px 20px; }

.gm-trophy { position: relative; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; }
.gm-trophy__ring {
  position: absolute; inset: 0; border-radius: 50%;
  border: 2px solid rgba(255,200,0,0.32);
  animation: trophy-ring 2.8s ease-in-out infinite;
}
@keyframes trophy-ring { 0%,100% { transform: scale(1); opacity: 0.55; } 50% { transform: scale(1.1); opacity: 0.85; } }
.gm-trophy__icon { font-size: 44px; position: relative; z-index: 1; }
.gm-trophy--end .gm-trophy__ring { border-color: rgba(80,120,255,0.22); animation: none; }

.gm-title { font-family: 'Anton', sans-serif; font-size: 30px; color: #e0eaff; text-align: center; letter-spacing: 1px; text-wrap: balance; }
.gm-modal--win .gm-title { background: linear-gradient(130deg, #ffe566, #ffb800, #ff8800); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

.gm-subtitle { font-size: 12px; color: #4a68a8; text-align: center; }

.gm-progress { width: 100%; max-width: 280px; }
.gm-progress__bar { height: 4px; background: linear-gradient(90deg, #4a7fff, #2af598); border-radius: 4px; transition: width 0.9s cubic-bezier(.16,1,.3,1); }
.gm-progress__label { display: block; text-align: right; font-size: 9px; color: #3a5080; margin-top: 3px; font-family: 'Courier New', monospace; }

/* ── Winners list ── */
.gm-winners { padding: 0 20px 16px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
.gm-winners::-webkit-scrollbar { width: 4px; }
.gm-winners::-webkit-scrollbar-thumb { background: rgba(80,120,255,0.2); border-radius: 2px; }
.gm-winners__title { font-size: 9.5px; font-weight: 700; color: #4a68a8; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 5px; margin-bottom: 4px; }
.gm-winners__title i { color: #ffd700; font-size: 8px; }

.gm-winner-card {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; border-radius: 12px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(80,120,255,0.1);
  animation: gmWinnerIn .4s cubic-bezier(.16,1,.3,1) both;
  animation-delay: var(--wd, 0ms);
}
@keyframes gmWinnerIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: none; }
}
.gm-winner-card--me {
  background: rgba(42,245,152,0.07); border-color: rgba(42,245,152,0.3);
  box-shadow: 0 0 0 1px rgba(42,245,152,0.08);
  animation: gmWinnerIn .4s cubic-bezier(.16,1,.3,1) both, gmMeGlow 2.4s ease-in-out .4s infinite;
}
@keyframes gmMeGlow {
  0%, 100% { box-shadow: 0 0 0 1px rgba(42,245,152,0.08); }
  50%      { box-shadow: 0 0 16px rgba(42,245,152,0.18), 0 0 0 1px rgba(42,245,152,0.15); }
}
@media (prefers-reduced-motion: reduce) {
  .gm-winner-card, .gm-winner-card--me { animation: none; }
}
.gm-winner-card__rank { font-size: 11px; font-weight: 700; color: #4a68a8; width: 24px; flex-shrink: 0; }
.gm-winner-card__info { flex: 1; min-width: 0; }
.gm-winner-card__name { font-size: 13px; font-weight: 700; color: #c8d8ff; display: flex; align-items: center; gap: 6px; }
.gm-winner-card__serial { font-size: 10px; color: #3a5080; margin-top: 1px; }
.gm-winner-card__prize { font-size: 14px; font-weight: 800; color: #ffd700; font-family: 'Courier New', monospace; flex-shrink: 0; }
.gm-winner-card__sym { font-size: 10px; font-weight: 700; margin-right: 2px; }
.gm-winner-card__you { font-size: 8px; font-weight: 900; color: #2af598; background: rgba(42,245,152,0.12); border: 1px solid rgba(42,245,152,0.3); border-radius: 4px; padding: 1px 5px; }

.gm-no-winner { padding: 16px 20px; display: flex; align-items: center; gap: 8px; color: #4a68a8; font-size: 12px; }
.gm-no-winner i { font-size: 16px; }

/* ── Chips summary ── */
.gm-chips-summary { padding: 0 20px 16px; flex-shrink: 0; }
.gm-chips-summary__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.gm-chips-summary__label { font-size: 9px; font-weight: 700; color: #3a5080; text-transform: uppercase; letter-spacing: 1px; }
.gm-chips-summary__count { font-size: 9px; font-weight: 700; color: #2a3860; font-family: 'Courier New', monospace; }
.gm-chips-summary__grid {
  display: flex; flex-wrap: wrap; gap: 4px;
  max-height: 120px; overflow: hidden;
  position: relative;
}
.gm-chips-summary__grid::after {
  content: ''; position: absolute; bottom: 0; left: 0; right: 0;
  height: 36px;
  background: linear-gradient(transparent, rgba(6,10,30,0.95));
  pointer-events: none;
}
.gm-chips-summary__grid--expanded { max-height: 200px; overflow-y: auto; }
.gm-chips-summary__grid--expanded::after { display: none; }
.gm-chips-summary__grid--expanded::-webkit-scrollbar { width: 4px; }
.gm-chips-summary__grid--expanded::-webkit-scrollbar-thumb { background: rgba(80,120,255,0.2); border-radius: 2px; }
.gm-chips-show-all {
  display: flex; align-items: center; gap: 5px; justify-content: center;
  width: 100%; margin-top: 6px; padding: 4px;
  background: none; border: none; cursor: pointer;
  color: #4a6aaa; font-size: 10px; font-weight: 700; transition: color 0.15s;
}
.gm-chips-show-all:hover { color: #7c9dff; }
.gm-chips-show-all i { font-size: 8px; }

/* ── Chip styles (copied from DrawnBoard so they work here without import) ── */
.ig-chip {
  display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
  border-radius: 50%; border: 2px solid rgba(255,255,255,0.25);
  font-weight: 700; line-height: 1; flex-shrink: 0; position: relative;
  box-shadow: 0 3px 10px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -2px 0 rgba(0,0,0,0.2);
  cursor: default; user-select: none;
}
.ig-chip--sm { width: 30px; height: 30px; }
.ig-chip--sm .ig-chip__letter { font-size: 6px; line-height: 1; }
.ig-chip--sm .ig-chip__num   { font-size: 10px; line-height: 1.1; font-family: 'Anton', sans-serif; }
.ig-chip__letter { text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; color: rgba(255,255,255,0.85); }
.ig-chip__num    { font-weight: 900; color: #fff; }
.ig-chip--B {
  background: radial-gradient(circle at 38% 32%, #5ab8ff, #1a7de0 60%, #0d55a8 100%);
  box-shadow: 0 3px 10px rgba(26,125,224,0.55), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -2px 0 rgba(0,0,0,0.2);
}
.ig-chip--I {
  background: radial-gradient(circle at 38% 32%, #b87cff, #7b3fd0 60%, #4e1fa0 100%);
  box-shadow: 0 3px 10px rgba(123,63,208,0.55), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -2px 0 rgba(0,0,0,0.2);
}
.ig-chip--N {
  background: radial-gradient(circle at 38% 32%, #6edd6e, #28a428 60%, #186018 100%);
  box-shadow: 0 3px 10px rgba(40,164,40,0.5), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -2px 0 rgba(0,0,0,0.2);
}
.ig-chip--G {
  background: radial-gradient(circle at 38% 32%, #ffe066, #e0a000 60%, #a06800 100%);
  box-shadow: 0 3px 10px rgba(224,160,0,0.5), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -2px 0 rgba(0,0,0,0.2);
}
.ig-chip--O {
  background: radial-gradient(circle at 38% 32%, #ff7070, #d42020 60%, #8c0e0e 100%);
  box-shadow: 0 3px 10px rgba(212,32,32,0.55), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -2px 0 rgba(0,0,0,0.2);
}

/* ── Actions ── */
.gm-actions { padding: 0 20px 20px; display: flex; flex-direction: column; gap: 8px; }
.gm-btn {
  width: 100%; padding: 11px; border-radius: 12px; font-size: 13px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: all 0.2s ease; border: 1px solid; text-decoration: none;
}
.gm-btn--primary { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.25); color: #7c9dff; }
.gm-btn--primary:hover { background: rgba(100,140,255,0.18); color: #b0c8ff; }
.gm-btn--secondary { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.09); color: #4a6aaa; }
.gm-btn--secondary:hover { background: rgba(255,255,255,0.06); color: #7c9dff; }

/* ── Confetti — position: absolute para quedar dentro del modal (overflow: hidden) ── */
.gm-confetti {
  position: absolute; width: 8px; height: 8px; border-radius: 2px; top: -10px;
  animation: confetti-fall linear infinite;
  pointer-events: none; z-index: 0;
}
@keyframes confetti-fall {
  0%   { transform: translateY(-10px) rotate(0deg); opacity: 1; }
  80%  { opacity: 0.8; }
  100% { transform: translateY(calc(100vh + 20px)) rotate(720deg); opacity: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .gm-confetti { animation: none; opacity: 0; }
}

/* ── Fade transition ── */
.gm-fade-enter-active, .gm-fade-leave-active { transition: opacity 0.25s ease; }
.gm-fade-enter-from, .gm-fade-leave-to { opacity: 0; }

/* ── Responsive ── */
@media (max-width: 480px) {
  .gm-modal { border-radius: 16px; }
  .gm-title { font-size: 24px; }
  .gm-trophy__icon { font-size: 36px; }
  .gm-header { padding: 28px 16px 16px; }
  .gm-chips-summary__grid { max-height: 80px; }
}
</style>
