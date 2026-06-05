<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import "@/css/games/bingo/bingo-game-rooms.css";

import BingoGameRoomCard from "@js/Components/Games/Bingo/Room/GameRooms/BingoGameRoomCard.vue";
import BingoPurchaseModal from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";
import BingoRoomInfoModal from "@js/Components/Games/Bingo/Room/GameRooms/BingoRoomInfoModal.vue";

import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useGameRoomEcho } from "@js/composables/games/bingo/room/game-rooms/useGameRoomEcho";
import { useGameRoomPurchaseModal } from "@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal";

import type {
  BingoGameRoomsPageProps,
  BingoGameRoom,
} from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

const props = defineProps<BingoGameRoomsPageProps>();

const bingoRoomImg = '/img/games/bingo/estelar_fd.png'

const page = usePage();

const isAdmin = computed(() => {
  const roles = page.props.auth?.user?.userRoles || [];
  return roles.some((ur: any) =>
    ur.role?.prefix === "admin" || ur.role?.prefix === "superadmin"
  );
});

const canBuy = computed(() => {
  const roles = page.props.auth?.user?.userRoles || [];
  return roles.some((ur: any) =>
    ["commerce", "seller", "player"].includes(ur.role?.prefix)
  );
});

const rooms = ref<BingoGameRoom[]>(props.rooms.map((r) => ({ ...r })));

const filteredRooms = computed(() => {
  const roles = page.props.auth?.user?.userRoles || [];
  const isPlayer = roles.some((ur: any) => ur.role?.prefix === "player");
  if (isPlayer) return rooms.value.filter((r) => r.room_type === "PUBLIC");
  return rooms.value;
});

useGameRoomEcho(rooms);

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown } = useGameRoomCountdown(
  serverTimestampMs,
  (_roomId) => {}
);

const modal = useGameRoomPurchaseModal();

// ── Modal intermedio de sala ──
const selectedRoomForInfo = ref<BingoGameRoom | null>(null);
const showRoomInfoModal = ref(false);

function openRoomInfo(roomId: string): void {
  const room = filteredRooms.value.find((r) => r.id === roomId);
  if (!room) return;
  selectedRoomForInfo.value = room;
  showRoomInfoModal.value = true;
  // Pre-carga el precio en segundo plano mientras el usuario lee la info de la sala
  modal.prefetch(room);
}

function closeRoomInfo(): void {
  showRoomInfoModal.value = false;
  selectedRoomForInfo.value = null;
}

function goToLobby(roomId: string): void {
  window.location.href = `/bingo/${roomId}/lobby`;
}

function openPurchaseFromInfo(): void {
  if (!selectedRoomForInfo.value) return;
  const room = selectedRoomForInfo.value;
  closeRoomInfo();
  modal.open(room);
}

function onPurchaseSuccess(
  roomId: string,
  result: { prizePool: number; ctns: number }
): void {
  const idx = rooms.value.findIndex((r) => r.id === roomId);
  if (idx === -1) return;
  const room = rooms.value[idx];
  const updatedPlayer = { ctns: (room.players_room?.[0]?.ctns ?? 0) + result.ctns };
  rooms.value[idx] = { ...room, prize_pool: result.prizePool, players_room: [updatedPlayer] };
}

// ── Formateo del countdown para el modal ──
function formatCountdown(roomId: string): string {
  const cd = getCountdown(roomId);
  if (!cd || cd.expired) return "EN VIVO";
  const { days, hours, minutes, seconds } = cd.time;
  if (days > 0) return `${days}d ${String(hours).padStart(2,"0")}h ${String(minutes).padStart(2,"0")}m`;
  if (hours > 0) return `${String(hours).padStart(2,"0")}:${String(minutes).padStart(2,"0")}:${String(seconds).padStart(2,"0")}`;
  return `${String(minutes).padStart(2,"0")}:${String(seconds).padStart(2,"0")}`;
}

function isLive(roomId: string): boolean {
  const cd = getCountdown(roomId);
  return !cd || cd.expired;
}

const gameModeMap: Record<string, string> = {
  ONLINE: "Online", OFFLINE: "Presencial", MIXED: "Mixto",
};

const roomTypeMap: Record<string, string> = {
  PUBLIC: "Pública", PRIVATE: "Privada",
};

// ── Meteoros ──
let meteorTimer: ReturnType<typeof setInterval> | null = null;

function spawnMeteor() {
  const container = document.getElementById('grm-meteors');
  if (!container) return;
  if (container.children.length >= 22) return;

  const m = document.createElement('div');
  m.className = 'grm-meteor';

  // top-left → bottom-right, ángulo variable 25°–55°
  const angle   = 25 + Math.random() * 30;      // 25°–55°
  const size    = 35 + Math.random() * 60;       // 35–95px (pequeño)
  const dur     = 1.2 + Math.random() * 1.8;     // 1.2–3s (rápido)
  const opacity = 0.18 + Math.random() * 0.28;   // 0.18–0.46 (apenas visible)

  // Origen en borde superior-izquierdo con spread
  const startX = -5 + Math.random() * 50;  // -5 → 45vw
  const startY = -3 + Math.random() * 8;   // -3 → 5vh

  // Desplazamiento siempre abajo-derecha
  const dx = 80 + Math.random() * 70;   // 80–150vw
  const dy = 65 + Math.random() * 60;   // 65–125vh

  m.style.cssText = `left:${startX}vw;top:${startY}vh;width:${size}px;opacity:${opacity};--rot:${angle}deg;--dx:${dx}vw;--dy:${dy}vh;--dur:${dur}s;`;

  container.appendChild(m);
  m.addEventListener('animationend', () => m.remove(), { once: true });
}

function startMeteors() {
  function schedule() {
    const delay = 500 + Math.random() * 1200; // 0.5–1.7s entre apariciones
    meteorTimer = setTimeout(() => {
      spawnMeteor();
      // 30% probabilidad de un segundo meteoro escalonado
      if (Math.random() < 0.3) setTimeout(spawnMeteor, 80 + Math.random() * 120);
      schedule();
    }, delay) as any;
  }
  setTimeout(spawnMeteor, 200);
  setTimeout(spawnMeteor, 700);
  schedule();
}

onMounted(() => {
  initCountdown(props.rooms);
  startMeteors();
});

onUnmounted(() => {
  if (meteorTimer) clearTimeout(meteorTimer);
});
</script>

<template>
  <div class="game-rooms-page">

    <!-- Cubre el viewport completo incluyendo el gap del navbar -->
    <div class="game-rooms-spacecover" />

    <!-- ── Meteoros ── -->
    <div id="grm-meteors" class="grm-meteors" aria-hidden="true" />

    <!-- ── Encabezado ── -->
    <div class="game-rooms-page__header">
      <div class="grp-header-glow" />
      <div class="grp-title-wrap">
        <span class="grp-dice grp-dice--1">&#9858;</span>
        <h1 class="game-rooms-page__title">{{ props.title ?? "SALAS DE JUEGO" }}</h1>
        <span class="grp-dice grp-dice--2">&#9858;</span>
      </div>
      <p class="game-rooms-page__subtitle">
        Selecciona una sala activa para comprar tus cartones
      </p>
      <div class="grp-header-line" />
    </div>

    <!-- ── Sin salas ── -->
    <div v-if="!filteredRooms.length" class="game-rooms-page__empty">
      <div class="grp-empty-icon">
        <i class="fas fa-door-closed" />
      </div>
      <p class="grp-empty-title">No hay salas disponibles</p>
      <p class="grp-empty-sub">Vuelve más tarde para ver las salas activas de hoy</p>
    </div>

    <!-- ── Grid de tarjetas ── -->
    <div v-else class="game-rooms-page__grid-wrap">
      <div
        class="game-rooms-page__grid"
        :class="{ 'game-rooms-page__grid--centered': filteredRooms.length < 4 }"
      >
        <div
          v-for="(room, index) in filteredRooms"
          :key="room.id"
          class="game-rooms-page__grid-item"
        >
          <BingoGameRoomCard
            :room="room"
            :exchange-rate="props.exchangeRate ?? 1"
            :currency-sym="props.currencySym ?? ''"
            :countdown="getCountdown(room.id)"
            :is-purchasing="false"
            :room-detail="null"
            :num-ctns="1"
            :total-amount="0"
            :prize-pool-label="'Premio'"
            :loading="false"
            :index="index"
            @select="openRoomInfo"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- ══ Modal de info de sala ══ -->
  <BingoRoomInfoModal
    v-if="showRoomInfoModal && selectedRoomForInfo"
    :room="selectedRoomForInfo"
    :countdown="getCountdown(selectedRoomForInfo.id)"
    :exchange-rate="props.exchangeRate ?? 1"
    :currency-sym="props.currencySym ?? 'USD'"
    :can-buy="canBuy"
    :bingo-room-img="bingoRoomImg"
    @close="closeRoomInfo"
    @go-lobby="goToLobby(selectedRoomForInfo.id)"
    @buy="openPurchaseFromInfo"
  />

  <!-- ══ Modal de compra ══ -->
  <BingoPurchaseModal
    v-if="modal.isOpen.value && canBuy"
    :is-open="modal.isOpen.value"
    :step="modal.step.value"
    :selected-room="modal.selectedRoom.value"
    :countries="modal.countries.value"
    :loading-countries="modal.loadingCountries.value"
    :selected-country="modal.selectedCountry.value"
    :card-price="modal.cardPrice.value"
    :currency-symbol="modal.currencySymbol.value"
    :loading-price="modal.loadingPrice.value"
    :num-ctns="modal.numCtns.value"
    :total-amount="modal.totalAmount.value"
    :can-advance="modal.canAdvance.value"
    :purchasing="modal.purchasing.value"
    :exchange-rate="props.exchangeRate ?? 1"
    :is-player="modal.isPlayer.value"
    @close="modal.close()"
    @select-country="modal.selectCountry($event)"
    @next="modal.nextStep()"
    @prev="modal.prevStep()"
    @increment="modal.increment()"
    @decrement="modal.decrement()"
    @set-ctns="modal.setNumCtns($event)"
    @purchase="modal.purchase(result => onPurchaseSuccess(modal.selectedRoom.value!.id, result))"
  />
</template>

<style>
/* ══════════════════════════════════════════════════════
   RESET LAYOUT — idéntico a Game/Index.vue
══════════════════════════════════════════════════════ */
html, body {
  height: auto !important;
  min-height: 100%;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  /* Fondo base que cubre el gap del navbar */
  background: #04081a !important;
}
body,
body.hold-transition,
#app-wrapper,
.wrapper,
.content-wrapper {
  background-color: #04081a !important;
  background-image: none !important;
}
/* Capa fija que cubre TODO el viewport incluyendo el gap del navbar (margin-top de AdminLTE) */
.game-rooms-spacecover {
  position: fixed;
  inset: 0;
  z-index: -10;
  pointer-events: none;
  background:
    radial-gradient(ellipse 90% 55% at 15%  5%, rgba( 80,  0,200,0.30) 0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%, rgba(  0, 60,220,0.24) 0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%, rgba(  0,150,110,0.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%, rgba(130,  0,210,0.20) 0%, transparent 58%),
    radial-gradient(ellipse 45% 38% at  8% 58%, rgba(  0, 90,255,0.14) 0%, transparent 52%),
    radial-gradient(ellipse 35% 30% at 40% 40%, rgba(  0,180,255,0.08) 0%, transparent 50%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
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

/* ── Meteoros ── */
.grm-meteor {
  position: absolute;
  height: 1px;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    transparent             0%,
    rgba(210,230,255,0.08) 40%,
    rgba(240,248,255,0.55) 78%,
    rgba(255,255,255,0.85) 92%,
    rgba(255,255,255,0.5) 100%
  );
  box-shadow: 1px 0 3px 0px rgba(255,255,255,0.4);
  transform: rotate(var(--rot, 35deg));
  animation: meteorFall var(--dur, 2s) linear forwards;
  pointer-events: none;
  will-change: transform, opacity;
}

@keyframes meteorFall {
  0%   { transform:rotate(var(--rot,35deg)) translate(0,0);                          opacity:0; }
  8%   { opacity:1; }
  85%  { opacity:0.9; }
  100% { transform:rotate(var(--rot,35deg)) translate(var(--dx,90vw),var(--dy,80vh)); opacity:0; }
}

/* idéntico a Game/Index.vue — sin clases dinámicas en body */
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
/* ═══════════════════════════════════════════════════════
   PÁGINA — fondo estelar directo (igual que Game/Index)
═══════════════════════════════════════════════════════ */
.game-rooms-page {
  flex: 1;
  min-height: 100vh;
  padding: 32px 12px 60px;
  position: relative;
  isolation: isolate;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: #7b4fff rgba(255,255,255,0.03);

  background: transparent;
}

.game-rooms-page::-webkit-scrollbar { width: 4px; }
.game-rooms-page::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 10px; }
.game-rooms-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
}
.game-rooms-page::-webkit-scrollbar-button { display: none; }

/* ── Capa 1: estrellas finas — mayor densidad ── */
.game-rooms-page::before {
  content: "";
  position: fixed; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    /* fila 1 */
    radial-gradient(1px 1px at  3%  4%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(1px 1px at  9% 14%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 16%  2%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 22% 28%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 29%  9%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 36% 18%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 42%  5%, rgba(255,255,255,0.88) 0%, transparent 100%),
    radial-gradient(1px 1px at 50% 22%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  8%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 63% 32%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at 70%  3%, rgba(255,255,255,0.86) 0%, transparent 100%),
    radial-gradient(1px 1px at 77% 18%, rgba(255,255,255,0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 84%  7%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 91% 25%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 97% 12%, rgba(255,255,255,0.84) 0%, transparent 100%),
    /* fila 2 */
    radial-gradient(1px 1px at  5% 42%, rgba(255,255,255,0.62) 0%, transparent 100%),
    radial-gradient(1px 1px at 13% 55%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 21% 67%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 28% 48%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 35% 72%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 58%, rgba(255,255,255,0.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 51% 80%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 59% 45%, rgba(255,255,255,0.64) 0%, transparent 100%),
    radial-gradient(1px 1px at 67% 62%, rgba(255,255,255,0.56) 0%, transparent 100%),
    radial-gradient(1px 1px at 74% 78%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 81% 50%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 88% 68%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 94% 85%, rgba(255,255,255,0.52) 0%, transparent 100%),
    /* fila 3 */
    radial-gradient(1px 1px at  7% 88%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 18% 93%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 87%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 48% 95%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 62% 90%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 78% 96%, rgba(255,255,255,0.46) 0%, transparent 100%),
    radial-gradient(1px 1px at 92% 89%, rgba(255,255,255,0.76) 0%, transparent 100%),
    /* estrellas coloreadas */
    radial-gradient(1.5px 1.5px at 11% 16%, rgba(190,210,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 26%  6%, rgba(210,185,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 46% 12%, rgba(180,225,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 66% 19%, rgba(200,255,220,0.82) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 85% 11%, rgba(255,240,180,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 38% 60%, rgba(190,210,255,0.78) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 72% 52%, rgba(255,200,200,0.72) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 55% 76%, rgba(200,225,255,0.86) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}

/* ── Capa 2: estrellas grandes con halo ── */
.game-rooms-page::after {
  content: "";
  position: fixed; inset: 0; z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at  8% 12%, rgba(255,255,255,0.96) 0%, transparent 100%),
    radial-gradient(2px 2px at 24% 35%, rgba(200,215,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 40%  6%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(2px 2px at 58% 22%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 74% 15%, rgba(180,200,255,0.86) 0%, transparent 100%),
    radial-gradient(2px 2px at 90% 38%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 15% 65%, rgba(200,255,240,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 50% 55%, rgba(220,200,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 80% 72%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 35% 82%, rgba(200,220,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 68% 88%, rgba(255,250,200,0.84) 0%, transparent 100%),
    /* grandes con halo */
    radial-gradient(3px 3px at 19% 20%, rgba(255,255,255,1.00) 0%, rgba(210,230,255,0.20) 55%, transparent 100%),
    radial-gradient(3px 3px at 44% 14%, rgba(220,200,255,0.96) 0%, rgba(185,165,255,0.18) 55%, transparent 100%),
    radial-gradient(3px 3px at 69% 42%, rgba(255,255,255,0.94) 0%, rgba(225,245,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 88% 60%, rgba(200,240,255,0.98) 0%, rgba(160,220,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 30% 78%, rgba(255,240,160,0.90) 0%, rgba(255,220,100,0.12) 55%, transparent 100%),
    /* especiales con halo amplio */
    radial-gradient(4px 4px at 55% 30%, rgba(255,255,255,1.00) 0%, rgba(205,225,255,0.28) 50%, transparent 100%),
    radial-gradient(4px 4px at 12% 45%, rgba(255,248,200,0.95) 0%, rgba(255,240,160,0.18) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0%   { opacity: 0.55; } 40%  { opacity: 0.95; }
  70%  { opacity: 0.70; } 100% { opacity: 0.85; }
}
@keyframes twinkle-b {
  0%   { opacity: 0.70; } 35%  { opacity: 0.50; }
  65%  { opacity: 1.00; } 100% { opacity: 0.65; }
}

/* ── Contenedor de meteoros ── */
.grm-meteors {
  position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
}

/* ═══════════════════════════════
   HEADER
═══════════════════════════════ */
.game-rooms-page__header {
  text-align: center;
  margin-bottom: 40px;
  position: relative;
  animation: fadeSlideDown 0.7s ease both;
}

@keyframes fadeSlideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.grp-header-glow {
  position: absolute;
  top: -60px; left: 50%; transform: translateX(-50%);
  width: 500px; height: 200px;
  background: radial-gradient(ellipse, rgba(123,79,255,0.25) 0%, transparent 70%);
  pointer-events: none;
}

.grp-title-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-bottom: 10px;
}

.grp-dice {
  font-size: 28px;
  filter: drop-shadow(0 0 10px rgba(42,245,152,0.6));
  animation: spinDice 8s linear infinite;
  color: #2af598;
}
.grp-dice--2 { animation-direction: reverse; animation-duration: 10s; color: #009efd; }

@keyframes spinDice {
  0%   { transform: rotate(0deg);   }
  100% { transform: rotate(360deg); }
}

.game-rooms-page__title {
  font-family: "Anton", sans-serif;
  font-size: clamp(28px, 4.5vw, 44px);
  letter-spacing: 4px;
  color: #e0eaff;
  margin: 0;
  text-shadow:
    0 0 40px rgba(42,245,152,0.25),
    0 0 80px rgba(123,79,255,0.15),
    0 2px 4px rgba(0,0,0,0.5);
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.game-rooms-page__subtitle {
  font-size: 13px;
  color: #4a6696;
  letter-spacing: 0.8px;
  margin: 0 0 16px;
}

.grp-header-line {
  width: 80px;
  height: 2px;
  margin: 0 auto;
  background: linear-gradient(90deg, transparent, #2af598, #009efd, transparent);
  border-radius: 2px;
  position: relative;
}
.grp-header-line::after {
  content: '';
  position: absolute;
  top: -1px; left: 50%; transform: translateX(-50%);
  width: 6px; height: 4px;
  background: #2af598;
  border-radius: 50%;
  box-shadow: 0 0 8px #2af598;
}

/* ═══════════════════════════════
   EMPTY STATE
═══════════════════════════════ */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 100px 0;
  animation: fadeSlideDown 0.6s ease both;
}

.grp-empty-icon {
  width: 80px; height: 80px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(100,140,255,0.15);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px;
  color: #2e4068;
  margin-bottom: 8px;
}

.grp-empty-title {
  font-family: "Anton", sans-serif;
  font-size: 20px;
  color: #3a5080;
  letter-spacing: 1px;
  margin: 0;
}

.grp-empty-sub {
  font-size: 13px;
  color: #2a3a5a;
  margin: 0;
}

/* ═══════════════════════════════
   GRID — cards horizontales responsive
═══════════════════════════════ */
.game-rooms-page__grid-wrap {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  padding: 0 4px;
}

.game-rooms-page__grid {
  display: grid;
  gap: 12px;
  grid-template-columns: 1fr;
}

.game-rooms-page__grid-item {
  display: flex;
  width: 100%;
}

/* Tablet: 2 columnas cuando hay espacio suficiente */
@media (min-width: 680px) {
  .game-rooms-page__grid { grid-template-columns: 1fr 1fr; }
}

/* Desktop: 3 columnas */
@media (min-width: 1100px) {
  .game-rooms-page__grid { grid-template-columns: 1fr 1fr 1fr; }
}

/* estilos del modal de info movidos a BingoRoomInfoModal.vue */
</style>
