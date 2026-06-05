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

      <!-- ── Thumbnail ── -->
      <div class="rc__thumb">
        <img :src="bingoRoomImg" alt="" class="rc__img" />
        <div class="rc__img-veil" />
        <!-- Acento lateral izquierdo -->
        <div class="rc__side-accent" />
      </div>

      <!-- ── Body ── -->
      <div class="rc__body">

        <!-- TOP: título + modo | estado + timer -->
        <div class="rc__top">
          <div class="rc__title-group">
            <span class="rc__name">Bingo {{ isPublic ? 'Público' : 'Privado' }}</span>
            <span class="rc__mode">
              <i class="fas fa-circle-nodes" />{{ gameModeLabel }}
            </span>
          </div>
          <div class="rc__top-right">
            <span class="rc__badge"
              :class="isAvailable ? 'rc__badge--ok' : isInProgress ? 'rc__badge--live' : 'rc__badge--off'"
            >
              <span class="rc__badge-dot" />
              {{ isAvailable ? 'Disponible' : isInProgress ? 'En Juego' : 'Cerrada' }}
            </span>
            <div class="rc__timer" :class="{ 'rc__timer--live': !hasTime }">
              <template v-if="hasTime && timerStr">
                <i class="far fa-clock" />
                <b>{{ timerStr }}</b>
              </template>
              <template v-else>
                <span class="rc__blink" /><b>LIVE</b>
              </template>
            </div>
          </div>
        </div>

        <!-- MID: premio + pills -->
        <div class="rc__mid">
          <!-- Premio -->
          <div class="rc__prize">
            <i class="fas fa-trophy rc__trophy-icon" />
            <span class="rc__prize-num">{{ prizeFormatted }}</span>
            <span class="rc__prize-sym">{{ sym }}</span>
          </div>

          <!-- Pills de stats -->
          <div class="rc__pills">
            <span class="rc__pill rc__pill--price">
              <i class="fas fa-ticket-alt" />
              {{ cardPriceFormatted }} {{ sym }}
            </span>
            <span class="rc__pill">
              <i class="fas fa-users" />
              {{ room.total_players }} jug.
            </span>
            <span v-if="playerCtns > 0" class="rc__pill rc__pill--mine">
              <i class="fas fa-layer-group" />
              {{ playerCtns }} míos
            </span>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Orbitron:wght@800;900&display=swap');

/* ══════════════════════════════
   BASE
══════════════════════════════ */
.rc {
  --d: 0ms;
  --accent: #2af598;
  --glow-clr: rgba(42, 245, 152, .12);

  position: relative;
  width: 100%;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  background: linear-gradient(140deg, #0f1f40 0%, #07101f 100%);
  border: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 6px 28px rgba(0,0,0,.55), 0 1px 0 rgba(255,255,255,.05) inset;
  transition: transform .22s cubic-bezier(.22,.68,0,1.2),
              box-shadow .22s ease, border-color .22s ease;
  animation: rcIn .4s ease both;
  animation-delay: var(--d);
}
@keyframes rcIn {
  from { opacity:0; transform:translateY(10px) scale(.98); }
  to   { opacity:1; transform:none; }
}

.rc--live { --accent: #ff4d6d; --glow-clr: rgba(255,77,109,.12); }

.rc--active:hover {
  transform: translateY(-3px) scale(1.003);
  border-color: rgba(42,245,152,.22);
  box-shadow: 0 14px 44px rgba(0,0,0,.65),
              0 0 0 1px rgba(42,245,152,.18),
              0 0 24px rgba(42,245,152,.1);
}
.rc--live:hover {
  transform: translateY(-2px);
  border-color: rgba(255,77,109,.2);
  box-shadow: 0 12px 36px rgba(0,0,0,.6), 0 0 0 1px rgba(255,77,109,.18);
}
.rc--off { opacity:.25; cursor:default; filter:saturate(.1) brightness(.7); pointer-events:none; }

/* glow */
.rc__glow {
  position:absolute; inset:0; z-index:0; pointer-events:none;
  background: radial-gradient(ellipse 100% 80% at 10% 120%, var(--glow-clr), transparent 60%);
}

/* shimmer */
.rc__shimmer {
  position:absolute; inset:0; z-index:1; pointer-events:none;
  background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,.025) 50%, transparent 70%);
  transform: translateX(-110%);
}
.rc--active:hover .rc__shimmer,
.rc--live:hover   .rc__shimmer {
  animation: shimmer .55s ease forwards;
}
@keyframes shimmer { to { transform: translateX(110%); } }

/* ── Inner ── */
.rc__inner {
  position: relative; z-index: 2;
  display: flex; align-items: stretch;
}

/* ══════════════════════════════
   THUMBNAIL
══════════════════════════════ */
.rc__thumb {
  position: relative;
  flex: 0 0 88px;
  overflow: hidden;
}
.rc__img {
  width:100%; height:100%;
  object-fit:cover; display:block;
  transition: transform .5s ease;
}
.rc--active:hover .rc__img,
.rc--live:hover   .rc__img { transform: scale(1.07); }

.rc__img-veil {
  position:absolute; inset:0;
  background: linear-gradient(90deg,
    rgba(7,16,31,0) 0%,
    rgba(7,16,31,.4) 50%,
    rgba(7,16,31,.96) 100%
  );
}

/* acento lateral — barra vertical izquierda de color */
.rc__side-accent {
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 3px;
  background: linear-gradient(180deg, var(--accent) 0%, transparent 100%);
  box-shadow: 1px 0 10px var(--glow-clr);
}

/* ══════════════════════════════
   BODY
══════════════════════════════ */
.rc__body {
  flex: 1; min-width: 0;
  padding: 10px 14px 10px 12px;
  display: flex; flex-direction: column;
  gap: 8px;
}

/* ── TOP ── */
.rc__top {
  display: flex; align-items: center;
  justify-content: space-between; gap: 10px;
}

.rc__title-group {
  display: flex; align-items: center; gap: 8px;
  min-width: 0; overflow: hidden;
}

.rc__name {
  font-family: 'Rajdhani', sans-serif;
  font-size: 15px; font-weight: 700;
  color: #ddeeff;
  white-space: nowrap;
  letter-spacing: .3px;
  flex-shrink: 0;
}

.rc__mode {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; color: #2e4a6a;
  white-space: nowrap;
  border-left: 1px solid rgba(255,255,255,.07);
  padding-left: 8px;
}
.rc__mode i { font-size: 9px; }

.rc__top-right {
  display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}

/* badge estado */
.rc__badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 20px;
  font-size: 9px; font-weight: 700; letter-spacing: .5px;
  text-transform: uppercase; white-space: nowrap;
}
.rc__badge-dot {
  width: 5px; height: 5px; border-radius: 50%;
  background: currentColor; flex-shrink: 0;
}
.rc__badge--ok   { background:rgba(42,245,152,.1);  border:1px solid rgba(42,245,152,.3);  color:#2af598; }
.rc__badge--live { background:rgba(255,77,109,.12); border:1px solid rgba(255,77,109,.3);  color:#ff6e8a; }
.rc__badge--off  { background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:#253648; }
.rc__badge--ok   .rc__badge-dot { animation: dp 2s infinite; }
.rc__badge--live .rc__badge-dot { animation: dp 1.1s infinite; }
@keyframes dp { 0%,100%{opacity:1} 50%{opacity:.1} }

/* timer */
.rc__timer {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 10px; color: #5a8ab8;
  white-space: nowrap;
}
.rc__timer i { font-size: 9px; opacity: .5; }
.rc__timer b {
  font-family: 'Orbitron', monospace;
  font-size: 12px; font-weight: 800; letter-spacing: .5px;
  color: #9ecaf0;
}
.rc__timer--live { color: #ff6e8a; }
.rc__timer--live b { color: #ff8aaa; }

.rc__blink {
  width: 6px; height: 6px; border-radius: 50%;
  background: currentColor; display: inline-block;
  animation: bk 1s infinite;
}
@keyframes bk { 0%,100%{opacity:1} 50%{opacity:.1} }

/* ── MID: premio + pills en una fila ── */
.rc__mid {
  display: flex; align-items: center;
  justify-content: space-between; gap: 10px;
}

/* Premio */
.rc__prize {
  display: flex; align-items: baseline; gap: 5px; flex-shrink: 0;
}
.rc__trophy-icon {
  font-size: 11px; color: #c8a028;
  filter: drop-shadow(0 0 5px rgba(200,160,40,.4));
  align-self: center; flex-shrink: 0;
}
.rc__prize-num {
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 24px; font-weight: 900; line-height: 1;
  background: linear-gradient(130deg, #ffe566 0%, #ffbb20 50%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 8px rgba(255,150,0,.28));
  letter-spacing: -1px;
}
.rc__prize-sym {
  font-family: 'Rajdhani', sans-serif;
  font-size: 13px; font-weight: 700;
  color: #e0a020; -webkit-text-fill-color: #e0a020;
}

/* pills */
.rc__pills {
  display: flex; align-items: center;
  flex-wrap: wrap; gap: 5px;
  justify-content: flex-end;
}

.rc__pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 7px;
  font-size: 10.5px; font-weight: 600;
  color: #5a8ab0;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.08);
  white-space: nowrap;
}
.rc__pill i { font-size: 9px; color: #2e4a68; }

.rc__pill--price {
  color: #7ab8e0;
  background: rgba(40,120,200,.08);
  border-color: rgba(40,120,200,.15);
}
.rc__pill--price i { color: #3a6898; }

.rc__pill--mine {
  color: #a070e8;
  background: rgba(120,60,220,.1);
  border-color: rgba(120,60,220,.22);
}
.rc__pill--mine i { color: #7848c0; }
</style>
