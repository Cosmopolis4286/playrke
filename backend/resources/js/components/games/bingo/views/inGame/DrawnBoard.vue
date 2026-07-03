<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  drawnNumbers: number[]
  totalChips: number
  chipLetter: (n: number) => string
}

const props = defineProps<Props>()

const last       = computed(() => props.drawnNumbers[props.drawnNumbers.length - 1])
const lastLetter = computed(() => last.value != null ? props.chipLetter(last.value) : '')
const pct        = computed(() => Math.round((props.drawnNumbers.length / props.totalChips) * 100))

// Grid chips en orden cronológico inverso, sin incluir el último (ya destacado arriba)
const historyChips  = computed(() => [...props.drawnNumbers].reverse().slice(1))
// Los 5 más recientes previos al actual (para la sección "anteriores")
const recentChips   = computed(() => historyChips.value.slice(0, 5))

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
              <div class="db-progress__fill" :style="{ width: `${pct}%` }" />
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
              :class="`db-chip--${chipLetter(n)}`"
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

      <div v-if="showHistory" class="db-history">
        <div
          v-for="n in historyChips"
          :key="n"
          class="db-chip db-chip--sm"
          :class="`db-chip--${chipLetter(n)}`"
          @mouseenter="hoveredChip = n"
          @mouseleave="hoveredChip = null"
        >
          <span class="db-chip__ltr">{{ chipLetter(n) }}</span>
          <span class="db-chip__num">{{ n }}</span>
          <!-- Tooltip minimalista -->
          <div v-if="hoveredChip === n" class="db-chip__tip">{{ chipLetter(n) }}{{ n }}</div>
        </div>
      </div>

    </div>
  </Transition>
</template>

<style scoped>
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
  display: flex; align-items: center; gap: 20px;
  padding: 14px 18px;
  border-bottom: 1px solid rgba(80,120,255,0.07);
}

/* Bola principal */
.db-hero__ball-wrap {
  position: relative; flex-shrink: 0;
  width: 64px; height: 64px;
  display: flex; align-items: center; justify-content: center;
}
.db-hero__glow {
  position: absolute; inset: -10px; border-radius: 50%;
  filter: blur(16px); opacity: 0.5;
  animation: glow-pulse 2s ease-in-out infinite;
}
@keyframes glow-pulse { 0%,100% { opacity: 0.35; transform: scale(0.9); } 50% { opacity: 0.6; transform: scale(1.1); } }

.db-glow--B { background: #1a7de0; }
.db-glow--I { background: #7b3fd0; }
.db-glow--N { background: #28a428; }
.db-glow--G { background: #e0a000; }
.db-glow--O { background: #d42020; }

.db-hero__ring {
  position: absolute; border-radius: 50%; border: 1px solid;
  animation: ring-out 2.2s ease-out infinite;
  pointer-events: none;
}
.db-hero__ring--1 { inset: -6px; animation-delay: 0s; }
.db-hero__ring--2 { inset: -14px; animation-delay: 0.7s; }
@keyframes ring-out { 0% { opacity: 0.5; transform: scale(0.85); } 100% { opacity: 0; transform: scale(1.4); } }

.db-ring--B { border-color: rgba(26,125,224,0.6); }
.db-ring--I { border-color: rgba(123,63,208,0.6); }
.db-ring--N { border-color: rgba(40,164,40,0.6); }
.db-ring--G { border-color: rgba(224,160,0,0.6); }
.db-ring--O { border-color: rgba(212,32,32,0.6); }

/* Meta: número grande + progreso */
.db-hero__meta { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px; }

.db-hero__num-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }

.db-hero__big-num {
  display: inline-block;
  font-family: 'Anton', sans-serif; font-size: 52px; line-height: 1;
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text; letter-spacing: -1px;
  filter: drop-shadow(0 2px 12px currentColor);
}
.db-num--B { background: linear-gradient(135deg, #90d0ff, #1a7de0); }
.db-num--I { background: linear-gradient(135deg, #d0a0ff, #7b3fd0); }
.db-num--N { background: linear-gradient(135deg, #90e890, #28a428); }
.db-num--G { background: linear-gradient(135deg, #ffe890, #e0a000); }
.db-num--O { background: linear-gradient(135deg, #ff9090, #d42020); }

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

.db-col-pill--B { background: rgba(26,125,224,0.12); border-color: rgba(26,125,224,0.25); }
.db-col-pill--B .db-col-pill__letter { color: #78c8ff; }
.db-col-pill--B .db-col-pill__count  { color: #90d8ff; }

.db-col-pill--I { background: rgba(123,63,208,0.12); border-color: rgba(123,63,208,0.25); }
.db-col-pill--I .db-col-pill__letter { color: #c090ff; }
.db-col-pill--I .db-col-pill__count  { color: #d4b0ff; }

.db-col-pill--N { background: rgba(40,164,40,0.10); border-color: rgba(40,164,40,0.22); }
.db-col-pill--N .db-col-pill__letter { color: #70d870; }
.db-col-pill--N .db-col-pill__count  { color: #90e890; }

.db-col-pill--G { background: rgba(224,160,0,0.10); border-color: rgba(224,160,0,0.22); }
.db-col-pill--G .db-col-pill__letter { color: #ffd040; }
.db-col-pill--G .db-col-pill__count  { color: #ffe870; }

.db-col-pill--O { background: rgba(212,32,32,0.10); border-color: rgba(212,32,32,0.22); }
.db-col-pill--O .db-col-pill__letter { color: #ff7070; }
.db-col-pill--O .db-col-pill__count  { color: #ff9090; }

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
.db-chip--hero { width: 64px; height: 64px; border-width: 2px; animation: hero-enter 0.55s cubic-bezier(0.175,0.885,0.32,1.275) both; }
.db-chip--hero .db-chip__ltr { font-size: 10px; }
.db-chip--hero .db-chip__num { font-size: 24px; }

.db-chip--md { width: 36px; height: 36px; }
.db-chip--md .db-chip__ltr { font-size: 7px; }
.db-chip--md .db-chip__num { font-size: 13px; }

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
  background: radial-gradient(circle at 36% 30%, #70c8ff, #1a7de0 55%, #0c4fa0 100%);
  box-shadow: 0 3px 10px rgba(26,125,224,0.5), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--I {
  background: radial-gradient(circle at 36% 30%, #c890ff, #7b3fd0 55%, #4a1898 100%);
  box-shadow: 0 3px 10px rgba(123,63,208,0.5), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--N {
  background: radial-gradient(circle at 36% 30%, #7ae07a, #28a428 55%, #166016 100%);
  box-shadow: 0 3px 10px rgba(40,164,40,0.45), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--G {
  background: radial-gradient(circle at 36% 30%, #ffe870, #e0a000 55%, #986800 100%);
  box-shadow: 0 3px 10px rgba(224,160,0,0.45), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
}
.db-chip--O {
  background: radial-gradient(circle at 36% 30%, #ff8080, #d42020 55%, #880e0e 100%);
  box-shadow: 0 3px 10px rgba(212,32,32,0.5), inset 0 1px 0 rgba(255,255,255,0.4), inset 0 -1px 0 rgba(0,0,0,0.25);
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
  .db-hero { flex-wrap: wrap; gap: 12px; padding: 12px 14px; }
  .db-hero__big-num { font-size: 40px; }
  .db-recent { flex-direction: row; align-items: center; gap: 8px; width: 100%; }
  .db-recent__label { writing-mode: horizontal-tb; margin-bottom: 0; }
  .db-history { padding: 8px 10px 10px; gap: 3px; }
}
@media (max-width: 420px) {
  .db-hero__ball-wrap { width: 52px; height: 52px; }
  .db-chip--hero { width: 52px; height: 52px; }
  .db-chip--hero .db-chip__num { font-size: 20px; }
  .db-hero__big-num { font-size: 34px; }
  .db-hero__col-pills { display: none; }
  .db-chip--sm { width: 24px; height: 24px; }
  .db-chip--sm .db-chip__ltr { font-size: 5px; }
  .db-chip--sm .db-chip__num { font-size: 8px; }
}
</style>
