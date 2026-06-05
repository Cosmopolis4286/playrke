<script setup lang="ts">
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

const emit = defineEmits<{ (e: 'select', roomId: string): void }>()

const isAvailable  = computed(() => props.room.status_code === 'ACTIVE')
const isInProgress = computed(() => props.room.status_code === 'IN_PROGRESS')
const isPublic     = computed(() => props.room.room_type === 'PUBLIC')
const playerCtns   = computed(() => props.room.players_room?.[0]?.ctns ?? 0)
const hasTime      = computed(() => props.countdown && !props.countdown.expired)
const animDelay    = computed(() => `${(props.index ?? 0) * 80}ms`)
const bingoRoomImg = '/img/games/bingo/estelar_fd.png'

const prizeFormatted = computed(() =>
  (props.room.prize_pool * props.exchangeRate).toFixed(2)
)
const cardPriceFormatted = computed(() => {
  if (props.room.card_price != null) return props.room.card_price.toFixed(2)
  return ((props.room.config?.card_price_usd ?? 0) * props.exchangeRate).toFixed(2)
})
const sym = computed(() => props.room.currency_symbol ?? props.currencySym)

const gameModeLabel = computed(() => {
  const m: Record<string, string> = { ONLINE: 'Online', OFFLINE: 'Presencial', MIXED: 'Mixto' }
  return m[props.room.game_mode ?? ''] ?? props.room.game_mode ?? ''
})

const timerStr = computed(() => {
  if (!hasTime.value || !props.countdown) return null
  const { days, hours, minutes, seconds } = props.countdown.time
  const ss = String(seconds).padStart(2, '0')
  const mm = String(minutes).padStart(2, '0')
  if (days > 0) return `${days}d ${String(hours).padStart(2,'0')}h ${mm}m`
  if (hours > 0) return `${String(hours).padStart(2,'0')}:${mm}:${ss}`
  return `${mm}:${ss}`
})

function handleClick() {
  if (!props.loading) emit('select', props.room.id)
}
</script>

<template>
  <div
    class="rc"
    :class="{
      'rc--active': isAvailable,
      'rc--live':   isInProgress,
      'rc--off':    !isAvailable && !isInProgress,
    }"
    :style="{ '--d': animDelay }"
    @click="handleClick"
  >
    <!-- Glow de fondo según estado -->
    <div class="rc__glow" />

    <!-- Acento superior -->
    <div class="rc__accent" />

    <div class="rc__inner">

      <!-- ── Thumbnail ── -->
      <div class="rc__thumb">
        <img :src="bingoRoomImg" alt="" class="rc__img" />
        <div class="rc__img-veil" />
        <!-- Tipo de sala -->
        <div class="rc__type-tag">
          <i class="fas" :class="isPublic ? 'fa-globe' : 'fa-lock'" />
          {{ isPublic ? 'Público' : 'Privado' }}
        </div>
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- Fila A: título + badge | timer -->
        <div class="rc__row rc__row--a">
          <div class="rc__title-block">
            <p class="rc__name">Bingo {{ isPublic ? 'Público' : 'Privado' }}</p>
            <p class="rc__mode">
              <i class="fas fa-gamepad" />
              {{ gameModeLabel }}
            </p>
          </div>

          <div class="rc__col-right">
            <span
              class="rc__badge"
              :class="isAvailable ? 'rc__badge--ok' : isInProgress ? 'rc__badge--live' : 'rc__badge--off'"
            >
              <span class="rc__badge-dot" />
              {{ isAvailable ? 'Disponible' : isInProgress ? 'En Juego' : 'Cerrada' }}
            </span>

            <div class="rc__timer" :class="{ 'rc__timer--live': !hasTime }">
              <template v-if="hasTime && timerStr">
                <i class="far fa-clock rc__timer-icon" />
                <span class="rc__timer-val">{{ timerStr }}</span>
              </template>
              <template v-else>
                <span class="rc__blink" />
                <span class="rc__live-txt">LIVE</span>
              </template>
            </div>
          </div>
        </div>

        <!-- Divisor con brillo -->
        <div class="rc__sep" />

        <!-- Fila B: premio + stats -->
        <div class="rc__row rc__row--b">

          <!-- Premio -->
          <div class="rc__prize">
            <span class="rc__prize-lbl">
              <i class="fas fa-trophy" />
              Premio
            </span>
            <div class="rc__prize-val">
              <span class="rc__prize-num">{{ prizeFormatted }}</span>
              <span class="rc__prize-sym">{{ sym }}</span>
            </div>
          </div>

          <!-- Stats verticales -->
          <div class="rc__stats">
            <div class="rc__stat">
              <div class="rc__stat-icon">
                <i class="fas fa-ticket-alt" />
              </div>
              <div class="rc__stat-info">
                <span class="rc__stat-val">{{ cardPriceFormatted }} {{ sym }}</span>
                <span class="rc__stat-lbl">c/cartón</span>
              </div>
            </div>

            <div class="rc__stat">
              <div class="rc__stat-icon">
                <i class="fas fa-users" />
              </div>
              <div class="rc__stat-info">
                <span class="rc__stat-val">{{ room.total_players }}</span>
                <span class="rc__stat-lbl">jugadores</span>
              </div>
            </div>

            <div v-if="playerCtns > 0" class="rc__stat rc__stat--mine">
              <div class="rc__stat-icon">
                <i class="fas fa-layer-group" />
              </div>
              <div class="rc__stat-info">
                <span class="rc__stat-val">{{ playerCtns }}</span>
                <span class="rc__stat-lbl">mis ctns</span>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

    <!-- Shimmer overlay al hover -->
    <div class="rc__shimmer" />
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Orbitron:wght@700;900&display=swap');

/* ══════════════════════════════════════
   BASE CARD
══════════════════════════════════════ */
.rc {
  --d: 0ms;
  --accent: #2af598;
  --accent2: #009efd;
  --glow-clr: rgba(42, 245, 152, 0.12);

  position: relative;
  width: 100%;
  border-radius: 18px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(145deg, #0d1b38 0%, #070f22 60%, #0a1528 100%);
  border: 1px solid rgba(255, 255, 255, 0.07);
  box-shadow:
    0 2px 0 rgba(255,255,255,0.04) inset,
    0 8px 32px rgba(0, 0, 0, 0.55);
  transition:
    transform .24s cubic-bezier(.22,.68,0,1.15),
    box-shadow .24s ease,
    border-color .24s ease;
  animation: rcIn .44s ease both;
  animation-delay: var(--d);
}

@keyframes rcIn {
  from { opacity: 0; transform: translateY(10px) scale(.98); }
  to   { opacity: 1; transform: translateY(0)   scale(1);    }
}

/* Estado live */
.rc--live {
  --accent: #ff4d6d;
  --accent2: #ff8c42;
  --glow-clr: rgba(255, 77, 109, 0.12);
}

/* Hover - solo ACTIVE */
.rc--active:hover {
  transform: translateY(-4px) scale(1.005);
  box-shadow:
    0 2px 0 rgba(255,255,255,0.06) inset,
    0 16px 48px rgba(0,0,0,.6),
    0 0 0 1px rgba(42,245,152,.22),
    0 0 28px rgba(42,245,152,.1);
  border-color: rgba(42, 245, 152, 0.22);
}

.rc--off {
  opacity: .3;
  cursor: default;
  filter: saturate(.15) brightness(.8);
}

/* ── Glow de fondo ── */
.rc__glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 80% 60% at 30% 120%, var(--glow-clr), transparent 70%);
  pointer-events: none;
  z-index: 0;
  transition: opacity .3s ease;
}
.rc--off .rc__glow { opacity: 0; }

/* ── Shimmer hover ── */
.rc__shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    105deg,
    transparent 30%,
    rgba(255,255,255,.025) 50%,
    transparent 70%
  );
  transform: translateX(-100%);
  pointer-events: none;
  z-index: 1;
  transition: transform 0s;
}
.rc--active:hover .rc__shimmer {
  transform: translateX(100%);
  transition: transform .55s ease;
}

/* ── Acento superior ── */
.rc__accent {
  position: relative;
  z-index: 2;
  height: 2.5px;
  width: 100%;
}
.rc--active .rc__accent {
  background: linear-gradient(90deg, #2af598 0%, #009efd 50%, transparent 90%);
  box-shadow: 0 0 12px rgba(42, 245, 152, .35);
}
.rc--live .rc__accent {
  background: linear-gradient(90deg, #ff4d6d 0%, #ff8c42 50%, transparent 90%);
  box-shadow: 0 0 12px rgba(255, 77, 109, .35);
}
.rc--off .rc__accent {
  background: rgba(255,255,255,.04);
}

/* ── Inner ── */
.rc__inner {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: stretch;
  min-height: 96px;
}

/* ══════════════════════════════════════
   THUMBNAIL
══════════════════════════════════════ */
.rc__thumb {
  position: relative;
  flex: 0 0 96px;
  overflow: hidden;
}
.rc__img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .5s cubic-bezier(.22,.68,0,1.2);
}
.rc--active:hover .rc__img { transform: scale(1.08); }

.rc__img-veil {
  position: absolute; inset: 0;
  background: linear-gradient(
    90deg,
    rgba(7,15,34,0) 0%,
    rgba(7,15,34,.5) 60%,
    rgba(7,15,34,.92) 100%
  );
}

/* Etiqueta tipo sala sobre la imagen */
.rc__type-tag {
  position: absolute;
  top: 8px; left: 6px;
  display: flex; align-items: center; gap: 3px;
  padding: 2px 6px;
  border-radius: 6px;
  font-size: 8px; font-weight: 700;
  letter-spacing: .5px;
  text-transform: uppercase;
  color: rgba(255,255,255,.7);
  background: rgba(0,0,0,.45);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255,255,255,.1);
}
.rc__type-tag i { font-size: 7px; }

/* ══════════════════════════════════════
   BODY
══════════════════════════════════════ */
.rc__body {
  flex: 1;
  min-width: 0;
  padding: 10px 14px 10px 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 6px;
}

/* ── Fila A ── */
.rc__row { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }

.rc__title-block { min-width: 0; }

.rc__name {
  font-family: 'Rajdhani', sans-serif;
  font-size: 13.5px; font-weight: 700;
  color: #d6e8ff;
  margin: 0 0 1px;
  line-height: 1.1;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  letter-spacing: .3px;
}
.rc__mode {
  font-size: 9px;
  color: #3a5070;
  margin: 0;
  display: flex; align-items: center; gap: 3px;
  letter-spacing: .2px;
}
.rc__mode i { font-size: 8px; }

.rc__col-right {
  display: flex; flex-direction: column;
  align-items: flex-end; gap: 5px;
  flex-shrink: 0;
}

/* ── Badge estado ── */
.rc__badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 2.5px 8px;
  border-radius: 20px;
  font-size: 8px; font-weight: 700;
  letter-spacing: .6px;
  text-transform: uppercase;
  white-space: nowrap;
}
.rc__badge-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}
.rc__badge--ok {
  background: rgba(42,245,152,.1);
  border: 1px solid rgba(42,245,152,.3);
  color: #2af598;
}
.rc__badge--ok .rc__badge-dot { animation: dp 2s infinite; }
.rc__badge--live {
  background: rgba(255,77,109,.1);
  border: 1px solid rgba(255,77,109,.3);
  color: #ff6e8a;
}
.rc__badge--live .rc__badge-dot { animation: dp 1.1s infinite; }
.rc__badge--off {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.07);
  color: #2a3d58;
}
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.15} }

/* ── Timer ── */
.rc__timer {
  display: inline-flex; align-items: center; gap: 4px;
  color: #7aacdc;
  white-space: nowrap;
}
.rc__timer-icon {
  font-size: 8px;
  opacity: .55;
}
.rc__timer-val {
  font-family: 'Orbitron', 'Anton', monospace;
  font-size: 11px; font-weight: 700;
  letter-spacing: .5px;
  color: #a8ccf0;
}
.rc__timer--live { color: #ff6e8a; }
.rc__live-txt {
  font-family: 'Orbitron', monospace;
  font-size: 9px; font-weight: 700;
  letter-spacing: 1px;
  color: #ff6e8a;
}

.rc__blink {
  display: inline-block; width: 5px; height: 5px; border-radius: 50%;
  background: currentColor;
  animation: bk 1s infinite;
  vertical-align: middle;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.1} }

/* ── Separador ── */
.rc__sep {
  height: 1px;
  background: linear-gradient(90deg,
    rgba(42,245,152,.12) 0%,
    rgba(80,140,220,.1) 50%,
    transparent 100%
  );
}
.rc--live .rc__sep {
  background: linear-gradient(90deg,
    rgba(255,77,109,.12) 0%,
    rgba(255,140,66,.08) 50%,
    transparent 100%
  );
}

/* ── Fila B ── */
.rc__row--b { align-items: center; }

/* Premio */
.rc__prize { display: flex; flex-direction: column; gap: 1px; }

.rc__prize-lbl {
  font-size: 7.5px;
  color: #253a58;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  display: flex; align-items: center; gap: 3px;
}
.rc__prize-lbl i { font-size: 7px; color: #c8992a; }

.rc__prize-val { display: flex; align-items: baseline; gap: 4px; }

.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 20px;
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, #ffe566 0%, #ffb31a 55%, #ff8c00 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 2px 8px rgba(255,160,0,.3));
  letter-spacing: -.5px;
}

.rc__prize-sym {
  font-family: 'Rajdhani', sans-serif;
  font-size: 11px; font-weight: 700;
  color: #f0a820;
  -webkit-text-fill-color: #f0a820;
  opacity: .9;
}

/* ── Stats ── */
.rc__stats {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-left: auto;
  padding-left: 12px;
  align-items: flex-end;
}

.rc__stat {
  display: flex;
  align-items: center;
  gap: 5px;
}

.rc__stat-icon {
  width: 18px; height: 18px;
  border-radius: 5px;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.07);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.rc__stat-icon i {
  font-size: 8px;
  color: #3a5878;
}

.rc__stat-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1;
}
.rc__stat-val {
  font-family: 'Rajdhani', sans-serif;
  font-size: 10px; font-weight: 700;
  color: #7aacdc;
  letter-spacing: .2px;
}
.rc__stat-lbl {
  font-size: 7.5px;
  color: #253a58;
  letter-spacing: .2px;
}

/* Mis cartones - highlight */
.rc__stat--mine .rc__stat-icon {
  background: rgba(130,80,220,.12);
  border-color: rgba(130,80,220,.2);
}
.rc__stat--mine .rc__stat-icon i { color: #9060d8; }
.rc__stat--mine .rc__stat-val { color: #a880f0; }
</style>
