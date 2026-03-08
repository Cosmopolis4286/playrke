<script setup lang="ts">
/**
 * BingoGameRoomCard
 *
 * Tarjeta premium individual de sala de juego de bingo.
 * Diseño glassmorphism oscuro con gradientes y animaciones sutiles.
 */
import { computed } from 'vue'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@/js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import type { BingoGameRoomDetail } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

const props = defineProps<{
  /** Datos de la sala */
  room: BingoGameRoom
  /** Tasa de cambio para mostrar el premio en moneda local */
  exchangeRate: number
  /** Símbolo de moneda (ej. "USD", "BS") */
  currencySym: string
  /** Countdown calculado por el composable padre */
  countdown: RoomCountdown | undefined
  /** Si esta sala tiene el panel de compra abierto */
  isPurchasing: boolean
  /** Detalle de la sala (cargado al abrir el panel) */
  roomDetail: BingoGameRoomDetail | null
  /** Número de cartones seleccionados */
  numCtns: number
  /** Total a pagar */
  totalAmount: number
  /** Etiqueta del tipo de premio */
  prizePoolLabel: string
  /** Estado de carga del fetch/compra */
  loading: boolean
}>()

// ---------------------------------------------------------------------------
// Emits
// ---------------------------------------------------------------------------

const emit = defineEmits<{
  (e: 'select', roomId: string): void
  (e: 'close'): void
  (e: 'increment'): void
  (e: 'decrement'): void
  (e: 'set-ctns', value: number): void
  (e: 'purchase'): void
}>()

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------

/** Si la sala está activa (status 2 = disponible para comprar) */
const isAvailable = computed(() => props.room.status === 2)

/** Etiqueta de estado visible */
const statusLabel = computed(() =>
  isAvailable.value ? 'Comprar Cartones' : 'En Juego',
)

/** Premio formateado con tasa de cambio */
const prizePoolFormatted = computed(() =>
  (props.room.prize_pool * props.exchangeRate).toFixed(2),
)

/** Precio del cartón formateado con tasa de cambio */
const cardPriceFormatted = computed(() => {
  const price = props.room.config?.card_price ?? 0
  return (price * props.exchangeRate).toFixed(2)
})

/** Cartones del jugador en esta sala */
const playerCtns = computed(() => props.room.players_room?.[0]?.ctns ?? 0)

/** Si el countdown todavía no expiró */
const hasTime = computed(() => props.countdown && !props.countdown.expired)

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function handleCardClick(): void {
  if (!isAvailable.value || props.loading) return
  emit('select', props.room.id)
}

function handleCtnsInput(event: Event): void {
  const val = parseInt((event.target as HTMLInputElement).value, 10)
  if (!isNaN(val)) emit('set-ctns', val)
}
</script>

<template>
  <div class="room-card-wrapper">

    <!-- ═══════════════════════════════════ TARJETA ══════════════════════════════════ -->
    <div
      class="room-card"
      :class="{
        'room-card--available': isAvailable,
        'room-card--unavailable': !isAvailable,
      }"
      @click="handleCardClick"
    >
      <!-- ── Glow de fondo animado ── -->
      <div class="room-card__glow" />

      <!-- ── Badge: mis cartones ── -->
      <div v-if="playerCtns > 0" class="room-card__ctns-badge" title="Mis Cartones">
        <i class="fas fa-ticket-alt" />
        +{{ playerCtns }}
      </div>

      <!-- ── Countdown pill ── -->
      <div class="room-card__timer" :class="{ 'room-card__timer--live': !hasTime }">
        <template v-if="hasTime && countdown">
          <div v-if="countdown.time.days > 0" class="timer__unit">
            <span class="timer__num">{{ countdown.time.days }}</span>
            <span class="timer__lbl">D</span>
          </div>
          <span v-if="countdown.time.days > 0" class="timer__sep">:</span>
          <div v-if="countdown.time.hours > 0" class="timer__unit">
            <span class="timer__num">{{ countdown.time.hours }}</span>
            <span class="timer__lbl">H</span>
          </div>
          <span v-if="countdown.time.hours > 0" class="timer__sep">:</span>
          <div class="timer__unit">
            <span class="timer__num">{{ String(countdown.time.minutes).padStart(2,'0') }}</span>
            <span class="timer__lbl">M</span>
          </div>
          <span class="timer__sep">:</span>
          <div class="timer__unit">
            <span class="timer__num">{{ String(countdown.time.seconds).padStart(2,'0') }}</span>
            <span class="timer__lbl">S</span>
          </div>
        </template>
        <template v-else>
          <i class="fas fa-circle-dot mr-1" style="font-size:9px; color:#ff4d4d;" />
          EN VIVO
        </template>
      </div>

      <!-- ── Imagen de sala ── -->
      <div class="room-card__image-wrap">
        <img :src="`/img/games/bingo/estelar_fd.png`" alt="Sala de Bingo" class="room-card__image" />
        <div class="room-card__image-overlay" />

        <!-- Modo de juego sobre la imagen -->
        <span class="room-card__mode-badge">
          <i class="fas fa-gamepad mr-1" />Cartón Lleno
        </span>
      </div>

      <!-- ── Cuerpo inferior ── -->
      <div class="room-card__body">

        <!-- Premio -->
        <div class="room-card__prize-row">
          <span class="room-card__prize-label">{{ prizePoolLabel }}</span>
          <div class="room-card__prize-amount">
            <span class="room-card__prize-num">{{ prizePoolFormatted }}</span>
            <span class="room-card__prize-sym">{{ currencySym }}</span>
          </div>
        </div>

        <!-- Divider -->
        <div class="room-card__divider" />

        <!-- Precio cartón + jugadores -->
        <div class="room-card__meta">
          <div class="room-card__meta-item">
            <i class="fas fa-ticket-alt" />
            <span>{{ cardPriceFormatted }} {{ currencySym }}</span>
          </div>
          <div class="room-card__meta-item">
            <i class="fas fa-users" />
            <span>{{ room.total_players }}</span>
          </div>
        </div>

        <!-- CTA / estado -->
        <div
          class="room-card__cta"
          :class="isAvailable ? 'room-card__cta--buy' : 'room-card__cta--live'"
        >
          <i :class="isAvailable ? 'fas fa-shopping-cart' : 'fas fa-play-circle'" class="mr-1" />
          {{ statusLabel }}
        </div>

      </div>
    </div>

    <!-- ═══════════════════════════════ PANEL DE COMPRA ═══════════════════════════════ -->
    <Transition name="panel-slide">
      <div
        v-if="isPurchasing && roomDetail"
        class="purchase-panel"
        @click.stop
      >
        <!-- Header del panel -->
        <div class="purchase-panel__header">
          <div class="purchase-panel__header-left">
            <i class="fas fa-ticket-alt mr-2" style="color:#7c9dff;" />
            <span>Comprar Cartones</span>
          </div>
          <button class="purchase-panel__close" aria-label="Cerrar" @click="emit('close')">
            <i class="fas fa-times" />
          </button>
        </div>

        <!-- Premio destacado -->
        <div class="purchase-panel__prize">
          <span class="purchase-panel__prize-label">{{ roomDetail.prizePoolType }}</span>
          <span class="purchase-panel__prize-amount">{{ roomDetail.prizePool }}</span>
          <span class="purchase-panel__prize-sym">{{ roomDetail.currency }}</span>
        </div>

        <!-- Selector cantidad -->
        <div class="purchase-panel__qty-section">
          <p class="purchase-panel__qty-label">Cantidad de cartones</p>
          <div class="purchase-panel__qty-row">
            <button
              class="qty-btn"
              :disabled="numCtns <= 1"
              @click="emit('decrement')"
            >
              <i class="fas fa-minus" />
            </button>
            <input
              type="number"
              class="qty-input"
              :value="numCtns"
              min="1"
              max="30"
              step="1"
              @input="handleCtnsInput"
            />
            <button
              class="qty-btn"
              :disabled="numCtns >= 30"
              @click="emit('increment')"
            >
              <i class="fas fa-plus" />
            </button>
          </div>
        </div>

        <!-- Total -->
        <div class="purchase-panel__total">
          <span class="purchase-panel__total-label">Total a pagar</span>
          <span class="purchase-panel__total-amount">
            {{ totalAmount.toFixed(2) }}
            <small>{{ roomDetail.currency }}</small>
          </span>
        </div>

        <!-- Botón confirmar -->
        <button
          class="purchase-panel__buy-btn"
          :disabled="loading"
          @click="emit('purchase')"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status" />
          <template v-else>
            <i class="fas fa-lock mr-2" />
          </template>
          {{ loading ? 'Procesando…' : 'Confirmar Compra' }}
        </button>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* ─────────────────────────────────────────────────────────
   WRAPPER
───────────────────────────────────────────────────────── */
.room-card-wrapper {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 8px 12px;
}

/* ─────────────────────────────────────────────────────────
   CARD BASE
───────────────────────────────────────────────────────── */
.room-card {
  position: relative;
  width: 230px;
  border-radius: 20px;
  overflow: visible;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease;

  background: linear-gradient(160deg, #0f1b3d 0%, #0a1228 100%);
  border: 1px solid rgba(100, 140, 255, 0.18);
  box-shadow:
    0 8px 32px rgba(0, 0, 0, 0.55),
    0 0 0 1px rgba(80, 120, 255, 0.08) inset;
}

.room-card--available:hover {
  transform: translateY(-6px);
  box-shadow:
    0 16px 48px rgba(0, 80, 255, 0.35),
    0 0 0 1px rgba(80, 140, 255, 0.25) inset;
}

.room-card--unavailable {
  opacity: 0.72;
  cursor: default;
  filter: saturate(0.6);
}

/* ── Glow animado ── */
.room-card__glow {
  position: absolute;
  inset: -2px;
  border-radius: 22px;
  background: linear-gradient(135deg, #2af598, #009efd, #7b4fff);
  z-index: -1;
  opacity: 0;
  transition: opacity 0.3s ease;
  filter: blur(6px);
}
.room-card--available:hover .room-card__glow {
  opacity: 0.5;
}

/* ─────────────────────────────────────────────────────────
   BADGE MIS CARTONES
───────────────────────────────────────────────────────── */
.room-card__ctns-badge {
  position: absolute;
  top: -10px;
  right: -10px;
  z-index: 10;
  background: linear-gradient(135deg, #0049cf, #0099ff);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 9px;
  border-radius: 20px;
  box-shadow: 0 2px 10px rgba(0, 80, 255, 0.5);
  display: flex;
  align-items: center;
  gap: 4px;
  letter-spacing: 0.3px;
}

/* ─────────────────────────────────────────────────────────
   TIMER PILL
───────────────────────────────────────────────────────── */
.room-card__timer {
  position: absolute;
  top: -18px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  background: rgba(10, 14, 35, 0.92);
  border: 1px solid rgba(100, 140, 255, 0.3);
  backdrop-filter: blur(8px);
  color: #e0e8ff;
  border-radius: 30px;
  padding: 5px 14px;
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: 'Anton', sans-serif;
  font-size: 13px;
  letter-spacing: 0.5px;
  white-space: nowrap;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
}

.room-card__timer--live {
  border-color: rgba(255, 70, 70, 0.5);
  color: #ff8080;
  animation: pulse-live 1.8s ease-in-out infinite;
}

@keyframes pulse-live {
  0%, 100% { box-shadow: 0 4px 16px rgba(0,0,0,0.5); }
  50%       { box-shadow: 0 4px 20px rgba(255, 60, 60, 0.35); }
}

.timer__unit {
  display: flex;
  align-items: baseline;
  gap: 1px;
}
.timer__num { font-size: 14px; font-weight: 700; }
.timer__lbl { font-size: 8px; color: #7c9dff; text-transform: uppercase; }
.timer__sep { color: #4a6aaa; font-size: 13px; line-height: 1; margin: 0 1px; }

/* ─────────────────────────────────────────────────────────
   IMAGEN
───────────────────────────────────────────────────────── */
.room-card__image-wrap {
  position: relative;
  border-radius: 20px 20px 0 0;
  overflow: hidden;
  height: 140px;
}

.room-card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.room-card__image-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg,
    rgba(5, 10, 30, 0.1) 0%,
    rgba(5, 10, 30, 0.7) 100%
  );
}

.room-card__mode-badge {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(10, 18, 50, 0.82);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(100, 140, 255, 0.25);
  color: #c8d8ff;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 12px;
  border-radius: 20px;
  white-space: nowrap;
  letter-spacing: 0.4px;
}

/* ─────────────────────────────────────────────────────────
   BODY
───────────────────────────────────────────────────────── */
.room-card__body {
  padding: 14px 16px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

/* Premio */
.room-card__prize-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  width: 100%;
}
.room-card__prize-label {
  font-size: 11px;
  color: #7c9dff;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}
.room-card__prize-amount {
  display: flex;
  align-items: baseline;
  gap: 5px;
}
.room-card__prize-num {
  font-family: 'Anton', sans-serif;
  font-size: 32px;
  line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 2px 6px rgba(255, 180, 0, 0.4));
}
.room-card__prize-sym {
  font-size: 13px;
  font-weight: 700;
  color: #ffd060;
  -webkit-text-fill-color: #ffd060;
}

/* Divider */
.room-card__divider {
  width: 100%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100,140,255,0.2), transparent);
}

/* Meta info */
.room-card__meta {
  display: flex;
  justify-content: space-between;
  width: 100%;
  padding: 0 4px;
}
.room-card__meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: #8aa4d0;
}
.room-card__meta-item i {
  color: #5578bb;
  font-size: 11px;
}

/* CTA */
.room-card__cta {
  width: calc(100% + 32px);
  margin: 4px -16px -20px;
  padding: 10px 0;
  text-align: center;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  border-radius: 0 0 20px 20px;
}
.room-card__cta--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #051830;
}
.room-card__cta--live {
  background: linear-gradient(90deg, #ff416c, #ff4b2b);
  color: #fff;
}

/* ─────────────────────────────────────────────────────────
   PANEL DE COMPRA
───────────────────────────────────────────────────────── */
.purchase-panel {
  width: 230px;
  margin-top: 12px;
  border-radius: 18px;
  overflow: hidden;
  background: rgba(10, 16, 40, 0.96);
  border: 1px solid rgba(100, 140, 255, 0.22);
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.7),
    0 0 0 1px rgba(80, 120, 255, 0.08) inset;
  backdrop-filter: blur(12px);
}

/* Header */
.purchase-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: linear-gradient(90deg, rgba(0,74,255,0.18), rgba(0,158,253,0.12));
  border-bottom: 1px solid rgba(100, 140, 255, 0.12);
  font-size: 13px;
  font-weight: 600;
  color: #c8d8ff;
}
.purchase-panel__header-left {
  display: flex;
  align-items: center;
}
.purchase-panel__close {
  background: rgba(255,255,255,0.06);
  border: none;
  color: #7c9dff;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  transition: background 0.2s;
}
.purchase-panel__close:hover {
  background: rgba(255,255,255,0.14);
  color: #fff;
}

/* Premio en panel */
.purchase-panel__prize {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 6px;
  padding: 14px 16px 10px;
}
.purchase-panel__prize-label {
  font-size: 11px;
  color: #7c9dff;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.purchase-panel__prize-amount {
  font-family: 'Anton', sans-serif;
  font-size: 26px;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.purchase-panel__prize-sym {
  font-size: 12px;
  font-weight: 700;
  color: #ffd060;
}

/* Cantidad */
.purchase-panel__qty-section {
  padding: 0 16px 10px;
}
.purchase-panel__qty-label {
  font-size: 11px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 8px;
  text-align: center;
}
.purchase-panel__qty-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.qty-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1.5px solid rgba(100, 140, 255, 0.3);
  background: rgba(255,255,255,0.04);
  color: #7c9dff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: all 0.2s;
}
.qty-btn:not(:disabled):hover {
  background: rgba(100, 140, 255, 0.15);
  border-color: rgba(100, 140, 255, 0.6);
  color: #fff;
}
.qty-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
.qty-input {
  width: 56px;
  background: rgba(255,255,255,0.05);
  border: 1.5px solid rgba(100, 140, 255, 0.2);
  border-radius: 10px;
  color: #e0e8ff;
  font-size: 22px;
  font-family: 'Anton', sans-serif;
  text-align: center;
  padding: 4px 0;
  outline: none;
  -moz-appearance: textfield;
  -webkit-appearance: none;
  appearance: none;
  transition: border-color 0.2s;
}
.qty-input:focus { border-color: rgba(100, 140, 255, 0.55); }
.qty-input::-webkit-inner-spin-button,
.qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

/* Total */
.purchase-panel__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: rgba(0, 74, 255, 0.07);
  border-top: 1px solid rgba(100,140,255,0.1);
  border-bottom: 1px solid rgba(100,140,255,0.1);
}
.purchase-panel__total-label {
  font-size: 11px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}
.purchase-panel__total-amount {
  font-family: 'Anton', sans-serif;
  font-size: 20px;
  color: #7affba;
  display: flex;
  align-items: baseline;
  gap: 5px;
}
.purchase-panel__total-amount small {
  font-family: inherit;
  font-size: 12px;
  color: #4ea87a;
}

/* Botón comprar */
.purchase-panel__buy-btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 0 0 18px 18px;
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #051830;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: filter 0.2s, opacity 0.2s;
}
.purchase-panel__buy-btn:not(:disabled):hover {
  filter: brightness(1.1);
}
.purchase-panel__buy-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ─────────────────────────────────────────────────────────
   TRANSICIONES
───────────────────────────────────────────────────────── */
.panel-slide-enter-active,
.panel-slide-leave-active {
  transition: opacity 0.28s ease, transform 0.28s ease;
}
.panel-slide-enter-from,
.panel-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.97);
}
</style>
