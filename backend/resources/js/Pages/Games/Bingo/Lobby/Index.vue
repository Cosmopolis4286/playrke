<script setup lang="ts">
/**
 * Página: Lobby de Bingo
 *
 * Vista intermedia antes de entrar al juego.
 * Muestra información de la sala y permite al jugador prepararse.
 */
import { computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import type { BingoRoom } from "@js/Services/bingoService";
import { BingoService } from "@js/Services/bingoService";
import axios from "axios";

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────
interface Props {
  room: BingoRoom;
  userCards?: number;
}

const props = defineProps<Props>();

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────
const roomId = computed(() => props.room?.id?.toString() ?? "");

const roomStatusText = computed(() => {
  if (!props.room) return "";
  return BingoService.getRoomStatusText(props.room.status);
});

// ─────────────────────────────────────────────────────────────────────────────
// Methods
// ─────────────────────────────────────────────────────────────────────────────
function enterGame() {
  router.visit(`/bingo/${roomId.value}/game`);
}

function buyCards() {
  // TODO: Open modal to select number of cards and country
  const numCards = prompt('¿Cuántos cartones deseas comprar?', '1');
  if (!numCards || isNaN(Number(numCards))) return;

  // For now, assume default country
  axios.post(`/api/bingo/rooms/${roomId.value}/purchase`, {
    num_ctns: Number(numCards),
    country_id: 'default' // TODO: Get from user selection
  })
  .then(response => {
    alert('Cartones comprados exitosamente!');
    // TODO: Update userCards count
    location.reload(); // Temporary reload
  })
  .catch(error => {
    alert('Error al comprar cartones: ' + (error.response?.data?.message || error.message));
  });
}
</script>

<template>
  <div class="bingo-lobby-page">
    <div class="lobby-container">
      <!-- Header -->
      <div class="lobby-header">
        <Link href="/bingo/rooms" class="lobby-back">
          <i class="fas fa-arrow-left" />
          Volver a salas
        </Link>
        <h1 class="lobby-title">Sala de Bingo</h1>
      </div>

      <!-- Room Info Card -->
      <div v-if="room" class="lobby-card">
        <div class="lobby-card__header">
          <h2 class="lobby-room-name">{{ room.roomTypeDescription }}</h2>
          <span class="lobby-room-status" :class="`status-${roomStatusText.toLowerCase().replace(' ', '_')}`">
            {{ roomStatusText }}
          </span>
        </div>

        <div class="lobby-card__body">
          <div class="lobby-info-grid">
            <div class="lobby-info-item">
              <i class="fas fa-trophy" />
              <span class="label">Premio</span>
              <span class="value">{{ room.prizePool }}</span>
            </div>
            <div class="lobby-info-item">
              <i class="fas fa-users" />
              <span class="label">Jugadores</span>
              <span class="value">{{ room.currentPlayers }}</span>
            </div>
            <div class="lobby-info-item">
              <i class="fas fa-ticket-alt" />
              <span class="label">Tus cartones</span>
              <span class="value">{{ userCards ?? 0 }}</span>
            </div>
          </div>
        </div>

        <div class="lobby-card__footer">
          <button
            v-if="userCards === 0"
            class="lobby-btn lobby-btn--secondary"
            @click="buyCards"
          >
            <i class="fas fa-shopping-cart" />
            Comprar Cartones
          </button>
          <button
            class="lobby-btn lobby-btn--primary"
            @click="enterGame"
          >
            <i class="fas fa-play" />
            Entrar al Juego
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else class="lobby-loading">
        <div class="lobby-spinner" />
        <p>Cargando información de la sala...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bingo-lobby-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
  padding: 24px;
}

.lobby-container {
  max-width: 800px;
  margin: 0 auto;
}

.lobby-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
}

.lobby-back {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #7c9dff;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s;
}

.lobby-back:hover {
  color: #a8bdff;
}

.lobby-title {
  font-family: 'Anton', sans-serif;
  font-size: 32px;
  color: #fff;
  margin: 0;
}

.lobby-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(100, 140, 255, 0.2);
  border-radius: 20px;
  padding: 32px;
  backdrop-filter: blur(10px);
}

.lobby-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(100, 140, 255, 0.1);
}

.lobby-room-name {
  font-size: 24px;
  color: #fff;
  margin: 0;
}

.lobby-room-status {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-active,
.status-in_progress {
  background: rgba(42, 245, 152, 0.15);
  color: #2af598;
}

.status-pending {
  background: rgba(255, 193, 7, 0.15);
  color: #ffc107;
}

.status-finished {
  background: rgba(255, 107, 107, 0.15);
  color: #ff6b6b;
}

.lobby-info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.lobby-info-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 12px;
  border: 1px solid rgba(100, 140, 255, 0.1);
}

.lobby-info-item i {
  font-size: 24px;
  color: #7b4fff;
}

.lobby-info-item .label {
  font-size: 12px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.lobby-info-item .value {
  font-size: 20px;
  font-weight: 700;
  color: #e0eaff;
}

.lobby-card__footer {
  display: flex;
  justify-content: center;
}

.lobby-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 32px;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.lobby-btn--primary {
  background: linear-gradient(135deg, #7b4fff 0%, #009efd 100%);
  color: #fff;
}

.lobby-btn--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(123, 79, 255, 0.4);
}

.lobby-btn--secondary {
  background: rgba(255, 255, 255, 0.1);
  color: #7c9dff;
  border: 1px solid rgba(100, 140, 255, 0.3);
  margin-right: 16px;
}

.lobby-btn--secondary:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-1px);
}

.lobby-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 64px;
  color: #5578bb;
}

.lobby-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(100, 140, 255, 0.2);
  border-top-color: #7b4fff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .lobby-info-grid {
    grid-template-columns: 1fr;
  }

  .lobby-title {
    font-size: 24px;
  }

  .lobby-room-name {
    font-size: 18px;
  }
}
</style>
