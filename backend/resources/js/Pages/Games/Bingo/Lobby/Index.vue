<script setup lang="ts">
/**
 * Página: Lobby de Bingo
 *
 * Vista intermedia antes de entrar al juego.
 * Muestra información de la sala y permite al jugador prepararse.
 */
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import type { BingoRoom } from "@js/Services/bingoService";
import { BingoService } from "@js/Services/bingoService";
import axios from "axios";
import { getEchoInstance } from "@js/plugins/echo";

// ─────────────────────────────────────────────────────────────────────────────
// Winner Modal State
// ─────────────────────────────────────────────────────────────────────────────
const showPurchaseModal = ref(false);
const purchaseQuantity = ref(1);
const selectedCountry = ref('');
const availableCountries = ref([]);
const cardPrice = ref(0);
const isPurchasing = ref(false);

// ─────────────────────────────────────────────────────────────────────────────
// Real-time Update Effects
// ─────────────────────────────────────────────────────────────────────────────
const prizeFlash = ref(false);
const playersFlash = ref(false);

// ─────────────────────────────────────────────────────────────────────────────
// Echo Channel
// ─────────────────────────────────────────────────────────────────────────────
let echoChannel = null;
interface Props {
  room: BingoRoom;
  roomRouteKey?: string;
  userCards?: number;
}

const props = defineProps<Props>();

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────
const roomId = computed(() => props.room?.id?.toString() ?? "");
const routeKey = computed(() => props.roomRouteKey?.trim() ?? "");
const roomRouteKey = computed(() => routeKey.value || roomId.value);
const canNavigateGame = computed(() => Boolean(routeKey.value));

const roomStatusText = computed(() => {
  if (!props.room) return "";
  return BingoService.getRoomStatusText(props.room.status);
});

const isRoomLoading = computed(() => !props.room);
const canPurchaseCards = computed(() => {
  if (!props.room) return false;
  return props.room.status === 1 && !props.room.closeRoom;
});

// ─────────────────────────────────────────────────────────────────────────────
// Lifecycle
// ─────────────────────────────────────────────────────────────────────────────
onMounted(() => {
  setupEcho();
});

onUnmounted(() => {
  if (echoChannel) {
    echoChannel.stopListening('RoomUpdated');
  }
});

function setupEcho() {
  if (!props.room?.id) return;

  const echo = getEchoInstance();
  echoChannel = echo.channel(`bingo.room.${props.room.id}`);

  echoChannel.listen('.RoomUpdated', (event) => {
    // Update room data in real-time
    if (props.room) {
      const prizeChanged = props.room.prizePool !== event.prize_pool;
      const playersChanged = props.room.currentPlayers !== event.total_players;

      props.room.prizePool = event.prize_pool;
      props.room.currentPlayers = event.total_players;

      // Trigger flash animations
      if (prizeChanged) {
        prizeFlash.value = true;
        setTimeout(() => prizeFlash.value = false, 2000);
      }
      if (playersChanged) {
        playersFlash.value = true;
        setTimeout(() => playersFlash.value = false, 2000);
      }
    }
  });
}

function openPurchaseModal() {
  if (!canNavigateGame.value) {
    alert('La sala no tiene una clave de ruta válida. Recarga la página o contacta al soporte.');
    return;
  }

  if (!canPurchaseCards.value) {
    alert('No es posible comprar cartones en esta sala: la sala debe estar abierta.');
    return;
  }

  showPurchaseModal.value = true;
  loadPurchaseData();
}

async function loadPurchaseData() {
  try {
    // Load available countries
    const countriesResponse = await axios.get(`/bingo-card/fetch-room`);
    availableCountries.value = countriesResponse.data.data || [];

    // Load card price
    const priceResponse = await axios.get(`/bingo-card/${roomRouteKey.value}/card-price`);
    cardPrice.value = priceResponse.data.data?.card_price || 0;

    // Set default country
    if (availableCountries.value.length > 0) {
      selectedCountry.value = availableCountries.value[0].id;
    }
  } catch (error) {
    console.error('Error loading purchase data:', error);
  }
}

async function confirmPurchase() {
  if (purchaseQuantity.value < 1) return;
  if (!canNavigateGame.value) {
    alert('No se puede completar la compra porque la sala no tiene una clave de ruta válida.');
    return;
  }

  isPurchasing.value = true;
  try {
    const response = await axios.post(`/api/bingo/rooms/${roomRouteKey.value}/purchase`, {
      num_ctns: purchaseQuantity.value,
      country_id: selectedCountry.value
    });

    showPurchaseModal.value = false;
    // Update user cards count
    // For now, reload the page
    window.location.reload();
  } catch (error) {
    alert('Error al comprar cartones: ' + (error.response?.data?.message || error.message));
  } finally {
    isPurchasing.value = false;
  }
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
      <div v-if="!isRoomLoading" class="lobby-card">
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
              <span class="value" :class="{ 'flash-update': prizeFlash }">{{ room.prizePool }}</span>
            </div>
            <div class="lobby-info-item">
              <i class="fas fa-users" />
              <span class="label">Jugadores</span>
              <span class="value" :class="{ 'flash-update': playersFlash }">{{ room.currentPlayers }}</span>
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
            v-if="userCards === 0 && canPurchaseCards"
            class="lobby-btn lobby-btn--secondary"
            @click="openPurchaseModal"
          >
            <i class="fas fa-shopping-cart" />
            Comprar Cartones
          </button>

          <template v-if="canNavigateGame">
            <Link
              :href="`/bingo/${roomRouteKey}/game`"
              class="lobby-btn lobby-btn--primary"
            >
              <i class="fas fa-play" />
              Entrar al Juego
            </Link>
          </template>

          <button
            v-else
            class="lobby-btn lobby-btn--primary lobby-btn--disabled"
            disabled
          >
            <i class="fas fa-play" />
            Entrar al Juego
          </button>
        </div>
        <p v-if="!canNavigateGame" class="lobby-warning">
          No se puede acceder al juego porque la sala no tiene una clave válida. Recarga la página.
        </p>
      </div>

      <!-- Loading State -->
      <div v-else class="lobby-loading">
        <div class="lobby-spinner" />
        <p>Cargando información de la sala...</p>
      </div>
    </div>

    <!-- Purchase Modal -->
    <div v-if="showPurchaseModal" class="modal-overlay" @click="showPurchaseModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Comprar Cartones</h3>
          <button class="modal-close" @click="showPurchaseModal = false">
            <i class="fas fa-times" />
          </button>
        </div>

        <div class="modal-body">
          <div class="purchase-info">
            <div class="info-item">
              <span class="label">Precio por cartón:</span>
              <span class="value">${{ cardPrice }}</span>
            </div>
            <div class="info-item">
              <span class="label">Total:</span>
              <span class="value">${{ (cardPrice * purchaseQuantity).toFixed(2) }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="quantity">Cantidad de cartones:</label>
            <input
              id="quantity"
              type="number"
              v-model.number="purchaseQuantity"
              min="1"
              max="10"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="country">País:</label>
            <select
              id="country"
              v-model="selectedCountry"
              class="form-input"
            >
              <option v-for="country in availableCountries" :key="country.id" :value="country.id">
                {{ country.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button class="modal-btn modal-btn--cancel" @click="showPurchaseModal = false">
            Cancelar
          </button>
          <button
            class="modal-btn modal-btn--primary"
            @click="confirmPurchase"
            :disabled="isPurchasing || purchaseQuantity < 1"
          >
            <i v-if="isPurchasing" class="fas fa-spinner fa-spin" />
            {{ isPurchasing ? 'Comprando...' : 'Comprar' }}
          </button>
        </div>
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
  transition: color 0.3s ease;
}

.flash-update {
  animation: flash 2s ease-in-out;
  color: #2af598 !important;
}

@keyframes flash {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); color: #2af598; }
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

.lobby-btn--disabled {
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.6);
  cursor: not-allowed;
  box-shadow: none;
}

.lobby-warning {
  margin-top: 16px;
  color: #ffc107;
  font-size: 14px;
  text-align: center;
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

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(5px);
}

.modal-content {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 0;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid rgba(100, 140, 255, 0.2);
}

.modal-header h3 {
  margin: 0;
  color: #1a1f3a;
  font-size: 24px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 20px;
  color: #5578bb;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background 0.2s;
}

.modal-close:hover {
  background: rgba(100, 140, 255, 0.1);
}

.modal-body {
  padding: 24px;
}

.purchase-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 24px;
  padding: 16px;
  background: rgba(100, 140, 255, 0.05);
  border-radius: 12px;
}

.info-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.info-item .label {
  font-size: 12px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item .value {
  font-size: 18px;
  font-weight: 700;
  color: #1a1f3a;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #1a1f3a;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid rgba(100, 140, 255, 0.2);
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #7b4fff;
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 24px;
  border-top: 1px solid rgba(100, 140, 255, 0.2);
  justify-content: flex-end;
}

.modal-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.modal-btn--cancel {
  background: rgba(255, 255, 255, 0.8);
  color: #5578bb;
  border: 1px solid rgba(100, 140, 255, 0.2);
}

.modal-btn--cancel:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: translateY(-1px);
}

.modal-btn--primary {
  background: linear-gradient(135deg, #7b4fff 0%, #009efd 100%);
  color: #fff;
}

.modal-btn--primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(123, 79, 255, 0.4);
}

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

@media (max-width: 640px) {
  .modal-content {
    width: 95%;
    margin: 20px;
  }

  .purchase-info {
    flex-direction: column;
    gap: 16px;
  }

  .modal-footer {
    flex-direction: column;
  }

  .modal-btn {
    width: 100%;
  }
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
