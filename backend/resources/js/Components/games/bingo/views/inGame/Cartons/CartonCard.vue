<script setup lang="ts">
import type { BingoVariantDef } from '@/js/modules/games/bingo/utils/bingoVariants'
import type { BingoCard } from '@/js/types/games/bingo/card/in-game/bingo-card'
import { shortSerial } from '@js/utils/bingoUtils'

const props = defineProps<{
  card: BingoCard
  idx: number
  variantDef: BingoVariantDef
  cardSize: 'sm' | 'md'
  isWinner: boolean
  isHot: boolean
  isActive: boolean
  isHit: boolean
  isFreshHot: boolean
  isFocusPulse: boolean
  num: (card: BingoCard, colLabel: string, r: number) => number | null
  drawn: (card: BingoCard, colLabel: string, r: number) => boolean
  cellCls: (card: BingoCard, colLabel: string, r: number) => string
  isFreeCell: (col: { freeRow: number | null }, r: number) => boolean
  hits: number
}>()

defineEmits<{
  click: []
}>()
</script>

<template>
  <div
    :data-card-id="card.id"
    class="ctn-card"
    :class="{
      'ctn-card--sm':        cardSize === 'sm',
      'ctn-card--winner':    isWinner,
      'ctn-card--hot':       isHot && !isWinner,
      'ctn-card--active':    isActive,
      'ctn-card--hit-flash': isHit && !isWinner,
      'ctn-card--fresh-hot': isFreshHot,
      'ctn-card--focus-pulse': isFocusPulse,
    }"
    :style="{ '--i': idx }"
    translate="no"
    @click="$emit('click')"
  >
    <!-- Header -->
    <div class="ctn-head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <span v-for="col in variantDef.columns" :key="col.label" class="ctn-head__l" :class="`ctn-head__l--col${col.colorIdx}`">{{ col.label }}</span>
    </div>

    <!-- Body -->
    <div class="ctn-body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <template v-for="r in variantDef.numRows" :key="r">
        <div
          v-for="col in variantDef.columns" :key="`${col.label}${r}`"
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

    <!-- Progress strip -->
    <div class="ctn-prog">
      <div class="ctn-prog__fill"
        :class="{ 'ctn-prog__fill--hot': isHot, 'ctn-prog__fill--win': isWinner }"
        :style="{ width: `${(hits/variantDef.totalCells)*100}%` }"
      />
    </div>

    <!-- Footer -->
    <div class="ctn-foot">
      <span class="ctn-foot__serial">{{ shortSerial(card.serialNumber) }}</span>
      <span v-if="isWinner" class="ctn-foot__badge ctn-foot__badge--win">
        <i class="fas fa-trophy" /> BINGO
      </span>
      <span v-else-if="isHot" class="ctn-foot__badge ctn-foot__badge--hot">
        <i class="fas fa-fire" /> {{ hits }}<small>/{{ variantDef.totalCells }}</small>
      </span>
      <span v-else-if="hits > 0" class="ctn-foot__hits">
        {{ hits }}<small>/{{ variantDef.totalCells }}</small>
      </span>
      <span v-else class="ctn-foot__hits ctn-foot__hits--zero">
        0<small>/{{ variantDef.totalCells }}</small>
      </span>
    </div>
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   CARD
═══════════════════════════════════════════ */
.ctn-card {
  position: relative; border-radius: 14px; overflow: hidden;
  background: #fff;
  border: 1.5px solid rgba(180,195,240,0.55);
  box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 1px 0 rgba(255,255,255,0.9) inset;
  transition: transform 0.22s cubic-bezier(0.22,0.68,0,1.2), box-shadow 0.22s ease, border-color 0.22s ease;
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  animation-delay: calc(var(--i,0) * 40ms);
  cursor: pointer;
}
/* shimmer on hover */
.ctn-card::after {
  content: ''; position: absolute; inset: 0; border-radius: inherit;
  background: linear-gradient(110deg, transparent 28%, rgba(255,255,255,0.6) 50%, transparent 72%);
  transform: translateX(-120%); pointer-events: none; z-index: 10;
}
.ctn-card:hover::after { animation: shimmer 0.5s ease forwards; }
@keyframes shimmer { to { transform: translateX(120%); } }

.ctn-card:hover {
  transform: translateY(-5px) scale(1.015);
  box-shadow: 0 16px 44px rgba(0,0,0,0.18), 0 0 0 2px rgba(80,130,255,0.25);
  border-color: rgba(80,130,255,0.5);
}
.ctn-card--active {
  border-color: rgba(42,200,130,0.65) !important;
  box-shadow: 0 16px 44px rgba(0,0,0,0.18), 0 0 0 2px rgba(42,200,130,0.35) !important;
  transform: translateY(-5px) scale(1.015);
}
/* Feedback táctil — :hover no dispara en touch, :active sí y da la
   sensación de "presioné esto" inmediata que se espera de una app nativa. */
.ctn-card:active { transform: scale(0.97); transition-duration: 0.1s; }
.ctn-card--hot {
  border-color: rgba(255,140,30,0.5);
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, hpulse 2.2s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 40ms), 0.45s;
}
.ctn-card--winner {
  border-color: rgba(220,170,0,0.7) !important;
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, wpulse 2.2s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 40ms), 0.45s;
}
@keyframes cin    { from { opacity:0; transform:translateY(22px) scale(0.92) } to { opacity:1; transform:translateY(0) scale(1) } }
@keyframes hpulse { 0%,100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 1.5px rgba(255,140,30,0.3) } 50% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 3.5px rgba(255,140,30,0.6) } }
@keyframes wpulse { 0%,100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 2px rgba(220,170,0,0.38) } 50% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 4px rgba(220,170,0,0.7) } }

/* Fresh-hot — parpadeo naranja al cruzar el umbral HOT por primera vez */
.ctn-card--fresh-hot { animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, fresh-hot-ring 1.5s ease-out forwards !important; }
@keyframes fresh-hot-ring {
  0%   { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 0 rgba(255,140,30,0.0); }
  15%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 4px rgba(255,140,30,0.8), 0 0 24px 4px rgba(255,140,30,0.35); }
  60%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 3px rgba(255,140,30,0.4); }
  100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 1.5px rgba(255,140,30,0.3); }
}

/* Hit flash — anillo que aparece y se desvanece en 700ms cuando una bola impacta el cartón */
.ctn-card--hit-flash { animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, card-hit-ring 0.7s ease-out forwards !important; }
@keyframes card-hit-ring {
  0%   { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 0 rgba(100,180,255,0.0),  0 0 0 0 rgba(42,245,152,0.0); }
  20%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 3px rgba(100,180,255,0.7), 0 0 18px 2px rgba(42,245,152,0.25); }
  100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 5px rgba(100,180,255,0.0), 0 0 0 0 rgba(42,245,152,0.0); }
}

/* Focus pulse — resalta el cartón al hacer click en él desde el header (fichas faltantes) */
.ctn-card--focus-pulse { animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, focus-pulse-ring 1.6s ease-out forwards !important; }
@keyframes focus-pulse-ring {
  0%   { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 0 rgba(123,79,255,0.0); }
  12%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 4px rgba(123,79,255,0.85), 0 0 26px 5px rgba(123,79,255,0.4); }
  100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 0 rgba(123,79,255,0.0); }
}

/* ── Header ── */
.ctn-head { display: grid; }
.ctn-head__l {
  display: flex; align-items: center; justify-content: center;
  padding: 8px 0 7px;
  font-family: 'Anton','Impact',sans-serif; font-size: 17px;
  letter-spacing: 1px; font-weight: 900; color: #fff;
  text-shadow: 0 2px 5px rgba(0,0,0,0.3); position: relative;
}
.ctn-head__l::after {
  content: ''; position: absolute; bottom: 0; left: 12%; right: 12%; height: 2px;
  background: rgba(255,255,255,0.22); border-radius: 2px;
}
.ctn-head__l--col0 { background: linear-gradient(160deg, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-head__l--col1 { background: linear-gradient(160deg, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-head__l--col2 { background: linear-gradient(160deg, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-head__l--col3 { background: linear-gradient(160deg, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-head__l--col4 { background: linear-gradient(160deg, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }
.ctn-card--sm .ctn-head__l { font-size: 13px; padding: 6px 0 5px; }

/* ── Body ── */
.ctn-body { display: grid; background: #fff; }
.ctn-cell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border-right: 1px solid #eaeefc; border-bottom: 1px solid #eaeefc;
  transition: background 0.1s; position: relative;
}
.ctn-cell--col0 { background: color-mix(in srgb, var(--bc0-solid) 6%, #fff); }
.ctn-cell--col1 { background: color-mix(in srgb, var(--bc1-solid) 6%, #fff); }
.ctn-cell--col2 { background: color-mix(in srgb, var(--bc2-solid) 6%, #fff); }
.ctn-cell--col3 { background: color-mix(in srgb, var(--bc3-solid) 5%, #fff); }
.ctn-cell--col4 { background: color-mix(in srgb, var(--bc4-solid) 6%, #fff); }

.ctn-cell--hit.ctn-cell--col0 { background: var(--bc0-tint); }
.ctn-cell--hit.ctn-cell--col1 { background: var(--bc1-tint); }
.ctn-cell--hit.ctn-cell--col2 { background: var(--bc2-tint); }
.ctn-cell--hit.ctn-cell--col3 { background: var(--bc3-tint); }
.ctn-cell--hit.ctn-cell--col4 { background: var(--bc4-tint); }
.ctn-cell--free { background: linear-gradient(135deg,rgba(42,245,152,0.1),rgba(0,158,253,0.07)) !important; }

.ctn-num {
  font-family: 'Poppins','Segoe UI',sans-serif; font-size: 13px;
  font-weight: 700; color: #1e2444; line-height: 1; letter-spacing: -0.3px;
}
.ctn-num--empty { opacity: 0; pointer-events: none; }
.ctn-card--sm .ctn-num { font-size: 11px; }

/* ── Balls ── */
.ctn-ball {
  width: 78%; height: 78%; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 3px 10px rgba(0,0,0,0.28), inset 0 -2px 4px rgba(0,0,0,0.18), inset 0 2px 4px rgba(255,255,255,0.35);
}
.ctn-ball--col0 { background: radial-gradient(circle at 36% 30%, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-ball--col1 { background: radial-gradient(circle at 36% 30%, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-ball--col2 { background: radial-gradient(circle at 36% 30%, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-ball--col3 { background: radial-gradient(circle at 36% 30%, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-ball--col4 { background: radial-gradient(circle at 36% 30%, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }
.ctn-ball span {
  font-family: 'Poppins',sans-serif; font-size: 11px; font-weight: 900; color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,0.5); line-height: 1;
}
.ctn-card--sm .ctn-ball span { font-size: 9px; }

@keyframes bpop {
  0%   { transform: scale(0) rotate(-18deg); opacity: 0 }
  65%  { transform: scale(1.2) rotate(4deg); opacity: 1 }
  82%  { transform: scale(0.95) rotate(-1deg) }
  100% { transform: scale(1) rotate(0deg) }
}
@keyframes cell-flash {
  0%   { filter: brightness(1.8) saturate(1.5); }
  100% { filter: brightness(1) saturate(1); }
}
.ctn-cell--hit .ctn-ball {
  animation: bpop 0.42s cubic-bezier(0.34,1.56,0.64,1) both, cell-flash 0.5s ease both;
}

.ctn-free {
  font-size: 18px; line-height: 1;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 1px 3px rgba(42,245,152,0.35));
}
.ctn-card--sm .ctn-free { font-size: 14px; }

/* ── Progress strip ── */
.ctn-prog { height: 3px; background: #eaeefc; }
.ctn-prog__fill { height: 100%; background: linear-gradient(90deg,#4a7fff,#2af598); transition: width 0.55s cubic-bezier(0.4,0,0.2,1); }
.ctn-prog__fill--hot { background: linear-gradient(90deg,#ff8c1a,#ffcc00); }
.ctn-prog__fill--win { background: linear-gradient(90deg,#ffd700,#ff9800); }

/* ── Footer ── */
.ctn-foot {
  display: flex; align-items: center; justify-content: space-between;
  padding: 5px 8px; background: #fff; border-top: 1px solid #eaeefc; gap: 5px;
}
.ctn-foot__serial {
  font-size: 7.5px; color: #9aa0c0; font-family: 'Courier New',monospace;
  letter-spacing: 0.3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  flex: 1; min-width: 0;
}
.ctn-foot__badge {
  display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px;
  border-radius: 20px; font-size: 9px; font-weight: 800; color: #fff;
  flex-shrink: 0; white-space: nowrap; letter-spacing: 0.3px;
}
.ctn-foot__badge i    { font-size: 7.5px; }
.ctn-foot__badge small { font-size: 7px; opacity: 0.6; font-weight: 500; }
.ctn-foot__badge--hot { background: linear-gradient(90deg,#ff8c1a,#ffcc00); color: #2a1000; }
.ctn-foot__badge--win { background: linear-gradient(90deg,#e8b800,#ffd94a); color: #1a0e00; }
.ctn-foot__hits       { font-size: 9px; font-weight: 700; color: #b0b8d8; flex-shrink: 0; }
.ctn-foot__hits small { font-size: 7.5px; opacity: 0.5; font-weight: 500; }
.ctn-foot__hits--zero { color: #d0d4e8; }
</style>
