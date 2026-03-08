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
 *  - Manejar la selección de sala y compra de cartones
 */
import { onMounted } from 'vue'
import '@/css/games/bingo/bingo-game-rooms.css'

// ==========================================================================
// Componentes
// ==========================================================================
import BingoGameRoomCard from '@/js/Components/Games/Bingo/Room/GameRooms/BingoGameRoomCard.vue'

// ==========================================================================
// Composables
// ==========================================================================
import { useGameRoomCountdown } from '@/js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import { useGameRoomCardPurchase } from '@/js/composables/games/bingo/room/game-rooms/useGameRoomCardPurchase'

// ==========================================================================
// Types
// ==========================================================================
import type { BingoGameRoomsPageProps } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

// ==========================================================================
// Props (Inertia)
// ==========================================================================
const props = defineProps<BingoGameRoomsPageProps>()

// ==========================================================================
// Countdown
// ==========================================================================

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now()

const { init: initCountdown, getCountdown } = useGameRoomCountdown(
  serverTimestampMs,
  /** onExpire: la tarjeta reacciona vía `countdown.expired` de forma reactiva */
  (_roomId) => {},
)

// ==========================================================================
// Compra de cartones
// ==========================================================================

const {
  selectedRoomId,
  roomDetail,
  numCtns,
  showPurchasePanel,
  loading,
  totalAmount,
  prizePoolLabel,
  fetchRoomDetail,
  closePurchasePanel,
  incrementCtns,
  decrementCtns,
  setNumCtns,
  purchase,
} = useGameRoomCardPurchase({
  creatorId: props.creator?.id ?? '',
  creatorType: props.creator?.type ?? '',
  player: props.player ?? null,
  currency: props.currency ?? null,
  creatorUser: props.creatorUser ?? null,
})

// ==========================================================================
// Lifecycle
// ==========================================================================

onMounted(() => {
  initCountdown(props.rooms)
})
</script>

<template>
  <div class="game-rooms-page">

    <!-- ── Encabezado ── -->
    <div class="game-rooms-page__header">
      <h1 class="game-rooms-page__title">
        <i class="fas fa-dice mr-2" style="color:#2af598;" />
        {{ props.title ?? 'SALAS DE JUEGO' }}
      </h1>
      <p class="game-rooms-page__subtitle">Selecciona una sala activa para comprar tus cartones</p>
    </div>

    <!-- ── Sin salas ── -->
    <div v-if="!props.rooms.length" class="game-rooms-page__empty">
      <i class="fas fa-door-closed" />
      <p>No hay salas activas para hoy</p>
    </div>

    <!-- ── Grid de tarjetas ── -->
    <div v-else class="game-rooms-page__grid-wrap">
      <div
        class="game-rooms-page__grid"
        :class="{ 'game-rooms-page__grid--centered': props.rooms.length < 4 }"
      >
        <div
          v-for="room in props.rooms"
          :key="room.id"
          class="game-rooms-page__grid-item"
        >
          <BingoGameRoomCard
            :room="room"
            :exchange-rate="props.exchangeRate ?? 1"
            :currency-sym="props.currencySym ?? ''"
            :countdown="getCountdown(room.id)"
            :is-purchasing="showPurchasePanel && selectedRoomId === room.id"
            :room-detail="roomDetail"
            :num-ctns="numCtns"
            :total-amount="totalAmount"
            :prize-pool-label="prizePoolLabel"
            :loading="loading"
            @select="fetchRoomDetail"
            @close="closePurchasePanel"
            @increment="incrementCtns"
            @decrement="decrementCtns"
            @set-ctns="setNumCtns"
            @purchase="purchase()"
          />
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* ── Fondo estelar puro CSS ── */
:global(body) {
  background-color: #04081a !important;
  background-image: none !important;
}

.game-rooms-page {
  min-height: 100vh;
  padding: 32px 16px 48px;
  position: relative;
  overflow: hidden;
  isolation: isolate;

  /* Nebulosa base */
  background:
    radial-gradient(ellipse 80% 60% at 20% 10%,  rgba(72, 0, 180, 0.28) 0%, transparent 65%),
    radial-gradient(ellipse 60% 50% at 80% 15%,  rgba(0, 80, 220, 0.22) 0%, transparent 60%),
    radial-gradient(ellipse 70% 40% at 50% 80%,  rgba(0, 160, 120, 0.14) 0%, transparent 65%),
    radial-gradient(ellipse 50% 60% at 90% 70%,  rgba(120, 0, 200, 0.18) 0%, transparent 60%),
    radial-gradient(ellipse 40% 35% at 10% 60%,  rgba(0, 100, 255, 0.12) 0%, transparent 55%),
    linear-gradient(160deg, #04081a 0%, #060d2a 40%, #030812 100%);
}

/* Capa de estrellas pequeñas */
.game-rooms-page::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -2;
  pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  8%  12%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 18%  35%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 32%   8%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 45%  22%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 60%   5%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(1px 1px at 73%  18%, rgba(255,255,255,0.65) 0%, transparent 100%),
    radial-gradient(1px 1px at 85%  30%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 93%   9%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at  5%  48%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 15%  62%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 27%  75%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 40%  55%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 55%  68%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 68%  45%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(1px 1px at 80%  72%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 92%  58%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 12%  88%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 35%  92%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 50%  85%, rgba(255,255,255,0.45) 0%, transparent 100%),
    radial-gradient(1px 1px at 75%  90%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 22%  28%, rgba(180,200,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 64%  14%, rgba(200,180,255,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 88%  42%, rgba(180,220,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 38%  78%, rgba(255,200,200,0.80) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at  3%  25%, rgba(200,255,220,0.85) 0%, transparent 100%),
    radial-gradient(2px   2px   at 52%  40%, rgba(255,255,255,0.95) 0%, transparent 100%),
    radial-gradient(2px   2px   at 78%  65%, rgba(200,220,255,0.90) 0%, transparent 100%),
    radial-gradient(2px   2px   at 10%  72%, rgba(255,240,200,0.85) 0%, transparent 100%);
  animation: twinkle 6s ease-in-out infinite alternate;
}

/* Capa de estrellas medianas con parpadeo desfasado */
.game-rooms-page::after {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-image:
    radial-gradient(2px 2px at 25%  18%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 48%  32%, rgba(200,210,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 70%  10%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 90%  25%, rgba(255,240,180,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at  7%  40%, rgba(180,200,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 30%  60%, rgba(255,255,255,0.85) 0%, transparent 100%),
    radial-gradient(2px 2px at 58%  55%, rgba(200,255,240,0.75) 0%, transparent 100%),
    radial-gradient(2px 2px at 82%  80%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(3px 3px at 42%  15%, rgba(255,255,255,1.00) 0%, transparent 100%),
    radial-gradient(3px 3px at 65%  70%, rgba(220,200,255,0.95) 0%, transparent 100%),
    radial-gradient(3px 3px at 15%  82%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(3px 3px at 96%  50%, rgba(200,240,255,0.95) 0%, transparent 100%);
  animation: twinkle 8s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle {
  0%   { opacity: 0.6; }
  50%  { opacity: 1.0; }
  100% { opacity: 0.7; }
}

/* Header */
.game-rooms-page__header {
  text-align: center;
  margin-bottom: 40px;
}
.game-rooms-page__title {
  font-family: 'Anton', sans-serif;
  font-size: clamp(24px, 4vw, 36px);
  letter-spacing: 2px;
  color: #dadada;
  margin-bottom: 6px;
  text-shadow: 0 2px 20px rgba(42, 245, 152, 0.25);
}
.game-rooms-page__subtitle {
  font-size: 13px;
  color: #5578bb;
  letter-spacing: 0.5px;
  margin: 0;
}

/* Empty state */
.game-rooms-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 80px 0;
  color: #3a4f7a;
}
.game-rooms-page__empty i {
  font-size: 48px;
  opacity: 0.5;
}
.game-rooms-page__empty p {
  font-size: 15px;
  margin: 0;
}

/* Grid */
.game-rooms-page__grid-wrap {
  width: 100%;
}
.game-rooms-page__grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  margin: 0 -8px;
}
.game-rooms-page__grid--centered {
  justify-content: center;
}
.game-rooms-page__grid-item {
  padding: 0 8px;
  flex: 0 0 auto;
}

@media (max-width: 576px) {
  .game-rooms-page__grid-item { width: 100%; display: flex; justify-content: center; }
}
@media (min-width: 576px) {
  .game-rooms-page__grid-item { width: 50%; display: flex; justify-content: center; }
}
@media (min-width: 768px) {
  .game-rooms-page__grid-item { width: 33.333%; }
}
@media (min-width: 1200px) {
  .game-rooms-page__grid-item { width: 25%; }
}
</style>
