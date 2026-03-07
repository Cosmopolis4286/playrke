<script setup lang="ts">
/**
 * BingoGameRoomCard
 *
 * Tarjeta individual de sala de juego de bingo.
 * Muestra:
 *   - Countdown al inicio
 *   - Premio (bote o fijo)
 *   - Precio del cartón
 *   - Estado (disponible / en juego)
 *   - Cartones del jugador actual
 *   - Panel de compra inline
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
  /** El usuario hizo clic en la tarjeta para ver el panel de compra */
  (e: 'select', roomId: string): void
  /** El usuario cerró el panel de compra */
  (e: 'close'): void
  /** El usuario incrementó el número de cartones */
  (e: 'increment'): void
  /** El usuario decrementó el número de cartones */
  (e: 'decrement'): void
  /** El usuario escribió directamente un número de cartones */
  (e: 'set-ctns', value: number): void
  /** El usuario confirmó la compra */
  (e: 'purchase'): void
}>()

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------

/** Si la sala está activa (status 2 = disponible para comprar) */
const isAvailable = computed(() => props.room.status === 2)

/** Etiqueta de estado visible */
const statusLabel = computed(() =>
  isAvailable.value ? 'Comprar cartones' : 'No disponible',
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
  <div class="d-flex justify-content-center py-4 mb-1">
    <!-- ─── Tarjeta de sala ─── -->
    <div
      class="card-game-room"
      :class="{ 'card-game-room--unavailable': !isAvailable }"
      :data-status="room.status"
      @click="handleCardClick"
    >
      <!-- Countdown / En Juego -->
      <div class="card-game-room__time">
        <template v-if="hasTime && countdown">
          <div class="card-game-room__time-column cnt-days" v-if="countdown.time.days > 0">
            <span class="column-datetime__count">{{ countdown.time.days }}</span>
            <span class="column-datetime__text">DÍAS</span>
          </div>
          <div class="card-game-room__time-column cnt-hours" v-if="countdown.time.hours > 0">
            <span class="column-datetime__count">{{ countdown.time.hours }}</span>
            <span class="column-datetime__text">HRS</span>
          </div>
          <div class="card-game-room__time-column cnt-minutes" v-if="countdown.time.minutes > 0">
            <span class="column-datetime__count">{{ countdown.time.minutes }}</span>
            <span class="column-datetime__text">MIN</span>
          </div>
          <div class="card-game-room__time-column cnt-seconds">
            <span class="column-datetime__count">{{ countdown.time.seconds }}</span>
            <span class="column-datetime__text">SEG</span>
          </div>
        </template>
        <template v-else>
          <span class="start-time__text">EN JUEGO</span>
        </template>
      </div>

      <!-- Modo de juego -->
      <span class="card-game-room__game-mode">Modo: Cartón Lleno</span>

      <!-- Cartones del jugador -->
      <div
        v-if="playerCtns > 0"
        class="card-game-room__number-of-ctns"
        title="Mis Cartones"
      >
        +{{ playerCtns }}
      </div>

      <!-- Imagen de sala -->
      <img
        class="card-game-room__room-image"
        src="/img/bg/estelar_fd.png"
        alt="Sala de Bingo"
      />

      <!-- Prize pool + precio + estado -->
      <div class="container-card-game-room__prize_pool">
        <span class="card-game-room__prize_pool-text">
          {{ prizePoolLabel }}:
        </span>

        <div class="card-game-room__prize-pool-amount">
          <h3>
            <span>{{ prizePoolFormatted }}</span>
            <small>{{ currencySym }}</small>
          </h3>
        </div>

        <span class="card-game-room__ctn-price">
          Cartón: {{ cardPriceFormatted }} {{ currencySym }}
        </span>

        <span class="card-game-room__status">
          {{ statusLabel }}
        </span>
      </div>
    </div>

    <!-- ─── Panel de compra (inline) ─── -->
    <Transition name="fade">
      <div
        v-if="isPurchasing && roomDetail"
        class="container-purchase-ctns"
        @click.stop
      >
        <!-- Header -->
        <div class="purchase-card__header">
          <span class="purchase-card_currency-label">{{ roomDetail.currency }}</span>
          <button
            class="btn-container-purchase-close"
            aria-label="Cerrar"
            @click="emit('close')"
          >
            <i class="fas fa-times" />
          </button>
        </div>

        <!-- Premio -->
        <div class="purchase-card__prize">
          <span class="purchase-card_currency-text">{{ roomDetail.prizePoolType }}</span>
          <strong id="prize-pool-amount">{{ roomDetail.prizePool }}</strong>
          <span>{{ roomDetail.currency }}</span>
        </div>

        <!-- Cantidad de cartones -->
        <div class="purchase-card__quantity">
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="numCtns <= 1"
            @click="emit('decrement')"
          >
            <i class="fas fa-minus" />
          </button>

          <input
            id="num-ctns"
            type="number"
            class="form-control form-control-sm text-center"
            :value="numCtns"
            min="1"
            max="30"
            step="1"
            @input="handleCtnsInput"
          />

          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="numCtns >= 30"
            @click="emit('increment')"
          >
            <i class="fas fa-plus" />
          </button>
        </div>

        <!-- Total -->
        <div class="purchase-card__total">
          Total:
          <strong id="total-amount">{{ totalAmount.toFixed(2) }}</strong>
          {{ roomDetail.currency }}
        </div>

        <!-- Botón comprar -->
        <button
          id="btn-purchase-ctns"
          class="btn btn-primary btn-sm btn-block mt-2"
          :disabled="loading"
          @click="emit('purchase')"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm mr-1" role="status" />
          {{ loading ? 'Procesando…' : 'Comprar' }}
        </button>
      </div>
    </Transition>
  </div>
</template>
