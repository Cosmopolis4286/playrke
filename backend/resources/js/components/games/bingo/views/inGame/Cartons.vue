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
          <i class="fas fa-random" />{{ totalDrawn }}<em>/{{ totalChips }}</em>
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
          <span v-for="col in variantDef.columns" :key="col.label" class="ctn-legend__dot" :class="`ctn-legend__dot--col${col.colorIdx}`" :title="col.label">{{ col.label }}</span>
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

        <!-- header columns -->
        <div class="ctn-head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
          <span v-for="col in variantDef.columns" :key="col.label" class="ctn-head__l" :class="`ctn-head__l--col${col.colorIdx}`">{{ col.label }}</span>
        </div>

        <!-- grid body -->
        <div class="ctn-body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
          <template v-for="col in variantDef.columns" :key="col.label">
            <div
              v-for="r in variantDef.numRows" :key="`${col.label}${r}`"
              class="ctn-cell"
              :class="[`ctn-cell--col${col.colorIdx}`, cellCls(card, col.label, r)]"
            >
              <template v-if="isFreeCell(col, r)">
                <span class="ctn-free">★</span>
              </template>
              <template v-else-if="drawn(card, col.label, r)">
                <div class="ctn-ball" :class="`ctn-ball--col${col.colorIdx}`">
                  <span>{{ num(card, col.label, r) }}</span>
                </div>
              </template>
              <template v-else>
                <span class="ctn-num" :class="{ 'ctn-num--empty': num(card, col.label, r) === null }">
                  {{ num(card, col.label, r) ?? '' }}
                </span>
              </template>
            </div>
          </template>
        </div>

        <!-- progress strip -->
        <div class="ctn-prog">
          <div class="ctn-prog__fill"
            :class="{ 'ctn-prog__fill--hot': isHot(card), 'ctn-prog__fill--win': isWinner(card) }"
            :style="{ width: `${(hits(card)/variantDef.totalCells)*100}%` }"
          />
        </div>

        <!-- footer -->
        <div class="ctn-foot">
          <span class="ctn-foot__serial">{{ shortSerial(card.serialNumber) }}</span>
          <span v-if="hits(card) > 0" class="ctn-foot__badge"
            :class="{ 'ctn-foot__badge--hot': isHot(card), 'ctn-foot__badge--win': isWinner(card) }">
            <i v-if="isWinner(card)" class="fas fa-trophy" />
            <i v-else-if="isHot(card)" class="fas fa-fire" />
            {{ hits(card) }}<small>/{{ variantDef.totalCells }}</small>
          </span>
          <span v-else class="ctn-foot__hits">0<small>/{{ variantDef.totalCells }}</small></span>
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
            <div class="ctn-dcard__head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
              <span v-for="col in variantDef.columns" :key="col.label" class="ctn-head__l" :class="`ctn-head__l--col${col.colorIdx}`">{{ col.label }}</span>
            </div>
            <div class="ctn-dcard__body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
              <template v-for="col in variantDef.columns" :key="col.label">
                <div v-for="r in variantDef.numRows" :key="`d${col.label}${r}`" class="ctn-dcell" :class="cellCls(selectedCard, col.label, r)">
                  <template v-if="isFreeCell(col, r)">
                    <span class="ctn-dfree">★</span>
                  </template>
                  <template v-else-if="drawn(selectedCard, col.label, r)">
                    <div class="ctn-ball ctn-dball" :class="`ctn-ball--col${col.colorIdx}`">
                      <span>{{ num(selectedCard, col.label, r) }}</span>
                    </div>
                  </template>
                  <template v-else>
                    <span class="ctn-dnum">{{ num(selectedCard, col.label, r) ?? '' }}</span>
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
              <span class="ctn-stat__v">{{ variantDef.totalCells - hits(selectedCard) }}</span>
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

    <!-- ── Modo espectador: sin cartones propios ── -->
    <template v-if="displayCards.length === 0">

      <!-- Si hay sorteo en curso → tablero de bolas espectador -->
      <div v-if="totalDrawn > 0" class="ctn-spectator">
        <div class="ctn-spectator__header">
          <i class="fas fa-eye" />
          <span>Modo espectador</span>
          <span class="ctn-spectator__count">{{ totalDrawn }}<small>/{{ totalChips }}</small> sorteadas</span>
        </div>

        <!-- Grilla de bolas por columna -->
        <div class="ctn-ball-grid">
          <template v-for="col in variantDef.columns" :key="col.label">
            <div class="ctn-ball-col">
              <div class="ctn-ball-col__hdr" :class="`ctn-ball-col__hdr--col${col.colorIdx}`">{{ col.label }}</div>
              <div
                v-for="n in colNumbers(col)"
                :key="n"
                class="ctn-ball-cell"
                :class="{
                  'ctn-ball-cell--hit':    drawnSet.has(n),
                  'ctn-ball-cell--latest': n === drawnNumbers?.[drawnNumbers.length - 1],
                  [`ctn-ball-cell--col${col.colorIdx}`]: true,
                }"
              >
                {{ n }}
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Si aún no hay sorteo → mensaje simple -->
      <div v-else class="ctn-empty">
        <i class="fas fa-eye" />
        <p>Modo espectador — el sorteo aún no ha comenzado</p>
      </div>
    </template>

  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref } from "vue";
import { type BingoCard } from "@js/Services/bingoService";
import { useBingoSound } from "@js/composables/games/bingo/useBingoSound";
import { getBingoVariantDef, type BingoVariantDef, type BingoVariantColumn } from "@/js/modules/games/bingo/utils/bingoVariants";

type Filter = "all" | "hot" | "win";

const HOT = 10;

interface Props {
  cartons?:        BingoCard[];
  customerLogo?:   string;
  roomId?:         string;
  drawnNumbers?:   number[];
  totalDrawn?:     number;
  autoCheckBingo?: boolean;
  gameVariant?:    string;
}

const props = withDefaults(defineProps<Props>(), {
  cartons:        () => [],
  customerLogo:   null,
  roomId:         "",
  drawnNumbers:   () => [],
  totalDrawn:     0,
  autoCheckBingo: true,
  gameVariant:    "BINGO_75",
});

const emit = defineEmits<{ (e: "bingo-detected", card: BingoCard): void }>();

const variantDef = computed<BingoVariantDef>(() => getBingoVariantDef(props.gameVariant));
const totalChips = computed(() => variantDef.value.totalChips);

const activeFilter = ref<Filter>("all");
const cardSize     = ref<"sm" | "md">("md");
const selectedId   = ref<string | null>(null);
const bingoSet     = ref<Set<string>>(new Set());

const { playSelect, playWinner } = useBingoSound();

const drawnSet     = computed(() => new Set(props.drawnNumbers));
const displayCards = computed(() => props.cartons);

const selectedCard = computed(() =>
  selectedId.value ? displayCards.value.find(c => c.id === selectedId.value) ?? null : null
);

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

function isFreeCell(col: BingoVariantColumn, r: number): boolean {
  return col.freeRow !== null && col.freeRow === r - 1;
}

function num(card: BingoCard, colLabel: string, r: number): number | null {
  const m = card.cardMatrix as Record<string, (number | null)[]> | null;
  if (!m) return null;
  const val = m[colLabel]?.[r - 1];
  return (val === undefined || val === null) ? null : val;
}

function drawn(card: BingoCard, colLabel: string, r: number): boolean {
  const n = num(card, colLabel, r);
  return n !== null && drawnSet.value.has(n);
}

function cellCls(card: BingoCard, colLabel: string, r: number) {
  const col = variantDef.value.columns.find(c => c.label === colLabel);
  if (col && isFreeCell(col, r)) return "ctn-cell--free";
  if (drawn(card, colLabel, r)) return "ctn-cell--hit";
  return "";
}

function hits(card: BingoCard): number {
  let h = 0;
  for (const col of variantDef.value.columns) {
    for (let r = 1; r <= variantDef.value.numRows; r++) {
      if (isFreeCell(col, r)) continue;
      if (drawn(card, col.label, r)) h++;
    }
  }
  return h;
}

function pct(card: BingoCard) { return Math.round((hits(card) / variantDef.value.totalCells) * 100); }
function isHot(card: BingoCard)    { return hits(card) >= HOT; }
function isWinner(card: BingoCard) { return bingoSet.value.has(card.id); }

function getFilterCount(f: Filter): number {
  if (f === "win") return displayCards.value.filter(c => isWinner(c)).length;
  if (f === "hot") return displayCards.value.filter(c => isHot(c) && !isWinner(c)).length;
  return displayCards.value.length;
}

function colNumbers(col: BingoVariantColumn): number[] {
  return Array.from({ length: col.max - col.min + 1 }, (_, i) => col.min + i);
}

function shortSerial(s: string): string {
  if (!s) return "—";
  const p = s.split("-");
  return p.length >= 3 ? `${p[0]}-…-${p[p.length - 1]}` : s.slice(-14);
}

function fullBingo(card: BingoCard): boolean {
  for (const col of variantDef.value.columns) {
    for (let r = 1; r <= variantDef.value.numRows; r++) {
      if (isFreeCell(col, r)) continue;
      const n = num(card, col.label, r);
      if (n === null || !drawnSet.value.has(n)) return false;
    }
  }
  return true;
}

watch(() => props.drawnNumbers, (next, prev) => {
  const newNums = next.filter(n => !prev?.includes(n));
  if (newNums.length > 0) {
    const anyHit = props.cartons.some(card =>
      variantDef.value.columns.some(col => {
        const m = card.cardMatrix as Record<string, (number | null)[]> | null;
        return m?.[col.label]?.some(n => n !== null && newNums.includes(n)) ?? false;
      })
    );
    if (anyHit) playSelect();
  }

  if (!props.autoCheckBingo) return;
  for (const card of props.cartons) {
    if (bingoSet.value.has(card.id)) continue;
    if (fullBingo(card)) {
      bingoSet.value.add(card.id);
      playWinner();
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
.ctn-legend__dot--col0 { background: linear-gradient(135deg,#1a5fff,#0e3ab0); }
.ctn-legend__dot--col1 { background: linear-gradient(135deg,#ff3a3a,#a00000); }
.ctn-legend__dot--col2 { background: linear-gradient(135deg,#d4b800,#8c7a00); }
.ctn-legend__dot--col3 { background: linear-gradient(135deg,#10c060,#067040); }
.ctn-legend__dot--col4 { background: linear-gradient(135deg,#b040ff,#7010c0); }

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
  position: relative; border-radius: 12px; overflow: hidden;
  background: #fff;
  border: 1.5px solid rgba(180,195,240,0.6);
  box-shadow: 0 4px 18px rgba(0,0,0,0.18), 0 1px 0 rgba(255,255,255,0.8) inset;
  transition: transform 0.22s cubic-bezier(0.22,0.68,0,1.2), box-shadow 0.22s ease, border-color 0.22s ease;
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  animation-delay: calc(var(--i,0) * 50ms);
  cursor: pointer;
}
/* shimmer al hover */
.ctn-card::after {
  content: '';
  position: absolute; inset: 0; border-radius: inherit;
  background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,0.55) 50%, transparent 70%);
  transform: translateX(-110%);
  pointer-events: none;
  z-index: 10;
}
.ctn-card:hover::after { animation: shimmer 0.55s ease forwards; }
@keyframes shimmer { to { transform: translateX(110%); } }

.ctn-card:hover {
  transform: translateY(-4px) scale(1.012);
  box-shadow: 0 14px 40px rgba(0,0,0,0.2), 0 0 0 2px rgba(80,130,255,0.22);
  border-color: rgba(80,130,255,0.5);
}
.ctn-card--active {
  border-color: rgba(42,200,130,0.7) !important;
  box-shadow: 0 14px 40px rgba(0,0,0,0.2), 0 0 0 2px rgba(42,200,130,0.35) !important;
  transform: translateY(-4px) scale(1.012);
}
.ctn-card--hot {
  border-color: rgba(255,140,30,0.55);
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, hpulse 2s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 50ms), 0.45s;
}
.ctn-card--winner {
  border-color: rgba(220,170,0,0.7) !important;
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, wpulse 2s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 50ms), 0.45s;
}
@keyframes cin {
  from { opacity:0; transform:translateY(20px) scale(0.93) }
  to   { opacity:1; transform:translateY(0) scale(1) }
}
@keyframes hpulse {
  0%,100% { box-shadow: 0 4px 18px rgba(0,0,0,0.18), 0 0 0 1.5px rgba(255,140,30,0.35) }
  50%     { box-shadow: 0 4px 18px rgba(0,0,0,0.18), 0 0 0 3px rgba(255,140,30,0.6) }
}
@keyframes wpulse {
  0%,100% { box-shadow: 0 4px 18px rgba(0,0,0,0.18), 0 0 0 2px rgba(220,170,0,0.4) }
  50%     { box-shadow: 0 4px 18px rgba(0,0,0,0.18), 0 0 0 4px rgba(220,170,0,0.7) }
}

/* Glow overlay — no aplica en diseño blanco */
.ctn-card__glow { display: none; }

/* ── Header columns ── */
.ctn-head {
  display: grid;
  position: relative;
}
.ctn-head__l {
  display: flex; align-items: center; justify-content: center;
  padding: 9px 0 8px;
  font-family: 'Anton','Impact',sans-serif;
  font-size: 17px; letter-spacing: 1px; font-weight: 900;
  color: #fff;
  text-shadow: 0 2px 6px rgba(0,0,0,0.35);
  position: relative;
}
.ctn-head__l::after {
  content: ''; position: absolute; bottom: 0; left: 10%; right: 10%; height: 2px;
  background: rgba(255,255,255,0.25); border-radius: 2px;
}
.ctn-head__l--col0 { background: linear-gradient(160deg,#2060ff,#1040c8); }
.ctn-head__l--col1 { background: linear-gradient(160deg,#e02020,#a00000); }
.ctn-head__l--col2 { background: linear-gradient(160deg,#c8a000,#906e00); }
.ctn-head__l--col3 { background: linear-gradient(160deg,#0ab848,#077030); }
.ctn-head__l--col4 { background: linear-gradient(160deg,#9c18e8,#6008b0); }
.ctn-grid--sm .ctn-head__l { font-size: 13px; padding: 7px 0 6px; }

/* ── Grid body ── */
.ctn-body {
  display: grid;
  background: #fff;
  column-gap: 0; row-gap: 0;
}
.ctn-cell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border-right: 1px solid #e8ecf8;
  border-bottom: 1px solid #e8ecf8;
  transition: background 0.12s;
  position: relative;
}
/* tinte de columna en reposo — indexed colors */
.ctn-cell--col0 { background: #f4f7ff; }
.ctn-cell--col1 { background: #fff4f4; }
.ctn-cell--col2 { background: #fdfaf0; }
.ctn-cell--col3 { background: #f2fbf5; }
.ctn-cell--col4 { background: #faf3ff; }

/* celda marcada */
.ctn-cell--hit.ctn-cell--col0 { background: rgba(32,96,255,0.1); }
.ctn-cell--hit.ctn-cell--col1 { background: rgba(224,32,32,0.1); }
.ctn-cell--hit.ctn-cell--col2 { background: rgba(200,160,0,0.1); }
.ctn-cell--hit.ctn-cell--col3 { background: rgba(10,184,72,0.1); }
.ctn-cell--hit.ctn-cell--col4 { background: rgba(156,24,232,0.1); }

.ctn-cell--free { background: linear-gradient(135deg,rgba(42,245,152,0.12),rgba(0,158,253,0.08)) !important; }

/* número en reposo */
.ctn-num {
  font-family: 'Poppins','Segoe UI',sans-serif; font-size: 13.5px;
  font-weight: 700; color: #22274a; line-height: 1;
  letter-spacing: -0.3px;
}
.ctn-num--empty { opacity: 0; pointer-events: none; }
.ctn-grid--sm .ctn-num { font-size: 11px; }

/* ── Bolas ── */
.ctn-ball {
  width: 78%; height: 78%; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow:
    0 3px 10px rgba(0,0,0,0.3),
    inset 0 -2px 4px rgba(0,0,0,0.2),
    inset 0 2px 4px rgba(255,255,255,0.35);
}
.ctn-ball--col0 { background: radial-gradient(circle at 38% 32%, #6090ff, #1040d0 75%); }
.ctn-ball--col1 { background: radial-gradient(circle at 38% 32%, #ff6060, #aa0000 75%); }
.ctn-ball--col2 { background: radial-gradient(circle at 38% 32%, #f0d840, #9c7c00 75%); }
.ctn-ball--col3 { background: radial-gradient(circle at 38% 32%, #40e888, #057838 75%); }
.ctn-ball--col4 { background: radial-gradient(circle at 38% 32%, #d860ff, #7008c8 75%); }
.ctn-ball span {
  font-family: 'Poppins',sans-serif; font-size: 11px;
  font-weight: 900; color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,0.55);
  line-height: 1;
}
.ctn-grid--sm .ctn-ball span { font-size: 9px; }

@keyframes bpop {
  0%   { transform: scale(0) rotate(-20deg); opacity: 0 }
  65%  { transform: scale(1.22) rotate(5deg); opacity: 1 }
  82%  { transform: scale(0.95) rotate(-1deg) }
  100% { transform: scale(1) rotate(0deg) }
}
@keyframes cell-flash {
  0%   { filter: brightness(2) saturate(1.5); }
  100% { filter: brightness(1) saturate(1); }
}
.ctn-cell--hit .ctn-ball {
  animation: bpop 0.42s cubic-bezier(0.34,1.56,0.64,1) both,
             cell-flash 0.55s ease both;
}

/* estrella central */
.ctn-free {
  font-size: 18px; line-height: 1;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 1px 3px rgba(42,245,152,0.4));
}
.ctn-grid--sm .ctn-free { font-size: 14px; }

/* ── Barra de progreso ── */
.ctn-prog {
  height: 3px;
  background: #e8ecf8;
}
.ctn-prog__fill {
  height: 100%;
  background: linear-gradient(90deg,#4a7fff,#2af598);
  transition: width 0.55s cubic-bezier(0.4,0,0.2,1);
}
.ctn-prog__fill--hot { background: linear-gradient(90deg,#ff8c1a,#ffcc00); }
.ctn-prog__fill--win { background: linear-gradient(90deg,#ffd700,#ff9800); }

/* ── Footer ── */
.ctn-foot {
  display: flex; align-items: center; justify-content: space-between;
  padding: 5px 8px;
  background: #fff;
  border-top: 1px solid #e8ecf8;
  position: relative;
  gap: 6px;
}

.ctn-foot__serial {
  font-size: 7.5px; color: #9aa0c0;
  font-family: 'Courier New',monospace; letter-spacing: 0.4px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  flex: 1; min-width: 0;
}
/* badge de hits */
.ctn-foot__badge {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 7px; border-radius: 20px;
  font-size: 9.5px; font-weight: 800; color: #fff;
  background: rgba(255,140,30,0.85);
  border: 1px solid rgba(255,160,50,0.4);
  flex-shrink: 0; white-space: nowrap;
}
.ctn-foot__badge i { font-size: 7.5px; }
.ctn-foot__badge--hot {
  background: linear-gradient(90deg,#ff8c1a,#ffcc00);
  border-color: rgba(255,200,0,0.4);
  color: #3a1a00;
}
.ctn-foot__badge--win {
  background: linear-gradient(90deg,#e8b800,#ffd94a);
  border-color: rgba(255,220,80,0.5);
  color: #2a1800;
}
.ctn-foot__badge small { font-size: 7.5px; opacity: 0.6; font-weight: 500; }
/* hits neutral (0 aciertos) */
.ctn-foot__hits {
  font-size: 9px; font-weight: 700; color: #b0b8d8;
  flex-shrink: 0;
}
.ctn-foot__hits small { font-size: 7.5px; opacity: 0.5; font-weight: 500; }

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

/* ══════════════════════════════════
   MODO ESPECTADOR
══════════════════════════════════ */
.ctn-spectator {
  padding: 12px 0 4px;
}

.ctn-spectator__header {
  display: flex; align-items: center; gap: 8px;
  font-size: 11px; font-weight: 700; color: #4a6aaa;
  text-transform: uppercase; letter-spacing: 0.8px;
  margin-bottom: 12px; padding: 0 2px;
}
.ctn-spectator__header i { color: #3d5280; font-size: 13px; }
.ctn-spectator__count {
  margin-left: auto; font-size: 12px; font-weight: 800; color: #7c9dff;
}
.ctn-spectator__count small { font-weight: 400; color: #4a6aaa; margin-left: 1px; }

/* Grilla de bolas 5 columnas */
.ctn-ball-grid {
  display: flex; gap: 6px;
}

.ctn-ball-col {
  flex: 1; display: flex; flex-direction: column; gap: 3px;
}

.ctn-ball-col__hdr {
  text-align: center; font-family: 'Anton', sans-serif; font-size: 15px;
  padding: 4px 0; border-radius: 6px; margin-bottom: 2px;
}
.ctn-ball-col__hdr--col0 { background: rgba(32,96,255,0.18); color: #4d8aff; }
.ctn-ball-col__hdr--col1 { background: rgba(255,48,48,0.16); color: #ff6060; }
.ctn-ball-col__hdr--col2 { background: rgba(212,186,0,0.16); color: #d4c400; }
.ctn-ball-col__hdr--col3 { background: rgba(12,187,90,0.16); color: #0cbb5a; }
.ctn-ball-col__hdr--col4 { background: rgba(168,48,255,0.16); color: #b060ff; }

.ctn-ball-cell {
  text-align: center; font-size: 12px; font-weight: 700;
  padding: 5px 2px; border-radius: 6px;
  color: #2c3a5a;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.05);
  transition: background 0.18s, color 0.18s, transform 0.18s;
}

/* Sorteada */
.ctn-ball-cell--hit.ctn-ball-cell--col0 { background: rgba(32,96,255,0.28); color: #8ab4ff; border-color: rgba(32,96,255,0.3); }
.ctn-ball-cell--hit.ctn-ball-cell--col1 { background: rgba(255,48,48,0.25); color: #ff9090; border-color: rgba(255,48,48,0.3); }
.ctn-ball-cell--hit.ctn-ball-cell--col2 { background: rgba(212,186,0,0.25); color: #e8d400; border-color: rgba(212,186,0,0.3); }
.ctn-ball-cell--hit.ctn-ball-cell--col3 { background: rgba(12,187,90,0.25); color: #3fe88a; border-color: rgba(12,187,90,0.3); }
.ctn-ball-cell--hit.ctn-ball-cell--col4 { background: rgba(168,48,255,0.25); color: #cc88ff; border-color: rgba(168,48,255,0.3); }

/* Última sorteada */
.ctn-ball-cell--latest {
  transform: scale(1.12);
  box-shadow: 0 0 8px rgba(255,255,255,0.3);
  font-size: 13px;
}
</style>
