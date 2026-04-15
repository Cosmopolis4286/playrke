<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import "@/css/games/bingo/bingo-in-game.css";
import { getEchoInstance } from "@js/plugins/echo";
import BingoCartons from "@js/Components/Games/Bingo/views/inGame/Cartons.vue";
import type { BingoRoom, BingoCard } from "@js/Services/bingoService";

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
interface Player { ctns: number }

interface Props {
  title?: string;
  room: BingoRoom & { Config: { rounds?: number; bingo_patterns?: boolean } };
  cartons?: BingoCard[];
  customerLogo?: string;
  currencySym?: string;
  prizePool?: number;
  totalPlayers?: number;
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
  totalPlayers: 0,
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

// ─────────────────────────────────────────────────────────────────────────────
// Reactive state (updated via WebSocket)
// ─────────────────────────────────────────────────────────────────────────────
const livePrize   = ref(props.prizePool ?? 0);
const livePlayers = ref(props.totalPlayers ?? 0);
const liveDrawn   = ref<number[]>([...(props.draw ?? [])]);
const prizeFlash  = ref(false);
const playersFlash = ref(false);

// ─────────────────────────────────────────────────────────────────────────────
// Countdown
// ─────────────────────────────────────────────────────────────────────────────
// startTime is unix seconds from server (from room config unix_date or published_at)
const countdown = ref({ h: 0, m: 0, s: 0, total: 0 });
const gameStarted = ref(false);
let countdownInterval: ReturnType<typeof setInterval> | null = null;

function updateCountdown() {
  const target = (props.startTime ?? 0) * 1000; // ms
  const now = Date.now();
  const diff = target - now;

  if (diff <= 0) {
    countdown.value = { h: 0, m: 0, s: 0, total: 0 };
    gameStarted.value = true;
    if (countdownInterval) clearInterval(countdownInterval);
    return;
  }

  const total = Math.floor(diff / 1000);
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  countdown.value = { h, m, s, total };
}

function pad(n: number) { return String(n).padStart(2, "0"); }

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────
const prizeFormatted = computed(() => {
  const n = livePrize.value;
  return n % 1 === 0 ? n.toFixed(0) : n.toFixed(2);
});

const cardCount = computed(() => props.cartons?.length ?? 0);

const roomId = computed(() => props.room?.id?.toString() ?? "");

// ─────────────────────────────────────────────────────────────────────────────
// WebSocket
// ─────────────────────────────────────────────────────────────────────────────
let channel: any = null;

function flash(target: Ref<boolean>) {
  target.value = true;
  setTimeout(() => { target.value = false; }, 1000);
}

function subscribeToRoom() {
  if (!roomId.value) return;
  const echo = getEchoInstance();
  channel = echo.channel(`bingo.room.${roomId.value}`);

  // Prize pool / players count updated
  channel.listen(".RoomUpdated", (e: { prizePool: number; totalPlayers: number }) => {
    if (e.prizePool !== undefined) {
      livePrize.value = e.prizePool;
      flash(prizeFlash);
    }
    if (e.totalPlayers !== undefined) {
      livePlayers.value = e.totalPlayers;
      flash(playersFlash);
    }
  });

  // Player joined
  channel.listen(".PlayerJoined", (e: { currentPlayers: number }) => {
    livePlayers.value = e.currentPlayers;
    flash(playersFlash);
  });

  // Number drawn
  channel.listen(".BingoNumberCalled", (e: { number: number }) => {
    if (e.number && !liveDrawn.value.includes(e.number)) {
      liveDrawn.value = [...liveDrawn.value, e.number];
    }
  });

  // Game started
  channel.listen(".GameStarted", () => {
    gameStarted.value = true;
    if (countdownInterval) clearInterval(countdownInterval);
    countdown.value = { h: 0, m: 0, s: 0, total: 0 };
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// TypeScript helper
// ─────────────────────────────────────────────────────────────────────────────
type Ref<T> = ReturnType<typeof ref<T>>;

// ─────────────────────────────────────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────────────────────────────────────
const revealed = ref(false);

onMounted(() => {
  setTimeout(() => { revealed.value = true; }, 80);
  updateCountdown();
  if (!gameStarted.value) {
    countdownInterval = setInterval(updateCountdown, 1000);
  }
  subscribeToRoom();
});

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval);
  if (channel && roomId.value) {
    const echo = getEchoInstance();
    echo.leaveChannel(`bingo.room.${roomId.value}`);
  }
});
</script>

<template>
  <div class="ig-page" :class="{ 'ig-page--revealed': revealed }">
    <div class="ig-inner">

      <!-- ══════════════════════════════════════════
           HEADER PANEL
      ══════════════════════════════════════════ -->
      <div class="ig-panel">

        <!-- Prize -->
        <div class="ig-prize" :class="{ 'ig-prize--flash': prizeFlash }">
          <span class="ig-prize__label">
            <i class="fas fa-trophy ig-prize__icon" />
            {{ prizePoolType }}
          </span>
          <div class="ig-prize__amount">
            <span class="ig-prize__sym">{{ currencySym }}</span>
            <span class="ig-prize__num">{{ prizeFormatted }}</span>
          </div>
        </div>

        <div class="ig-panel__divider" />

        <!-- Countdown -->
        <div class="ig-countdown" v-if="!gameStarted && countdown.total > 0">
          <span class="ig-countdown__label">
            <i class="fas fa-clock" /> Inicia en
          </span>
          <div class="ig-countdown__display">
            <div class="ig-cd-unit">
              <span class="ig-cd-unit__num">{{ pad(countdown.h) }}</span>
              <span class="ig-cd-unit__lbl">h</span>
            </div>
            <span class="ig-cd-sep">:</span>
            <div class="ig-cd-unit">
              <span class="ig-cd-unit__num">{{ pad(countdown.m) }}</span>
              <span class="ig-cd-unit__lbl">m</span>
            </div>
            <span class="ig-cd-sep">:</span>
            <div class="ig-cd-unit">
              <span class="ig-cd-unit__num" :class="{ 'ig-cd-unit__num--tick': countdown.s % 2 === 0 }">
                {{ pad(countdown.s) }}
              </span>
              <span class="ig-cd-unit__lbl">s</span>
            </div>
          </div>
        </div>

        <div v-else-if="gameStarted" class="ig-live-badge">
          <span class="ig-live-badge__dot" />
          EN VIVO
        </div>

        <div class="ig-panel__divider" v-if="!gameStarted || livePlayers > 0" />

        <!-- Meta -->
        <div class="ig-meta">
          <!-- Players -->
          <div class="ig-badge ig-badge--players" :class="{ 'ig-badge--flash': playersFlash }">
            <i class="fas fa-users" />
            <span>{{ livePlayers }}</span>
            <small>jugadores</small>
          </div>

          <!-- Round -->
          <div v-if="rounds" class="ig-badge ig-badge--round">
            <i class="fas fa-sync-alt" />
            RONDA <strong>#{{ round }}</strong>
          </div>

          <!-- Pattern -->
          <div v-if="bingoPattern" class="ig-badge ig-badge--pattern">
            <i class="fas fa-th" />
            {{ bingoPattern }}
          </div>

          <!-- My cards -->
          <div class="ig-badge ig-badge--cards">
            <i class="fas fa-ticket-alt" />
            {{ cardCount }} cartón{{ cardCount === 1 ? '' : 'es' }}
          </div>
        </div>

      </div>

      <!-- ══════════════════════════════════════════
           CARDS GRID
      ══════════════════════════════════════════ -->
      <div class="ig-cards-section">
        <BingoCartons
          :cartons="cartons ?? []"
          :customerLogo="customerLogo ?? null"
          :roomId="roomId"
          :drawnNumbers="liveDrawn"
        />
      </div>

    </div>
  </div>
</template>

<style>
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
.wrapper, .content-wrapper {
  height: auto !important;
  min-height: 100vh;
  overflow: visible !important;
}
</style>

<style scoped>
/* ══════════════════════════════════════
   PAGE — fondo estelar
══════════════════════════════════════ */
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
  background:
    radial-gradient(ellipse 90% 55% at 15%  5%,  rgba(80,  0, 200, 0.30) 0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%,  rgba( 0, 60, 220, 0.24) 0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%,  rgba( 0,150, 110, 0.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%,  rgba(130,  0, 210, 0.20) 0%, transparent 58%),
    radial-gradient(ellipse 45% 38% at  8% 58%,  rgba( 0, 90, 255, 0.14) 0%, transparent 52%),
    radial-gradient(ellipse 35% 30% at 40% 40%,  rgba( 0,180, 255, 0.08) 0%, transparent 50%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}
.ig-page--revealed { opacity: 1; transform: translateY(0) }

.ig-page::-webkit-scrollbar { width: 4px }
.ig-page::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 10px }
.ig-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
}
.ig-page::-webkit-scrollbar-button { display: none }

.ig-page::before {
  content: "";
  position: fixed; inset: 0;
  z-index: -2; pointer-events: none;
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
    radial-gradient(1px 1px at 53% 48%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(180,200,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(200,180,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,220,255,0.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,220,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 90% 45%, rgba(255,240,180,0.80) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}
.ig-page::after {
  content: "";
  position: fixed; inset: 0;
  z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at 10% 15%, rgba(255,255,255,0.95) 0%, transparent 100%),
    radial-gradient(2px 2px at 45%  8%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180,200,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 23% 70%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 55% 62%, rgba(220,200,255,0.85) 0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1.00) 0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220,200,255,0.96) 0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1.00) 0%, rgba(200,220,255,0.3) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0% { opacity: 0.55 } 40% { opacity: 0.95 } 70% { opacity: 0.70 } 100% { opacity: 0.85 }
}
@keyframes twinkle-b {
  0% { opacity: 0.70 } 35% { opacity: 0.50 } 65% { opacity: 1.00 } 100% { opacity: 0.65 }
}

/* ══════════════════════════════════════
   INNER
══════════════════════════════════════ */
.ig-inner {
  position: relative;
  z-index: 1;
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ══════════════════════════════════════
   PANEL
══════════════════════════════════════ */
.ig-panel {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0;
  background: linear-gradient(135deg, rgba(13,22,51,0.94) 0%, rgba(8,15,38,0.94) 100%);
  border: 1px solid rgba(100,140,255,0.18);
  border-radius: 20px;
  padding: 14px 20px;
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(80,120,255,0.06) inset;
  position: relative;
  overflow: hidden;
}
.ig-panel::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100,140,255,0.4), rgba(42,245,152,0.3), transparent);
}

/* ── Prize ── */
.ig-prize {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex-shrink: 0;
  transition: transform 0.3s ease;
}
.ig-prize--flash { animation: prize-pop 0.6s ease }
@keyframes prize-pop {
  0%  { transform: scale(1) }
  40% { transform: scale(1.06) }
  100%{ transform: scale(1) }
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
.ig-prize__icon { color: #f5c518; font-size: 10px }
.ig-prize__amount { display: flex; align-items: baseline; gap: 4px }
.ig-prize__sym { font-size: 16px; font-weight: 700; color: #ffd060 }
.ig-prize__num {
  font-family: 'Anton', sans-serif;
  font-size: 38px;
  line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ── Divider ── */
.ig-panel__divider {
  width: 1px; height: 52px;
  background: rgba(100,140,255,0.15);
  margin: 0 18px;
  flex-shrink: 0;
}

/* ── Countdown ── */
.ig-countdown { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0 }
.ig-countdown__label {
  font-size: 10px;
  font-weight: 700;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.ig-countdown__display { display: flex; align-items: center; gap: 4px }

.ig-cd-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(0,5,30,0.5);
  border: 1px solid rgba(100,140,255,0.2);
  border-radius: 8px;
  padding: 3px 8px;
  min-width: 38px;
}
.ig-cd-unit__num {
  font-family: 'Anton', sans-serif;
  font-size: 20px;
  line-height: 1;
  color: #e0eaff;
  transition: color 0.15s;
}
.ig-cd-unit__num--tick { color: #2af598 }
.ig-cd-unit__lbl { font-size: 8px; color: #3a5080; letter-spacing: 1px; text-transform: uppercase }

.ig-cd-sep {
  font-family: 'Anton', sans-serif;
  font-size: 18px;
  color: #3a5080;
  margin-bottom: 8px;
  animation: blink-sep 1s step-start infinite;
}
@keyframes blink-sep { 0%,100%{ opacity:1 } 50%{ opacity:0.2 } }

/* ── Live badge ── */
.ig-live-badge {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 5px 14px;
  background: rgba(42,245,152,0.1);
  border: 1px solid rgba(42,245,152,0.3);
  border-radius: 30px;
  font-size: 11px;
  font-weight: 800;
  color: #2af598;
  letter-spacing: 1.5px;
  flex-shrink: 0;
}
.ig-live-badge__dot {
  width: 7px; height: 7px;
  background: #2af598;
  border-radius: 50%;
  animation: dot-pulse 1.4s ease-in-out infinite;
}
@keyframes dot-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(42,245,152,0.6) }
  50%      { box-shadow: 0 0 0 6px rgba(42,245,152,0) }
}

/* ── Meta badges ── */
.ig-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  align-items: center;
}
.ig-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.3px;
  border: 1px solid;
  transition: box-shadow 0.3s ease;
}
.ig-badge i { font-size: 10px; opacity: 0.75 }
.ig-badge small { font-size: 9px; opacity: 0.65; font-weight: 400 }

.ig-badge--flash { animation: badge-pop 0.5s ease }
@keyframes badge-pop {
  0%  { transform: scale(1); box-shadow: 0 0 0 0 rgba(42,245,152,0.4) }
  40% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(42,245,152,0) }
  100%{ transform: scale(1) }
}

.ig-badge--players {
  background: rgba(0,158,253,0.1);
  border-color: rgba(0,158,253,0.28);
  color: #66d4ff;
}
.ig-badge--round {
  background: rgba(123,79,255,0.12);
  border-color: rgba(123,79,255,0.3);
  color: #b49dff;
}
.ig-badge--pattern {
  background: rgba(255,171,0,0.1);
  border-color: rgba(255,171,0,0.25);
  color: #ffd060;
}
.ig-badge--cards {
  background: rgba(42,245,152,0.08);
  border-color: rgba(42,245,152,0.22);
  color: #7affba;
}

/* ══════════════════════════════════════
   CARDS SECTION
══════════════════════════════════════ */
.ig-cards-section { width: 100% }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 640px) {
  .ig-panel { gap: 10px }
  .ig-panel__divider { display: none }
  .ig-prize__num { font-size: 28px }
  .ig-cd-unit__num { font-size: 16px }
  .ig-cd-unit { min-width: 30px }
}
</style>
