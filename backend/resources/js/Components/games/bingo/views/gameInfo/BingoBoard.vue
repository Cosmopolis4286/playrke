<!-- BingoBoard.vue — Spectator board: dark theme, draw position + timestamp -->
<template>
  <div class="bb-root">

    <!-- ═══════════════════════════════
         STATS BAR
    ═══════════════════════════════ -->
    <div class="bb-stats">
      <div class="bb-stat">
        <span class="bb-stat__val">{{ calledNumbers.length }}</span>
        <span class="bb-stat__lbl">sorteados</span>
      </div>
      <div class="bb-stat__sep" />
      <div class="bb-stat">
        <span class="bb-stat__val">{{ totalNumbers - calledNumbers.length }}</span>
        <span class="bb-stat__lbl">restantes</span>
      </div>
      <div class="bb-stat__sep" />
      <div class="bb-stat">
        <span class="bb-stat__val">{{ pct }}%</span>
        <span class="bb-stat__lbl">completado</span>
      </div>
      <!-- progress bar -->
      <div class="bb-stat__bar">
        <div class="bb-stat__bar-fill" :style="{ width: pct + '%' }" />
      </div>
    </div>

    <!-- ═══════════════════════════════
         LAST DRAWN CHIP
    ═══════════════════════════════ -->
    <Transition name="bb-pop">
      <div v-if="currentNumber !== null" class="bb-last" :class="`bb-last--${colLabel(currentNumber)}`">
        <!-- glow backdrop -->
        <div class="bb-last__glow" :class="`bb-last__glow--${colLabel(currentNumber)}`" />

        <!-- left: bola estilo bingo -->
        <div class="bb-last__ball" :class="`bb-last__ball--${colLabel(currentNumber)}`">
          <div class="bb-last__ball-inner">
            <span class="bb-last__ball-letter">{{ colLabel(currentNumber) }}</span>
            <span class="bb-last__ball-num">{{ currentNumber }}</span>
          </div>
          <div class="bb-last__ball-shine" />
        </div>

        <!-- center: info -->
        <div class="bb-last__info">
          <div class="bb-last__tag">ÚLTIMO SORTEADO</div>
          <div class="bb-last__pos">#{{ calledNumbers.length }}</div>
          <div v-if="lastCalledAt" class="bb-last__time">
            <i class="fas fa-clock" />{{ lastCalledAt }}
          </div>
        </div>

        <!-- right: pulse rings -->
        <div class="bb-last__rings">
          <div class="bb-last__ring bb-last__ring--1" :class="`bb-last__ring--c-${colLabel(currentNumber)}`" />
          <div class="bb-last__ring bb-last__ring--2" :class="`bb-last__ring--c-${colLabel(currentNumber)}`" />
        </div>
      </div>
    </Transition>

    <!-- ═══════════════════════════════
         BOARD GRID
    ═══════════════════════════════ -->
    <div ref="boardRef" class="bb-wrap">
      <!-- Legend -->
      <div class="bb-legend">
        <span class="bb-legend__item"><span class="bb-dot bb-dot--pending" />Pendiente</span>
        <span class="bb-legend__item"><span class="bb-dot bb-dot--called" />Sorteado</span>
        <span class="bb-legend__item"><span class="bb-dot bb-dot--last" />Último</span>
        <span class="bb-legend__hint">Pasa el cursor sobre un número para ver su posición y hora</span>
      </div>

      <div class="bb-board">
        <div v-for="(group, colIdx) in bingoGroups" :key="colIdx" class="bb-col">
          <!-- Column header -->
          <div class="bb-col__hdr" :class="`bb-col__hdr--${colLabels[colIdx]}`">
            {{ colLabels[colIdx] }}
          </div>

          <!-- Cells -->
          <div
            v-for="number in group"
            :key="number"
            class="bb-cell"
            :class="{
              'bb-cell--called': calledNumbers.includes(number) && number !== currentNumber,
              'bb-cell--last':   number === currentNumber,
              'bb-cell--new':    number === newestNumber,
            }"
            :style="{ width: boxSize + 'px', height: boxSize + 'px' }"
            @mouseenter="showTooltip(number, $event)"
            @mouseleave="hideTooltip"
          >
            <span class="bb-cell__n">{{ number }}</span>
            <!-- badge solo en celdas llamadas que NO son la última -->
            <span
              v-if="calledNumbers.includes(number) && number !== currentNumber"
              class="bb-cell__badge"
            >#{{ drawPos(number) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════
         HISTORY RIBBON (last 10)
    ═══════════════════════════════ -->
    <div v-if="calledNumbers.length > 0" class="bb-history">
      <span class="bb-history__label">Historial</span>
      <div class="bb-history__scroll">
        <TransitionGroup name="bb-chip-slide" tag="div" class="bb-history__inner">
          <div
            v-for="(n, idx) in recentChips"
            :key="n"
            class="bb-hchip"
            :class="[`bb-hchip--${colLabel(n)}`, { 'bb-hchip--first': idx === 0 }]"
            :title="`#${drawPos(n)} · ${drawTime(n)}`"
          >
            <span class="bb-hchip__letter">{{ colLabel(n) }}</span>
            <span class="bb-hchip__num">{{ n }}</span>
            <span class="bb-hchip__pos">#{{ drawPos(n) }}</span>
          </div>
        </TransitionGroup>
      </div>
    </div>

    <!-- ═══════════════════════════════
         TOOLTIP
    ═══════════════════════════════ -->
    <Transition name="bb-tt">
      <div
        v-if="tooltip.visible && tooltip.number !== null && calledNumbers.includes(tooltip.number)"
        class="bb-tooltip"
        :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px' }"
      >
        <div class="bb-tooltip__chip" :class="`bb-tooltip__chip--${colLabel(tooltip.number)}`">
          {{ colLabel(tooltip.number) }}{{ tooltip.number }}
        </div>
        <div class="bb-tooltip__row">
          <i class="fas fa-list-ol" />
          <span>Posición <strong>#{{ drawPos(tooltip.number) }}</strong></span>
        </div>
        <div class="bb-tooltip__row">
          <i class="fas fa-clock" />
          <span>{{ drawTime(tooltip.number) }}</span>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import {
  ref, reactive, computed,
  onMounted, onBeforeUnmount,
  nextTick, watch,
} from "vue";

// ── Props ─────────────────────────────────────────────────────────────────────
const props = defineProps({
  calledNumbers:  { type: Array,  default: () => [] },
  currentNumber:  { type: Number, default: null },
  variant:        { type: String, default: "BINGO_75" },
  // Map of number → ISO timestamp string (populated by parent when chip arrives)
  drawTimestamps: { type: Object, default: () => ({}) },
});

const calledNumbers = computed(() => /** @type {number[]} */ (props.calledNumbers));

// ── Variant definitions ───────────────────────────────────────────────────────
const VARIANT_DEFS = {
  BINGO_75: { columns: [
    { label: "B", min: 1,  max: 15 },
    { label: "I", min: 16, max: 30 },
    { label: "N", min: 31, max: 45 },
    { label: "G", min: 46, max: 60 },
    { label: "O", min: 61, max: 75 },
  ]},
  BINGO_90: { columns: [
    { label: "1", min: 1,  max: 9  }, { label: "2", min: 10, max: 19 },
    { label: "3", min: 20, max: 29 }, { label: "4", min: 30, max: 39 },
    { label: "5", min: 40, max: 49 }, { label: "6", min: 50, max: 59 },
    { label: "7", min: 60, max: 69 }, { label: "8", min: 70, max: 79 },
    { label: "9", min: 80, max: 90 },
  ]},
  BINGO_80: { columns: [
    { label: "1", min: 1,  max: 20 }, { label: "2", min: 21, max: 40 },
    { label: "3", min: 41, max: 60 }, { label: "4", min: 61, max: 80 },
  ]},
  BINGO_30: { columns: [
    { label: "1", min: 1,  max: 10 }, { label: "2", min: 11, max: 20 },
    { label: "3", min: 21, max: 30 },
  ]},
};

const variantDef   = computed(() => VARIANT_DEFS[props.variant] ?? VARIANT_DEFS.BINGO_75);
const colLabels    = computed(() => variantDef.value.columns.map(c => c.label));
const totalNumbers = computed(() => variantDef.value.columns.at(-1).max);
const pct          = computed(() => totalNumbers.value > 0
  ? Math.round((props.calledNumbers.length / totalNumbers.value) * 100)
  : 0
);

const bingoGroups = computed(() =>
  variantDef.value.columns.map(col => {
    const g = [];
    for (let n = col.min; n <= col.max; n++) g.push(n);
    return g;
  })
);

function colLabel(n) {
  for (const col of variantDef.value.columns) {
    if (n >= col.min && n <= col.max) return col.label;
  }
  return '?';
}

// ── Draw metadata ─────────────────────────────────────────────────────────────
function drawPos(n) {
  const idx = props.calledNumbers.indexOf(n);
  return idx === -1 ? '-' : idx + 1;
}

function drawTime(n) {
  const ts = props.drawTimestamps?.[n];
  if (!ts) return '—';
  const d = new Date(ts);
  const h = String(d.getHours()).padStart(2, '0');
  const m = String(d.getMinutes()).padStart(2, '0');
  const s = String(d.getSeconds()).padStart(2, '0');
  return `${h}:${m}:${s}`;
}

const lastCalledAt = computed(() => props.currentNumber !== null ? drawTime(props.currentNumber) : null);

// ── Newest number (triggers entrance animation) ───────────────────────────────
const newestNumber = ref(null);
let newestTimer = null;
watch(() => props.currentNumber, (n) => {
  if (n === null) return;
  newestNumber.value = n;
  clearTimeout(newestTimer);
  newestTimer = setTimeout(() => { newestNumber.value = null; }, 800);
});

// ── Recent chips (last 10, newest first) ─────────────────────────────────────
const recentChips = computed(() => [...calledNumbers.value].reverse().slice(0, 10));

// ── Tooltip ───────────────────────────────────────────────────────────────────
const tooltip = reactive({ visible: false, number: null, x: 0, y: 0 });
let tooltipTimer = null;

function showTooltip(number, event) {
  clearTimeout(tooltipTimer);
  if (!props.calledNumbers.includes(number)) return;
  const rect = event.target.getBoundingClientRect();
  const rootRect = event.target.closest('.bb-root')?.getBoundingClientRect() ?? rect;
  tooltip.number  = number;
  tooltip.x       = rect.left - rootRect.left + rect.width / 2;
  tooltip.y       = rect.top  - rootRect.top  - 8;
  tooltip.visible = true;
}
function hideTooltip() {
  tooltipTimer = setTimeout(() => { tooltip.visible = false; }, 120);
}

// ── Responsive layout ─────────────────────────────────────────────────────────
const boardRef = ref(null);
const boxSize  = ref(38);

const updateLayout = () => {
  if (!boardRef.value) return;
  const w       = boardRef.value.getBoundingClientRect().width;
  const numCols = variantDef.value.columns.length;
  const gaps    = (numCols - 1) * 4; // gap between columns
  const colW    = (w - gaps) / numCols;
  // Each column header is fixed; cell width = column width
  const cw = Math.min(52, Math.max(26, colW));
  boxSize.value = cw;
};

onMounted(() => { nextTick(updateLayout); window.addEventListener("resize", updateLayout); });
onBeforeUnmount(() => { window.removeEventListener("resize", updateLayout); clearTimeout(newestTimer); });
watch(() => props.variant, () => nextTick(updateLayout));
</script>

<style scoped>
/* ═══ Root ═══════════════════════════════════════════════════════════════════ */
.bb-root {
  position: relative;
  width: 100%;
  background: linear-gradient(145deg, #0b0f27 0%, #060a1c 100%);
  border: 1px solid rgba(123,79,255,0.20);
  border-radius: 14px;
  overflow: hidden;
  padding: 14px 14px 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ═══ Stats bar ══════════════════════════════════════════════════════════════ */
.bb-stats {
  display: flex;
  align-items: center;
  gap: 8px;
}
.bb-stat { display: flex; flex-direction: column; align-items: center; }
.bb-stat__val { font-size: 15px; font-weight: 900; color: #e0eaff; line-height: 1; font-family: 'Anton', sans-serif; }
.bb-stat__lbl { font-size: 8px; font-weight: 700; color: #3a4a70; text-transform: uppercase; letter-spacing: 0.6px; }
.bb-stat__sep { width: 1px; height: 22px; background: rgba(255,255,255,0.07); margin: 0 4px; }
.bb-stat__bar {
  flex: 1;
  height: 4px;
  background: rgba(255,255,255,0.06);
  border-radius: 2px;
  overflow: hidden;
  margin-left: 6px;
}
.bb-stat__bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #7b4fff, #2af598);
  border-radius: 2px;
  transition: width 0.5s ease;
}

/* ═══ Last drawn chip ════════════════════════════════════════════════════════ */
.bb-last {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  border-radius: 12px;
  padding: 10px 16px 10px 12px;
  background: rgba(10,14,32,0.80);
  border: 1px solid rgba(255,255,255,0.08);
  overflow: hidden;
  min-height: 64px;
}

/* glow backdrop — color por letra */
.bb-last__glow {
  position: absolute; inset: 0;
  opacity: 0.18;
  pointer-events: none;
  border-radius: inherit;
}
.bb-last__glow--B { background: radial-gradient(ellipse at 15% 50%, #3b82f6 0%, transparent 65%); }
.bb-last__glow--I { background: radial-gradient(ellipse at 15% 50%, #ef4444 0%, transparent 65%); }
.bb-last__glow--N { background: radial-gradient(ellipse at 15% 50%, #22c55e 0%, transparent 65%); }
.bb-last__glow--G { background: radial-gradient(ellipse at 15% 50%, #eab308 0%, transparent 65%); }
.bb-last__glow--O { background: radial-gradient(ellipse at 15% 50%, #a855f7 0%, transparent 65%); }
.bb-last__glow--1,.bb-last__glow--2,.bb-last__glow--3,
.bb-last__glow--4,.bb-last__glow--5,.bb-last__glow--6,
.bb-last__glow--7,.bb-last__glow--8,.bb-last__glow--9 {
  background: radial-gradient(ellipse at 15% 50%, #6366f1 0%, transparent 65%);
}

/* ── Bola ── */
.bb-last__ball {
  position: relative;
  width: 54px; height: 54px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  z-index: 2;
}
.bb-last__ball--B { background: radial-gradient(circle at 35% 30%, #60a5fa, #1d4ed8 70%); box-shadow: 0 4px 18px rgba(59,130,246,0.55), 0 0 30px rgba(59,130,246,0.20); }
.bb-last__ball--I { background: radial-gradient(circle at 35% 30%, #f87171, #b91c1c 70%); box-shadow: 0 4px 18px rgba(239,68,68,0.55),  0 0 30px rgba(239,68,68,0.20); }
.bb-last__ball--N { background: radial-gradient(circle at 35% 30%, #4ade80, #15803d 70%); box-shadow: 0 4px 18px rgba(34,197,94,0.55),  0 0 30px rgba(34,197,94,0.20); }
.bb-last__ball--G { background: radial-gradient(circle at 35% 30%, #fde047, #a16207 70%); box-shadow: 0 4px 18px rgba(234,179,8,0.55),  0 0 30px rgba(234,179,8,0.20); }
.bb-last__ball--O { background: radial-gradient(circle at 35% 30%, #d8b4fe, #7e22ce 70%); box-shadow: 0 4px 18px rgba(168,85,247,0.55), 0 0 30px rgba(168,85,247,0.20); }
.bb-last__ball--1,.bb-last__ball--2,.bb-last__ball--3,
.bb-last__ball--4,.bb-last__ball--5,.bb-last__ball--6,
.bb-last__ball--7,.bb-last__ball--8,.bb-last__ball--9 {
  background: radial-gradient(circle at 35% 30%, #a5b4fc, #4338ca 70%);
  box-shadow: 0 4px 18px rgba(99,102,241,0.55), 0 0 30px rgba(99,102,241,0.20);
}

/* brillo superior */
.bb-last__ball-shine {
  position: absolute;
  top: 8px; left: 9px;
  width: 22px; height: 14px;
  border-radius: 50%;
  background: rgba(255,255,255,0.30);
  filter: blur(3px);
  pointer-events: none;
}

.bb-last__ball-inner {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 0;
  z-index: 1;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));
}
.bb-last__ball-letter {
  font-size: 9px; font-weight: 900; line-height: 1;
  color: rgba(255,255,255,0.85); letter-spacing: 0.5px;
  text-transform: uppercase;
}
.bb-last__ball-num {
  font-size: 22px; font-weight: 900; line-height: 1;
  color: #fff;
  font-family: 'Anton', sans-serif;
  text-shadow: 0 1px 4px rgba(0,0,0,0.6);
}

/* ── Info central ── */
.bb-last__info {
  display: flex; flex-direction: column; gap: 3px;
  z-index: 2; flex: 1;
}
.bb-last__tag {
  font-size: 8px; font-weight: 800; letter-spacing: 1px;
  text-transform: uppercase; color: #3a4a70;
}
.bb-last__pos {
  font-size: 26px; font-weight: 900; line-height: 1;
  color: #e0eaff;
  font-family: 'Anton', sans-serif;
}
.bb-last__time {
  display: flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 700; color: #5a6a8a;
  font-variant-numeric: tabular-nums;
}
.bb-last__time i { font-size: 9px; }

/* ── Anillos de pulso (derecha) ── */
.bb-last__rings {
  position: absolute;
  right: 14px; top: 50%;
  transform: translateY(-50%);
  width: 44px; height: 44px;
  pointer-events: none; z-index: 1;
}
.bb-last__ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 1.5px solid;
  opacity: 0;
}
.bb-last__ring--1 { animation: bb-ring-out 2s ease-out infinite; }
.bb-last__ring--2 { animation: bb-ring-out 2s ease-out 0.7s infinite; }

.bb-last__ring--c-B { border-color: #3b82f6; }
.bb-last__ring--c-I { border-color: #ef4444; }
.bb-last__ring--c-N { border-color: #22c55e; }
.bb-last__ring--c-G { border-color: #eab308; }
.bb-last__ring--c-O { border-color: #a855f7; }
.bb-last__ring--c-1,.bb-last__ring--c-2,.bb-last__ring--c-3,
.bb-last__ring--c-4,.bb-last__ring--c-5,.bb-last__ring--c-6,
.bb-last__ring--c-7,.bb-last__ring--c-8,.bb-last__ring--c-9 { border-color: #6366f1; }

@keyframes bb-ring-out {
  0%   { transform: scale(0.6); opacity: 0.7; }
  100% { transform: scale(2.2); opacity: 0; }
}

/* ═══ Board wrap ═════════════════════════════════════════════════════════════ */
.bb-wrap {
  width: 100%;
}

/* Legend */
.bb-legend {
  display: flex; align-items: center; flex-wrap: wrap;
  gap: 8px; margin-bottom: 8px;
}
.bb-legend__item {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 9px; color: #4a5a88; letter-spacing: 0.3px;
}
.bb-legend__hint {
  margin-left: auto;
  font-size: 8px; color: #2a3455; font-style: italic;
}
.bb-dot {
  width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0;
}
.bb-dot--pending { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); }
.bb-dot--called  { background: #ff4d6d; }
.bb-dot--last    { background: #ffd700; box-shadow: 0 0 5px #ffd700; }

/* Board grid */
.bb-board {
  display: flex;
  gap: 4px;
  width: 100%;
  justify-content: center;
}

.bb-col {
  display: flex; flex-direction: column; gap: 2px; align-items: center;
  flex: 1;
}

/* Column header */
.bb-col__hdr {
  width: 100%; height: 22px;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 900; letter-spacing: 1.5px;
  border-radius: 5px; margin-bottom: 3px; user-select: none;
}
.bb-col__hdr--B { background: rgba(59,130,246,0.20); color: #60a5fa; border: 1px solid rgba(59,130,246,0.30); }
.bb-col__hdr--I { background: rgba(239,68,68,0.18);  color: #f87171; border: 1px solid rgba(239,68,68,0.30); }
.bb-col__hdr--N { background: rgba(34,197,94,0.16);  color: #4ade80; border: 1px solid rgba(34,197,94,0.28); }
.bb-col__hdr--G { background: rgba(234,179,8,0.16);  color: #facc15; border: 1px solid rgba(234,179,8,0.25); }
.bb-col__hdr--O { background: rgba(168,85,247,0.16); color: #c084fc; border: 1px solid rgba(168,85,247,0.28); }
.bb-col__hdr--1,.bb-col__hdr--2,.bb-col__hdr--3,
.bb-col__hdr--4,.bb-col__hdr--5,.bb-col__hdr--6,
.bb-col__hdr--7,.bb-col__hdr--8,.bb-col__hdr--9 {
  background: rgba(99,102,241,0.16); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.26);
}

/* ── Cell ── */
.bb-cell {
  position: relative;
  width: 100%;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  border-radius: 5px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  color: rgba(255,255,255,0.25);
  font-size: 0.78rem; font-weight: 700;
  user-select: none; cursor: default;
  transition: background 0.3s, border-color 0.3s, box-shadow 0.3s, color 0.3s, transform 0.15s;
  overflow: visible;
}

.bb-cell:hover {
  border-color: rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.06);
}

.bb-cell__n { line-height: 1; }

/* Called */
.bb-cell--called {
  background: radial-gradient(circle at 40% 40%, rgba(220,38,38,0.28) 0%, rgba(150,0,30,0.16) 100%);
  border-color: rgba(220,38,38,0.35);
  color: rgba(252,165,165,0.95);
}
.bb-cell--called:hover {
  background: radial-gradient(circle at 40% 40%, rgba(220,38,38,0.38) 0%, rgba(150,0,30,0.24) 100%);
  border-color: rgba(220,38,38,0.55);
  cursor: help;
}

/* Last drawn */
.bb-cell--last {
  background: radial-gradient(circle at 40% 40%, rgba(255,215,0,0.32) 0%, rgba(180,130,0,0.20) 100%) !important;
  border-color: rgba(255,215,0,0.65) !important;
  color: #ffd700 !important;
  box-shadow: 0 0 14px rgba(255,215,0,0.30), inset 0 0 8px rgba(255,215,0,0.10);
  animation: bb-last-glow 1.6s ease-in-out infinite alternate;
}
@keyframes bb-last-glow {
  from { box-shadow: 0 0 8px rgba(255,215,0,0.25); }
  to   { box-shadow: 0 0 22px rgba(255,215,0,0.55); }
}

/* New entrance animation */
.bb-cell--new {
  animation: bb-pop-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes bb-pop-in {
  0%   { transform: scale(0.6); opacity: 0.3; }
  60%  { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}

/* Draw-position micro-badge */
.bb-cell__badge {
  position: absolute;
  bottom: -2px; right: -2px;
  font-size: 6px; font-weight: 900;
  color: rgba(255,120,130,0.9);
  background: rgba(180,0,30,0.55);
  border-radius: 3px;
  padding: 0 2px;
  line-height: 10px;
  z-index: 2;
  pointer-events: none;
  letter-spacing: 0;
}
.bb-cell__badge--last {
  background: rgba(180,130,0,0.80);
  color: #fff8c0;
}

/* ═══ History ribbon ═════════════════════════════════════════════════════════ */
.bb-history {
  display: flex; align-items: flex-start; gap: 8px;
  border-top: 1px solid rgba(255,255,255,0.05);
  padding-top: 10px;
}
.bb-history__label {
  font-size: 8px; font-weight: 800; color: #2a3455;
  text-transform: uppercase; letter-spacing: 0.8px;
  padding-top: 6px; flex-shrink: 0;
}
.bb-history__scroll {
  overflow-x: auto; flex: 1;
  scrollbar-width: none;
}
.bb-history__scroll::-webkit-scrollbar { display: none; }
.bb-history__inner {
  display: flex; gap: 5px; align-items: center;
  padding-bottom: 2px;
}

/* History chip */
.bb-hchip {
  position: relative;
  display: inline-flex; flex-direction: column;
  align-items: center; justify-content: center;
  width: 32px; min-width: 32px; height: 38px;
  border-radius: 7px;
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(255,255,255,0.05);
  flex-shrink: 0;
  cursor: default;
  transition: transform 0.15s, box-shadow 0.15s;
}
.bb-hchip:hover { transform: translateY(-2px); }

.bb-hchip__letter { font-size: 7px; font-weight: 800; line-height: 1; opacity: 0.75; }
.bb-hchip__num    { font-size: 13px; font-weight: 900; line-height: 1; font-family: 'Anton', sans-serif; }
.bb-hchip__pos    { font-size: 6px; font-weight: 700; opacity: 0.6; line-height: 1; }

.bb-hchip--B { background: rgba(59,130,246,0.16);  border-color: rgba(59,130,246,0.30);  color: #93c5fd; }
.bb-hchip--I { background: rgba(239,68,68,0.14);   border-color: rgba(239,68,68,0.30);   color: #fca5a5; }
.bb-hchip--N { background: rgba(34,197,94,0.12);   border-color: rgba(34,197,94,0.25);   color: #86efac; }
.bb-hchip--G { background: rgba(234,179,8,0.12);   border-color: rgba(234,179,8,0.23);   color: #fde047; }
.bb-hchip--O { background: rgba(168,85,247,0.14);  border-color: rgba(168,85,247,0.26);  color: #d8b4fe; }
.bb-hchip--1,.bb-hchip--2,.bb-hchip--3,
.bb-hchip--4,.bb-hchip--5,.bb-hchip--6,
.bb-hchip--7,.bb-hchip--8,.bb-hchip--9 {
  background: rgba(99,102,241,0.14); border-color: rgba(99,102,241,0.24); color: #c7d2fe;
}

/* newest chip in ribbon */
.bb-hchip--first {
  border-color: rgba(255,215,0,0.45) !important;
  box-shadow: 0 0 10px rgba(255,215,0,0.25);
}
.bb-hchip--first .bb-hchip__num { color: #ffd700 !important; }

/* ═══ Tooltip ════════════════════════════════════════════════════════════════ */
.bb-tooltip {
  position: absolute;
  transform: translate(-50%, -100%);
  background: rgba(15,20,48,0.97);
  border: 1px solid rgba(123,79,255,0.35);
  border-radius: 8px;
  padding: 8px 11px;
  min-width: 130px;
  z-index: 100;
  pointer-events: none;
  box-shadow: 0 8px 24px rgba(0,0,0,0.6);
  display: flex; flex-direction: column; gap: 5px;
}
.bb-tooltip::after {
  content: '';
  position: absolute; bottom: -5px; left: 50%;
  transform: translateX(-50%);
  width: 8px; height: 8px;
  background: rgba(15,20,48,0.97);
  border-right: 1px solid rgba(123,79,255,0.35);
  border-bottom: 1px solid rgba(123,79,255,0.35);
  clip-path: polygon(0 0, 100% 100%, 0 100%);
  rotate: -45deg;
}
.bb-tooltip__chip {
  font-size: 11px; font-weight: 900;
  color: #e0eaff; letter-spacing: 0.5px;
  padding-bottom: 5px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.bb-tooltip__row {
  display: flex; align-items: center; gap: 7px;
  font-size: 11px; color: #8090b8;
}
.bb-tooltip__row i { font-size: 9px; color: #5a6a90; width: 12px; text-align: center; }
.bb-tooltip__row strong { color: #e0eaff; }

/* ═══ Chip slide animation (history ribbon) ══════════════════════════════════ */
.bb-chip-slide-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.bb-chip-slide-enter-from   { opacity: 0; transform: translateX(-16px) scale(0.7); }

/* ═══ Last-chip pop ══════════════════════════════════════════════════════════ */
.bb-pop-enter-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.bb-pop-enter-from   { opacity: 0; transform: scale(0.85); }
.bb-pop-leave-active { transition: all 0.25s ease; }
.bb-pop-leave-to     { opacity: 0; transform: scale(0.9); }

/* ═══ Tooltip fade ═══════════════════════════════════════════════════════════ */
.bb-tt-enter-active, .bb-tt-leave-active { transition: opacity 0.15s, transform 0.15s; }
.bb-tt-enter-from, .bb-tt-leave-to { opacity: 0; transform: translate(-50%, calc(-100% + 4px)); }
</style>
