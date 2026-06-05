<template>
  <div class="ctn-root">

    <!-- ── Single unified toolbar ── -->
    <div class="ctn-bar" v-if="displayCards.length > 0 || totalDrawn > 0">

      <!-- Left: title + count + drawn -->
      <div class="ctn-bar__left">
        <span class="ctn-bar__title">
          <i class="fas fa-ticket-alt" />
          Cartones
        </span>
        <span class="ctn-bar__count">{{ displayCards.length }}</span>
        <span v-if="totalDrawn > 0" class="ctn-bar__drawn">
          <i class="fas fa-random" />{{ totalDrawn }}<em>/75</em>
        </span>
      </div>

      <!-- Center: filters (only show when relevant) -->
      <div class="ctn-bar__filters">
        <button
          v-for="f in visibleFilters"
          :key="f.value"
          class="ctn-fbtn"
          :class="{ 'ctn-fbtn--active': activeFilter === f.value }"
          @click="activeFilter = f.value"
        >
          <i :class="f.icon" />
          {{ f.label }}
          <span class="ctn-fbtn__n">{{ getFilterCount(f.value) }}</span>
        </button>
      </div>

      <!-- Right: size toggle + color legend -->
      <div class="ctn-bar__right">
        <div v-if="totalDrawn > 0" class="ctn-legend">
          <span v-for="l in LETTERS" :key="l" class="ctn-legend__dot" :class="`ctn-legend__dot--${l.toLowerCase()}`" :title="l">{{ l }}</span>
        </div>
        <div class="ctn-sizebar">
          <button class="ctn-sz" :class="{ 'ctn-sz--on': cardSize === 'sm' }" @click="cardSize = 'sm'" title="Compacto"><i class="fas fa-th" /></button>
          <button class="ctn-sz" :class="{ 'ctn-sz--on': cardSize === 'md' }" @click="cardSize = 'md'" title="Normal"><i class="fas fa-th-large" /></button>
        </div>
      </div>
    </div>

    <!-- ── Card grid ── -->
    <TransitionGroup name="ctn-pop" tag="div" class="ctn-grid" :class="`ctn-grid--${cardSize}`">
      <div
        v-for="(card, idx) in filteredCards"
        :key="card.id || idx"
        class="ctn-card"
        :class="{
          'ctn-card--winner': isWinner(card),
          'ctn-card--hot':    isHot(card) && !isWinner(card),
          'ctn-card--active': selectedId === card.id,
        }"
        :style="{ '--i': idx }"
        translate="no"
        @click="selectedId = selectedId === card.id ? null : card.id"
      >
        <!-- animated border glow -->
        <div class="ctn-card__glow" />

        <!-- hit badge -->
        <div v-if="hits(card) > 0" class="ctn-badge" :class="{ 'ctn-badge--hot': isHot(card), 'ctn-badge--win': isWinner(card) }">
          <i v-if="isWinner(card)" class="fas fa-trophy" />
          <i v-else-if="isHot(card)" class="fas fa-fire" />
          {{ hits(card) }}
        </div>

        <!-- BINGO header -->
        <div class="ctn-head">
          <span v-for="l in LETTERS" :key="l" class="ctn-head__l" :class="`ctn-head__l--${l.toLowerCase()}`">{{ l }}</span>
        </div>

        <!-- grid body -->
        <div class="ctn-body">
          <template v-for="l in LETTERS" :key="l">
            <div
              v-for="r in 5" :key="`${l}${r}`"
              class="ctn-cell"
              :class="cellCls(card, l, r)"
            >
              <template v-if="l === 'N' && r === 3">
                <span class="ctn-free">★</span>
              </template>
              <template v-else-if="drawn(card, l, r)">
                <div class="ctn-ball" :class="`ctn-ball--${l.toLowerCase()}`">
                  <span>{{ num(card, l, r) }}</span>
                </div>
              </template>
              <template v-else>
                <span class="ctn-num">{{ num(card, l, r) ?? '–' }}</span>
              </template>
            </div>
          </template>
        </div>

        <!-- progress strip -->
        <div class="ctn-prog">
          <div class="ctn-prog__fill"
            :class="{ 'ctn-prog__fill--hot': isHot(card), 'ctn-prog__fill--win': isWinner(card) }"
            :style="{ width: `${(hits(card)/24)*100}%` }"
          />
        </div>

        <!-- footer -->
        <div class="ctn-foot">
          <span class="ctn-foot__serial">{{ shortSerial(card.serialNumber) }}</span>
          <span class="ctn-foot__hits" :class="{ 'ctn-foot__hits--hot': isHot(card) }">
            {{ hits(card) }}<small>/24</small>
          </span>
        </div>
      </div>
    </TransitionGroup>

    <!-- ── Detail modal ── -->
    <Transition name="ctn-modal">
      <div v-if="selectedCard" class="ctn-modal-bg" @click.self="selectedId = null">
        <div class="ctn-modal">
          <button class="ctn-modal__x" @click="selectedId = null"><i class="fas fa-times" /></button>

          <div class="ctn-modal__tag" v-if="isWinner(selectedCard)">🏆 BINGO</div>

          <!-- big card -->
          <div class="ctn-dcard">
            <div class="ctn-dcard__head">
              <span v-for="l in LETTERS" :key="l" class="ctn-head__l" :class="`ctn-head__l--${l.toLowerCase()}`">{{ l }}</span>
            </div>
            <div class="ctn-dcard__body">
              <template v-for="l in LETTERS" :key="l">
                <div v-for="r in 5" :key="`d${l}${r}`" class="ctn-dcell" :class="cellCls(selectedCard, l, r)">
                  <template v-if="l === 'N' && r === 3">
                    <span class="ctn-dfree">★</span>
                  </template>
                  <template v-else-if="drawn(selectedCard, l, r)">
                    <div class="ctn-ball ctn-dball" :class="`ctn-ball--${l.toLowerCase()}`">
                      <span>{{ num(selectedCard, l, r) }}</span>
                    </div>
                  </template>
                  <template v-else>
                    <span class="ctn-dnum">{{ num(selectedCard, l, r) ?? '–' }}</span>
                  </template>
                </div>
              </template>
            </div>
          </div>

          <!-- stats -->
          <div class="ctn-stats">
            <div class="ctn-stat">
              <span class="ctn-stat__v">{{ hits(selectedCard) }}</span>
              <span class="ctn-stat__l">marcados</span>
            </div>
            <div class="ctn-stat">
              <span class="ctn-stat__v">{{ 24 - hits(selectedCard) }}</span>
              <span class="ctn-stat__l">faltan</span>
            </div>
            <div class="ctn-stat">
              <span class="ctn-stat__v">{{ pct(selectedCard) }}%</span>
              <span class="ctn-stat__l">completado</span>
            </div>
          </div>

          <div class="ctn-modal__serial">{{ selectedCard.serialNumber }}</div>
        </div>
      </div>
    </Transition>

    <!-- ── Empty ── -->
    <div v-if="displayCards.length === 0" class="ctn-empty">
      <i class="fas fa-ticket-alt" />
      <p>Sin cartones en esta sala</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref } from "vue";
import { type BingoCard } from "@js/Services/bingoService";

type Filter = "all" | "hot" | "win";

const HOT = 10;
const LETTERS = ["B", "I", "N", "G", "O"] as const;

interface Props {
  cartons?:      BingoCard[];
  customerLogo?: string;
  roomId?:       string;
  drawnNumbers?: number[];
  totalDrawn?:   number;
  autoCheckBingo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  cartons:        () => [],
  customerLogo:   null,
  roomId:         "",
  drawnNumbers:   () => [],
  totalDrawn:     0,
  autoCheckBingo: true,
});

const emit = defineEmits<{ (e: "bingo-detected", card: BingoCard): void }>();

const activeFilter = ref<Filter>("all");
const cardSize     = ref<"sm" | "md">("md");
const selectedId   = ref<string | null>(null);
const bingoSet     = ref<Set<string>>(new Set());

const drawnSet     = computed(() => new Set(props.drawnNumbers));
const displayCards = computed(() => props.cartons);

const selectedCard = computed(() =>
  selectedId.value ? displayCards.value.find(c => c.id === selectedId.value) ?? null : null
);

// Filters — only show "hot" and "win" tabs when there are candidates
const visibleFilters = computed(() => {
  const base: { value: Filter; label: string; icon: string }[] = [
    { value: "all", label: "Todos",    icon: "fas fa-border-all" },
  ];
  if (getFilterCount("hot") > 0) base.push({ value: "hot", label: "Caliente", icon: "fas fa-fire" });
  if (getFilterCount("win") > 0) base.push({ value: "win", label: "Bingo",    icon: "fas fa-trophy" });
  return base;
});

const filteredCards = computed(() => {
  if (activeFilter.value === "win") return displayCards.value.filter(c => isWinner(c));
  if (activeFilter.value === "hot") return displayCards.value.filter(c => isHot(c) && !isWinner(c));
  return displayCards.value;
});

function num(card: BingoCard, l: string, r: number): number | null {
  const m = card.cardMatrix;
  if (!m || !m[l as keyof typeof m]) return null;
  return (m[l as keyof typeof m] as number[])[r - 1] ?? null;
}

function drawn(card: BingoCard, l: string, r: number): boolean {
  const n = num(card, l, r);
  return n !== null && drawnSet.value.has(n);
}

function cellCls(card: BingoCard, l: string, r: number) {
  if (l === "N" && r === 3) return "ctn-cell--free";
  if (drawn(card, l, r))    return "ctn-cell--hit";
  return "";
}

function hits(card: BingoCard): number {
  let h = 0;
  for (const l of LETTERS)
    for (let r = 1; r <= 5; r++)
      if (!(l === "N" && r === 3) && drawn(card, l, r)) h++;
  return h;
}

function pct(card: BingoCard) { return Math.round((hits(card) / 24) * 100); }
function isHot(card: BingoCard)    { return hits(card) >= HOT; }
function isWinner(card: BingoCard) { return bingoSet.value.has(card.id); }

function getFilterCount(f: Filter): number {
  if (f === "win") return displayCards.value.filter(c => isWinner(c)).length;
  if (f === "hot") return displayCards.value.filter(c => isHot(c) && !isWinner(c)).length;
  return displayCards.value.length;
}

function shortSerial(s: string): string {
  if (!s) return "—";
  const p = s.split("-");
  return p.length >= 3 ? `${p[0]}-…-${p[p.length - 1]}` : s.slice(-14);
}

function fullBingo(card: BingoCard): boolean {
  for (const l of LETTERS)
    for (let r = 1; r <= 5; r++) {
      if (l === "N" && r === 3) continue;
      const n = num(card, l, r);
      if (n === null || !drawnSet.value.has(n)) return false;
    }
  return true;
}

watch(() => props.drawnNumbers, () => {
  if (!props.autoCheckBingo) return;
  for (const card of props.cartons) {
    if (bingoSet.value.has(card.id)) continue;
    if (fullBingo(card)) {
      bingoSet.value.add(card.id);
      emit("bingo-detected", card);
    }
  }
}, { deep: true });
</script>

<style scoped>
/* ── Root ── */
.ctn-root { width: 100%; display: flex; flex-direction: column; gap: 10px; }

/* ══════════════════════════════════
   UNIFIED TOOLBAR
══════════════════════════════════ */
.ctn-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  background: rgba(255,255,255,0.028);
  border: 1px solid rgba(80,110,220,0.13);
  border-radius: 12px;
  flex-wrap: wrap;
}

.ctn-bar__left {
  display: flex; align-items: center; gap: 7px;
  flex-shrink: 0;
}
.ctn-bar__title {
  font-size: 11px; font-weight: 700; color: #6a80b0;
  text-transform: uppercase; letter-spacing: 0.9px;
  display: flex; align-items: center; gap: 5px;
}
.ctn-bar__title i { color: #7b4fff; font-size: 10px; }
.ctn-bar__count {
  background: rgba(123,79,255,0.15); border: 1px solid rgba(123,79,255,0.28);
  color: #a090e0; border-radius: 10px; padding: 1px 7px;
  font-size: 10px; font-weight: 800;
}
.ctn-bar__drawn {
  display: flex; align-items: center; gap: 3px;
  font-size: 10px; font-weight: 700; color: #2af598;
  padding: 2px 7px;
  background: rgba(42,245,152,0.07);
  border: 1px solid rgba(42,245,152,0.18);
  border-radius: 10px;
  letter-spacing: 0.2px;
}
.ctn-bar__drawn i { font-size: 9px; opacity: 0.6; }
.ctn-bar__drawn em { opacity: 0.35; font-style: normal; font-weight: 500; }

/* Center filters */
.ctn-bar__filters {
  display: flex; align-items: center; gap: 4px;
  flex: 1; justify-content: center; flex-wrap: wrap;
}
.ctn-fbtn {
  display: flex; align-items: center; gap: 4px;
  padding: 3px 10px;
  border-radius: 8px;
  border: 1px solid rgba(80,110,220,0.18);
  background: transparent;
  color: #4a5e90; font-size: 10px; font-weight: 600;
  cursor: pointer; transition: all 0.15s ease;
  letter-spacing: 0.2px;
}
.ctn-fbtn:hover { border-color: rgba(100,140,255,0.35); color: #8090c0; }
.ctn-fbtn--active {
  background: rgba(100,140,255,0.1);
  border-color: rgba(100,140,255,0.38);
  color: #b0c4ff;
}
.ctn-fbtn__n {
  min-width: 14px; height: 14px; padding: 0 3px;
  border-radius: 7px; background: rgba(100,140,255,0.12);
  font-size: 9px; font-weight: 800; color: #6a80b0;
  display: inline-flex; align-items: center; justify-content: center;
}
.ctn-fbtn--active .ctn-fbtn__n { background: rgba(42,245,152,0.15); color: #2af598; }

/* Right: legend + size */
.ctn-bar__right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

.ctn-legend { display: flex; align-items: center; gap: 4px; }
.ctn-legend__dot {
  width: 14px; height: 14px; border-radius: 50%;
  font-size: 7.5px; font-weight: 800; color: #fff;
  display: flex; align-items: center; justify-content: center;
  text-shadow: 0 1px 2px rgba(0,0,0,0.6);
  box-shadow: 0 1px 4px rgba(0,0,0,0.4);
}
.ctn-legend__dot--b { background: linear-gradient(135deg,#1a5fff,#0e3ab0); }
.ctn-legend__dot--i { background: linear-gradient(135deg,#ff3a3a,#a00000); }
.ctn-legend__dot--n { background: linear-gradient(135deg,#d4b800,#8c7a00); }
.ctn-legend__dot--g { background: linear-gradient(135deg,#10c060,#067040); }
.ctn-legend__dot--o { background: linear-gradient(135deg,#b040ff,#7010c0); }

.ctn-sizebar { display: flex; gap: 3px; }
.ctn-sz {
  width: 26px; height: 26px; border-radius: 7px;
  border: 1px solid rgba(80,110,220,0.18);
  background: transparent; color: #384870;
  font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.14s ease;
}
.ctn-sz:hover { border-color: rgba(100,140,255,0.35); color: #6a80b0; }
.ctn-sz--on { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.38); color: #b0c4ff; }

/* ══════════════════════════════════
   GRID
══════════════════════════════════ */
.ctn-grid {
  display: grid; gap: 12px;
}
.ctn-grid--md { grid-template-columns: repeat(auto-fill, minmax(188px, 1fr)); }
.ctn-grid--sm { grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 8px; }

/* ══════════════════════════════════
   CARD
══════════════════════════════════ */
.ctn-card {
  position: relative; border-radius: 14px; overflow: hidden;
  background: linear-gradient(165deg, #0b1030 0%, #060820 100%);
  border: 1.5px solid rgba(70,100,210,0.17);
  box-shadow: 0 5px 22px rgba(0,0,0,0.5);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  animation: cin 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
  animation-delay: calc(var(--i,0) * 45ms);
  cursor: pointer;
}
.ctn-card:hover {
  transform: translateY(-4px) scale(1.012);
  box-shadow: 0 16px 44px rgba(0,0,0,0.6), 0 0 18px rgba(80,130,255,0.1);
  border-color: rgba(80,130,255,0.34);
}
.ctn-card--active {
  border-color: rgba(42,245,152,0.5) !important;
  box-shadow: 0 16px 44px rgba(0,0,0,0.6), 0 0 22px rgba(42,245,152,0.14) !important;
  transform: translateY(-4px) scale(1.012);
}
.ctn-card--hot   { border-color: rgba(255,140,30,0.35); }
.ctn-card--winner {
  border-color: rgba(255,210,0,0.55) !important;
  animation: cin 0.4s cubic-bezier(0.34,1.56,0.64,1) both, wpulse 2.4s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 45ms), 0.4s;
}
@keyframes cin {
  from { opacity:0; transform:translateY(18px) scale(0.94) }
  to   { opacity:1; transform:translateY(0) scale(1) }
}
@keyframes wpulse {
  0%,100% { box-shadow: 0 5px 22px rgba(0,0,0,0.5), 0 0 14px rgba(255,210,0,0.18) }
  50%     { box-shadow: 0 5px 22px rgba(0,0,0,0.5), 0 0 32px rgba(255,210,0,0.38) }
}

/* Glow overlay */
.ctn-card__glow {
  position: absolute; inset: 0; border-radius: inherit;
  background: radial-gradient(ellipse at 50% 0%, rgba(80,140,255,0.1), transparent 65%);
  opacity: 0; pointer-events: none;
  transition: opacity 0.25s;
}
.ctn-card:hover .ctn-card__glow,
.ctn-card--active .ctn-card__glow { opacity: 1; }
.ctn-card--hot .ctn-card__glow {
  background: radial-gradient(ellipse at 50% 0%, rgba(255,140,30,0.09), transparent 65%);
  opacity: 1;
}
.ctn-card--winner .ctn-card__glow {
  background: radial-gradient(ellipse at 50% 0%, rgba(255,210,0,0.12), transparent 65%);
  opacity: 1;
}

/* Hit badge */
.ctn-badge {
  position: absolute; top: 6px; right: 6px; z-index: 5;
  display: flex; align-items: center; gap: 3px;
  padding: 2px 6px; border-radius: 8px;
  background: rgba(255,120,30,0.85); backdrop-filter: blur(4px);
  font-size: 9px; font-weight: 800; color: #fff; letter-spacing: 0.2px;
}
.ctn-badge i { font-size: 7px; }
.ctn-badge--hot { background: rgba(255,140,30,0.88); }
.ctn-badge--win { background: rgba(220,170,0,0.92); color: #1a1000; }

/* ── BINGO Header ── */
.ctn-head {
  display: grid; grid-template-columns: repeat(5,1fr);
  background: linear-gradient(135deg,#0a052a 0%,#08103c 50%,#0a052a 100%);
  border-bottom: 1px solid rgba(60,90,180,0.18);
}
.ctn-head__l {
  display: flex; align-items: center; justify-content: center;
  padding: 6px 0;
  font-family: 'Anton','Righteous',sans-serif;
  font-size: 15px; letter-spacing: 0.3px;
}
.ctn-head__l--b { color: #4a9fff; }
.ctn-head__l--i { color: #ff6464; }
.ctn-head__l--n { color: #f0d040; }
.ctn-head__l--g { color: #40e090; }
.ctn-head__l--o { color: #c860ff; }
.ctn-grid--sm .ctn-head__l { font-size: 12px; padding: 5px 0; }

/* ── Grid body ── */
.ctn-body {
  display: grid; grid-template-columns: repeat(5,1fr);
  background: #eef2ff;
}
.ctn-cell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border: 0.5px solid rgba(140,165,230,0.38);
  transition: background 0.15s;
}
.ctn-cell--hit  { background: linear-gradient(135deg,#0e0628 0%,#07092e 100%); }
.ctn-cell--free { background: linear-gradient(135deg,#0c0424 0%,#080c38 100%); }

.ctn-num {
  font-family: 'Poppins',sans-serif; font-size: 12.5px;
  font-weight: 700; color: #19204c; line-height: 1;
}
.ctn-grid--sm .ctn-num { font-size: 10.5px; }

/* balls */
.ctn-ball {
  width: 74%; height: 74%; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  animation: bpop 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
  box-shadow: 0 2px 7px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.2);
}
.ctn-ball--b { background: linear-gradient(135deg,#1a60ff,#0d38b0); }
.ctn-ball--i { background: linear-gradient(135deg,#ff3838,#9c0000); }
.ctn-ball--n { background: linear-gradient(135deg,#d8c400,#8c7c00); }
.ctn-ball--g { background: linear-gradient(135deg,#0ec060,#06643a); }
.ctn-ball--o { background: linear-gradient(135deg,#b038ff,#700ec0); }
.ctn-ball span {
  font-family: 'Poppins',sans-serif; font-size: 10.5px;
  font-weight: 800; color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,0.55);
}
.ctn-grid--sm .ctn-ball span { font-size: 9px; }
@keyframes bpop {
  from { transform: scale(0) rotate(-18deg); opacity: 0 }
  to   { transform: scale(1) rotate(0deg);  opacity: 1 }
}

/* free center */
.ctn-free {
  font-size: 15px; line-height: 1;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.ctn-grid--sm .ctn-free { font-size: 12px; }

/* ── Progress strip ── */
.ctn-prog { height: 2.5px; background: rgba(255,255,255,0.05); }
.ctn-prog__fill {
  height: 100%;
  background: linear-gradient(90deg,#4a7fff,#2af598);
  border-radius: 2px;
  transition: width 0.5s cubic-bezier(0.4,0,0.2,1);
}
.ctn-prog__fill--hot { background: linear-gradient(90deg,#ff8c1a,#ffcc00); }
.ctn-prog__fill--win { background: linear-gradient(90deg,#ffd700,#ff9800); }

/* ── Footer ── */
.ctn-foot {
  display: flex; align-items: center; justify-content: space-between;
  padding: 4px 8px;
  background: rgba(0,0,0,0.5);
  border-top: 1px solid rgba(60,90,180,0.1);
}
.ctn-foot__serial {
  font-size: 8px; color: #2e3e68;
  font-family: 'Courier New',monospace; letter-spacing: 0.2px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%;
  display: flex; align-items: center; gap: 3px;
}
.ctn-foot__hits {
  font-size: 9.5px; font-weight: 800; color: #384870;
  font-family: 'Poppins',sans-serif; flex-shrink: 0;
}
.ctn-foot__hits small { font-size: 7.5px; opacity: 0.5; font-weight: 500; }
.ctn-foot__hits--hot { color: #ff9c38; }

/* ══════════════════════════════════
   DETAIL MODAL
══════════════════════════════════ */
.ctn-modal-bg {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(2,4,18,0.82); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.ctn-modal {
  position: relative; width: 100%; max-width: 400px;
  background: linear-gradient(160deg,#0c1340 0%,#060922 100%);
  border: 1.5px solid rgba(70,120,255,0.26);
  border-radius: 18px; padding: 22px 18px 18px;
  box-shadow: 0 24px 80px rgba(0,0,0,0.75), 0 0 36px rgba(70,120,255,0.08);
}
.ctn-modal__x {
  position: absolute; top: 12px; right: 12px;
  width: 26px; height: 26px; border-radius: 50%;
  border: 1px solid rgba(100,130,255,0.22); background: rgba(255,255,255,0.05);
  color: #5a70a0; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.14s;
}
.ctn-modal__x:hover { background: rgba(255,255,255,0.1); color: #b0c0e0; }
.ctn-modal__tag {
  display: inline-flex; align-items: center; gap: 5px;
  background: linear-gradient(135deg,#ffd700,#ffaa00);
  color: #1a1000; font-size: 10px; font-weight: 900;
  padding: 2px 10px; border-radius: 10px; letter-spacing: 0.5px;
  margin-bottom: 12px;
}

/* big card */
.ctn-dcard {
  border-radius: 10px; overflow: hidden;
  border: 1px solid rgba(70,100,200,0.22); margin-bottom: 14px;
}
.ctn-dcard__head {
  display: grid; grid-template-columns: repeat(5,1fr);
  background: linear-gradient(135deg,#0a052a,#08103c,#0a052a);
}
.ctn-dcard__body {
  display: grid; grid-template-columns: repeat(5,1fr);
  background: #eef2ff;
}
.ctn-dcell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border: 0.5px solid rgba(140,165,230,0.35);
}
.ctn-dcell.ctn-cell--hit  { background: linear-gradient(135deg,#0e0628,#07092e); }
.ctn-dcell.ctn-cell--free { background: linear-gradient(135deg,#0c0424,#080c38); }
.ctn-dfree {
  font-size: 20px;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.ctn-dball { width: 70%; height: 70%; }
.ctn-dnum  { font-size: 14px; font-weight: 700; color: #19204c; }

/* stats */
.ctn-stats {
  display: flex; border-radius: 10px; overflow: hidden;
  border: 1px solid rgba(70,100,200,0.18); margin-bottom: 10px;
}
.ctn-stat {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  padding: 9px 4px; background: rgba(255,255,255,0.025);
  border-right: 1px solid rgba(70,100,200,0.13);
}
.ctn-stat:last-child { border-right: none; }
.ctn-stat__v { font-size: 20px; font-weight: 800; color: #b0c4ff; line-height: 1; }
.ctn-stat__l { font-size: 8.5px; color: #384870; margin-top: 2px; letter-spacing: 0.3px; }

.ctn-modal__serial {
  text-align: center; font-size: 8px; color: #243055;
  font-family: 'Courier New',monospace;
}

/* ══════════════════════════════════
   EMPTY
══════════════════════════════════ */
.ctn-empty {
  display: flex; flex-direction: column; align-items: center;
  gap: 8px; padding: 55px 20px; color: #283660;
}
.ctn-empty i { font-size: 30px; opacity: 0.25; }
.ctn-empty p { font-size: 13px; font-weight: 600; margin: 0; color: #3a4e80; }

/* ══════════════════════════════════
   TRANSITIONS
══════════════════════════════════ */
.ctn-pop-enter-active { transition: opacity 0.35s ease, transform 0.35s ease; }
.ctn-pop-enter-from  { opacity: 0; transform: translateY(14px) scale(0.96); }
.ctn-pop-leave-active{ transition: opacity 0.15s ease; }
.ctn-pop-leave-to    { opacity: 0; }

.ctn-modal-enter-active { transition: opacity 0.2s ease; }
.ctn-modal-enter-from   { opacity: 0; }
.ctn-modal-leave-active { transition: opacity 0.15s ease; }
.ctn-modal-leave-to     { opacity: 0; }

/* ══════════════════════════════════
   RESPONSIVE
══════════════════════════════════ */
@media (max-width: 540px) {
  .ctn-grid--md { grid-template-columns: repeat(2, 1fr); gap: 8px; }
  .ctn-grid--sm { grid-template-columns: repeat(2, 1fr); gap: 7px; }
  .ctn-bar { gap: 6px; padding: 6px 10px; }
  .ctn-bar__filters { justify-content: flex-start; }
  .ctn-legend { display: none; }
}
</style>
