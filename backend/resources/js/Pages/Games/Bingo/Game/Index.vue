<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import "@/css/games/bingo/bingo-in-game.css";
import BingoCartons from "@js/Components/Games/Bingo/views/inGame/Cartons.vue";
import type { BingoRoom, BingoCard } from '@js/Services/bingoService';

interface Player { ctns: number }

interface Props {
  title?: string;
  room: BingoRoom & { Config: { rounds?: number; bingo_patterns?: boolean } };
  cartons?: BingoCard[];
  customerLogo?: string;
  currencySym?: string;
  prizePool?: number;
  prizePoolType?: string;
  round?: number;
  rounds?: number;
  bingoPattern?: string;
  vanDraw?: number[];
  player?: Player;
  encrypt?: boolean;
  chipsRest?: number[];
  draw?: number[];
  timeServ?: number;
  allChips?: number[];
  infoGame?: any;
  creatorType?: string;
  creatorId?: string;
  currencyId?: string;
  cardPrice?: number;
  startTime?: number;
  closingRoute?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: "Juego de Bingo",
  cartons: () => [],
  customerLogo: null,
  currencySym: "$",
  prizePool: 0,
  prizePoolType: "PREMIO",
  round: 1,
  bingoPattern: "",
  vanDraw: () => [],
  player: () => ({ ctns: 0 }),
  encrypt: false,
  chipsRest: () => [],
  draw: () => [],
  timeServ: 0,
  allChips: () => [],
  infoGame: null,
  creatorType: "",
  creatorId: "",
  currencyId: "",
  cardPrice: 0,
  startTime: 0,
  closingRoute: "",
});

const prizeFormatted = computed(() => {
  const n = props.prizePool ?? 0;
  return n % 1 === 0 ? n.toString() : n.toFixed(2);
});

const cardCount = computed(() => props.cartons?.length ?? 0);

// Shimmer reveal on mount
const revealed = ref(false);
onMounted(() => {
  setTimeout(() => { revealed.value = true }, 80);
});
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <div class="ig-inner">

      <!-- ══════════════════════════════════════
           INFO PANEL
      ══════════════════════════════════════ -->
      <div class="ig-panel">

        <!-- Prize -->
        <div class="ig-prize">
          <span class="ig-prize__label">
            <i class="fas fa-trophy ig-prize__icon" />
            {{ prizePoolType }}
          </span>
          <div class="ig-prize__amount">
            <span class="ig-prize__sym">{{ currencySym }}</span>
            <span class="ig-prize__num">{{ prizeFormatted }}</span>
          </div>
        </div>

        <!-- Divider -->
        <div class="ig-panel__divider" />

        <!-- Meta chips -->
        <div class="ig-meta">
          <div v-if="rounds" class="ig-badge ig-badge--round">
            <i class="fas fa-sync-alt" />
            RONDA <strong>#{{ round }}</strong>
          </div>
          <div v-if="bingoPattern" class="ig-badge ig-badge--pattern">
            <i class="fas fa-th" />
            {{ bingoPattern }}
          </div>
          <div class="ig-badge ig-badge--cards">
            <i class="fas fa-ticket-alt" />
            {{ cardCount }} cartón{{ cardCount === 1 ? '' : 'es' }}
          </div>
        </div>

      </div>

      <!-- ══════════════════════════════════════
           CARDS GRID
      ══════════════════════════════════════ -->
      <div class="ig-cards-section">
        <BingoCartons
          :cartons="cartons ?? []"
          :customerLogo="customerLogo ?? null"
          :roomId="room.id?.toString()"
        />
      </div>

    </div>
  </div>
</template>

<style>
/* ── body + AdminLTE wrappers ── */
html, body {
  height: auto !important;
  min-height: 100%;
  overflow-y: auto !important;
  overflow-x: hidden !important;
}
body,
body.hold-transition,
#app-wrapper,
.wrapper,
.content-wrapper {
  background-color: #04081a !important;
  background-image: none !important;
}
.content-header {
  display: none !important;
  padding: 0 !important;
  min-height: 0 !important;
  height: 0 !important;
}
.content-wrapper > .content {
  padding: 0 !important;
  display: flex;
  flex-direction: column;
  overflow: visible !important;
  height: auto !important;
  min-height: 100vh;
}
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: auto !important;
  min-height: 100vh;
  overflow: visible !important;
}
.wrapper,
.content-wrapper {
  height: auto !important;
  min-height: 100vh;
  overflow: visible !important;
}
</style>

<style scoped>
/* ════════════════════════════════════════
   PAGE — fondo estelar idéntico a GameRooms
════════════════════════════════════════ */
.ig-page {
  flex: 1;
  min-height: 100vh;
  padding: 24px 16px 64px;
  position: relative;
  isolation: isolate;
  overflow-y: auto;
  overflow-x: hidden;
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.4s ease, transform 0.4s ease;
  scrollbar-width: thin;
  scrollbar-color: #7b4fff rgba(255,255,255,0.03);

  /* Nebulosa base */
  background:
    radial-gradient(ellipse 90% 55% at 15%  5%,  rgba(80,  0, 200, 0.30) 0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%,  rgba( 0, 60, 220, 0.24) 0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%,  rgba( 0,150, 110, 0.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%,  rgba(130,  0, 210, 0.20) 0%, transparent 58%),
    radial-gradient(ellipse 45% 38% at  8% 58%,  rgba( 0, 90, 255, 0.14) 0%, transparent 52%),
    radial-gradient(ellipse 35% 30% at 40% 40%,  rgba( 0,180, 255, 0.08) 0%, transparent 50%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}
.ig-page--revealed {
  opacity: 1;
  transform: translateY(0);
}

/* Scrollbar */
.ig-page::-webkit-scrollbar { width: 4px }
.ig-page::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.03);
  border-radius: 10px;
}
.ig-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
  box-shadow: 0 0 6px rgba(42,245,152,0.45);
}
.ig-page::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #9d6fff 0%, #3fffa8 45%, #33b8ff 100%);
  box-shadow: 0 0 10px rgba(42,245,152,0.7);
}
.ig-page::-webkit-scrollbar-button { display: none }

/* ── Estrellas pequeñas (capa 1) ── */
.ig-page::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: -2;
  pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  6%  8%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(1px 1px at 14% 22%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 24%  4%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 38%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 16%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  7%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 30%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 88%  5%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 95% 22%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at  3% 45%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 60%, rgba(255,255,255,0.40) 0%, transparent 100%),
    radial-gradient(1px 1px at 20% 75%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 30% 52%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 42% 68%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 62% 80%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 72% 55%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 93% 62%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at  8% 88%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 22% 93%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 38% 85%, rgba(255,255,255,0.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 50% 96%, rgba(255,255,255,0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 68% 90%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 80% 83%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 91% 92%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(180,200,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(200,180,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,220,255,0.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 35% 72%, rgba(255,200,200,0.78) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,220,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 90% 45%, rgba(255,240,180,0.80) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}

/* ── Estrellas medianas (capa 2) ── */
.ig-page::after {
  content: "";
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-image:
    radial-gradient(2px 2px at 10% 15%, rgba(255,255,255,0.95) 0%, transparent 100%),
    radial-gradient(2px 2px at 28% 32%, rgba(200,210,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 45%  8%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180,200,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 96% 38%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at  5% 55%, rgba(200,255,240,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 23% 70%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 55% 62%, rgba(220,200,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 74% 78%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 88% 58%, rgba(200,240,255,0.88) 0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1.00) 0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220,200,255,0.96) 0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(3px 3px at 92% 72%, rgba(200,240,255,0.98) 0%, transparent 100%),
    radial-gradient(3px 3px at 50% 90%, rgba(255,240,160,0.90) 0%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1.00) 0%, rgba(200,220,255,0.3) 50%, transparent 100%),
    radial-gradient(4px 4px at 25% 48%, rgba(255,248,200,0.95) 0%, rgba(255,240,160,0.2) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0%   { opacity: 0.55 }
  40%  { opacity: 0.95 }
  70%  { opacity: 0.70 }
  100% { opacity: 0.85 }
}
@keyframes twinkle-b {
  0%   { opacity: 0.70 }
  35%  { opacity: 0.50 }
  65%  { opacity: 1.00 }
  100% { opacity: 0.65 }
}

/* ── Inner layout ── */
.ig-inner {
  position: relative;
  z-index: 1;
  max-width: 860px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ════════════════════════════════════════
   INFO PANEL
════════════════════════════════════════ */
.ig-panel {
  display: flex;
  align-items: center;
  gap: 0;
  background: linear-gradient(135deg, rgba(13, 22, 51, 0.92) 0%, rgba(8, 15, 38, 0.92) 100%);
  border: 1px solid rgba(100, 140, 255, 0.18);
  border-radius: 18px;
  padding: 14px 20px;
  backdrop-filter: blur(12px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.45), 0 0 0 1px rgba(80,120,255,0.06) inset;
  overflow: hidden;
  position: relative;
}
.ig-panel::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100,140,255,0.35), rgba(42,245,152,0.25), transparent);
}

/* Prize section */
.ig-prize {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex-shrink: 0;
}
.ig-prize__label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10px;
  font-weight: 700;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 1.2px;
}
.ig-prize__icon {
  color: #f5c518;
  font-size: 10px;
}
.ig-prize__amount {
  display: flex;
  align-items: baseline;
  gap: 4px;
}
.ig-prize__sym {
  font-size: 16px;
  font-weight: 700;
  color: #ffd060;
}
.ig-prize__num {
  font-family: 'Anton', sans-serif;
  font-size: 36px;
  line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: none;
}

/* Divider */
.ig-panel__divider {
  width: 1px;
  height: 48px;
  background: rgba(100,140,255,0.15);
  margin: 0 20px;
  flex-shrink: 0;
}

/* Meta badges */
.ig-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.ig-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.4px;
  border: 1px solid;
}
.ig-badge i { font-size: 10px; opacity: 0.75 }

.ig-badge--round {
  background: rgba(123, 79, 255, 0.12);
  border-color: rgba(123, 79, 255, 0.3);
  color: #b49dff;
}
.ig-badge--pattern {
  background: rgba(0, 158, 253, 0.1);
  border-color: rgba(0, 158, 253, 0.28);
  color: #66d4ff;
}
.ig-badge--cards {
  background: rgba(42, 245, 152, 0.08);
  border-color: rgba(42, 245, 152, 0.22);
  color: #7affba;
}

/* ════════════════════════════════════════
   CARDS SECTION
════════════════════════════════════════ */
.ig-cards-section {
  width: 100%;
}

/* ════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════ */
@media (max-width: 600px) {
  .ig-panel {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .ig-panel__divider {
    width: 100%; height: 1px;
    margin: 0;
  }
  .ig-prize__num { font-size: 28px }
}
</style>
