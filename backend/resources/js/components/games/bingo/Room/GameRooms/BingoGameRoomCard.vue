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
const animDelay    = computed(() => `${(props.index ?? 0) * 70}ms`)
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

const roundsCount = computed(() => props.room.rounds_count ?? 1)

const timerStr = computed(() => {
  if (!hasTime.value || !props.countdown) return null
  const { days, hours, minutes, seconds } = props.countdown.time
  const ss = String(seconds).padStart(2, '0')
  const mm = String(minutes).padStart(2, '0')
  if (days > 0) return `${days}d ${String(hours).padStart(2, '0')}h ${mm}m`
  if (hours > 0) return `${String(hours).padStart(2, '0')}:${mm}:${ss}`
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
    <div class="rc__glow" />
    <div class="rc__shimmer" />

    <div class="rc__inner">

      <!-- ── Thumbnail + badge encima ── -->
      <div class="rc__thumb">
        <img :src="bingoRoomImg" alt="" class="rc__img" />
        <div class="rc__img-veil" />

        <!-- Badge de estado: top-left sobre la imagen -->
        <div
          class="rc__status-badge"
          :class="isAvailable ? 'rc__status-badge--ok' : isInProgress ? 'rc__status-badge--live' : 'rc__status-badge--off'"
        >
          <span class="rc__status-dot" />
          {{ isAvailable ? 'ACTIVA' : isInProgress ? 'EN JUEGO' : 'CERRADA' }}
        </div>

        <!-- Acento lateral izquierdo -->
        <div class="rc__side-accent" />

        <!-- Cartones del jugador: bottom-center sobre la imagen -->
        <span v-if="playerCtns > 0" class="rc__ctns-badge">
          <i class="fas fa-layer-group" />{{ playerCtns }}
        </span>
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- Fila 1: badges tipo+rondas | precio · timer -->
        <div class="rc__top">
          <div class="rc__badges">
            <span class="rc__tag rc__tag--rounds">
              <i class="fas fa-rotate" />{{ roundsCount }} Rondas
            </span>
          </div>

          <div class="rc__top-right">
            <span class="rc__pill rc__pill--price rc__pill--sm">
              <i class="fas fa-ticket-alt" />{{ cardPriceFormatted }} {{ sym }}
            </span>
            <div class="rc__timer" :class="{ 'rc__timer--live': !hasTime }">
              <template v-if="hasTime && timerStr">
                <i class="far fa-clock" /><b>{{ timerStr }}</b>
              </template>
              <template v-else>
                <span class="rc__blink" /><b>LIVE</b>
              </template>
            </div>
          </div>
        </div>

        <!-- Fila 2: premio + jugadores/míos -->
        <div class="rc__bottom">
          <div class="rc__prize">
            <i class="fas fa-trophy rc__trophy" />
            <span class="rc__prize-num">{{ prizeFormatted }}</span>
            <span class="rc__prize-sym">{{ sym }}</span>
          </div>

          <div class="rc__pills">
            <span class="rc__pill">
              <i class="fas fa-users" />{{ room.total_players }}
            </span>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Orbitron:wght@800;900&display=swap');

/* ══════════════════════
   BASE
══════════════════════ */
.rc {
  --d: 0ms;
  --accent: #2af598;
  --glow-clr: rgba(42,245,152,.12);

  position: relative;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(140deg, #0f1e3a 0%, #070d1c 100%);
  border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 4px 20px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.05) inset;
  transition: transform .2s cubic-bezier(.22,.68,0,1.2),
              box-shadow .2s ease, border-color .2s ease;
  animation: rcIn .38s ease both;
  animation-delay: var(--d);
}
@keyframes rcIn {
  from { opacity:0; transform:translateY(8px) scale(.98); }
  to   { opacity:1; transform:none; }
}

.rc--live   { --accent: #ff4d6d; --glow-clr: rgba(255,77,109,.12); }

.rc--active:hover {
  transform: translateY(-3px) scale(1.003);
  border-color: rgba(42,245,152,.2);
  box-shadow: 0 10px 36px rgba(0,0,0,.6),
              0 0 0 1px rgba(42,245,152,.16),
              0 0 18px rgba(42,245,152,.08);
}
.rc--live:hover {
  transform: translateY(-2px);
  border-color: rgba(255,77,109,.2);
  box-shadow: 0 10px 32px rgba(0,0,0,.6), 0 0 0 1px rgba(255,77,109,.16);
}
.rc--off {
  opacity: .22; cursor: default;
  filter: saturate(.1) brightness(.65);
  pointer-events: none;
}

/* glow fondo */
.rc__glow {
  position:absolute; inset:0; z-index:0; pointer-events:none;
  background: radial-gradient(ellipse 100% 80% at 5% 120%, var(--glow-clr), transparent 60%);
}

/* shimmer */
.rc__shimmer {
  position:absolute; inset:0; z-index:1; pointer-events:none;
  background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,.02) 50%, transparent 70%);
  transform: translateX(-110%);
}
.rc--active:hover .rc__shimmer,
.rc--live:hover   .rc__shimmer { animation: shimmer .5s ease forwards; }
@keyframes shimmer { to { transform: translateX(110%); } }

/* inner */
.rc__inner {
  position: relative; z-index: 2;
  display: flex; align-items: stretch;
  min-height: 96px;
}

/* ══════════════════════
   THUMBNAIL
══════════════════════ */
.rc__thumb {
  position: relative;
  flex: 0 0 96px;
  overflow: hidden;
}
.rc__img {
  width:100%; height:100%;
  object-fit:cover; display:block;
  transition: transform .45s ease;
}
.rc--active:hover .rc__img,
.rc--live:hover   .rc__img { transform: scale(1.08); }

.rc__img-veil {
  position:absolute; inset:0;
  background: linear-gradient(90deg,
    rgba(7,13,28,.1) 0%,
    rgba(7,13,28,.5) 55%,
    rgba(7,13,28,.97) 100%
  );
}

/* badge flotante sobre imagen — centrado superior */
.rc__status-badge {
  position: absolute;
  top: 5px; left: 50%;
  transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 6px;
  border-radius: 20px;
  font-size: 7px; font-weight: 800;
  letter-spacing: .6px; text-transform: uppercase;
  white-space: nowrap;
  backdrop-filter: blur(8px);
  z-index: 3;
}
.rc__status-dot {
  width: 4px; height: 4px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
}
.rc__status-badge--ok {
  background: rgba(10,30,20,.75);
  border: 1px solid rgba(42,245,152,.35);
  color: #2af598;
}
.rc__status-badge--ok .rc__status-dot { animation: dp 2s infinite; }
.rc__status-badge--live {
  background: rgba(30,10,18,.75);
  border: 1px solid rgba(255,77,109,.35);
  color: #ff6e8a;
}
.rc__status-badge--live .rc__status-dot { animation: dp 1.1s infinite; }
.rc__status-badge--off {
  background: rgba(10,15,28,.75);
  border: 1px solid rgba(255,255,255,.1);
  color: #3a5068;
}
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.1} }

/* acento lateral */
.rc__side-accent {
  position: absolute; left:0; top:0; bottom:0; width:3px;
  background: linear-gradient(180deg, var(--accent) 0%, transparent 100%);
  box-shadow: 1px 0 8px var(--glow-clr);
}

/* badge de cartones del jugador — bottom-center sobre la imagen */
.rc__ctns-badge {
  position: absolute;
  bottom: 5px; left: 50%;
  transform: translateX(-50%);
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 8px; font-weight: 800;
  letter-spacing: .4px;
  white-space: nowrap;
  backdrop-filter: blur(8px);
  color: #c090ff;
  background: rgba(110,55,210,.55);
  border: 1px solid rgba(150,80,255,.4);
  z-index: 3;
}
.rc__ctns-badge i { font-size: 7px; }

/* ══════════════════════
   BODY
══════════════════════ */
.rc__body {
  flex:1; min-width:0;
  padding: 6px 8px 6px 8px;
  display: flex; flex-direction: column;
  justify-content: space-between;
  gap: 0;
}

/* Fila 1 */
.rc__top {
  display: flex; align-items: center;
  justify-content: space-between; gap: 6px;
}

/* Badges izquierda: tipo + modo */
.rc__badges {
  display: flex; align-items: center; gap: 4px;
  min-width: 0; overflow: hidden; flex-shrink: 1;
}

.rc__tag {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 1.5px 6px; border-radius: 5px;
  font-size: 9px; font-weight: 700; letter-spacing: .3px;
  white-space: nowrap; flex-shrink: 0;
}
.rc__tag i { font-size: 7.5px; }

.rc__tag--type {
  color: #5a9ad8;
  background: rgba(40,100,200,.1);
  border: 1px solid rgba(40,100,200,.18);
}
.rc__tag--type i { color: #4080c0; }

.rc__tag--mode {
  color: #3a5a78;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.07);
}
.rc__tag--mode i { font-size: 7px; color: #2a4060; }

.rc__tag--rounds {
  color: #a878e8;
  background: rgba(130,60,220,.1);
  border: 1px solid rgba(130,60,220,.2);
}
.rc__tag--rounds i { font-size: 7.5px; color: #8048c0; }

.rc__top-right {
  display: flex; align-items: center; gap: 6px; flex-shrink: 0;
}

/* timer */
.rc__timer {
  display: inline-flex; align-items: center; gap: 4px;
  color: #4a7aaa; white-space: nowrap; flex-shrink: 0;
}
.rc__timer i { font-size: 8.5px; opacity: .5; }
.rc__timer b {
  font-family: 'Orbitron', monospace;
  font-size: 10px; font-weight: 800; letter-spacing: .4px;
  color: #9ecaf0;
}
.rc__timer--live { color: #ff6e8a; }
.rc__timer--live b { color: #ff8aaa; }
.rc__blink {
  width:5px; height:5px; border-radius:50%;
  background:currentColor; display:inline-block;
  animation: bk 1s infinite;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.08} }

/* Fila 2 */
.rc__bottom {
  display: flex; align-items: center;
  justify-content: space-between; gap: 8px;
}

/* Premio */
.rc__prize {
  display: flex; align-items: baseline; gap: 4px; flex-shrink: 0;
}
.rc__trophy {
  font-size: 10px; color: #c8a028; align-self: center; flex-shrink: 0;
  filter: drop-shadow(0 0 4px rgba(200,160,40,.4));
}
.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 22px; font-weight: 900; line-height: 1;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 6px rgba(255,150,0,.25));
  letter-spacing: -1px;
}
.rc__prize-sym {
  font-family: 'Rajdhani', sans-serif;
  font-size: 12px; font-weight: 700;
  color: #e0a020; -webkit-text-fill-color: #e0a020;
}

/* Pills */
.rc__pills {
  display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;
}
.rc__pill {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 2px 7px; border-radius: 6px;
  font-size: 10px; font-weight: 600;
  color: #4a6a8a;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.07);
  white-space: nowrap;
}
.rc__pill i { font-size: 8px; color: #243a58; }

.rc__pill--price {
  color: #6aaad8;
  background: rgba(40,110,190,.08);
  border-color: rgba(40,110,190,.14);
}
.rc__pill--price i { color: #3a6898; }

/* pill compacto para la fila del timer */
.rc__pill--sm {
  font-size: 9.5px;
  padding: 2px 6px;
}

.rc__pill--mine {
  color: #9868e0;
  background: rgba(110,55,210,.1);
  border-color: rgba(110,55,210,.2);
}
.rc__pill--mine i { color: #7040b8; }
</style>
