<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import "@/css/games/bingo/bingo-game-rooms.css";

import BingoGameRoomCard from "@js/Components/Games/Bingo/Room/GameRooms/BingoGameRoomCard.vue";
import BingoPurchaseModal from "@js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";

import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useGameRoomEcho } from "@js/composables/games/bingo/room/game-rooms/useGameRoomEcho";
import { useGameRoomPurchaseModal } from "@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal";

import type {
  BingoGameRoomsPageProps,
  BingoGameRoom,
} from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

const props = defineProps<BingoGameRoomsPageProps>();

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
}

function closeRoomInfo(): void {
  showRoomInfoModal.value = false;
  selectedRoomForInfo.value = null;
}

function goToLobby(roomId: string): void {
  window.location.href = `/bingo/${roomId}/game`;
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

onMounted(() => {
  initCountdown(props.rooms);
});
</script>

<template>
  <div class="game-rooms-page">

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
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="showRoomInfoModal && selectedRoomForInfo"
        class="grm-overlay"
        @click.self="closeRoomInfo"
      >
        <div class="grm-modal">

          <!-- Cierre -->
          <button class="grm-modal__close" @click="closeRoomInfo">
            <i class="fas fa-times" />
          </button>

          <!-- Imagen header -->
          <div class="grm-modal__img-wrap">
            <img src="/img/games/bingo/estelar_fd.png" alt="Sala" class="grm-modal__img" />
            <div class="grm-modal__img-overlay" />

            <!-- Countdown sobre la imagen -->
            <div
              class="grm-modal__timer"
              :class="{ 'grm-modal__timer--live': isLive(selectedRoomForInfo.id) }"
            >
              <template v-if="isLive(selectedRoomForInfo.id)">
                <span class="grm-live-dot" />
                EN VIVO
              </template>
              <template v-else>
                <i class="fas fa-clock mr-1" style="font-size:12px;opacity:.7;" />
                {{ formatCountdown(selectedRoomForInfo.id) }}
              </template>
            </div>
          </div>

          <!-- Cuerpo -->
          <div class="grm-modal__body">

            <!-- Badges -->
            <div class="grm-modal__badges">
              <span class="grm-modal__badge grm-modal__badge--type">
                <i class="fas fa-globe mr-1" />
                {{ roomTypeMap[selectedRoomForInfo.room_type ?? ''] ?? selectedRoomForInfo.room_type ?? 'Sala' }}
              </span>
              <span class="grm-modal__badge grm-modal__badge--mode">
                <i class="fas fa-gamepad mr-1" />
                {{ gameModeMap[selectedRoomForInfo.game_mode ?? ''] ?? selectedRoomForInfo.game_mode ?? 'Online' }}
              </span>
            </div>

            <!-- Bote -->
            <div class="grm-modal__prize">
              <p class="grm-modal__prize-label">Premio Acumulado</p>
              <div class="grm-modal__prize-amount-row">
                <span class="grm-modal__prize-amount">
                  {{ (selectedRoomForInfo.prize_pool * (props.exchangeRate ?? 1)).toFixed(2) }}
                </span>
                <span class="grm-modal__prize-sym">{{ props.currencySym ?? 'USD' }}</span>
              </div>
            </div>

            <!-- Stats -->
            <div class="grm-modal__stats">
              <div class="grm-modal__stat">
                <span class="grm-modal__stat-val">
                  {{ selectedRoomForInfo.total_players }}
                </span>
                <span class="grm-modal__stat-lbl">Jugadores</span>
              </div>
              <div class="grm-modal__stat">
                <span class="grm-modal__stat-val">
                  {{ selectedRoomForInfo.players_room?.[0]?.ctns ?? 0 }}
                </span>
                <span class="grm-modal__stat-lbl">Mis cartones</span>
              </div>
              <div class="grm-modal__stat">
                <span class="grm-modal__stat-val" style="font-size:13px;">
                  {{ selectedRoomForInfo.card_price != null
                      ? selectedRoomForInfo.card_price.toFixed(2)
                      : ((selectedRoomForInfo.config?.card_price_usd ?? 0) * (props.exchangeRate ?? 1)).toFixed(2)
                  }}
                  <small style="font-size:9px;color:#5578bb;">{{ selectedRoomForInfo.currency_symbol ?? props.currencySym }}</small>
                </span>
                <span class="grm-modal__stat-lbl">Precio cartón</span>
              </div>
            </div>

            <!-- Acciones -->
            <div class="grm-modal__actions">
              <button
                class="grm-modal__btn grm-modal__btn--lobby"
                @click="goToLobby(selectedRoomForInfo.id)"
              >
                <i class="fas fa-sign-in-alt" />
                Ir al Lobby
              </button>
              <button
                v-if="canBuy && selectedRoomForInfo.status_code === 'ACTIVE'"
                class="grm-modal__btn grm-modal__btn--buy"
                @click="openPurchaseFromInfo"
              >
                <i class="fas fa-shopping-cart" />
                Comprar Cartones
              </button>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

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
html, body {
  height: auto !important;
  min-height: 100%;
  overflow-y: auto !important;
  overflow-x: hidden !important;
}

body, body.hold-transition, #app-wrapper, .wrapper, .content-wrapper {
  background-color: #04081a !important;
  background-image: none !important;
}

.content-header {
  display: none !important;
  padding: 0 !important;
  min-height: 0 !important;
  height: 0 !important;
}

.content-wrapper > .content { padding: 0 !important; }
.content-wrapper > .content { display: flex; flex-direction: column; }

.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: auto !important;
  min-height: 100vh;
  overflow: visible !important;
}

.content-wrapper > .content {
  overflow: visible !important;
  height: auto !important;
  min-height: 100vh;
}

.wrapper, .content-wrapper {
  height: auto !important;
  min-height: 100vh;
  overflow: visible !important;
}
</style>

<style scoped>
/* ═══════════════════════════════
   PÁGINA
═══════════════════════════════ */
.game-rooms-page {
  flex: 1;
  min-height: 100vh;
  padding: 40px 16px 80px;
  position: relative;
  isolation: isolate;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: #7b4fff rgba(255,255,255,0.03);

  background:
    radial-gradient(ellipse 90% 55% at 15% 5%,  rgba(80,0,200,0.3)   0%, transparent 60%),
    radial-gradient(ellipse 70% 50% at 85% 12%,  rgba(0,60,220,0.24)  0%, transparent 58%),
    radial-gradient(ellipse 60% 45% at 50% 85%,  rgba(0,150,110,0.16) 0%, transparent 62%),
    radial-gradient(ellipse 55% 65% at 92% 68%,  rgba(130,0,210,0.2)  0%, transparent 58%),
    radial-gradient(ellipse 45% 38% at 8%  58%,  rgba(0,90,255,0.14)  0%, transparent 52%),
    radial-gradient(ellipse 35% 30% at 40% 40%,  rgba(0,180,255,0.08) 0%, transparent 50%),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}

.game-rooms-page::-webkit-scrollbar { width: 4px; }
.game-rooms-page::-webkit-scrollbar-track { background: rgba(255,255,255,0.03); border-radius: 10px; }
.game-rooms-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
}

/* Estrellas */
.game-rooms-page::before {
  content: "";
  position: fixed; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  6%  8%, rgba(255,255,255,0.9)  0%, transparent 100%),
    radial-gradient(1px 1px at 14% 22%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 24%  4%, rgba(255,255,255,0.8)  0%, transparent 100%),
    radial-gradient(1px 1px at 33% 38%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 16%, rgba(255,255,255,0.7)  0%, transparent 100%),
    radial-gradient(1px 1px at 57%  7%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 30%, rgba(255,255,255,0.6)  0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 88%  5%, rgba(255,255,255,0.5)  0%, transparent 100%),
    radial-gradient(1px 1px at 95% 22%, rgba(255,255,255,0.8)  0%, transparent 100%),
    radial-gradient(1px 1px at  3% 45%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 60%, rgba(255,255,255,0.4)  0%, transparent 100%),
    radial-gradient(1px 1px at 20% 75%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 30% 52%, rgba(255,255,255,0.5)  0%, transparent 100%),
    radial-gradient(1px 1px at 42% 68%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 72% 55%, rgba(255,255,255,0.7)  0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 18% 18%, rgba(180,200,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 48% 10%, rgba(200,180,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 78% 28%, rgba(180,220,255,0.92) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 60% 58%, rgba(200,255,220,0.85) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}

.game-rooms-page::after {
  content: "";
  position: fixed; inset: 0; z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at 10% 15%, rgba(255,255,255,0.95)  0%, transparent 100%),
    radial-gradient(2px 2px at 28% 32%, rgba(200,210,255,0.88)  0%, transparent 100%),
    radial-gradient(2px 2px at 45%  8%, rgba(255,255,255,0.82)  0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255,240,180,0.92)  0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180,200,255,0.85)  0%, transparent 100%),
    radial-gradient(3px 3px at 37% 20%, rgba(255,255,255,1)     0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220,200,255,0.96)  0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255,255,255,0.92)  0%, transparent 100%),
    radial-gradient(4px 4px at 62% 35%, rgba(255,255,255,1) 0%, rgba(200,220,255,0.3) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0%   { opacity: 0.55; }
  40%  { opacity: 0.95; }
  70%  { opacity: 0.7;  }
  100% { opacity: 0.85; }
}
@keyframes twinkle-b {
  0%   { opacity: 0.7; }
  35%  { opacity: 0.5; }
  65%  { opacity: 1;   }
  100% { opacity: 0.65;}
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
   GRID
═══════════════════════════════ */
.game-rooms-page__grid-wrap { width: 100%; }

.game-rooms-page__grid {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -12px;
}

.game-rooms-page__grid--centered { justify-content: center; }

.game-rooms-page__grid-item {
  padding: 0 12px 24px;
  flex: 0 0 auto;
  display: flex;
  justify-content: center;
}

@media (max-width: 575px)  { .game-rooms-page__grid-item { width: 100%; } }
@media (min-width: 576px)  { .game-rooms-page__grid-item { width: 50%; } }
@media (min-width: 900px)  { .game-rooms-page__grid-item { width: 33.333%; } }
@media (min-width: 1200px) { .game-rooms-page__grid-item { width: 25%; } }

/* ═══════════════════════════════
   MODAL OVERLAY
═══════════════════════════════ */
.grm-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(2, 5, 20, 0.88);
  backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}

.grm-modal {
  width: 100%; max-width: 420px;
  background: linear-gradient(160deg, #0d1635 0%, #080f28 100%);
  border: 1px solid rgba(100, 140, 255, 0.28);
  border-radius: 24px;
  box-shadow:
    0 32px 80px rgba(0,0,0,0.85),
    0 0 0 1px rgba(80,120,255,0.1) inset;
  overflow: hidden;
  position: relative;
}

.grm-modal::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 200px;
  background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(123,79,255,0.35), transparent 70%);
  pointer-events: none;
}

/* Imagen */
.grm-modal__img-wrap {
  position: relative;
  height: 190px;
  overflow: hidden;
}
.grm-modal__img {
  width: 100%; height: 100%; object-fit: cover; display: block;
}
.grm-modal__img-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(5,10,30,0.15) 0%, rgba(8,15,40,0.88) 100%);
}

/* Timer sobre imagen */
.grm-modal__timer {
  position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%);
  background: rgba(8, 14, 40, 0.9);
  border: 1px solid rgba(100,140,255,0.35);
  backdrop-filter: blur(8px);
  color: #c8d8ff;
  border-radius: 30px;
  padding: 6px 18px;
  font-family: 'Anton', sans-serif;
  font-size: 15px;
  letter-spacing: 1px;
  display: flex; align-items: center; gap: 6px;
  white-space: nowrap;
  box-shadow: 0 4px 16px rgba(0,0,0,0.5);
}
.grm-modal__timer--live {
  border-color: rgba(255,70,70,0.55);
  color: #ff8080;
  animation: pulse-live 1.8s ease-in-out infinite;
}
@keyframes pulse-live {
  0%, 100% { box-shadow: 0 4px 16px rgba(0,0,0,0.5); }
  50%       { box-shadow: 0 4px 20px rgba(255,60,60,0.4); }
}
.grm-live-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #ff4d4d;
  box-shadow: 0 0 6px #ff4d4d;
  animation: blink-dot 1s ease-in-out infinite;
}
@keyframes blink-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

/* Cierre */
.grm-modal__close {
  position: absolute; top: 12px; right: 12px; z-index: 20;
  background: rgba(0,0,0,0.55);
  border: 1px solid rgba(255,255,255,0.18);
  color: #fff;
  width: 34px; height: 34px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 13px;
  transition: background 0.2s, transform 0.15s;
}
.grm-modal__close:hover {
  background: rgba(255,255,255,0.18);
  transform: rotate(90deg);
}

/* Body */
.grm-modal__body {
  padding: 20px 24px 26px;
  position: relative; z-index: 1;
}

/* Badges */
.grm-modal__badges {
  display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap;
}
.grm-modal__badge {
  padding: 5px 14px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: 0.5px;
  display: flex; align-items: center;
}
.grm-modal__badge--type {
  background: rgba(123,79,255,0.2);
  border: 1px solid rgba(123,79,255,0.45);
  color: #b48fff;
}
.grm-modal__badge--mode {
  background: rgba(0,158,253,0.15);
  border: 1px solid rgba(0,158,253,0.38);
  color: #7cd4ff;
}

/* Premio */
.grm-modal__prize { text-align: center; margin-bottom: 20px; }
.grm-modal__prize-label {
  font-size: 11px; color: #7c9dff;
  text-transform: uppercase; letter-spacing: 1.2px;
  margin-bottom: 4px; display: block;
}
.grm-modal__prize-amount-row {
  display: flex; align-items: baseline;
  justify-content: center; gap: 6px;
}
.grm-modal__prize-amount {
  font-family: 'Anton', sans-serif; font-size: 52px; line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 4px 14px rgba(255,180,0,0.5));
}
.grm-modal__prize-sym {
  font-size: 20px; font-weight: 700; color: #ffd060; -webkit-text-fill-color: #ffd060;
}

/* Stats */
.grm-modal__stats {
  display: flex; gap: 10px; margin-bottom: 22px;
}
.grm-modal__stat {
  flex: 1;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(100,140,255,0.13);
  border-radius: 14px; padding: 12px 8px; text-align: center;
  transition: border-color 0.2s;
}
.grm-modal__stat:hover { border-color: rgba(100,140,255,0.28); }
.grm-modal__stat-val {
  font-family: 'Anton', sans-serif; font-size: 20px; color: #e0eaff;
  display: block; margin-bottom: 3px;
}
.grm-modal__stat-lbl {
  font-size: 9px; color: #5578bb;
  text-transform: uppercase; letter-spacing: 0.8px;
}

/* Acciones */
.grm-modal__actions { display: flex; flex-direction: column; gap: 10px; }
.grm-modal__btn {
  width: 100%; padding: 15px; border: none; border-radius: 14px;
  font-size: 13px; font-weight: 800; letter-spacing: 1.2px;
  text-transform: uppercase; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  transition: filter 0.2s, transform 0.15s, box-shadow 0.2s;
}
.grm-modal__btn--lobby {
  background: linear-gradient(90deg, #7b4fff 0%, #009efd 100%);
  color: #fff;
  box-shadow: 0 6px 24px rgba(123,79,255,0.4);
}
.grm-modal__btn--lobby:hover {
  filter: brightness(1.12);
  transform: translateY(-2px);
  box-shadow: 0 10px 32px rgba(123,79,255,0.55);
}
.grm-modal__btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #051830;
  box-shadow: 0 6px 24px rgba(42,245,152,0.35);
}
.grm-modal__btn--buy:hover {
  filter: brightness(1.08);
  transform: translateY(-2px);
  box-shadow: 0 10px 32px rgba(42,245,152,0.5);
}

/* Transition modal */
.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>
