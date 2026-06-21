<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import "@/css/games/bingo/bingo-game-rooms.css";

import BingoGameRoomCard from "@js/Components/Games/Bingo/Room/GameRooms/BingoGameRoomCard.vue";
import BingoPurchaseModal from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";
import BingoRoomInfoModal from "@js/Components/Games/Bingo/Room/GameRooms/BingoRoomInfoModal.vue";
import BingoRulesModal from "@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue";

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

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown, updateRoomTime } = useGameRoomCountdown(
  serverTimestampMs,
  (_roomId) => {}
);

useGameRoomEcho(rooms, {
  userId: page.props.auth?.user?.id ?? null,
  onRoomAdded: (room) => {
    if (room.config?.unix_date) updateRoomTime(room.id, room.config.unix_date);
  },
});

const modal = useGameRoomPurchaseModal();

// ── Modal de reglamento ──
const showRulesModal = ref(false);

// ── Modal intermedio de sala ──
// Se guarda solo el id; la sala se resuelve de forma reactiva desde `rooms`
// para que el modal refleje las actualizaciones en tiempo real (WebSockets).
const selectedRoomId = ref<string | null>(null);
const showRoomInfoModal = ref(false);

const selectedRoomForInfo = computed<BingoGameRoom | null>(() => {
  if (!selectedRoomId.value) return null;
  return rooms.value.find((r) => r.id === selectedRoomId.value) ?? null;
});

function openRoomInfo(roomId: string): void {
  const room = filteredRooms.value.find((r) => r.id === roomId);
  if (!room) return;
  selectedRoomId.value = roomId;
  showRoomInfoModal.value = true;
  // Pre-carga el precio en segundo plano mientras el usuario lee la info de la sala
  modal.prefetch(room);
}

function closeRoomInfo(): void {
  showRoomInfoModal.value = false;
  selectedRoomId.value = null;
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

    <!-- ── Botón flotante: reglamento del bingo ── -->
    <button
      type="button"
      class="grp-rules-fab"
      title="Ver reglamento del Bingo"
      aria-label="Ver reglamento del Bingo"
      @click="showRulesModal = true"
    >
      <i class="fas fa-book" />
    </button>

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
      <div class="grp-empty-line" />
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

  <!-- ══ Modal de reglamento del bingo ══ -->
  <BingoRulesModal
    v-model="showRulesModal"
    :rules="props.bingoRules"
    :image="props.imageBingoRules"
  />

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
    :can-customize="modal.canCustomize.value"
    :customize-card="modal.customizeCard.value"
    :custom-card-numbers="modal.customCardNumbers.value"
    :confirm-incomplete-custom-card="modal.confirmIncompleteCustomCard"
    @close="modal.close()"
    @select-country="modal.selectCountry($event)"
    @next="modal.nextStep()"
    @prev="modal.prevStep()"
    @increment="modal.increment()"
    @decrement="modal.decrement()"
    @set-ctns="modal.setNumCtns($event)"
    @toggle-customize="modal.toggleCustomize()"
    @update:custom-card-numbers="modal.customCardNumbers.value = $event"
    @purchase="modal.purchase(result => onPurchaseSuccess(modal.selectedRoom.value!.id, result))"
  />
</template>

<style>
/* ══════════════════════════════════════════════════════
   RESET LAYOUT — la página vive DENTRO de #content-scrollable,
   que ya es el único contenedor con scroll (overflow-auto) del
   layout AdminLTE. No tocar html/body ni forzar 100vh aquí,
   o se rompe el wrapper vh-100 y aparece scroll a nivel de página.
══════════════════════════════════════════════════════ */
body,
body.hold-transition,
#app-wrapper,
.wrapper,
.content-wrapper {
  background-color: #04081a !important;
  background-image: none !important;
}
/* Capa fija que cubre el viewport hasta el borde superior del footer */
.game-rooms-spacecover {
  position: fixed;
  inset: 0 0 41px 0;
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
}
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100%;
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

</style>

<style scoped>
/* ═══════════════════════════════════════════════════════
   PÁGINA — fondo estelar directo (igual que Game/Index)
═══════════════════════════════════════════════════════ */
.game-rooms-page {
  flex: 1;
  min-height: 100%;
  padding: 28px 12px 48px;
  position: relative;
  isolation: isolate;
  background: transparent;
}

/* ── Capa 1: estrellas finas — mayor densidad ── */
.game-rooms-page::before {
  content: "";
  position: fixed; inset: 0 0 41px 0; z-index: -2; pointer-events: none;
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
  position: fixed; inset: 0 0 41px 0; z-index: -1; pointer-events: none;
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
  position: fixed; inset: 0 0 41px 0; z-index: 0; pointer-events: none; overflow: hidden;
}

/* ── Botón flotante: reglamento del bingo ── */
.grp-rules-fab {
  position: fixed;
  right: 20px;
  bottom: 69px;
  z-index: 50;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 1px solid rgba(123,79,255,0.35);
  background: linear-gradient(135deg, #2a1f5e 0%, #1a2a52 100%);
  color: #cdd8ff;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.5);
  transition: transform .2s ease, box-shadow .2s ease, color .2s ease, border-color .2s ease;
  animation: rulesFabPulse 3s ease-in-out infinite;
}

.grp-rules-fab:hover {
  transform: translateY(-2px) scale(1.06);
  color: #fff;
  border-color: rgba(42,245,152,0.6);
  box-shadow: 0 6px 22px rgba(0,0,0,0.5), 0 0 18px rgba(42,245,152,0.35);
}

.grp-rules-fab:active {
  transform: translateY(0) scale(0.97);
}

@keyframes rulesFabPulse {
  0%, 100% { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.45); }
  50%      { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 8px rgba(123,79,255,0); }
}

@media (max-width: 576px) {
  .grp-rules-fab {
    right: 14px;
    bottom: 18px;
    width: 44px;
    height: 44px;
    font-size: 16px;
  }
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
  color: #7a96c8;
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
   EMPTY STATE — tarjeta promo con borde degradado
═══════════════════════════════ */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  max-width: 420px;
  margin: 48px auto;
  padding: 56px 32px;
  position: relative;
  border-radius: 24px;
  text-align: center;
  background:
    linear-gradient(160deg, rgba(123,79,255,0.08) 0%, rgba(0,158,253,0.05) 50%, rgba(42,245,152,0.06) 100%),
    rgba(12,18,40,0.55);
  border: 1px solid rgba(123,79,255,0.18);
  box-shadow:
    0 18px 50px rgba(0,0,0,0.45),
    0 0 0 1px rgba(255,255,255,0.02) inset,
    0 0 60px rgba(42,245,152,0.05) inset;
  backdrop-filter: blur(6px);
  animation: fadeSlideDown 0.6s ease both;
  overflow: hidden;
}

/* halo giratorio sutil detrás del icono */
.game-rooms-page__empty::before {
  content: '';
  position: absolute;
  top: -90px; left: 50%; transform: translateX(-50%);
  width: 280px; height: 280px;
  background: radial-gradient(ellipse, rgba(123,79,255,0.20) 0%, transparent 70%);
  pointer-events: none;
}

.grp-empty-icon {
  width: 84px; height: 84px;
  background: linear-gradient(135deg, rgba(42,245,152,0.12) 0%, rgba(0,158,253,0.12) 50%, rgba(123,79,255,0.12) 100%);
  border: 1px solid rgba(123,79,255,0.28);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 34px;
  color: #8fb4ff;
  margin-bottom: 4px;
  position: relative;
  z-index: 1;
  box-shadow: 0 0 30px rgba(42,245,152,0.12), 0 4px 18px rgba(0,0,0,0.35);
  animation: emptyIconFloat 3.4s ease-in-out infinite;
}

@keyframes emptyIconFloat {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-6px); }
}

.grp-empty-title {
  font-family: "Anton", sans-serif;
  font-size: 21px;
  letter-spacing: 1.5px;
  margin: 0;
  position: relative;
  z-index: 1;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.grp-empty-sub {
  font-size: 13px;
  color: #6a85b8;
  letter-spacing: 0.2px;
  margin: 0;
  position: relative;
  z-index: 1;
}

.grp-empty-line {
  width: 60px;
  height: 2px;
  margin: 4px auto 0;
  background: linear-gradient(90deg, transparent, #2af598, #009efd, #7b4fff, transparent);
  border-radius: 2px;
  position: relative;
  z-index: 1;
}

/* ═══════════════════════════════
   GRID — cards horizontales responsive
═══════════════════════════════ */
.game-rooms-page__grid-wrap {
  width: 100%;
  max-width: 860px;
  margin: 0 auto;
  padding: 0 4px;
}

.game-rooms-page__grid {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}

.game-rooms-page__grid-item {
  display: flex;
  width: 100%;
  max-width: 360px;
}

/* Con pocas cards: centrar la fila */
.game-rooms-page__grid--centered {
  justify-content: center;
}
.game-rooms-page__grid--centered .game-rooms-page__grid-item {
  max-width: 360px;
}

/* estilos del modal de info movidos a BingoRoomInfoModal.vue */
</style>
