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
:global(body) {
  background-image: url('/img/background/bg_1.png') !important;
  background-size: cover;
  background-attachment: fixed;
  background-color: #060d1f !important;
}

.game-rooms-page {
  min-height: 100vh;
  padding: 32px 16px 48px;
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
