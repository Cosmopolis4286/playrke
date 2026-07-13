<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  drawnNumbers: number[]
  totalChips: number
  chipLetter: (n: number) => string
  newBallFlash?: boolean
  highlightNumbers?: number[]
}

const props = withDefaults(defineProps<Props>(), { newBallFlash: false, highlightNumbers: () => [] })
const highlightSet = computed(() => new Set<number>(props.highlightNumbers))

const last       = computed(() => props.drawnNumbers[props.drawnNumbers.length - 1])
const lastLetter = computed(() => last.value != null ? props.chipLetter(last.value) : '')
const pct        = computed(() => Math.round((props.drawnNumbers.length / props.totalChips) * 100))

// Grid chips en orden cronológico inverso, sin incluir el último (ya destacado arriba)
const historyChips    = computed(() => [...props.drawnNumbers].reverse().slice(1))
// Los 5 más recientes previos al actual (para la sección "anteriores")
const recentChips     = computed(() => historyChips.value.slice(0, 5))
// Versión lazy: muestra las últimas 30 hasta que el usuario expande
const LAZY_LIMIT      = 30
const showAll         = ref(false)
const visibleHistory  = computed(() => showAll.value ? historyChips.value : historyChips.value.slice(0, LAZY_LIMIT))
const hiddenCount     = computed(() => Math.max(0, historyChips.value.length - LAZY_LIMIT))

// Agrupar por letra — dinámico, no hardcodeado a BINGO_75
const colCounts = computed(() => {
  const counts: Record<string, number> = {}
  for (const n of props.drawnNumbers) {
    const ltr = props.chipLetter(n)
    counts[ltr] = (counts[ltr] ?? 0) + 1
  }
  return counts
})

// Letras únicas en orden de aparición del sorteo (para las pills)
const colLetters = computed(() => {
  const seen = new Set<string>()
  const order: string[] = []
  for (const n of props.drawnNumbers) {
    const ltr = props.chipLetter(n)
    if (!seen.has(ltr)) { seen.add(ltr); order.push(ltr) }
  }
  return order.sort()
})

// Tooltip hover sobre chip
const hoveredChip = ref<number | null>(null)

// Historial completo colapsado por defecto: libera espacio vertical para los
// cartones, que son la prioridad real del jugador durante la partida.
const showHistory = ref(false)
</script>

<template>
  <Transition name="db-reveal">
    <div v-if="drawnNumbers.length > 0" class="db-root">

      <!-- ═══════════════════════════════════════════
           HERO: última bola + progreso
      ════════════════════════════════════════════ -->
      <div class="db-hero">

        <!-- Bola principal con efecto glow -->
        <div class="db-hero__ball-wrap">
          <div class="db-hero__glow" :class="`db-glow--${lastLetter}`" />
          <div class="db-hero__ring db-hero__ring--1" :class="`db-ring--${lastLetter}`" />
          <div class="db-hero__ring db-hero__ring--2" :class="`db-ring--${lastLetter}`" />
          <div class="db-chip db-chip--hero" :class="`db-chip--${lastLetter}`">
            <span class="db-chip__ltr">{{ lastLetter }}</span>
            <span class="db-chip__num">{{ last }}</span>
          </div>
        </div>
        <!-- aria-live: anuncia la nueva bola a lectores de pantalla -->
        <div aria-live="polite" aria-atomic="true" class="db-sr-only">{{ lastLetter }}{{ last }}</div>

        <!-- Número grande + barra + resumen columnas -->
        <div class="db-hero__meta">
          <div class="db-hero__num-row">
            <span class="db-hero__big-num" :class="`db-num--${lastLetter}`">{{ last }}</span>
            <div class="db-hero__col-pills">
              <div
                v-for="l in colLetters"
                :key="l"
                class="db-col-pill"
                :class="`db-col-pill--${l}`"
              >
                <span class="db-col-pill__letter">{{ l }}</span>
                <span class="db-col-pill__count">{{ colCounts[l] ?? 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Barra de progreso con etiqueta -->
          <div class="db-progress">
            <div class="db-progress__track">
              <div class="db-progress__fill" :class="{ 'db-progress__fill--flash': newBallFlash }" :style="{ width: `${pct}%` }" />
              <div class="db-progress__shine" :style="{ left: `${pct}%` }" />
            </div>
            <div class="db-progress__labels">
              <span class="db-progress__drawn">{{ drawnNumbers.length }} sorteadas</span>
              <span class="db-progress__pct">{{ pct }}%</span>
              <span class="db-progress__total">{{ totalChips }} total</span>
            </div>
          </div>
        </div>

        <!-- Recientes (últimas 5 previas a la actual) -->
        <div class="db-recent">
          <span class="db-recent__label">anteriores</span>
          <div class="db-recent__row">
            <div
              v-for="(n, i) in recentChips"
              :key="`rec-${n}`"
              class="db-chip db-chip--md"
              :class="[`db-chip--${chipLetter(n)}`, { 'db-chip--pending': highlightSet.has(n) }]"
              :style="{ opacity: 1 - i * 0.12 }"
            >
              <span class="db-chip__ltr">{{ chipLetter(n) }}</span>
              <span class="db-chip__num">{{ n }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════
           HISTORIAL: todas menos la actual — colapsado por
           defecto para no restarle espacio a los cartones
      ════════════════════════════════════════════ -->
      <button
        v-if="historyChips.length > 0"
        class="db-history-toggle"
        type="button"
        @click="showHistory = !showHistory"
      >
        <i class="fas fa-history" />
        {{ showHistory ? 'Ocultar historial' : `Ver historial completo (${historyChips.length})` }}
        <i class="fas fa-chevron-down db-history-toggle__chev" :class="{ 'db-history-toggle__chev--open': showHistory }" />
      </button>

      <!-- En mobile: scroll horizontal compacto, siempre visible sin toggle -->
      <div v-if="historyChips.length > 0" class="db-history db-history--mobile-scroll">
        <div
          v-for="n in historyChips"
          :key="`ms-${n}`"
          class="db-chip db-chip--sm"
          :class="`db-chip--${chipLetter(n)}`"
        >
          <span class="db-chip__ltr">{{ chipLetter(n) }}</span>
          <span class="db-chip__num">{{ n }}</span>
        </div>
      </div>

      <div v-if="showHistory" class="db-history db-history--desktop">
        <div
          v-for="n in visibleHistory"
          :key="n"
          class="db-chip db-chip--sm"
          :class="[`db-chip--${chipLetter(n)}`, { 'db-chip--pending': highlightSet.has(n) }]"
          @mouseenter="hoveredChip = n"
          @mouseleave="hoveredChip = null"
        >
          <span class="db-chip__ltr">{{ chipLetter(n) }}</span>
          <span class="db-chip__num">{{ n }}</span>
          <div v-if="hoveredChip === n" class="db-chip__tip">{{ chipLetter(n) }}{{ n }}</div>
        </div>
        <button
          v-if="!showAll && hiddenCount > 0"
          class="db-show-all-btn"
          type="button"
          @click="showAll = true"
        >+ {{ hiddenCount }} más</button>
      </div>

    </div>
  </Transition>
</template>

<style scoped>
/* Visually hidden but readable by screen readers */
.db-sr-only {
  position: absolute; width: 1px; height: 1px;
  padding: 0; margin: -1px; overflow: hidden;
  clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}

/* Chip del cartón activo seleccionado — resaltado en el historial */
.db-chip--pending {
  outline: 2px solid rgba(42,245,152,0.7);
  outline-offset: 1px;
  box-shadow: 0 0 8px rgba(42,245,152,0.3);
}

/* ══════════════════════════════════════════════════════
   ROOT
══════════════════════════════════════════════════════ */
.db-root {
  background: linear-gradient(160deg, rgba(9,14,40,0.97) 0%, rgba(5,8,24,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.13);
  border-radius: 16px; overflow: hidden;
  display: flex; flex-direction: column; gap: 0;
}

/* ══════════════════════════════════════════════════════
   HERO
══════════════════════════════════════════════════════ */
.db-hero {
  display: flex; align-items: center; gap: 14px;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(80,120,255,0.07);
}

/* Bola principal */
.db-hero__ball-wrap {
  position: relative; flex-shrink: 0;
  width: 48px; height: 48px;
  display: flex; align-items: center; justify-content: center;
}
.db-hero__glow {
  position: absolute; inset: -10px; border-radius: 50%;
  filter: blur(16px); opacity: 0.5;
  animation: glow-pulse 2s ease-in-out infinite;
}
@keyframes glow-pulse { 0%,100% { opacity: 0.35; transform: scale(0.9); } 50% { opacity: 0.6; transform: scale(1.1); } }

.db-glow--B { background: var(--bc0-solid); }
.db-glow--I { background: var(--bc1-solid); }
.db-glow--N { background: var(--bc2-solid); }
.db-glow--G { background: var(--bc3-solid); }
.db-glow--O { background: var(--bc4-solid); }

.db-hero__ring {
  position: absolute; border-radius: 50%; border: 1px solid;
  animation: ring-out 2.2s ease-out infinite;
  pointer-events: none;
}
.db-hero__ring--1 { inset: -6px; animation-delay: 0s; }
.db-hero__ring--2 { inset: -14px; animation-delay: 0.7s; }
@keyframes ring-out { 0% { opacity: 0.5; transform: scale(0.85); } 100% { opacity: 0; transform: scale(1.4); } }

.db-ring--B { border-color: var(--bc0-glow); }
.db-ring--I { border-color: var(--bc1-glow); }
.db-ring--N { border-color: var(--bc2-glow); }
.db-ring--G { border-color: var(--bc3-glow); }
.db-ring--O { border-color: var(--bc4-glow); }

/* Meta: número grande + progreso */
.db-hero__meta { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }

.db-hero__num-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

.db-hero__big-num {
  display: inline-block;
  font-family: 'Anton', sans-serif; font-size: 38px; line-height: 1;
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text; letter-spacing: -1px;
  filter: drop-shadow(0 2px 10px currentColor);
}
.db-num--B { background: linear-gradient(135deg, var(--bc0-light), var(--bc0-solid)); }
.db-num--I { background: linear-gradient(135deg, var(--bc1-light), var(--bc1-solid)); }
.db-num--N { background: linear-gradient(135deg, var(--bc2-light), var(--bc2-solid)); }
.db-num--G { background: linear-gradient(135deg, var(--bc3-light), var(--bc3-solid)); }
.db-num--O { background: linear-gradient(135deg, var(--bc4-light), var(--bc4-solid)); }

/* Columnas BINGO mini pills */
.db-hero__col-pills { display: flex; gap: 4px; flex-wrap: wrap; }
.db-col-pill {
  display: flex; flex-direction: column; align-items: center;
  padding: 3px 7px; border-radius: 8px; gap: 0;
  border: 1px solid transparent; min-width: 26px;
  transition: transform 0.2s ease;
}
.db-col-pill:hover { transform: translateY(-1px); }
.db-col-pill__letter { font-size: 8px; font-weight: 900; letter-spacing: 0.5px; opacity: 0.8; }
.db-col-pill__count  { font-size: 13px; font-weight: 800; line-height: 1; font-family: 'Courier New', monospace; }

.db-col-pill--B { background: var(--bc0-tint); border-color: var(--bc0-glow); }
.db-col-pill--B .db-col-pill__letter { color: var(--bc0-light); }
.db-col-pill--B .db-col-pill__count  { color: var(--bc0-light); }

.db-col-pill--I { background: var(--bc1-tint); border-color: var(--bc1-glow); }
.db-col-pill--I .db-col-pill__letter { color: var(--bc1-light); }
.db-col-pill--I .db-col-pill__count  { color: var(--bc1-light); }

.db-col-pill--N { background: var(--bc2-tint); border-color: var(--bc2-glow); }
.db-col-pill--N .db-col-pill__letter { color: var(--bc2-light); }
.db-col-pill--N .db-col-pill__count  { color: var(--bc2-light); }

.db-col-pill--G { background: var(--bc3-tint); border-color: var(--bc3-glow); }
.db-col-pill--G .db-col-pill__letter { color: var(--bc3-light); }
.db-col-pill--G .db-col-pill__count  { color: var(--bc3-light); }

.db-col-pill--O { background: var(--bc4-tint); border-color: var(--bc4-glow); }
.db-col-pill--O .db-col-pill__letter { color: var(--bc4-light); }
.db-col-pill--O .db-col-pill__count  { color: var(--bc4-light); }

/* Barra de progreso */
.db-progress { display: flex; flex-direction: column; gap: 5px; }
.db-progress__track {
  height: 5px; background: rgba(255,255,255,0.05); border-radius: 5px;
  position: relative; overflow: visible;
}
.db-progress__fill {
  height: 100%; border-radius: 5px;
  background: linear-gradient(90deg, #4a7fff 0%, #2af598 100%);
  transition: width 0.7s cubic-bezier(0.4,0,0.2,1);
  position: relative;
}
.db-progress__fill--flash {
  animation: progress-flash 0.6s ease-out;
}
@keyframes progress-flash {
  0%   { filter: brightness(1); }
  30%  { filter: brightness(2.2) saturate(1.5); }
  100% { filter: brightness(1); }
}
.db-progress__fill::after {
  content: ''; position: absolute; right: 0; top: 50%;
  transform: translate(50%, -50%);
  width: 9px; height: 9px; border-radius: 50%;
  background: #fff; box-shadow: 0 0 8px rgba(42,245,152,0.8);
}
.db-progress__shine {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 30px; height: 10px; border-radius: 5px;
  background: rgba(255,255,255,0.25); filter: blur(4px);
  pointer-events: none; margin-left: -30px;
  transition: left 0.7s cubic-bezier(0.4,0,0.2,1);
}
.db-progress__labels {
  display: flex; align-items: center; justify-content: space-between;
  font-size: 9.5px; font-weight: 700; font-family: 'Courier New', monospace;
}
.db-progress__drawn { color: #4a7fff; }
.db-progress__pct   { color: #2af598; }
.db-progress__total { color: #2a3860; }

/* Recientes */
.db-recent { display: flex; flex-direction: column; gap: 5px; flex-shrink: 0; }
.db-recent__label {
  font-size: 8px; font-weight: 700; color: #2a3860; letter-spacing: 1.4px;
  text-transform: uppercase; text-align: center;
}
.db-recent__row { display: flex; gap: 3px; align-items: center; }

/* ══════════════════════════════════════════════════════
   HISTORY TOGGLE
══════════════════════════════════════════════════════ */
.db-history-toggle {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 6px 10px; border: none;
  background: rgba(0,0,0,0.1); border-top: 1px solid rgba(80,120,255,0.06);
  color: #4a6aaa; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px;
  cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
}
.db-history-toggle:hover { background: rgba(80,120,255,0.06); color: #7c9dff; }
.db-history-toggle__chev { font-size: 8px; transition: transform 0.2s ease; }
.db-history-toggle__chev--open { transform: rotate(180deg); }

/* ══════════════════════════════════════════════════════
   HISTORY GRID
══════════════════════════════════════════════════════ */
.db-history {
  display: flex; flex-wrap: wrap; gap: 3px;
  padding: 10px 14px 12px;
  background: rgba(0,0,0,0.12);
}

.db-show-all-btn {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 3px 10px; border-radius: 8px;
  border: 1px solid rgba(80,120,255,0.2); background: rgba(80,120,255,0.06);
  color: #4a7fff; font-size: 9px; font-weight: 700; cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
  align-self: center;
}
.db-show-all-btn:hover { background: rgba(80,120,255,0.14); color: #7ca8ff; }

/* Mobile: scroll horizontal compacto, siempre visible */
.db-history--mobile-scroll {
  display: none;
}
.db-history--desktop {
  display: flex;
}

@media (max-width: 640px) {
  .db-history--mobile-scroll {
    display: flex; flex-wrap: nowrap; overflow-x: auto;
    padding: 6px 12px 8px; gap: 3px; background: rgba(0,0,0,0.10);
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .db-history--mobile-scroll::-webkit-scrollbar { display: none; }
  .db-history-toggle { display: none; }
  .db-history--desktop { display: none; }
}

/* ══════════════════════════════════════════════════════
   CHIP — base
══════════════════════════════════════════════════════ */
.db-chip {
  display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
  border-radius: 50%; border: 1.5px solid rgba(255,255,255,0.22);
  flex-shrink: 0; line-height: 1; position: relative;
  box-shadow: 0 2px 8px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -1px 0 rgba(0,0,0,0.25);
  cursor: default; user-select: none;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.db-chip:hover { transform: scale(1.18); z-index: 2; }

.db-chip__ltr { text-transform: uppercase; font-weight: 800; color: rgba(255,255,255,0.82); letter-spacing: 0.3px; }
.db-chip__num { font-weight: 900; color: #fff; font-family: 'Anton', sans-serif; }

/* Sizes */
.db-chip--hero { width: 48px; height: 48px; border-width: 2px; animation: hero-enter 0.55s cubic-bezier(0.175,0.885,0.32,1.275) both; }
.db-chip--hero .db-chip__ltr { font-size: 8px; }
.db-chip--hero .db-chip__num { font-size: 18px; }

.db-chip--md { width: 32px; height: 32px; }
.db-chip--md .db-chip__ltr { font-size: 6.5px; }
.db-chip--md .db-chip__num { font-size: 11px; }

.db-chip--sm { width: 28px; height: 28px; }
.db-chip--sm .db-chip__ltr { font-size: 5.5px; }
.db-chip--sm .db-chip__num { font-size: 9.5px; }

@keyframes hero-enter {
  0%   { transform: scale(0.3) rotate(-20deg); opacity: 0; }
  65%  { transform: scale(1.1) rotate(3deg); }
  100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

/* Tooltip */
.db-chip__tip {
  position: absolute; bottom: calc(100% + 5px); left: 50%; transform: translateX(-50%);
  background: rgba(10,16,40,0.95); border: 1px solid rgba(80,120,255,0.3);
  border-radius: 6px; padding: 2px 7px; font-size: 10px; font-weight: 700; color: #c8d8ff;
  white-space: nowrap; pointer-events: none; z-index: 10;
  box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}
.db-chip__tip::after {
  content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
  border: 4px solid transparent; border-top-color: rgba(80,120,255,0.3);
}

/* ══════════════════════════════════════════════════════
   CHIP COLORS
══════════════════════════════════════════════════════ */
.db-chip--B {
  background: radial-gradient(circle at 36% 30%, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark) 100%);
  box-shadow: 0 3px 10px var(--bc0-glow), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--I {
  background: radial-gradient(circle at 36% 30%, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark) 100%);
  box-shadow: 0 3px 10px var(--bc1-glow), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--N {
  background: radial-gradient(circle at 36% 30%, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark) 100%);
  box-shadow: 0 3px 10px var(--bc2-glow), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--G {
  background: radial-gradient(circle at 36% 30%, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark) 100%);
  box-shadow: 0 3px 10px var(--bc3-glow), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--O {
  background: radial-gradient(circle at 36% 30%, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark) 100%);
  box-shadow: 0 3px 10px var(--bc4-glow), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}

/* ══════════════════════════════════════════════════════
   TRANSITION
══════════════════════════════════════════════════════ */
.db-reveal-enter-active { transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.4,0,0.2,1); }
.db-reveal-leave-active { transition: opacity 0.25s ease; }
.db-reveal-enter-from  { opacity: 0; transform: translateY(8px); }
.db-reveal-leave-to    { opacity: 0; }

/* ══════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  .db-hero { flex-wrap: wrap; gap: 10px; padding: 10px 12px; }
  .db-hero__big-num { font-size: 30px; }
  .db-recent { flex-direction: row; align-items: center; gap: 8px; width: 100%; }
  .db-recent__label { writing-mode: horizontal-tb; margin-bottom: 0; }
  .db-history { padding: 8px 10px 10px; gap: 3px; }
}
@media (max-width: 420px) {
  .db-hero__ball-wrap { width: 40px; height: 40px; }
  .db-chip--hero { width: 40px; height: 40px; }
  .db-chip--hero .db-chip__num { font-size: 15px; }
  .db-hero__big-num { font-size: 26px; }
  .db-hero__col-pills { display: none; }
  .db-chip--sm { width: 24px; height: 24px; }
  .db-chip--sm .db-chip__ltr { font-size: 5px; }
  .db-chip--sm .db-chip__num { font-size: 8px; }
}
</style>
