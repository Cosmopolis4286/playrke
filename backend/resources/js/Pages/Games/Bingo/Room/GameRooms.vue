<script setup lang="ts">
/**
 * Página: Salas de Juego de Bingo (game-rooms)
 *
 * Vista tipo card-grid equivalente al blade `game-rooms.blade.php`,
 * migrada a Vue 3 + Inertia.
 *
 * Responsabilidades:
 *  - Renderizar las salas activas del día como tarjetas
 *  - Gestionar el countdown de cada sala
 *  - Manejar la selección de sala y compra de cartones (modal)
 */
// ==========================================================================
// Imports
// ==========================================================================
import { ref, onMounted, computed } from "vue";
import "@/css/games/bingo/bingo-game-rooms.css";

// ==========================================================================
// Services
// ==========================================================================
import { AuthService } from "@/js/Services/authService";

// ==========================================================================
// Componentes
// ==========================================================================
import BingoGameRoomCard from "@/js/Components/Games/Bingo/Room/GameRooms/BingoGameRoomCard.vue";
import BingoPurchaseModal from "@/js/Components/Games/Bingo/Card/PurchaseModal/BingoPurchaseModal.vue";

// ==========================================================================
// Composables
// ==========================================================================
import { useGameRoomCountdown } from "@/js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useGameRoomEcho } from "@/js/composables/games/bingo/room/game-rooms/useGameRoomEcho";
import { useGameRoomPurchaseModal } from "@/js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal";

// ==========================================================================
// Types
// ==========================================================================
import type {
  BingoGameRoomsPageProps,
  BingoGameRoom,
} from "@/js/types/games/bingo/room/game-rooms/bingo-game-room";

// ==========================================================================
// Props (Inertia)
// ==========================================================================
const props = defineProps<BingoGameRoomsPageProps>();

// ==========================================================================
// Estado local de salas (mutable para actualizar prize_pool y ctns)
// ==========================================================================
const rooms = ref<BingoGameRoom[]>(props.rooms.map((r) => ({ ...r })));

// ==========================================================================
// Filtrado de salas por rol de usuario
// ==========================================================================
const filteredRooms = computed(() => {
  // Si el usuario tiene rol 'player', mostrar solo salas PUBLIC
  if (AuthService.hasRole("player")) {
    return rooms.value.filter((room) => room.room_type === "PUBLIC");
  }
  // Para otros roles, mostrar todas las salas
  return rooms.value;
});

// ==========================================================================
// WebSocket — actualizaciones en tiempo real (prize_pool, total_players, card_price)
// ==========================================================================
useGameRoomEcho(rooms);

// ==========================================================================
// Countdown
// ==========================================================================

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown } = useGameRoomCountdown(
  serverTimestampMs,
  /** onExpire: la tarjeta reacciona vía `countdown.expired` de forma reactiva */
  (_roomId) => {}
);

// ==========================================================================
// Modal de compra de cartones
// ==========================================================================

const modal = useGameRoomPurchaseModal();

/** Abre el modal para la sala con el ID dado. */
function openModal(roomId: string): void {
  const room = filteredRooms.value.find((r) => r.id === roomId);
  if (room) modal.open(room);
}

/**
 * Callback de compra exitosa:
 * actualiza prize_pool y ctns del jugador sin recargar la página.
 */
function onPurchaseSuccess(
  roomId: string,
  result: { prizePool: number; ctns: number }
): void {
  const idx = rooms.value.findIndex((r) => r.id === roomId);
  if (idx === -1) return;

  const room = rooms.value[idx];
  const updatedPlayer = { ctns: (room.players_room?.[0]?.ctns ?? 0) + result.ctns };

  rooms.value[idx] = {
    ...room,
    prize_pool: result.prizePool,
    players_room: [updatedPlayer],
  };
}

// ==========================================================================
// Lifecycle
// ==========================================================================

onMounted(() => {
  initCountdown(props.rooms);
});
</script>

<template>
  <div class="game-rooms-page">
    <!-- ── Encabezado ── -->
    <div class="game-rooms-page__header">
      <h1 class="game-rooms-page__title">
        <i class="fas fa-dice mr-2" style="color: #2af598" />
        {{ props.title ?? "SALAS DE JUEGO" }}
      </h1>
      <p class="game-rooms-page__subtitle">
        Selecciona una sala activa para comprar tus cartones
      </p>
    </div>

    <!-- ── Sin salas ── -->
    <div v-if="!filteredRooms.length" class="game-rooms-page__empty">
      <i class="fas fa-door-closed" />
      <p>No hay salas activas para hoy</p>
    </div>

    <!-- ── Grid de tarjetas ── -->
    <div v-else class="game-rooms-page__grid-wrap">
      <div
        class="game-rooms-page__grid"
        :class="{ 'game-rooms-page__grid--centered': filteredRooms.length < 4 }"
      >
        <div
          v-for="room in filteredRooms"
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
            @select="openModal"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- ══ Modal de compra ══ -->
  <BingoPurchaseModal
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
/* ═══════════════════════════════════════════════════════════════
   FONDO ESTELAR — cubre body + AdminLTE wrappers
═══════════════════════════════════════════════════════════════ */

body,
body.hold-transition,
#app-wrapper,
.wrapper,
.content-wrapper {
  background-color: #04081a !important;
  background-image: none !important;
}

/* Eliminar content-header y padding de section.content para
   que GameRooms ocupe todo el espacio disponible */
.content-header {
  display: none !important;
  padding: 0 !important;
  min-height: 0 !important;
  height: 0 !important;
}

.content-wrapper > .content {
  padding: 0 !important;
}

/* La section.content debe ser flex para que .game-rooms-page
   pueda estirarse al 100% de altura */
.content-wrapper > .content {
  display: flex;
  flex-direction: column;
}

/* El div#app que Inertia monta también debe estirarse */
.content-wrapper > .content > #app,
.content-wrapper > .content > [data-page] {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* El scroll lo gestiona .game-rooms-page (scoped), no el wrapper */
.content-wrapper > .content {
  overflow: hidden !important;
}
</style>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   PÁGINA — nebulosas + estrellas CSS
═══════════════════════════════════════════════════════════════ */
.game-rooms-page {
  flex: 1;
  min-height: 100%;
  padding: 32px 16px 60px;
  position: relative;
  isolation: isolate;

  /* Scroll propio — el scrollbar queda 100% scoped */
  overflow-y: auto;
  overflow-x: hidden;

  /* Scrollbar premium (scoped al componente) */
  scrollbar-width: thin;
  scrollbar-color: #7b4fff rgba(255, 255, 255, 0.03);

  /* Nebulosa base */
  background: radial-gradient(
      ellipse 90% 55% at 15% 5%,
      rgba(80, 0, 200, 0.3) 0%,
      transparent 60%
    ),
    radial-gradient(
      ellipse 70% 50% at 85% 12%,
      rgba(0, 60, 220, 0.24) 0%,
      transparent 58%
    ),
    radial-gradient(
      ellipse 60% 45% at 50% 85%,
      rgba(0, 150, 110, 0.16) 0%,
      transparent 62%
    ),
    radial-gradient(
      ellipse 55% 65% at 92% 68%,
      rgba(130, 0, 210, 0.2) 0%,
      transparent 58%
    ),
    radial-gradient(ellipse 45% 38% at 8% 58%, rgba(0, 90, 255, 0.14) 0%, transparent 52%),
    radial-gradient(
      ellipse 35% 30% at 40% 40%,
      rgba(0, 180, 255, 0.08) 0%,
      transparent 50%
    ),
    linear-gradient(160deg, #04081a 0%, #060d2a 45%, #030812 100%);
}

/* Scrollbar Webkit — scoped al componente */
.game-rooms-page::-webkit-scrollbar {
  width: 4px;
}
.game-rooms-page::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
}
.game-rooms-page::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff 0%, #2af598 45%, #009efd 100%);
  border-radius: 10px;
  box-shadow: 0 0 6px rgba(42, 245, 152, 0.45);
}
.game-rooms-page::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #9d6fff 0%, #3fffa8 45%, #33b8ff 100%);
  box-shadow: 0 0 10px rgba(42, 245, 152, 0.7);
}
.game-rooms-page::-webkit-scrollbar-button {
  display: none;
}

/* ── Estrellas pequeñas (capa 1) ── */
.game-rooms-page::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: -2;
  pointer-events: none;
  background-image: radial-gradient(
      1px 1px at 6% 8%,
      rgba(255, 255, 255, 0.9) 0%,
      transparent 100%
    ),
    radial-gradient(1px 1px at 14% 22%, rgba(255, 255, 255, 0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 24% 4%, rgba(255, 255, 255, 0.8) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 38%, rgba(255, 255, 255, 0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 16%, rgba(255, 255, 255, 0.7) 0%, transparent 100%),
    radial-gradient(1px 1px at 57% 7%, rgba(255, 255, 255, 0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 66% 30%, rgba(255, 255, 255, 0.6) 0%, transparent 100%),
    radial-gradient(1px 1px at 76% 12%, rgba(255, 255, 255, 0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 88% 5%, rgba(255, 255, 255, 0.5) 0%, transparent 100%),
    radial-gradient(1px 1px at 95% 22%, rgba(255, 255, 255, 0.8) 0%, transparent 100%),
    radial-gradient(1px 1px at 3% 45%, rgba(255, 255, 255, 0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 11% 60%, rgba(255, 255, 255, 0.4) 0%, transparent 100%),
    radial-gradient(1px 1px at 20% 75%, rgba(255, 255, 255, 0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 30% 52%, rgba(255, 255, 255, 0.5) 0%, transparent 100%),
    radial-gradient(1px 1px at 42% 68%, rgba(255, 255, 255, 0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 53% 48%, rgba(255, 255, 255, 0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 62% 80%, rgba(255, 255, 255, 0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 72% 55%, rgba(255, 255, 255, 0.7) 0%, transparent 100%),
    radial-gradient(1px 1px at 83% 70%, rgba(255, 255, 255, 0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 93% 62%, rgba(255, 255, 255, 0.8) 0%, transparent 100%),
    radial-gradient(1px 1px at 8% 88%, rgba(255, 255, 255, 0.6) 0%, transparent 100%),
    radial-gradient(1px 1px at 22% 93%, rgba(255, 255, 255, 0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 38% 85%, rgba(255, 255, 255, 0.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 50% 96%, rgba(255, 255, 255, 0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 68% 90%, rgba(255, 255, 255, 0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 80% 83%, rgba(255, 255, 255, 0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 91% 92%, rgba(255, 255, 255, 0.48) 0%, transparent 100%),
    /* Estrellas azuladas/violetas */
      radial-gradient(
        1.5px 1.5px at 18% 18%,
        rgba(180, 200, 255, 0.95) 0%,
        transparent 100%
      ),
    radial-gradient(
      1.5px 1.5px at 48% 10%,
      rgba(200, 180, 255, 0.88) 0%,
      transparent 100%
    ),
    radial-gradient(
      1.5px 1.5px at 78% 28%,
      rgba(180, 220, 255, 0.92) 0%,
      transparent 100%
    ),
    radial-gradient(
      1.5px 1.5px at 35% 72%,
      rgba(255, 200, 200, 0.78) 0%,
      transparent 100%
    ),
    radial-gradient(
      1.5px 1.5px at 60% 58%,
      rgba(200, 255, 220, 0.85) 0%,
      transparent 100%
    ),
    radial-gradient(1.5px 1.5px at 90% 45%, rgba(255, 240, 180, 0.8) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}

/* ── Estrellas medianas (capa 2, desfasada) ── */
.game-rooms-page::after {
  content: "";
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-image: radial-gradient(
      2px 2px at 10% 15%,
      rgba(255, 255, 255, 0.95) 0%,
      transparent 100%
    ),
    radial-gradient(2px 2px at 28% 32%, rgba(200, 210, 255, 0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 45% 8%, rgba(255, 255, 255, 0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 65% 25%, rgba(255, 240, 180, 0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 82% 18%, rgba(180, 200, 255, 0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 96% 38%, rgba(255, 255, 255, 0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 5% 55%, rgba(200, 255, 240, 0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 23% 70%, rgba(255, 255, 255, 0.9) 0%, transparent 100%),
    radial-gradient(2px 2px at 55% 62%, rgba(220, 200, 255, 0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 74% 78%, rgba(255, 255, 255, 0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 88% 58%, rgba(200, 240, 255, 0.88) 0%, transparent 100%),
    /* Estrellas grandes / brillantes */
      radial-gradient(3px 3px at 37% 20%, rgba(255, 255, 255, 1) 0%, transparent 100%),
    radial-gradient(3px 3px at 70% 45%, rgba(220, 200, 255, 0.96) 0%, transparent 100%),
    radial-gradient(3px 3px at 15% 82%, rgba(255, 255, 255, 0.92) 0%, transparent 100%),
    radial-gradient(3px 3px at 92% 72%, rgba(200, 240, 255, 0.98) 0%, transparent 100%),
    radial-gradient(3px 3px at 50% 90%, rgba(255, 240, 160, 0.9) 0%, transparent 100%),
    /* Estrella brillante especial con halo */
      radial-gradient(
        4px 4px at 62% 35%,
        rgba(255, 255, 255, 1) 0%,
        rgba(200, 220, 255, 0.3) 50%,
        transparent 100%
      ),
    radial-gradient(
      4px 4px at 25% 48%,
      rgba(255, 248, 200, 0.95) 0%,
      rgba(255, 240, 160, 0.2) 50%,
      transparent 100%
    );
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0% {
    opacity: 0.55;
  }
  40% {
    opacity: 0.95;
  }
  70% {
    opacity: 0.7;
  }
  100% {
    opacity: 0.85;
  }
}
@keyframes twinkle-b {
  0% {
    opacity: 0.7;
  }
  35% {
    opacity: 0.5;
  }
  65% {
    opacity: 1;
  }
  100% {
    opacity: 0.65;
  }
}

/* ═══════════════════════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════════════════════ */
.game-rooms-page__header {
  text-align: center;
  margin-bottom: 44px;
  position: relative;
}

/* Línea decorativa bajo el título */
.game-rooms-page__header::after {
  content: "";
  display: block;
  width: 60px;
  height: 2px;
  margin: 10px auto 0;
  background: linear-gradient(90deg, #2af598, #009efd);
  border-radius: 2px;
  opacity: 0.7;
}

.game-rooms-page__title {
  font-family: "Anton", sans-serif;
  font-size: clamp(26px, 4vw, 38px);
  letter-spacing: 3px;
  color: #e0eaff;
  margin-bottom: 6px;
  text-shadow: 0 0 30px rgba(42, 245, 152, 0.2), 0 2px 4px rgba(0, 0, 0, 0.5);
}
.game-rooms-page__subtitle {
  font-size: 13px;
  color: #4a6696;
  letter-spacing: 0.5px;
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════════════════════════════ */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 100px 0;
  color: #2e4068;
}
.game-rooms-page__empty i {
  font-size: 52px;
  opacity: 0.4;
}
.game-rooms-page__empty p {
  font-size: 15px;
  margin: 0;
}

/* ═══════════════════════════════════════════════════════════════
   GRID
═══════════════════════════════════════════════════════════════ */
.game-rooms-page__grid-wrap {
  width: 100%;
}
.game-rooms-page__grid {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -10px;
}
.game-rooms-page__grid--centered {
  justify-content: center;
}
.game-rooms-page__grid-item {
  padding: 0 10px;
  flex: 0 0 auto;
  display: flex;
  justify-content: center;
}

@media (max-width: 575px) {
  .game-rooms-page__grid-item {
    width: 100%;
  }
}
@media (min-width: 576px) {
  .game-rooms-page__grid-item {
    width: 50%;
  }
}
@media (min-width: 768px) {
  .game-rooms-page__grid-item {
    width: 33.333%;
  }
}
@media (min-width: 1200px) {
  .game-rooms-page__grid-item {
    width: 25%;
  }
}
</style>
