<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'
import type { RoomCountdown } from '@/js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'
import type { BingoGameRoomDetail } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

const props = defineProps<{
  room: BingoGameRoom
  exchangeRate: number
  currencySym: string
  countdown: RoomCountdown | undefined
  isPurchasing: boolean
  roomDetail: BingoGameRoomDetail | null
  numCtns: number
  totalAmount: number
  prizePoolLabel: string
  loading: boolean
  index?: number
}>()

const emit = defineEmits<{
  (e: 'select', roomId: string): void
  (e: 'close'): void
  (e: 'increment'): void
  (e: 'decrement'): void
  (e: 'set-ctns', value: number): void
  (e: 'purchase'): void
}>()

const page = usePage()

const isAvailable = computed(() => props.room.status_code === 'ACTIVE')
const isInProgress = computed(() => props.room.status_code === 'IN_PROGRESS')

const gameModeLabel = computed(() => {
  const map: Record<string, string> = { ONLINE: 'Online', OFFLINE: 'Presencial', MIXED: 'Mixto' }
  return map[props.room.game_mode ?? ''] ?? props.room.game_mode ?? 'Online'
})

const roomTypeLabel = computed(() => {
  const map: Record<string, string> = { PUBLIC: 'Pública', PRIVATE: 'Privada' }
  return map[props.room.room_type ?? ''] ?? props.room.room_type ?? ''
})

const isPublic = computed(() => props.room.room_type === 'PUBLIC')

const prizePoolFormatted = computed(() =>
  (props.room.prize_pool * props.exchangeRate).toFixed(2)
)

const cardPriceFormatted = computed(() => {
  if (props.room.card_price != null) return props.room.card_price.toFixed(2)
  const price = props.room.config?.card_price_usd ?? 0
  return (price * props.exchangeRate).toFixed(2)
})

const effectiveCurrencySym = computed(() => props.room.currency_symbol ?? props.currencySym)

const playerCtns = computed(() => props.room.players_room?.[0]?.ctns ?? 0)

const hasTime = computed(() => props.countdown && !props.countdown.expired)

const animDelay = computed(() => `${(props.index ?? 0) * 80}ms`)

function handleCardClick(): void {
  if (props.loading) return
  emit('select', props.room.id)
}

function handleCtnsInput(event: Event): void {
  const val = parseInt((event.target as HTMLInputElement).value, 10)
  if (!isNaN(val)) emit('set-ctns', val)
}
</script>

<template>
  <div
    class="room-card-wrapper"
    :style="{ '--anim-delay': animDelay }"
  >
    <!-- ══ TARJETA ══ -->
    <div
      class="room-card"
      :class="{
        'room-card--available':   isAvailable,
        'room-card--in-progress': isInProgress && playerCtns === 0,
        'room-card--enter-game':  isInProgress && playerCtns > 0,
        'room-card--unavailable': !isAvailable && !isInProgress,
      }"
      @click="handleCardClick"
    >
      <!-- Glow animado -->
      <div class="room-card__glow" />

      <!-- Badge mis cartones -->
      <div v-if="playerCtns > 0" class="room-card__ctns-badge">
        <i class="fas fa-ticket-alt" />
        {{ playerCtns }}
      </div>

      <!-- Badge tipo sala -->
      <div
        class="room-card__type-badge"
        :class="isPublic ? 'room-card__type-badge--public' : 'room-card__type-badge--private'"
      >
        <i :class="isPublic ? 'fas fa-globe' : 'fas fa-lock'" />
        {{ roomTypeLabel }}
      </div>

      <!-- Timer -->
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
          <span class="live-dot" />
          EN VIVO
        </template>
      </div>

      <!-- Imagen -->
      <div class="room-card__image-wrap">
        <img src="/img/games/bingo/estelar_fd.png" alt="Sala de Bingo" class="room-card__image" />
        <div class="room-card__image-overlay" />
        <span class="room-card__mode-badge">
          <i class="fas fa-gamepad mr-1" />{{ gameModeLabel }}
        </span>
      </div>

      <!-- Cuerpo -->
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

        <!-- Meta -->
        <div class="room-card__meta">
          <div class="room-card__meta-item">
            <i class="fas fa-ticket-alt" />
            <span>{{ cardPriceFormatted }} <small>{{ effectiveCurrencySym }}</small></span>
          </div>
          <div class="room-card__meta-item">
            <i class="fas fa-users" />
            <span>{{ room.total_players }}</span>
          </div>
        </div>

        <!-- CTA -->
        <div
          class="room-card__cta"
          :class="{
            'room-card__cta--buy':   isAvailable,
            'room-card__cta--live':  isInProgress && playerCtns === 0,
            'room-card__cta--enter': isInProgress && playerCtns > 0,
            'room-card__cta--off':   !isAvailable && !isInProgress,
          }"
        >
          <i
            :class="{
              'fas fa-eye mr-1':           true,
              'fa-eye':                    !isAvailable || (isInProgress && playerCtns === 0),
              'fa-shopping-cart':           isAvailable,
              'fa-sign-in-alt':            isInProgress && playerCtns > 0,
            }"
            class="mr-1"
          />
          <template v-if="isAvailable">Ver sala</template>
          <template v-else-if="isInProgress && playerCtns > 0">Entrar a Sala</template>
          <template v-else-if="isInProgress">En Juego</template>
          <template v-else>No disponible</template>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Wrapper ── */
.room-card-wrapper {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 8px 12px;
  animation: fadeInUp 0.55s ease both;
  animation-delay: var(--anim-delay, 0ms);
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0)    scale(1);    }
}

/* ── Card base ── */
.room-card {
  position: relative;
  width: 260px;
  border-radius: 22px;
  overflow: visible;
  cursor: pointer;
  transition: transform 0.28s cubic-bezier(.22,.68,0,1.2), box-shadow 0.28s ease;
  background: linear-gradient(160deg, #0f1b3d 0%, #090f25 100%);
  border: 1px solid rgba(100,140,255,0.18);
  box-shadow:
    0 8px 32px rgba(0,0,0,0.6),
    0 0 0 1px rgba(80,120,255,0.07) inset;
}

/* ── Estados ── */
.room-card--available:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow:
    0 24px 60px rgba(0,80,255,0.4),
    0 0 0 1px rgba(80,140,255,0.3) inset,
    0 0 40px rgba(42,245,152,0.1);
}

.room-card--unavailable {
  opacity: 0.6; cursor: default; filter: saturate(0.5) brightness(0.8);
}

.room-card--in-progress {
  opacity: 0.8; cursor: pointer; filter: saturate(0.75);
}
.room-card--in-progress:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(255,65,108,0.3), 0 0 0 1px rgba(255,65,108,0.2) inset;
}

.room-card--enter-game {
  border: 2px solid #00ff88 !important;
  box-shadow:
    0 8px 32px rgba(0,255,136,0.4),
    0 0 24px rgba(0,255,136,0.25),
    0 0 0 2px rgba(0,255,136,0.15) !important;
  animation: cardPulse 2.5s ease-in-out infinite;
  cursor: pointer !important;
  opacity: 1 !important;
  filter: saturate(1.2) !important;
}

.room-card--enter-game:hover {
  animation-play-state: paused !important;
  transform: translateY(-8px) scale(1.02) !important;
  box-shadow:
    0 20px 55px rgba(0,255,136,0.55),
    0 0 0 2px rgba(0,255,136,0.3) !important;
}

@keyframes cardPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 8px 32px rgba(0,255,136,0.4), 0 0 24px rgba(0,255,136,0.25);
  }
  50% {
    transform: scale(1.018);
    box-shadow: 0 14px 44px rgba(0,255,136,0.6), 0 0 36px rgba(0,255,136,0.4);
  }
}

/* ── Glow ── */
.room-card__glow {
  position: absolute; inset: -2px; border-radius: 24px;
  background: linear-gradient(135deg, #2af598, #009efd, #7b4fff);
  z-index: -1; opacity: 0;
  transition: opacity 0.3s ease;
  filter: blur(8px);
}
.room-card--available:hover .room-card__glow { opacity: 0.55; }

/* ── Badge cartones ── */
.room-card__ctns-badge {
  position: absolute; top: -12px; right: -12px; z-index: 10;
  background: linear-gradient(135deg, #0049cf, #0099ff);
  color: #fff; font-size: 11px; font-weight: 700;
  padding: 5px 10px; border-radius: 20px;
  box-shadow: 0 3px 12px rgba(0,80,255,0.55);
  display: flex; align-items: center; gap: 4px;
  border: 1.5px solid rgba(255,255,255,0.2);
}

/* ── Badge tipo sala ── */
.room-card__type-badge {
  position: absolute; top: -12px; left: -12px; z-index: 10;
  font-size: 10px; font-weight: 700; letter-spacing: 0.4px;
  padding: 5px 10px; border-radius: 20px;
  display: flex; align-items: center; gap: 4px;
  border: 1.5px solid transparent;
}
.room-card__type-badge--public {
  background: rgba(42,245,152,0.18);
  border-color: rgba(42,245,152,0.4);
  color: #2af598;
  box-shadow: 0 2px 10px rgba(42,245,152,0.25);
}
.room-card__type-badge--private {
  background: rgba(255,180,0,0.18);
  border-color: rgba(255,180,0,0.4);
  color: #ffb800;
  box-shadow: 0 2px 10px rgba(255,180,0,0.2);
}

/* ── Timer ── */
.room-card__timer {
  position: absolute; top: -20px; left: 50%; transform: translateX(-50%);
  z-index: 10;
  background: rgba(8, 12, 32, 0.94);
  border: 1px solid rgba(100,140,255,0.35);
  backdrop-filter: blur(8px);
  color: #d0deff;
  border-radius: 30px; padding: 5px 14px;
  display: flex; align-items: center; gap: 4px;
  font-family: 'Anton', sans-serif; font-size: 13px;
  letter-spacing: 0.5px; white-space: nowrap;
  box-shadow: 0 4px 16px rgba(0,0,0,0.55);
}
.room-card__timer--live {
  border-color: rgba(255,70,70,0.55);
  color: #ff8080;
  animation: pulse-live 1.8s ease-in-out infinite;
}
@keyframes pulse-live {
  0%, 100% { box-shadow: 0 4px 16px rgba(0,0,0,0.5); }
  50%       { box-shadow: 0 4px 20px rgba(255,60,60,0.4); }
}

.live-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #ff4d4d;
  box-shadow: 0 0 5px #ff4d4d;
  animation: blink 1s ease-in-out infinite;
}
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

.timer__unit { display: flex; align-items: baseline; gap: 1px; }
.timer__num  { font-size: 14px; font-weight: 700; }
.timer__lbl  { font-size: 8px; color: #7c9dff; text-transform: uppercase; }
.timer__sep  { color: #4a6aaa; font-size: 13px; line-height: 1; margin: 0 1px; }

/* ── Imagen ── */
.room-card__image-wrap {
  position: relative;
  border-radius: 22px 22px 0 0;
  overflow: hidden;
  height: 160px;
}
.room-card__image {
  width: 100%; height: 100%; object-fit: cover; display: block;
  transition: transform 0.4s ease;
}
.room-card--available:hover .room-card__image { transform: scale(1.06); }

.room-card__image-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(5,10,30,0.1) 0%, rgba(5,10,30,0.72) 100%);
}

.room-card__mode-badge {
  position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
  background: rgba(8,16,50,0.85);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(100,140,255,0.28);
  color: #c8d8ff;
  font-size: 11px; font-weight: 600;
  padding: 4px 14px; border-radius: 20px;
  white-space: nowrap; letter-spacing: 0.5px;
}

/* ── Body ── */
.room-card__body {
  padding: 16px 18px 22px;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
}

/* Premio */
.room-card__prize-row {
  display: flex; flex-direction: column; align-items: center; gap: 2px; width: 100%;
}
.room-card__prize-label {
  font-size: 10px; color: #7c9dff;
  text-transform: uppercase; letter-spacing: 1.2px; font-weight: 600;
}
.room-card__prize-amount {
  display: flex; align-items: baseline; gap: 6px;
}
.room-card__prize-num {
  font-family: 'Anton', sans-serif; font-size: 36px; line-height: 1;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 3px 8px rgba(255,180,0,0.45));
}
.room-card__prize-sym {
  font-size: 14px; font-weight: 700; color: #ffd060; -webkit-text-fill-color: #ffd060;
}

/* Divider */
.room-card__divider {
  width: 100%; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100,140,255,0.22), transparent);
}

/* Meta */
.room-card__meta {
  display: flex; justify-content: space-between; width: 100%; padding: 0 4px;
}
.room-card__meta-item {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: #8aa4d0;
}
.room-card__meta-item i { color: #5578bb; font-size: 11px; }
.room-card__meta-item small { font-size: 10px; color: #5578bb; }

/* CTA */
.room-card__cta {
  width: calc(100% + 36px); margin: 4px -18px -22px;
  padding: 13px 0;
  text-align: center;
  font-size: 12px; font-weight: 800;
  letter-spacing: 1.2px; text-transform: uppercase;
  border-radius: 0 0 22px 22px;
  position: relative; overflow: hidden;
  transition: filter 0.2s;
}
/* Sweep shimmer */
.room-card__cta::after {
  content: '';
  position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
  transform: skewX(-20deg);
  transition: left 0.5s ease;
}
.room-card--available:hover .room-card__cta::after { left: 150%; }

.room-card__cta--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #051830;
}
.room-card__cta--live {
  background: linear-gradient(90deg, #ff416c, #ff4b2b);
  color: #fff;
}
.room-card__cta--enter {
  background: linear-gradient(90deg, #00ff88, #00d2ff, #3a7bd5, #00d2ff, #00ff88);
  background-size: 300% 100%;
  color: #002a1a;
  font-weight: 900;
  animation: gradientShift 2.2s ease-in-out infinite;
}
.room-card__cta--off {
  background: rgba(255,255,255,0.06);
  color: #3a4e6a;
}

@keyframes gradientShift {
  0%   { background-position: 0%   50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0%   50%; }
}
</style>
